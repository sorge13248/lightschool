<?php

namespace App\Http\Controllers;

use App\Http\Requests\UploadFileRequest;
use App\Models\File;
use App\Models\Share;
use App\Models\UserExpanded;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\HeaderUtils;

class FileManagerController extends Controller
{
    /** MIME types accepted at upload time (detected by PHP fileinfo, not client-supplied). */
    protected array $allowedMimeTypes = [
        // Images
        'image/png', 'image/jpeg', 'image/bmp', 'image/gif', 'image/tiff', 'image/webp',
        // Audio
        'audio/mpeg', 'audio/wav', 'audio/x-wav', 'audio/ogg',
        // Video
        'video/mp4', 'video/quicktime', 'video/x-msvideo', 'video/ogg',
        // PDF / XPS
        'application/pdf', 'application/vnd.ms-xpsdocument',
        // Microsoft Office
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'application/vnd.ms-powerpoint',
        'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        'application/vnd.ms-access', 'application/x-msaccess', 'application/msaccess',
        // OpenDocument
        'application/vnd.oasis.opendocument.text',
        'application/vnd.oasis.opendocument.spreadsheet',
        'application/vnd.oasis.opendocument.presentation',
        'application/vnd.oasis.opendocument.database',
        // Text / source code (fileinfo often returns text/plain for many source files)
        'text/plain', 'text/html', 'text/css',
        'text/javascript', 'application/javascript',
        'text/rtf', 'application/rtf',
        'text/x-java-source', 'text/x-java',
        'text/x-c', 'text/x-c++',
        'text/x-python', 'text/x-go',
        'text/x-scss', 'text/x-sass',
        // Compiled Java bytecode
        'application/java-vm', 'application/x-java-class',
    ];

    public function handle(Request $request)
    {
        $type = $request->query('type');

        return match ($type) {
            'create-folder'       => $this->createFolder($request),
            'details'             => $this->details($request),
            'rename'              => $this->rename($request),
            'delete'              => $this->delete($request),
            'fav'                 => $this->fav($request),
            'set-profile-picture' => $this->setProfilePicture($request),
            'set-wallpaper'       => $this->setWallpaper($request),
            'move'                => $this->move($request),
            'upload'              => $this->upload($request),
            'list-files'          => $this->listFiles($request),
            'list-trash'          => $this->listTrash($request),
            'restore'             => $this->restore($request),
            'empty'               => $this->emptyTrash(),
            'provide-file'        => $this->provideFile($request),
            'set-bypass'          => $this->setBypass($request),
            'list-icons'          => $this->listIcons(),
            'set-icon'            => $this->setIcon($request),
            default               => response()->json(['response' => 'error', 'text' => 'Invalid type.']),
        };
    }

    protected function createFolder(Request $request): JsonResponse
    {
        $userId = auth()->id();
        $folder = $request->query('folder') !== null && $request->query('folder') !== '' ? (int) $request->query('folder') : null;

        if ($err = $this->validateInput(
            ['name' => $request->input('name')],
            ['name' => 'required|string|max:255'],
            ['name.required' => __('fm-error-name-required'), 'name.max' => __('fm-error-name-long')]
        )) return $err;

        $name = trim((string) $request->input('name'));

        if ($folder !== null && !$this->ownsFile($folder, $userId)) {
            return response()->json(['response' => 'error', 'text' => 'Parent folder not found or not yours.']);
        }

        $exists = File::where('user_id', $userId)->where('name', $name)->ofType('folder')
            ->inFolder($folder)
            ->whereNull('deleted_at')
            ->exists();

        if ($exists) return response()->json(['response' => 'error', 'text' => 'A folder with this name already exists.']);

        File::create(['user_id' => $userId, 'type' => 'folder', 'name' => $name, 'folder' => $folder]);
        return response()->json(['response' => 'success', 'text' => 'Folder created successfully.']);
    }

