<?php

use App\Models\Setting;

if (!function_exists('setting')) {
    /**
     * Look up a value from the admin-managed settings table.
     */
    function setting(string $key, ?string $default = null): ?string
    {
        return Setting::where('key', $key)->value('value') ?? $default;
    }
}
