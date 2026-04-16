<?php

namespace App\Http\Controllers;

use App\Models\Contact;
use App\Models\User;
use App\Models\UserExpanded;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ContactController extends Controller
{

    public function handle(Request $request): JsonResponse
    {
        $type = $request->query('type');
        return match ($type) {
            'get-contacts' => $this->getContacts($request),
            'details'      => $this->details($request),
            'create'       => $this->create($request),
            'delete'       => $this->delete($request),
            'fav'          => $this->fav($request),
            'block'        => $this->block($request),
            default        => response()->json(['response' => 'error', 'text' => 'Invalid type.']),
        };
    }

    protected function getContacts(Request $request): JsonResponse
    {
        $userId = auth()->id();
        $start  = max(0, (int) $request->query('limit', 0));
        $sortBy = $request->query('sortBy', 'name, surname');

        $orderBy = $sortBy === 'surname, name' ? 'contact.surname, contact.name' : 'contact.name, contact.surname';

        $contacts = Contact::join('users', 'contact.contact_id', '=', 'users.id')
            ->join('users_expanded', 'users.id', '=', 'users_expanded.id')
            ->where('contact.user_id', $userId)
            ->where('contact.trash', 0)->where('contact.deleted', 0)
            ->whereNotNull('contact.contact_id')
            ->select('users_expanded.name as ue_name', 'users_expanded.surname as ue_surname',
                'contact.id', 'contact.name', 'contact.surname', 'contact.contact_id',
                'users_expanded.profile_picture', 'users.username', 'contact.fav', 'users.id as user_id')
            ->orderByRaw($orderBy)
            ->skip($start)->take(20)
            ->get();

        $blocked = auth()->user()->expanded->blocked ?? [];

        $result = $contacts->map(function ($c) use ($blocked) {
            $row = $c->toArray();
            $row['blocked'] = in_array($row['user_id'], (array) $blocked) ? 1 : 0;
            unset($row['user_id']);
            return $row;
        });

        return response()->json(['response' => 'success', 'contacts' => $result]);
    }

    protected function details(Request $request): JsonResponse
    {
        $userId = auth()->id();
        $id     = (int) $request->query('id');

        $contact = Contact::join('users', 'contact.contact_id', '=', 'users.id')
            ->join('users_expanded', 'users.id', '=', 'users_expanded.id')
            ->where('contact.id', $id)->where('contact.user_id', $userId)->where('contact.deleted', 0)
            ->select('users_expanded.name as ue_name', 'users_expanded.surname as ue_surname',
                'users_expanded.profile_picture', 'users.username', 'contact.name', 'contact.surname', 'contact.fav')
            ->first();

        if (!$contact) return response()->json(['response' => 'error', 'text' => 'Contact not found.']);

        return response()->json(['response' => 'success', 'contact' => $contact->toArray()]);
    }

    protected function create(Request $request): JsonResponse
    {
        $userId   = auth()->id();
        $name     = trim(preg_replace('/[\\\\\\/:*?"<>|&]/', ' ', (string) $request->input('name', '')));
        $surname  = trim(preg_replace('/[\\\\\\/:*?"<>|&]/', ' ', (string) $request->input('surname', '')));
        $username = trim(preg_replace('/[\\\\\\/:*?"<>|&]/', ' ', (string) $request->input('username', '')));

        if ($err = $this->validateInput(
            compact('name', 'surname', 'username'),
            [
                'name'     => 'required|string',
                'surname'  => 'required|string',
                'username' => 'required|string',
            ],
            [
                'name.required'     => __('contact-error-name-required'),
                'surname.required'  => __('contact-error-surname-required'),
                'username.required' => __('contact-error-username-required'),
            ]
        )) return $err;

        $targetUser = User::where('username', $username)->first();
        if (!$targetUser) return response()->json(['response' => 'error', 'text' => 'User not found.']);
        if ($targetUser->id === $userId) return response()->json(['response' => 'error', 'text' => 'Cannot add yourself.']);

        // Check privacy
        $expanded = $targetUser->expanded;
        if ($expanded && isset($expanded->privacy_allow_contacts) && !$expanded->privacy_allow_contacts) {
            return response()->json(['response' => 'error', 'text' => 'This user does not allow new contacts.']);
        }

        if (Contact::where('user_id', $userId)->where('contact_id', $targetUser->id)->where('deleted', 0)->exists()) {
            return response()->json(['response' => 'error', 'text' => 'Already in your contacts.']);
        }

        Contact::create([
            'user_id'    => $userId,
            'contact_id' => $targetUser->id,
            'name'       => $name,
            'surname'    => $surname,
        ]);

        return response()->json(['response' => 'success', 'text' => 'Contact added.']);
    }

    protected function delete(Request $request): JsonResponse
    {
        $userId = auth()->id();
        $id     = (int) $request->query('id');
        $type   = $request->input('type', 'trash');

        if ($type === 'delete') {
            Contact::where('id', $id)->where('user_id', $userId)->update(['deleted' => 1]);
        } else {
            Contact::where('id', $id)->where('user_id', $userId)->update(['trash' => 1]);
        }
        return response()->json(['response' => 'success', 'text' => 'Done.']);
    }

    protected function fav(Request $request): JsonResponse
    {
        $userId = auth()->id();
        $id     = (int) $request->query('id');
        $action = $request->query('action', 'add');

        Contact::where('id', $id)->where('user_id', $userId)->update(['fav' => $action === 'add' ? 1 : 0]);
        return response()->json(['response' => 'success', 'text' => 'Done.']);
    }

    protected function block(Request $request): JsonResponse
    {
        $userId   = auth()->id();
        $username = trim((string) $request->input('username', ''));

        $target = User::where('username', $username)->first();
        if (!$target) return response()->json(['response' => 'error', 'text' => 'User not found.']);

        $expanded    = UserExpanded::find($userId);
        $blockedList = $expanded->blocked ?? [];

        if (in_array($target->id, $blockedList)) {
            $blockedList = array_values(array_filter($blockedList, fn($id) => $id !== $target->id));
            $msg = 'User unblocked.';
        } else {
            $blockedList[] = $target->id;
            $msg = 'User blocked.';
        }

        $expanded?->update(['blocked' => $blockedList]);
        return response()->json(['response' => 'success', 'text' => $msg]);
    }
}
