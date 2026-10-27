<?php

namespace App\Http\Requests\Contract;

use Illuminate\Support\Facades\Gate;

class UpdateContractRequest extends StoreContractRequest
{
    public function authorize(): bool
    {
        Gate::authorize('view', $this->route('contract'));
        Gate::authorize('manage', $this->route('contract'));

        return true;
    }
}
