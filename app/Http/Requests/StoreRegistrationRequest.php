<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class StoreRegistrationRequest extends FormRequest
{

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'first_name' => 'required|string|max:50', // Nama depan wajib diisi, string, dan max 50 karakter
            'last_name' => 'required|string|max:50', // Nama belakang opsional, string, dan max 50 karakter
            'email' => 'required|email|unique:users,email', // Email wajib, valid, unik di tabel users
            'password' => 'required|string|confirmed', // Password wajib, minimal 8 karakter, dan dikonfirmasi
            'password_confirmation' => 'required'
        ];
    }

    public function messages(): array
    {
        return [
            'first_name.required' => 'First name is required.',
            'first_name.string' => 'First name must be a valid string.',
            'first_name.max' => 'First name cannot exceed 50 characters.',
            'last_name.string' => 'Last name must be a valid string.',
            'last_name.max' => 'Last name cannot exceed 50 characters.',
            'email.required' => 'Email is required.',
            'email.email' => 'Please provide a valid email address.',
            'email.unique' => 'This email is already registered.',
            'password.required' => 'Password is required.',
            'password.string' => 'Password must be a valid string.',
            'password.min' => 'Password must be at least 8 characters.',
            'password.max' => 'Password cannot exceed 255 characters.',
            'password.confirmed' => 'Password confirmation does not match.',
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json(['errors' => $validator->errors()], 422));
    }

}
