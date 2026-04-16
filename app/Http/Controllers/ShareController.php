<?php

namespace App\Http\Controllers;

use App\Models\File;
use App\Models\Share;
use App\Models\User;
use App\Models\UserExpanded;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ShareController extends Controller
{

    public function handle(Request $request): JsonResponse
    {
        $type = $request->query('type');
        return match ($type) {
            'get-all'         => $this->getAll(),
            'get-shared'      => $this->getShared(),
            'get-sharing'     => $this->getSharing(),
            'get-user-shared' => $this->getUserShared($request),
            'get-user-sharing'=> $this->getUserSharing($request),
            'add'             => $this->add($request),
            'delete'          => $this->delete($request),
            'file-shared'     => $this->fileShared($request),
            default           => response()->json(['response' => 'error', 'text' => 'Invalid type.']),
        };
    }

    protected function getAll(): JsonResponse
    {
        $userId = auth()->id();
        $shares = Share::where(fn($q) => $q->where('sender', $userId)->orWhere('receiving', $userId))
            ->active()
            ->get();
        return response()->json(['response' => 'success', 'shares' => $shares]);
    }

    protected function getShared(): JsonResponse
    {
        $userId = auth()->id();
        $shares = Share::join('file', 'share.file', '=', 'file.id')
            ->sent($userId)
            ->active()
            ->whereNull('file.deleted_at')
            ->select('share.id', 'share.receiving', 'share.file as file_id', 'file.name', 'file.type')
            ->get();
        return response()->json(['response' => 'success', 'shares' => $shares]);
    }

    protected function getSharing(): JsonResponse
    {
        $userId = auth()->id();
        $shares = Share::join('file', 'share.file', '=', 'file.id')
            ->received($userId)
            ->active()
            ->whereNull('file.deleted_at')
            ->select('share.id', 'share.sender', 'share.file as file_id', 'file.name', 'file.type')
            ->get();
        return response()->json(['response' => 'success', 'shares' => $shares]);
    }

    protected function getUserShared(Request $request): JsonResponse
    {
        $userId = auth()->id();
        $sender = (int) $request->query('sender');
        $shares = Share::join('file', 'share.file', '=', 'file.id')
            ->where('share.sender', $sender)
            ->received($userId)
            ->active()
            ->whereNull('file.deleted_at')
            ->select('share.id', 'share.file as file_id', 'file.name', 'file.type')
            ->get();
        return response()->json(['response' => 'success', 'shares' => $shares]);
    }

    protected function getUserSharing(Request $request): JsonResponse
    {
        $userId    = auth()->id();
        $receiving = (int) $request->query('receiving');
        $shares    = Share::join('file', 'share.file', '=', 'file.id')
            ->sent($userId)
            ->where('share.receiving', $receiving)
            ->active()
            ->whereNull('file.deleted_at')
            ->select('share.id', 'share.file as file_id', 'file.name', 'file.type')
            ->get();
        return response()->json(['response' => 'success', 'shares' => $shares]);
    }

    protected function add(Request $request): JsonResponse
    {
        $userId   = auth()->id();
        $fileId   = (int) $request->input('id');
        $username = trim((string) $request->input('username', ''));

        // Verify ownership
        if (!File::where('id', $fileId)->where('user_id', $userId)->whereNull('deleted_at')->exists()) {
            return response()->json(['response' => 'error', 'text' => 'File not found or not yours.']);
        }

        $recipient = User::where('username', $username)->first();
        if (!$recipient) return response()->json(['response' => 'error', 'text' => 'User not found.']);
        if ($recipient->id === $userId) return response()->json(['response' => 'error', 'text' => 'Cannot share with yourself.']);

        // Check privacy
        $recipientExpanded = $recipient->expanded;
        $privacyOk = $recipientExpanded && ($recipientExpanded->privacy_share_documents ?? 1);
        if (!$privacyOk) return response()->json(['response' => 'error', 'text' => 'This user does not accept shared files.']);

        if (Share::where('sender', $userId)->where('receiving', $recipient->id)->where('file', $fileId)->active()->exists()) {
            return response()->json(['response' => 'error', 'text' => 'Already shared.']);
        }

        Share::create(['sender' => $userId, 'receiving' => $recipient->id, 'file' => $fileId]);
        return response()->json(['response' => 'success', 'text' => 'Shared.']);
    }

    protected function delete(Request $request): JsonResponse
    {
        $userId = auth()->id();
        $id     = (int) $request->query('id');
        $fileId = (int) $request->query('file_id');

        $affected = Share::where('id', $id)->where('file', $fileId)
            ->where(fn($q) => $q->where('sender', $userId)->orWhere('receiving', $userId))
            ->update(['deleted' => 1]);

        if (!$affected) return response()->json(['response' => 'error', 'text' => 'Share not found.']);
        return response()->json(['response' => 'success', 'text' => 'Share removed.']);
    }

    protected function fileShared(Request $request): JsonResponse
    {
        $userId = auth()->id();
        $fileId = (int) $request->query('id');

        if (!File::where('id', $fileId)->where('user_id', $userId)->whereNull('deleted_at')->exists()) {
            return response()->json(['response' => 'error', 'text' => 'Not authorised.']);
        }

        $users = Share::join('users', 'share.receiving', '=', 'users.id')
            ->join('users_expanded', 'users.id', '=', 'users_expanded.id')
            ->where('share.file', $fileId)
            ->sent($userId)
            ->active()
            ->select('share.id', 'users.username', 'users_expanded.name', 'users_expanded.surname', 'users_expanded.profile_picture')
            ->get();

        return response()->json(['response' => 'success', 'users' => $users]);
    }
}
