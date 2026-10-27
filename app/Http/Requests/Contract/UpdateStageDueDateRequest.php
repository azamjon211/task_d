<?php

namespace App\Http\Requests\Contract;

class UpdateStageDueDateRequest extends ContractRequest
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'due_date' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:'.$this->contract()->start_date->toDateString()],
        ];
    }

    public function dueDate(): ?string
    {
        return $this->filled('due_date') ? $this->string('due_date')->toString() : null;
    }
}
