<?php

namespace App\Policies;

use App\Models\Contract;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class ContractPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Contract $contract): Response
    {
        if ($user->isExecutor() && ! $contract->isOwnedBy($user)) {
            return Response::denyAsNotFound();
        }

        return Response::allow();
    }

    public function create(User $user): bool
    {
        return $user->isExecutor();
    }

    public function manage(User $user, Contract $contract): Response
    {
        if (! $user->isExecutor()) {
            return Response::deny("Shartnomani faqat ijrochi o'zgartira oladi.");
        }

        return $contract->isOwnedBy($user) ? Response::allow() : Response::denyAsNotFound();
    }

    public function decidePenalty(User $user, Contract $contract): Response
    {
        return $user->isHead()
            ? Response::allow()
            : Response::deny('Jarimani faqat boshliq tasdiqlaydi yoki rad etadi.');
    }

    public function manageAccounting(User $user, Contract $contract): Response
    {
        return $user->isAccountant()
            ? Response::allow()
            : Response::deny("To'lovlarni faqat buxgalter yuritadi.");
    }
}