    protected function details(Request $request): JsonResponse
    {
        $id     = (int) $request->query('id');
        $userId = auth()->id();

        $file = File::whereNull('deleted_at')->find($id);
        if (!$file) return response()->json(['response' => 'error', 'text' => 'File not found.']);

        if ($file->user_id !== $userId) {
            if (!Share::where('file', $id)->where('receiving', $userId)->active()->exists()) {
                return response()->json(['response' => 'error', 'text' => 'Not authorised.']);
            }
        }

        $data = $file->toArray();
        unset($data['html'], $data['cypher']);

        if (!empty($data['file_size'])) {
            $data['file_size_human'] = $this->humanSize($data['file_size']);
        }

        return response()->json(['response' => 'success', 'file' => $data]);
    }

    protected function rename(Request $request): JsonResponse
    {
        $userId = auth()->id();
        $id     = (int) $request->query('id');

        if ($err = $this->validateInput(
            ['name' => $request->input('name')],
            ['name' => 'required|string|max:255'],
            ['name.required' => __('fm-error-name-required'), 'name.max' => __('fm-error-name-long')]
        )) return $err;

        $name = trim((string) $request->input('name'));

        if (!$this->ownsFile($id, $userId)) return response()->json(['response' => 'error', 'text' => 'Not authorised.']);

        File::where('id', $id)->where('user_id', $userId)->update(['name' => $name]);
        return response()->json(['response' => 'success', 'text' => 'Renamed.']);
    }

    protected function delete(Request $request): JsonResponse
    {
        $userId = auth()->id();
        $id     = (int) $request->query('id');
        $mode   = $request->input('delete_mode', 'move_to_trash');

        if (!$this->ownsFile($id, $userId)) return response()->json(['response' => 'error', 'text' => 'Not authorised.']);

        if ($mode === 'delete_completely') {
            File::where('id', $id)->where('user_id', $userId)->update(['deleted_at' => now()]);
            return response()->json(['response' => 'success', 'text' => 'File permanently deleted.']);
        }

        File::where('id', $id)->where('user_id', $userId)->update(['trash' => 1]);
        return response()->json(['response' => 'success', 'text' => 'File moved to trash.']);
    }

    protected function fav(Request $request): JsonResponse
    {
        $userId = auth()->id();
        $id     = (int) $request->query('id');

        if (!$this->ownsFile($id, $userId)) return response()->json(['response' => 'error', 'text' => 'Not authorised.']);

        $file   = File::where('id', $id)->where('user_id', $userId)->first(['id', 'fav']);
        $newFav = $file->fav ? 0 : 1;

        $file->update(['fav' => $newFav]);
        return response()->json(['response' => 'success', 'text' => $newFav ? 'Added to desktop.' : 'Removed from desktop.']);
    }

    protected function setProfilePicture(Request $request): JsonResponse
    {
        $userId = auth()->id();
        $id     = (int) $request->query('id');

        if (!File::where('id', $id)->where('user_id', $userId)->whereNull('deleted_at')->exists()) {
            return response()->json(['response' => 'error', 'text' => 'File not found.']);
        }

        UserExpanded::where('id', $userId)->update(['profile_picture' => $id]);
        return response()->json(['response' => 'success', 'text' => 'Profile picture updated.']);
    }

    protected function setWallpaper(Request $request): JsonResponse
    {
        $userId  = auth()->id();
        $id      = (int) $request->query('id');
        $opacity = $request->input('opacity', '80');
        $color   = $request->input('color', '#ffffff');

        if (!$this->ownsFile($id, $userId)) return response()->json(['response' => 'error', 'text' => 'Not authorised.']);

        UserExpanded::where('id', $userId)->update([
            'wallpaper' => ['id' => $id, 'opacity' => $opacity, 'color' => $color],
        ]);
        return response()->json(['response' => 'success', 'text' => 'Wallpaper updated.']);
    }

