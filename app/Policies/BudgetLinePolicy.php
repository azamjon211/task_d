<?php

namespace App\Policies;

use App\Models\BudgetLine;
use App\Models\User;

class BudgetLinePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAccountant();
    }

    public function create(User $user): bool
    {
        return $user->isAccountant();
    }

    public function update(User $user, BudgetLine $budgetLine): bool
    {
        return $user->isAccountant();
    }

    public function delete(User $user, BudgetLine $budgetLine): bool
    {
        return $user->isAccountant();
    }
}
