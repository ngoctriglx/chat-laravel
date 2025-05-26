<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UserDetailRequest extends FormRequest {
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool {
        return auth()->check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array {
        return [
            'first_name' => 'sometimes|string|max:255',
            'last_name' => 'sometimes|string|max:255',
            'picture' => 'sometimes|file|image|mimes:jpeg,png,jpg|max:2048',
            'gender' => ['sometimes', Rule::in(['male', 'female'])],
            'birth_date' => 'sometimes|date|before:today|date_format:Y-m-d',
            'status_message' => 'sometimes|nullable|string|max:255',
            'background_image' => 'sometimes|file|image|mimes:jpeg,png,jpg|max:2048'
        ];
    }
}