    protected function move(Request $request): JsonResponse
    {
        $userId = auth()->id();
        $id     = (int) $request->query('id');
        $raw    = $request->input('target');
        $folder = ($raw !== null && $raw !== '') ? (int) $raw : null;

        if (!$this->ownsFile($id, $userId)) return response()->json(['response' => 'error', 'text' => 'Not authorised.']);
        if ($folder !== null && !$this->ownsFile($folder, $userId)) return response()->json(['response' => 'error', 'text' => 'Target folder not found.']);

        File::where('id', $id)->where('user_id', $userId)->whereNull('deleted_at')->update(['folder' => $folder]);
        return response()->json(['response' => 'success', 'text' => 'Moved.']);
    }

    protected function upload(Request $request): JsonResponse
    {
        if (!config('lightschool.allow_user_uploads')) {
            return response()->json(['response' => 'error', 'text' => 'File uploads are disabled.']);
        }

        $userId = auth()->id();
        $folder = $request->query('folder') !== null && $request->query('folder') !== '' ? (int) $request->query('folder') : null;

        // Validate presence and basic integrity via the Form Request rules.
        try {
            $request->validate((new UploadFileRequest())->rules());
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['response' => 'error', 'text' => $e->validator->errors()->first()]);
        }

        $uploadedFile = $request->file('file');
        $originalName = $uploadedFile->getClientOriginalName();
        $fileSize     = $uploadedFile->getSize();

        // Detect the real MIME type using PHP fileinfo (not the client-supplied value).
        $detectedMime = $uploadedFile->getMimeType();
        if (!in_array($detectedMime, $this->allowedMimeTypes, true)) {
            return response()->json([
                'response'  => 'error',
                'text'      => "File type \"{$detectedMime}\" is not allowed.",
                'file_name' => $originalName,
            ]);
        }

        // Check disk quota.
        $expanded  = UserExpanded::with('plan')->find($userId);
        $diskMb    = $expanded?->plan->disk_space ?? 100;
        $usedBytes = (int) File::where('user_id', $userId)->whereNull('deleted_at')->sum('file_size');

        if ($usedBytes + $fileSize > $diskMb * 1048576) {
            return response()->json(['response' => 'error', 'text' => 'Not enough disk space.', 'file_name' => $originalName]);
        }

        // Generate a UUID-based filename to prevent collisions and path traversal.
        $ext       = strtolower($uploadedFile->getClientOriginalExtension());
        $uuidName  = Str::uuid() . ($ext !== '' ? '.' . $ext : '');
        $directory = md5((string) $userId) . '/' . date('Y-m-d');

        try {
            $storedPath = Storage::disk('uploads')->putFileAs($directory, $uploadedFile, $uuidName);
        } catch (\Throwable) {
            return response()->json(['response' => 'error', 'text' => 'Failed to store file.', 'file_name' => $originalName]);
        }

        File::create([
            'user_id'   => $userId,
            'type'      => 'file',
            'name'      => $originalName,       // original name kept for display
            'file_url'  => $storedPath,          // UUID-based path relative to disk root
            'file_type' => $detectedMime,        // detected, not client-supplied
            'file_size' => $fileSize,
            'folder'    => $folder,
        ]);

