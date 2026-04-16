<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;

class SetLocale
{
    public function handle(Request $request, Closure $next): mixed
    {
        $locale = $request->cookie('language', config('app.locale', 'en'));

        if (!file_exists(lang_path("{$locale}.json"))) {
            $locale = config('app.locale', 'en');
        }

        App::setLocale($locale);

        // Sync the cookie locale to the DB for authenticated users so that
        // server-side operations (e.g. emails) can use the correct language.
        if (Auth::check()) {
            $expanded = Auth::user()->expanded;
            if ($expanded && $expanded->language !== $locale) {
                $expanded->language = $locale;
                $expanded->save();
            }
        }

        return $next($request);
    }
}
