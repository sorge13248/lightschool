<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;

class UploadFileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'file' => ['required', 'file'],
        ];
    }

    /**
     * Return the custom API error format instead of Laravel's default
     * validation error response so the frontend receives a consistent shape.
     */
    protected function failedValidation(Validator $validator): never
    {
        throw new ValidationException(
            $validator,
            response()->json([
                'response' => 'error',
                'text'     => $validator->errors()->first(),
            ], 422)
        );
    }
}