        return response()->json(['response' => 'success', 'text' => 'File uploaded.']);
    }

    protected function listFiles(Request $request): JsonResponse
    {
        $userId = auth()->id();
        $folder = $request->query('folder');

        if ($folder === 'desktop') {
            $rows = File::where('user_id', $userId)->favorites()->active()
                ->whereIn('type', ['folder', 'notebook', 'file', 'diary'])
                ->select('id', 'name', 'type', 'icon', 'file_type', 'file_url', 'fav', 'diary_type', 'diary_date')
                ->orderByRaw("FIELD(type,'folder','notebook','file','diary'), name")
                ->get();
        } elseif ($folder === null || $folder === '') {
            $rows = File::where('user_id', $userId)->active()
                ->whereNull('folder')
                ->whereIn('type', ['folder', 'notebook', 'file'])
                ->whereNull('history')
                ->select('id', 'name', 'type', 'icon', 'file_type', 'file_url', 'fav')
                ->orderByRaw("FIELD(type,'folder','notebook','file'), name")
                ->get();
        } else {
            $folderId = (int) $folder;
            if (!$this->ownsFile($folderId, $userId)) {
                return response()->json(['response' => 'error', 'text' => 'Folder not found.']);
            }
            $rows = File::where('user_id', $userId)->active()
                ->where('folder', $folderId)
                ->whereIn('type', ['folder', 'notebook', 'file'])
                ->whereNull('history')
                ->select('id', 'name', 'type', 'icon', 'file_type', 'file_url', 'fav')
                ->orderByRaw("FIELD(type,'folder','notebook','file'), name")
                ->get();
        }

        $items = $rows->map(function ($row) {
            $item     = $row->toArray();
            $type     = $item['type']      ?? '';
            $name     = $item['name']      ?? '';
            $icon     = $item['icon']      ?? null;
            $fileUrl  = $item['file_url']  ?? null;
            $fileType = $item['file_type'] ?? null;
            $id       = $item['id'];

            $item['link'] = match ($type) {
                'folder'   => url('/my/app/file-manager') . '?folder=' . $id,
                'notebook' => url('/my/app/reader/notebook/' . $id),
                'diary'    => url('/my/app/reader/diary/' . $id),
                default    => url('/my/app/reader/file/' . $id),
            };

            if ($type === 'folder') {
                $count = File::where('folder', $id)->where('trash', 0)->whereNull('deleted_at')->count();
                $item['secondRow'] = "{$count} elementi";
            } elseif ($type === 'notebook') {
                $item['secondRow'] = 'Quaderno';
            } elseif ($type === 'diary') {
                $date = $item['diary_date'] ?? null;
                $item['secondRow'] = $date ? \Carbon\Carbon::parse($date)->format('d/m/Y') : '';
            } elseif ($type === 'file') {
                $item['secondRow'] = 'File';
            } else {
                $item['secondRow'] = '';
            }

            $item['style']       = '';
            $item['file_exists'] = false;
            $item['iconKey']     = null;

            if ($icon !== null) {
                $item['icon'] = asset('img/color/' . $icon);
            } elseif ($type === 'file') {
                $exists = $fileUrl && Storage::disk('uploads')->exists($fileUrl);
                $item['file_exists'] = $exists;
                if ($exists) {
                    if ($fileType && str_contains($fileType, 'image/')) {
                        $item['icon']  = url('/api/file/' . $id);
                        $item['style'] = 'max-height: 40px; width: auto';
                    } else {
                        $item['icon']    = null;
                        $item['iconKey'] = match (true) {
                            str_ends_with($name, '.txt')                                                             => 'file-text',
                            $fileType && str_contains($fileType, 'pdf')                                              => 'file-pdf',
                            str_ends_with($name, '.doc') || str_ends_with($name, '.docx')                           => 'file-doc',
                            str_ends_with($name, '.xls') || str_ends_with($name, '.xlsx')                           => 'file-xls',
                            str_ends_with($name, '.ppt') || str_ends_with($name, '.pptx')                           => 'file-ppt',
                            $fileType && str_contains($fileType, 'vnd.oasis') && str_ends_with($name, '.odt')       => 'file-doc',
                            $fileType && str_contains($fileType, 'vnd.oasis') && str_ends_with($name, '.ods')       => 'file-xls',
                            $fileType && str_contains($fileType, 'vnd.oasis') && str_ends_with($name, '.odp')       => 'file-ppt',
                            default                                                                                  => 'file',
                        };
                    }
                } else {
                    $item['icon']    = null;
                    $item['iconKey'] = 'file-missing';
                }
            } else {
                $item['icon']    = null;
                $item['iconKey'] = match ($type) {
                    'folder'   => 'folder',
                    'notebook' => 'notebook',
                    'diary'    => 'diary',
                    default    => 'file',
                };
            }

            return $item;
        })->values()->all();

        return response()->json(['response' => 'success', 'items' => $items]);
    }

    protected function listTrash(Request $request): JsonResponse
    {
        $userId = auth()->id();
        $start  = max(0, (int) $request->query('start', 0));

        $rows = File::where('user_id', $userId)->inTrash()
            ->whereIn('type', ['folder', 'notebook', 'file', 'diary'])
            ->select('id', 'name', 'type', 'icon', 'file_type', 'file_url', 'fav', 'diary_color', 'diary_type', 'diary_date')
            ->orderByDesc('id')
            ->offset($start)->limit(20)
            ->get();

        $items = $rows->map(function ($row) {
            $item     = $row->toArray();
            $type     = $item['type']      ?? '';
            $name     = $item['name']      ?? '';
            $icon     = $item['icon']      ?? null;
            $fileUrl  = $item['file_url']  ?? null;
            $fileType = $item['file_type'] ?? null;
            $id       = $item['id'];

            $item['link'] = match ($type) {
                'folder'   => url('/my/app/file-manager') . '?folder=' . $id,
                'notebook' => url('/my/app/reader/notebook/' . $id),
                'diary'    => url('/my/app/reader/diary/' . $id),
                default    => url('/my/app/reader/file/' . $id),
            };

            if ($type === 'folder') {
                $count = File::where('folder', $id)->where('trash', 0)->whereNull('deleted_at')->count();
                $item['secondRow'] = "{$count} elementi";
            } elseif ($type === 'notebook') {
                $item['secondRow'] = "Quaderno";
            } elseif ($type === 'diary') {
                $date      = $item['diary_date'] ?? null;
                $formatted = $date ? \Carbon\Carbon::parse($date)->format('d/m/Y') : '';
                $item['secondRow'] = $formatted;
            } else {
                $item['secondRow'] = "File";
            }

            $item['style']       = '';
            $item['file_exists'] = false;
            $item['iconKey']     = null;

            if ($icon !== null) {
                $item['icon'] = asset('img/color/' . $icon);
            } elseif ($type === 'file') {
                $exists = $fileUrl && Storage::disk('uploads')->exists($fileUrl);
                $item['file_exists'] = $exists;
                if ($exists) {
                    if ($fileType && str_contains($fileType, 'image/')) {
                        $item['icon']  = url('/api/file/' . $id);
                        $item['style'] = 'max-height: 40px; width: auto';
                    } else {
                        $item['icon']    = null;
                        $item['iconKey'] = match (true) {
                            str_ends_with($name, '.txt')                                                             => 'file-text',
                            $fileType && str_contains($fileType, 'pdf')                                              => 'file-pdf',
                            str_ends_with($name, '.doc') || str_ends_with($name, '.docx')                           => 'file-doc',
                            str_ends_with($name, '.xls') || str_ends_with($name, '.xlsx')                           => 'file-xls',
                            str_ends_with($name, '.ppt') || str_ends_with($name, '.pptx')                           => 'file-ppt',
                            $fileType && str_contains($fileType, 'vnd.oasis') && str_ends_with($name, '.odt')       => 'file-doc',
                            $fileType && str_contains($fileType, 'vnd.oasis') && str_ends_with($name, '.ods')       => 'file-xls',
                            $fileType && str_contains($fileType, 'vnd.oasis') && str_ends_with($name, '.odp')       => 'file-ppt',
                            default                                                                                  => 'file',
                        };
                    }
                } else {
                    $item['icon']    = null;
                    $item['iconKey'] = 'file-missing';
                }
            } else {
                $item['icon']    = null;
                $item['iconKey'] = match ($type) {
                    'folder'   => 'folder',
                    'notebook' => 'notebook',
                    'diary'    => 'diary',
                    default    => 'file',
                };
            }

            return $item;
        })->values()->all();

        return response()->json($items);
    }

    protected function restore(Request $request): JsonResponse
    {
        $userId = auth()->id();
        $id     = (int) $request->query('id');

        if (!$this->ownsFile($id, $userId)) return response()->json(['response' => 'error', 'text' => 'Not authorised.']);

        File::where('id', $id)->where('user_id', $userId)->update(['trash' => 0]);
        return response()->json(['response' => 'success', 'text' => 'Restored.']);
    }

    protected function emptyTrash(): JsonResponse
    {
        $userId = auth()->id();
        File::where('user_id', $userId)->inTrash()->update(['deleted_at' => now()]);
        return response()->json(['response' => 'success', 'text' => 'Trash emptied.']);
    }

    /**
     * Set a short-lived bypass token so Office Online Viewer can fetch the file
     * without a session. The token is valid for 5 minutes.
     */
    protected function setBypass(Request $request): JsonResponse
    {
        $userId = auth()->id();
        if (!$userId) {
            return response()->json(['response' => 'error', 'text' => 'Unauthenticated.'], 401);
        }

        $id = (int) $request->query('id');
        if (!$this->ownsFile($id, $userId)) {
            return response()->json(['response' => 'error', 'text' => 'Unauthorized.'], 403);
        }

        File::where('id', $id)->update(['bypass' => now()->addMinutes(5)]);

        return response()->json([
            'response' => 'success',
            'url'      => url('/api/file/' . $id),
        ]);
    }

    public function serveFile(Request $request, int $id)
    {
        return $this->provideFile($request, $id);
    }

    protected function provideFile(Request $request, int $id = 0)
    {
        if ($id === 0) {
            $id = (int) $request->query('id');
        }
        $userId = auth()->id();
        $owner  = null;

        Log::debug('provideFile: start', ['file_id' => $id, 'user_id' => $userId, 'ip' => $request->ip()]);

        if ($userId) {
            if ($this->ownsFile($id, $userId)) {
                $owner = $userId;
                Log::debug('provideFile: access granted — owner', ['file_id' => $id, 'user_id' => $userId]);
            } else {
                // Allow if the file is someone's profile picture (any authenticated user may view it)
                $profileRow = UserExpanded::where('profile_picture', $id)->first();
                if ($profileRow) {
                    $owner = $profileRow->id;
                    Log::debug('provideFile: access granted — profile picture', ['file_id' => $id, 'user_id' => $userId, 'owner_id' => $owner]);
                } else {
                    // Allow if the file is shared with the current user
                    if (Share::where('file', $id)->where('receiving', $userId)->active()->exists()) {
                        $owner = File::where('id', $id)->value('user_id');
                        Log::debug('provideFile: access granted — active share', ['file_id' => $id, 'user_id' => $userId, 'owner_id' => $owner]);
                    } else {
                        Log::debug('provideFile: 403 — authenticated but not owner, not profile picture, no active share', [
                            'file_id' => $id,
                            'user_id' => $userId,
                        ]);
                    }
                }
            }
        } else {
            // Unauthenticated: allow only if a short-lived bypass token is still valid
            $bypass = File::where('id', $id)
                ->whereNotNull('bypass')
                ->where('bypass', '>=', now())
                ->first();
            if ($bypass) {
                $owner = $bypass->user_id;
                Log::debug('provideFile: access granted — bypass token', ['file_id' => $id, 'owner_id' => $owner]);
            } else {
                $raw = File::where('id', $id)->first(['bypass']);
                Log::debug('provideFile: 403 — unauthenticated, no valid bypass token', [
                    'file_id'        => $id,
                    'bypass_exists'  => $raw && $raw->bypass !== null,
                    'bypass_expired' => $raw && $raw->bypass !== null && $raw->bypass < now(),
                ]);
            }
        }

        if ($owner === null) {
            Log::debug('provideFile: aborting with 403 — owner is null', ['file_id' => $id, 'user_id' => $userId]);
            abort(403);
        }

        $file = File::where('id', $id)
            ->where('user_id', $owner)
            ->ofType('file')
            ->first(['name', 'file_url', 'file_type', 'file_size']);

        if (!$file) {
            Log::debug('provideFile: 404 — file record not found or wrong type', ['file_id' => $id, 'owner_id' => $owner]);
            abort(404);
        }

        $disk     = Storage::disk('uploads');
        $fullPath = $disk->path($file->file_url);

        // Guard against path traversal: resolved path must be inside the disk root.
        $diskRoot = realpath($disk->path(''));
        $realPath = realpath($fullPath);
        if ($realPath === false || $diskRoot === false || !str_starts_with($realPath, $diskRoot . DIRECTORY_SEPARATOR)) {
            Log::warning('provideFile: 403 — path traversal check failed', [
                'file_id'   => $id,
                'file_url'  => $file->file_url,
                'full_path' => $fullPath,
                'real_path' => $realPath,
                'disk_root' => $diskRoot,
            ]);
            abort(403);
        }

        if (!file_exists($realPath)) {
            Log::debug('provideFile: 404 — file does not exist on disk', ['file_id' => $id, 'real_path' => $realPath]);
            abort(404);
        }

        Log::debug('provideFile: serving file', ['file_id' => $id, 'name' => $file->name, 'size' => $file->file_size]);

        $contentDisposition = HeaderUtils::makeDisposition(
            HeaderUtils::DISPOSITION_INLINE,
            $file->name
        );

        return response()->file($realPath, [
            'Content-Type'        => $file->file_type ?? 'application/octet-stream',
            'Content-Disposition' => $contentDisposition,
        ]);
    }

    protected function listIcons(): JsonResponse
    {
        $files = glob(public_path('img/color/*'));
        $names = [];
        foreach ($files as $path) {
            $base = basename($path);
            // Only expose safe filenames (letters, digits, hyphens, underscores + extension)
            if (preg_match('/^[a-z0-9_\-]+\.(png|jpg|svg|webp)$/i', $base)) {
                $names[] = $base;
            }
        }
        sort($names);
        return response()->json(['response' => 'success', 'icons' => $names]);
    }

    protected function setIcon(Request $request): JsonResponse
    {
        $userId = auth()->id();
        $id     = (int) $request->query('id');
        $icon   = (string) $request->input('icon', '');

        if (!$this->ownsFile($id, $userId)) {
            return response()->json(['response' => 'error', 'text' => 'File not found.']);
        }

        // Verify the file is not in trash and is one of the allowed types
        $file = File::where('id', $id)->where('user_id', $userId)->whereNull('deleted_at')
            ->whereIn('type', ['folder', 'notebook', 'file'])
            ->first();

        if (!$file) {
            return response()->json(['response' => 'error', 'text' => 'File not found.']);
        }

        if ($icon === '') {
            $file->icon = null;
            $file->save();
            return response()->json(['response' => 'success', 'text' => __('icon-reset'), 'icon' => null]);
        }

        // Strip any path components and validate format
        $safe = basename($icon);
        if (!preg_match('/^[a-z0-9_\-]+\.(png|jpg|svg|webp)$/i', $safe)) {
            return response()->json(['response' => 'error', 'text' => 'Invalid icon name.']);
        }

        // Confirm the file actually exists in our controlled directory
        if (!file_exists(public_path('img/color/' . $safe))) {
            return response()->json(['response' => 'error', 'text' => 'Icon not found.']);
        }

        $file->icon = $safe;
        $file->save();

        return response()->json([
            'response' => 'success',
            'text'     => __('icon-changed'),
            'icon'     => asset('img/color/' . $safe),
        ]);
    }

    protected function ownsFile(int $id, int $userId): bool
    {
        return File::where('id', $id)->where('user_id', $userId)->whereNull('deleted_at')->exists();
    }

    protected function humanSize(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;
        while ($bytes >= 1024 && $i < 3) { $bytes /= 1024; $i++; }
        return round($bytes, 2) . ' ' . $units[$i];
    }
}
