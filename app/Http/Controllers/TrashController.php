<?php

namespace App\Http\Controllers;

use App\Models\File;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TrashController extends Controller
{

    public function handle(Request $request): JsonResponse
    {
        $type = $request->query('type');
        return match ($type) {
            'get'     => $this->get($request),
            'delete'  => $this->delete($request),
            'restore' => $this->restore($request),
            'empty'   => $this->empty(),
            default   => response()->json(['response' => 'error', 'text' => 'Invalid type.']),
        };
    }

    protected function get(Request $request): JsonResponse
    {
        $userId = auth()->id();
        $start  = max(0, (int) $request->query('start', 0));

        $items = File::where('user_id', $userId)
            ->inTrash()
            ->whereIn('type', ['folder', 'notebook', 'file', 'diary'])
            ->select('id', 'name', 'type', 'icon', 'file_type', 'file_url', 'diary_type', 'diary_date')
            ->orderByRaw("FIELD(type,'folder','notebook','file','diary'), name")
            ->skip($start)->take(20)
            ->get();

        return response()->json(['response' => 'success', 'items' => $items]);
    }

    protected function delete(Request $request): JsonResponse
    {
        $userId = auth()->id();
        $id     = (int) $request->query('id');

        $affected = File::where('id', $id)->where('user_id', $userId)->inTrash()
            ->update(['deleted_at' => now()]);

        if (!$affected) return response()->json(['response' => 'error', 'text' => 'File not found.']);
        return response()->json(['response' => 'success', 'text' => 'Permanently deleted.']);
    }

    protected function restore(Request $request): JsonResponse
    {
        $userId = auth()->id();
        $id     = (int) $request->query('id');

        $affected = File::where('id', $id)->where('user_id', $userId)->inTrash()
            ->update(['trash' => 0]);

        if (!$affected) return response()->json(['response' => 'error', 'text' => 'File not found.']);
        return response()->json(['response' => 'success', 'text' => 'Restored.']);
    }

    protected function empty(): JsonResponse
    {
        $userId = auth()->id();
        File::where('user_id', $userId)->inTrash()->update(['deleted_at' => now()]);
        return response()->json(['response' => 'success', 'text' => 'Trash emptied.']);
    }
}
