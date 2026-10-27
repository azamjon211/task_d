<?php

namespace App\Http\Requests\Contract;

use App\Models\Contract;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreContractRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Contract::class);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'number' => ['required', 'string', 'max:50', Rule::unique('contracts', 'number')->ignore($this->route('contract'))],
            'supplier' => ['required', 'string', 'max:255'],
            'start_date' => ['required', 'date_format:Y-m-d'],
            'duration_days' => ['required', 'integer', 'min:1', 'max:3650'],
        ];
    }

    /**
     * @return array{number: string, supplier: string, start_date: string, duration_days: int}
     */
    public function attributesForContract(): array
    {
        return [
            'number' => trim($this->string('number')),
            'supplier' => trim($this->string('supplier')),
            'start_date' => $this->string('start_date')->toString(),
            'duration_days' => $this->integer('duration_days'),
        ];
    }
}
