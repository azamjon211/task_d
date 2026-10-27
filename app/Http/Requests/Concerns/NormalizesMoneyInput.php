<?php

namespace App\Http\Requests\Concerns;

trait NormalizesMoneyInput
{
    public const string MONEY_REGEX = '/^\d{1,13}(\.\d{1,2})?$/';

    /**
     * @param  list<string>  $fields
     */
    protected function normalizeMoney(array $fields): void
    {
        $normalized = [];

        foreach ($fields as $field) {
            $value = $this->input($field);

            if (is_string($value) && $value !== '') {
                $normalized[$field] = str_replace([' ', "\u{00A0}", ','], ['', '', '.'], trim($value));
            }
        }

        $this->merge($normalized);
    }
}
