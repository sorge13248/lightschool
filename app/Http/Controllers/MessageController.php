<?php

namespace App\Http\Controllers;

use App\Models\Contact;
use App\Models\Message;
use App\Models\MessageActor;
use App\Models\MessageChat;
use App\Models\User;
use App\Models\UserExpanded;
use App\Services\CryptoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MessageController extends Controller
{
    public function __construct(protected CryptoService $crypto)
    {
    }

    public function handle(Request $request): JsonResponse
    {
        $type = $request->query('type');

        return match ($type) {
            'list'  => $this->list($request),
            'chat'  => $this->chat($request),
            'send'  => $this->send($request),
            'new'   => $this->newConversation($request),
            default => response()->json(['response' => 'error', 'text' => 'Invalid type']),
        };
    }

    protected function list(Request $request): JsonResponse
    {
        $userId = auth()->id();
        $start  = max(0, (int) $request->query('start', 0));

        $items = Message::join('message_actors', 'message_list.id', '=', 'message_actors.list_id')
            ->join('message_chat', 'message_list.id', '=', 'message_chat.message_list_id')
            ->where('message_actors.user_id', $userId)
            ->selectRaw('MAX(message_chat.date) AS date, message_list.id')
            ->groupBy('message_list.id')
            ->orderByRaw('date DESC')
            ->skip($start)->take(20)
            ->get();

        $result = $items->map(function ($item) use ($userId) {
            $row = $item->toArray();

            $other = MessageActor::where('list_id', $row['id'])->where('user_id', '!=', $userId)->first();
            $row['user'] = $this->getUserProfile($other ? $other->user_id : $userId);
            $row['new']  = MessageChat::where('message_list_id', $row['id'])
                ->where('sender', '!=', $userId)
                ->whereNull('read_at')
                ->exists();

            return $row;
        });

        return response()->json($result);
    }

    protected function chat(Request $request): JsonResponse
    {
        $userId = auth()->id();
        $listId = (int) $request->query('id');
        $start  = max(0, (int) $request->query('start', 0));

        $messages = MessageChat::join('message_actors', 'message_chat.message_list_id', '=', 'message_actors.list_id')
            ->where('message_actors.user_id', $userId)
            ->where('message_actors.list_id', $listId)
            ->where('message_chat.message_list_id', $listId)
            ->select('message_chat.id as id', 'message_chat.date', 'message_chat.cypher',
                'message_chat.body', 'message_chat.sender', 'message_chat.attachment', 'message_chat.read_at')
            ->orderBy('message_chat.date', 'desc')
            ->skip($start)->take(20)
            ->get();

        if ($messages->isEmpty()) {
            return response()->json(['response' => 'error', 'text' => __('message-error-invalid-conversation')]);
        }

        // Mark received messages as read
        MessageChat::where('message_list_id', $listId)
            ->where('sender', '!=', $userId)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        $chat = $messages->map(function ($msg) {
            $m = $msg->toArray();

            $decrypted = $this->crypto->decryptMessage(
                $m['body'],
                $m['attachment'],
                $m['cypher'],
                $m['sender'],
            );

            $m['body']       = $decrypted['body'];
            $m['attachment'] = $decrypted['attachment'];

            if ($m['attachment'] !== null) {
                $m['attachment'] = json_decode($m['attachment'], true);
                if (isset($m['attachment']['type']) && $m['attachment']['type'] === 'contact') {
                    $m['attachment']['user'] = $this->getUserProfile($m['attachment']['user']);
                }
            }

            unset($m['cypher']);
            return $m;
        });

        $other        = MessageActor::where('list_id', $listId)->where('user_id', '!=', $userId)->first();
        $otherProfile = $this->getUserProfile($other ? $other->user_id : $userId);

        return response()->json([
            'response'        => 'success',
            'current_user_id' => $userId,
            'other_user'      => $otherProfile,
            'chat'            => $chat,
        ]);
    }

    protected function send(Request $request): JsonResponse
    {
        $userId = auth()->id();

        if ($err = $this->validateInput(
            ['id' => $request->query('id')],
            ['id' => 'required|integer|min:1'],
            ['id.*' => __('message-error-id-required')]
        )) return $err;

        $listId = (int) $request->query('id');

        $body = $request->input('body');
        if ($body !== null) {
            $body = base64_decode($body);
            $body = trim($body) !== '' ? nl2br(trim($body)) : null;
        }

        if ($body === null) return response()->json(['response' => 'error', 'text' => __('message-error-body-required')]);

        if (!MessageActor::where('list_id', $listId)->where('user_id', $userId)->exists()) {
            return response()->json(['response' => 'error', 'text' => __('message-error-invalid-conversation')]);
        }

        $other = MessageActor::where('list_id', $listId)->where('user_id', '!=', $userId)->first();
        if (!$other) {
            return response()->json(['response' => 'error', 'text' => __('message-error-invalid-conversation')]);
        }

        if (!$this->checkMessagingPrivacy($other->user_id, $userId)) {
            return response()->json(['response' => 'error', 'text' => __('message-error-privacy')]);
        }

        $encrypted = $this->crypto->encryptMessage('<p>' . $body . '</p>', null, $userId);

        MessageChat::create([
            'message_list_id' => $listId,
            'sender'          => $userId,
            'cypher'          => $encrypted['key'],
            'body'            => $encrypted['body'],
        ]);

        return response()->json(['response' => 'success', 'text' => __('message-success-sent')]);
    }

    protected function newConversation(Request $request): JsonResponse
    {
        $userId = auth()->id();

        if ($err = $this->validateInput(
            ['username' => $request->input('username')],
            ['username' => 'required|string'],
            ['username.*' => __('message-error-username-required')]
        )) return $err;

        $username = trim((string) $request->input('username'));

        $body = $request->input('body');
        if ($body !== null) {
            $body = base64_decode($body);
            $body = trim($body) !== '' ? nl2br(trim($body)) : null;
        }
        if ($body === null) return response()->json(['response' => 'error', 'text' => __('message-error-body-required')]);

        $targetUser = User::where('username', $username)->first();
        if (!$targetUser) return response()->json(['response' => 'error', 'text' => __('message-error-invalid-username')]);

        $targetId = $targetUser->id;
        if ($targetId === $userId) return response()->json(['response' => 'error', 'text' => __('message-error-self')]);

        // Handle attachment
        $attach = $request->input('attach');
        if ($attach !== null) {
            $attach = json_decode(base64_decode($attach), true);
            if (isset($attach['type']) && $attach['type'] === 'contact') {
                $contactUser = User::where('username', $attach['value'] ?? '')->first();
                if ($contactUser) {
                    $attach['user'] = $contactUser->id;
                }
                unset($attach['value']);
                $attach = json_encode($attach);
            } else {
                $attach = null;
            }
        }

        // Find or create conversation list
        $existing = MessageActor::where('user_id', $userId)
            ->whereIn('list_id', MessageActor::where('user_id', $targetId)->select('list_id'))
            ->first();

        if ($existing) {
            $listId = $existing->list_id;
        } else {
            $list   = Message::create([]);
            $listId = $list->id;
            MessageActor::create(['list_id' => $listId, 'user_id' => $userId]);
            MessageActor::create(['list_id' => $listId, 'user_id' => $targetId]);
        }

        if (!$this->checkMessagingPrivacy($targetId, $userId)) {
            return response()->json(['response' => 'error', 'text' => __('message-error-privacy')]);
        }

        $encrypted = $this->crypto->encryptMessage('<p>' . $body . '</p>', $attach, $userId);

        MessageChat::create([
            'message_list_id' => $listId,
            'sender'          => $userId,
            'cypher'          => $encrypted['key'],
            'body'            => $encrypted['body'],
            'attachment'      => $encrypted['attachment'],
        ]);

        return response()->json(['response' => 'success', 'text' => __('message-success-sent'), 'id' => $listId]);
    }

    protected function getUserProfile(int $userId): array
    {
        $user = User::with('expanded')->find($userId);
        if (!$user) return [];
        return [
            'username'        => $user->username,
            'name'            => $user->expanded?->name,
            'surname'         => $user->expanded?->surname,
            'profile_picture' => $user->expanded?->profile_picture,
        ];
    }

    protected function checkMessagingPrivacy(int $targetId, int $senderId): bool
    {
        $expanded = UserExpanded::find($targetId);
        if (!$expanded) return false;

        $level = (int) $expanded->privacy_send_messages;
        if ($level === 0) return false;
        if ($level === 1) {
            return Contact::where('user_id', $targetId)->where('contact_id', $senderId)
                ->where('trash', 0)->where('deleted', 0)->exists();
        }
        return true; // level 2 = everyone
    }
}
