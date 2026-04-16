<?php

namespace App\Http\Controllers;

use App\Models\File;
use App\Models\ProjectFile;
use App\Models\Share;
use App\Services\CryptoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WriterController extends Controller
{
    public function __construct(protected CryptoService $crypto)
    {
    }

    public function handle(Request $request): JsonResponse
    {
        $type = $request->query('type');

        return match ($type) {
            'get'    => $this->get($request),
            'create' => $this->create($request),
            'edit'   => $this->edit($request),
            default  => response()->json(['response' => 'error', 'text' => 'Invalid type']),
        };
    }

    protected function get(Request $request): JsonResponse
    {
        $userId = auth()->id();
        $fileId = (int) $request->query('id');

        $file = File::whereNull('deleted_at')->find($fileId);

        if (!$file) {
            return response()->json(['response' => 'error', 'text' => 'File not found']);
        }

        $ownerUserId = $file->user_id;
        $isOwner     = ($ownerUserId === $userId);
        $canEdit     = $isOwner;

        if (!$isOwner) {
            $projectCode = $request->cookie('project_code');
            if ($projectCode) {
                $projectFile = ProjectFile::join('project', 'project_files.project', '=', 'project.code')
                    ->where('project_files.file', $fileId)
                    ->where('project_files.project', $projectCode)
                    ->whereBetween('project.timestamp', [now()->subDays(7), now()])
                    ->select('project_files.editable')
                    ->first();
                if (!$projectFile) {
                    return response()->json(['response' => 'error', 'text' => 'Access denied']);
                }
                $canEdit = (bool) $projectFile->editable;
            } else {
                $share = Share::where('file', $fileId)->where('receiving', $userId)->active()->first();
                if (!$share) {
                    return response()->json(['response' => 'error', 'text' => 'Access denied']);
                }
            }
        }

        $content = null;
        if ($file->cypher && $file->html) {
            try {
                $content = $this->crypto->decrypt($file->html, $file->cypher, $ownerUserId);
            } catch (\Throwable $e) {
                \Log::error('Notebook decryption failed', [
                    'file_id'       => $fileId,
                    'owner_user_id' => $ownerUserId,
                    'error'         => $e->getMessage(),
                ]);
                $content = null;
            }
        } elseif ($file->html && !$file->cypher) {
            // Legacy unencrypted notebook (no cypher) — serve plaintext as-is
            $content = $file->html;
        }

        return response()->json([
            'response' => 'success',
            'name'     => $file->name,
            'content'  => $content,
            'n_ver'    => (int) ($file->n_ver ?? 1),
            'can_edit' => $canEdit,
        ]);
    }

    protected function create(Request $request): JsonResponse
    {
        $userId = auth()->id();

        if ($err = $this->validateInput(
            ['name' => $request->input('name')],
            ['name' => 'required|string|max:255'],
            ['name.*' => __('writer-error-name-required')]
        )) return $err;

        $name     = $request->input('name');
        $folderId = $request->query('id') !== null ? (int) $request->query('id') : 0;
        $content  = (string) $request->input('content', '');

        // Check folder ownership (if not root)
        if ($folderId !== 0) {
            $folder = File::where('id', $folderId)
                ->where('user_id', $userId)
                ->ofType('folder')
                ->whereNull('deleted_at')
                ->first();
            if (!$folder) {
                return response()->json(['response' => 'error', 'text' => __('writer-error-folder-not-found')]);
            }
        }

        // Check for duplicate name in folder
        $duplicate = File::where('user_id', $userId)
            ->where('name', $name)
            ->active()
            ->inFolder($folderId === 0 ? null : $folderId)
            ->exists();

        if ($duplicate) {
            return response()->json(['response' => 'error', 'text' => __('writer-error-name-exists')]);
        }

        $encrypted = $this->crypto->encrypt($content, $userId);

        $newFile = File::create([
            'user_id'     => $userId,
            'type'        => 'notebook',
            'name'        => $name,
            'n_ver'       => 2,
            'cypher'      => $encrypted['key'],
            'html'        => $encrypted['data'],
            'folder'      => $folderId === 0 ? null : $folderId,
        ]);

        return response()->json(['response' => 'success', 'text' => __('writer-success-created'), 'id' => $newFile->id]);
    }

    protected function edit(Request $request): JsonResponse
    {
        $userId = auth()->id();

        if ($err = $this->validateInput(
            ['id' => $request->query('id')],
            ['id' => 'required|integer|min:1'],
            ['id.*' => __('writer-error-id-invalid')]
        )) return $err;

        $fileId      = (int) $request->query('id');
        $name        = $request->input('name') ?: null;
        $content     = (string) $request->input('content', '');
        $ownerUserId = $userId;

        // Check if file is being shared via a project
        $projectCode = $request->cookie('project_code');
        if ($projectCode) {
            $projectFile = ProjectFile::join('project', 'project_files.project', '=', 'project.code')
                ->where('project_files.file', $fileId)
                ->where('project_files.project', $projectCode)
                ->where('project_files.editable', 1)
                ->whereBetween('project.timestamp', [now()->subDays(7), now()])
                ->select('project_files.user')
                ->first();
            if ($projectFile) {
                $ownerUserId = $projectFile->user;
            }
        }

        $file = File::where('id', $fileId)->where('user_id', $ownerUserId)->first();
        if (!$file) {
            return response()->json(['response' => 'error', 'text' => __('writer-error-file-not-found')]);
        }

        $encrypted = $this->crypto->encrypt($content, $ownerUserId);

        $file->update([
            'name'        => $name,
            'cypher'      => $encrypted['key'],
            'html'        => $encrypted['data'],
            'n_ver'       => 2,
            'last_edit'   => now(),
        ]);

        return response()->json(['response' => 'success', 'text' => __('writer-success-updated')]);
    }
}
