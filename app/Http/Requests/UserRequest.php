<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'user_email' => 'sometimes|email:rfc,dns|max:255|unique:users,user_email,' . auth()->id() . ',user_id',
            'user_phone' => 'sometimes|regex:/^\+?[1-9]\d{9,14}$/|unique:users,user_phone,' . auth()->id() . ',user_id',
            'user_password' => 'sometimes|string|min:8|max:16|regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]+$/',
        ];
    }

    public function messages(): array
    {
        return [
            'user_password.regex' => 'Password must contain at least one uppercase letter, one lowercase letter, one number and one special character.',
            'user_phone.regex' => 'Phone number must be a valid international format starting with + followed by 10-15 digits.',
        ];
    }
}
