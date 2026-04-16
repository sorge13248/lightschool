<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

abstract class Controller
{
    /**
     * Run Laravel validation and return a JSON error response if it fails,
     * or null if it passes. Keeps the custom {'response':'error','text':'...'} format.
     */
    protected function validateInput(array $data, array $rules, array $messages = []): ?JsonResponse
    {
        $v = Validator::make($data, $rules, $messages);

        if ($v->fails()) {
            return response()->json(['response' => 'error', 'text' => $v->errors()->first()]);
        }

        return null;
    }
}
