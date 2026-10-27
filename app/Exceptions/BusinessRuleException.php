<?php

namespace App\Exceptions;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use RuntimeException;

class BusinessRuleException extends RuntimeException
{
    public function __construct(string $message, public readonly string $field = 'general')
    {
        parent::__construct($message);
    }

    public function render(Request $request): RedirectResponse
    {
        return back()->withInput()->withErrors([$this->field => $this->getMessage()]);
    }
}
