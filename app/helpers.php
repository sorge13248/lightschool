<?php

if (!function_exists('monoIconColor')) {
    /**
     * Return the mono icon color ('black' or 'white') for the current authenticated user.
     * Falls back to 'black' for guests or when the theme has no icon setting.
     */
    function monoIconColor(): string
    {
        if (!auth()->check()) {
            return 'black';
        }

        $themeKey = auth()->user()->expanded?->theme;
        if (!$themeKey) {
            return 'black';
        }

        return \App\Models\Theme::where('unique_name', $themeKey)->value('icon') ?? 'black';
    }
}
