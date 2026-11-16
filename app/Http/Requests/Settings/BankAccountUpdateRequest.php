<?php

namespace App\Http\Requests\Settings;

use App\Services\XenditService;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BankAccountUpdateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'bank_code' => ['required', 'string', Rule::in(array_keys(XenditService::getSupportedBanks()))],
            'bank_account_number' => ['required', 'string', 'min:5', 'max:30', 'regex:/^[0-9]+$/'],
            'bank_account_name' => ['required', 'string', 'min:2', 'max:100'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'bank_code.required' => 'Pilih bank tujuan pencairan.',
            'bank_code.in' => 'Bank yang dipilih tidak didukung.',
            'bank_account_number.required' => 'Nomor rekening wajib diisi.',
            'bank_account_number.min' => 'Nomor rekening minimal 5 digit.',
            'bank_account_number.regex' => 'Nomor rekening hanya boleh berisi angka.',
            'bank_account_name.required' => 'Nama pemilik rekening wajib diisi.',
            'bank_account_name.min' => 'Nama pemilik rekening minimal 2 karakter.',
        ];
    }
}
