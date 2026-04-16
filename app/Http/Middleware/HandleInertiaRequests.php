<?php

namespace App\Http\Middleware;

use App\Models\App;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    public function share(Request $request): array
    {
        $shared = [
            'appName'         => config('app.name'),
            'locale'          => app()->getLocale(),
            'isAuthenticated' => auth()->check(),
            'username'        => auth()->check() ? (auth()->user()->username ?? auth()->user()->name ?? null) : null,
            'currentUrl'      => $request->fullUrl(),
        ];

        if (!auth()->check()) {
            return array_merge(parent::share($request), $shared);
        }

        $user     = auth()->user();
        $expanded = $user->expanded;

        $taskbarIds  = $expanded->getTaskbarArray();
        $taskbarApps = App::whereIn('id', $taskbarIds)->select('id', 'unique_name')->get()->keyBy('id');
        $taskbar     = collect($taskbarIds)
            ->map(fn($id) => $taskbarApps->get($id))
            ->filter()
            ->map(fn($app) => ['id' => $app->id, 'unique_name' => $app->unique_name])
            ->values()
            ->toArray();

        $wallpaperRaw = $expanded->wallpaper ? $expanded->wallpaper : null;
        $wallpaper    = ($wallpaperRaw && isset($wallpaperRaw['id'])) ? [
            'id'      => (int) $wallpaperRaw['id'],
            'opacity' => (string) ($wallpaperRaw['opacity'] ?? '0.5'),
            'color'   => (string) ($wallpaperRaw['color']   ?? '0, 0, 0'),
            'blur'    => (int)    ($wallpaperRaw['blur']     ?? 0),
        ] : null;

        return array_merge(parent::share($request), $shared, [
            'currentUser' => [
                'name'            => $expanded->name     ?? '',
                'surname'         => $expanded->surname   ?? '',
                'username'        => $user->username       ?? '',
                'profile_picture' => $expanded->profile_picture
                    ? url('/api/file/' . (int) $expanded->profile_picture)
                    : null,
                'taskbar_size'    => (int) ($expanded->taskbar_size ?? 0),
                'taskbar'         => $taskbar,
                'accent'          => '#' . ltrim($expanded->accent ?? '1e6ad3', '#'),
                'wallpaper'       => $wallpaper,
            ],
            'allApps' => App::orderBy('unique_name')->select('id', 'unique_name')->get()->toArray(),
        ]);
    }
}
