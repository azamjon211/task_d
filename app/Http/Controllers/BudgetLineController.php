<?php

namespace App\Http\Controllers;

use App\Http\Requests\Budget\StoreBudgetLineRequest;
use App\Http\Requests\Budget\UpdateBudgetLineRequest;
use App\Models\BudgetLine;
use App\Services\Accounting\BudgetService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class BudgetLineController extends Controller
{
    public function __construct(private readonly BudgetService $budget) {}

    public function index(): View
    {
        Gate::authorize('viewAny', BudgetLine::class);

        return view('budget.index', [
            'lines' => $this->budget->lines(),
            'months' => BudgetLine::MONTH_NAMES,
        ]);
    }

    public function store(StoreBudgetLineRequest $request): RedirectResponse
    {
        $this->budget->create($request->attributesForBudgetLine());

        return redirect()->route('budget-lines.index')->with('status', "Smeta qatori qo'shildi.");
    }

    public function edit(BudgetLine $budgetLine): View
    {
        Gate::authorize('update', $budgetLine);

        return view('budget.edit', ['line' => $budgetLine]);
    }

    public function update(UpdateBudgetLineRequest $request, BudgetLine $budgetLine): RedirectResponse
    {
        $this->budget->updateAllocated($budgetLine, $request->allocatedAmount());

        return redirect()->route('budget-lines.index')->with('status', 'Smeta qatori saqlandi.');
    }

    public function destroy(BudgetLine $budgetLine): RedirectResponse
    {
        Gate::authorize('delete', $budgetLine);

        $this->budget->delete($budgetLine);

        return redirect()->route('budget-lines.index')->with('status', "Smeta qatori o'chirildi.");
    }
}
