<?php

namespace App\Http\Controllers;

use App\Models\File;
use App\Models\Project;
use App\Models\ProjectFile;
use App\Models\UserExpanded;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProjectController extends Controller
{

    public function handle(Request $request): JsonResponse
    {
        $type = $request->query('type');

        return match ($type) {
            'delete'        => $this->delete($request),
            'code'          => $this->code($request),
            'files'         => $this->files($request),
            'files-by-code' => $this->filesByCode($request),
            'your-files'    => $this->yourFiles(),
            'project'       => $this->project($request),
            'stop'          => $this->stop($request),
            default         => response()->json(['response' => 'error', 'text' => 'Invalid type']),
        };
    }

    protected function delete(Request $request): JsonResponse
    {
        $code = $request->cookie('project_code');

        if (!$code) {
            return response()->json(['response' => 'error', 'text' => 'No active project']);
        }

        $project = Project::where('code', $code)->first();
        if (!$project) {
            return response()->json(['response' => 'error', 'text' => 'Project not found']);
        }

        ProjectFile::where('project', $code)->delete();
        $project->delete();

        return response()->json(['response' => 'success', 'text' => 'Project deleted'])
            ->withoutCookie('project_code');
    }

    protected function code(Request $request): JsonResponse
    {
        $code = $request->cookie('project_code');

        if ($code) {
            $valid = Project::where('code', $code)
                ->whereBetween('timestamp', [now()->subDays(7), now()])
                ->exists();

            if (!$valid) {
                ProjectFile::where('project', $code)->delete();
                Project::where('code', $code)->delete();
                $code = null;
            }
        }

        if (!$code) {
            $newCode  = null;
            $attempts = 0;
            do {
                $candidate = Project::generateCode();
                try {
                    Project::create(['code' => $candidate]);
                    $newCode = $candidate;
                    break;
                } catch (UniqueConstraintViolationException $e) {
                    // try again
                } catch (\Throwable $e) {
                    return response()->json(['response' => 'error', 'text' => $e->getMessage()]);
                }
                $attempts++;
            } while ($attempts < 100);

            if (!$newCode) {
                return response()->json(['response' => 'error', 'text' => 'Could not generate project code']);
            }
            $code = $newCode;
        }

        return response()->json(['response' => 'success', 'code' => $code, 'text' => 'Project code is'])
            ->cookie('project_code', $code, 60 * 24 * 7);
    }

    protected function files(Request $request): JsonResponse
    {
        $code = $request->cookie('project_code');

        if (!$code) {
            return response()->json(['response' => 'error', 'text' => 'No active project']);
        }

        $rows = ProjectFile::join('project', 'project_files.project', '=', 'project.code')
            ->where('project_files.project', $code)
            ->whereBetween('project.timestamp', [now()->subDays(7), now()])
            ->select('project_files.user', 'project_files.file', 'project_files.editable')
            ->get();

        if ($rows->isEmpty()) {
            return response()->json(['response' => 'error', 'text' => 'No files in project or project expired']);
        }

        $files = $rows->map(fn($row) => [
            'id'         => (int) $row->file,
            'editable'   => $row->editable,
            ...$this->getFileDetails((int) $row->file, (int) $row->user),
            'user'       => $this->getUserName((int) $row->user),
        ]);

        return response()->json($files);
    }

    protected function filesByCode(Request $request): JsonResponse
    {
        if ($err = $this->validateInput(
            ['code' => $request->query('code')],
            ['code' => 'required|string'],
            ['code.*' => __('project-error-code-required')]
        )) return $err;

        $code = strtoupper(trim((string) $request->query('code')));

        $rows = ProjectFile::join('project', 'project_files.project', '=', 'project.code')
            ->where('project_files.project', $code)
            ->whereBetween('project.timestamp', [now()->subDays(7), now()])
            ->select('project_files.user', 'project_files.file', 'project_files.editable')
            ->get();

        if ($rows->isEmpty()) {
            return response()->json(['response' => 'error', 'text' => 'No files found or project expired']);
        }

        $files = $rows->map(fn($row) => [
            'id'       => (int) $row->file,
            'editable' => $row->editable,
            'name'     => $this->getFileDetails((int) $row->file, (int) $row->user)['name'] ?? null,
            'type'     => $this->getFileDetails((int) $row->file, (int) $row->user)['type'] ?? null,
            'user'     => $this->getUserName((int) $row->user),
        ]);

        return response()->json(['response' => 'success', 'files' => $files]);
    }

    protected function yourFiles(): JsonResponse
    {
        $userId = auth()->id();

        $rows = ProjectFile::join('project', 'project_files.project', '=', 'project.code')
            ->where('project_files.user', $userId)
            ->whereBetween('project.timestamp', [now()->subDays(7), now()])
            ->select('project_files.user', 'project_files.file', 'project_files.editable', 'project_files.project')
            ->get();

        if ($rows->isEmpty()) {
            return response()->json(['response' => 'success', 'files' => []]);
        }

        $files = $rows->map(fn($row) => [
            'id'       => (int) $row->file,
            'project'  => $row->project,
            'editable' => $row->editable,
            ...$this->getFileDetails((int) $row->file, (int) $row->user),
            'user'     => $this->getUserName((int) $row->user),
        ]);

        return response()->json(['response' => 'success', 'files' => $files]);
    }

    protected function project(Request $request): JsonResponse
    {
        $userId   = auth()->id();
        $editable = $request->has('editable');

        if ($err = $this->validateInput(
            ['file' => $request->query('file'), 'project' => $request->input('project')],
            ['file' => 'required|integer|min:1', 'project' => 'required|string'],
            ['file.*' => __('project-error-file-required'), 'project.*' => __('project-error-code-required')]
        )) return $err;

        $fileId = (int) $request->query('file');
        $code   = trim((string) $request->input('project'));

        if (!File::where('id', $fileId)->where('user_id', $userId)->exists()) {
            return response()->json(['response' => 'error', 'text' => 'File not owned by you']);
        }

        if (ProjectFile::where('project', $code)->where('file', $fileId)->exists()) {
            return response()->json(['response' => 'error', 'text' => 'File already in project']);
        }

        if (!Project::where('code', $code)->whereBetween('timestamp', [now()->subDays(7), now()])->exists()) {
            return response()->json(['response' => 'error', 'text' => 'Invalid or expired project code']);
        }

        ProjectFile::create([
            'project'  => $code,
            'file'     => $fileId,
            'user'     => $userId,
            'editable' => $editable ? 1 : 0,
        ]);

        return response()->json(['response' => 'success', 'text' => 'File added to project']);
    }

    protected function stop(Request $request): JsonResponse
    {
        $userId = auth()->id();

        if ($err = $this->validateInput(
            ['file' => $request->query('file'), 'project' => $request->query('project')],
            ['file' => 'required|integer|min:1', 'project' => 'required|string'],
            ['file.*' => __('project-error-file-required'), 'project.*' => __('project-error-code-required')]
        )) return $err;

        $fileId = (int) $request->query('file');
        $code   = trim((string) $request->query('project'));

        if (!File::where('id', $fileId)->where('user_id', $userId)->exists()) {
            return response()->json(['response' => 'error', 'text' => 'File not owned by you']);
        }

        if (!ProjectFile::where('project', $code)->where('file', $fileId)->exists()) {
            return response()->json(['response' => 'error', 'text' => 'File not in project']);
        }

        if (!Project::where('code', $code)->exists()) {
            return response()->json(['response' => 'error', 'text' => 'Invalid project code']);
        }

        ProjectFile::where('project', $code)->where('file', $fileId)->where('user', $userId)->delete();

        return response()->json(['response' => 'success', 'text' => 'File removed from project']);
    }

    protected function getFileDetails(int $fileId, int $ownerId): array
    {
        $file = File::where('id', $fileId)->where('user_id', $ownerId)->first(['name', 'type', 'icon', 'diary_type', 'diary_date']);
        if (!$file) return ['name' => null, 'type' => null, 'icon' => null, 'diary_type' => null, 'diary_date' => null];
        return [
            'name'       => $file->name,
            'type'       => $file->type,
            'icon'       => $file->icon,
            'diary_type' => $file->diary_type,
            'diary_date' => $file->diary_date,
        ];
    }

    protected function getUserName(int $userId): array
    {
        $expanded = UserExpanded::find($userId, ['name', 'surname']);
        return $expanded ? ['name' => $expanded->name, 'surname' => $expanded->surname] : [];
    }
}
