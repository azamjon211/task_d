<?php

namespace App\Http\Requests\Budget;

use App\Http\Requests\Concerns\NormalizesMoneyInput;
use App\Models\BudgetLine;
use App\Support\Money;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreBudgetLineRequest extends FormRequest
{
    use NormalizesMoneyInput;

    public function authorize(): bool
    {
        return $this->user()->can('create', BudgetLine::class);
    }

    protected function prepareForValidation(): void
    {
        $this->normalizeMoney(['allocated_amount']);

        if (is_string($this->input('expense_item'))) {
            $this->merge(['expense_item' => trim($this->input('expense_item'))]);
        }
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'fiscal_year' => ['required', 'integer', 'min:2000', 'max:2100'],
            'expense_item' => ['required', 'string', 'max:255'],
            'month' => [
                'required',
                'integer',
                'between:1,12',
                Rule::unique('budget_lines', 'month')
                    ->where('fiscal_year', $this->integer('fiscal_year'))
                    ->where('expense_item', (string) $this->input('expense_item')),
            ],
            'allocated_amount' => ['required', 'regex:'.self::MONEY_REGEX],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'month.unique' => 'Bu yil, xarajat moddasi va oy uchun smeta qatori allaqachon mavjud.',
        ];
    }

    /**
     * @return array{fiscal_year: int, expense_item: string, month: int, allocated_amount: Money}
     */
    public function attributesForBudgetLine(): array
    {
        return [
            'fiscal_year' => $this->integer('fiscal_year'),
            'expense_item' => $this->string('expense_item')->toString(),
            'month' => $this->integer('month'),
            'allocated_amount' => Money::of($this->string('allocated_amount')->toString()),
        ];
    }
}
