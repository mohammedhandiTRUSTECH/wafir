<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateTargetRequest extends FormRequest
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
            'name' => 'required',
            'target'  => 'required|numeric|min:0',
            'users'  => 'required|array',
            'users.*'  => 'required|exists:users,id',
            'sales_percentage' =>  'required|numeric|min:1|max:100',
            'commission_percentage' =>  'required|numeric|max:100',
        ];
    }
}
