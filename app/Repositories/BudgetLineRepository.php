<?php

namespace App\Repositories;

use App\Models\BudgetLine;
use App\Support\Money;
use Illuminate\Database\Eloquent\Collection;

class BudgetLineRepository
{
    /**
     * @return Collection<int, BudgetLine>
     */
    public function allWithSpent(): Collection
    {
        return BudgetLine::query()
            ->withSpent()
            ->orderByDesc('fiscal_year')
            ->orderBy('expense_item')
            ->orderBy('month')
            ->get();
    }

    public function lockForUpdate(int $budgetLineId): BudgetLine
    {
        return BudgetLine::query()->whereKey($budgetLineId)->lockForUpdate()->firstOrFail();
    }

    /**
     * @param  array{fiscal_year: int, expense_item: string, month: int, allocated_amount: Money}  $attributes
     */
    public function create(array $attributes): BudgetLine
    {
        return BudgetLine::query()->create($attributes);
    }

    public function updateAllocated(BudgetLine $budgetLine, Money $allocated): BudgetLine
    {
        $budgetLine->update(['allocated_amount' => $allocated]);

        return $budgetLine;
    }

    public function delete(BudgetLine $budgetLine): void
    {
        $budgetLine->delete();
    }
}
