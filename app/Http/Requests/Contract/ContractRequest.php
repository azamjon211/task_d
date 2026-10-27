<?php

namespace App\Http\Requests\Contract;

use App\Models\Contract;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

abstract class ContractRequest extends FormRequest
{
    protected string $ability = 'manage';

    public function authorize(): bool
    {
        Gate::authorize('view', $this->contract());
        Gate::authorize($this->ability, $this->contract());

        return true;
    }

    public function contract(): Contract
    {
        return $this->route('contract');
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [];
    }
}
