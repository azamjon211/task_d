<?php

namespace App\Models;

use App\Casts\MoneyCast;
use App\Support\Money;
use Database\Factories\BudgetLineFactory;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use LogicException;

/**
 * @property int $fiscal_year
 * @property string $expense_item
 * @property int $month
 * @property Money $allocated_amount
 */
class BudgetLine extends Model
{
    /** @use HasFactory<BudgetLineFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'fiscal_year',
        'expense_item',
        'month',
        'allocated_amount',
    ];

    public const array MONTH_NAMES = [
        1 => 'Yanvar', 2 => 'Fevral', 3 => 'Mart', 4 => 'Aprel',
        5 => 'May', 6 => 'Iyun', 7 => 'Iyul', 8 => 'Avgust',
        9 => 'Sentabr', 10 => 'Oktabr', 11 => 'Noyabr', 12 => 'Dekabr',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'fiscal_year' => 'integer',
            'month' => 'integer',
            'allocated_amount' => MoneyCast::class,
        ];
    }

    /**
     * @param  Builder<BudgetLine>  $query
     */
    #[Scope]
    protected function withSpent(Builder $query): void
    {
        $query->withSum(['payments as spent' => fn (Builder $payments) => $payments->active()], 'amount');
    }

    public function spentAmount(): Money
    {
        if (! array_key_exists('spent', $this->attributes)) {
            throw new LogicException("Smeta qatori sarfi yuklanmagan: so'rovga withSpent() qo'shing.");
        }

        return Money::of((string) ($this->attributes['spent'] ?? '0'));
    }

    public function remaining(): Money
    {
        return $this->allocated_amount->minus($this->spentAmount());
    }

    public function monthName(): string
    {
        return self::MONTH_NAMES[$this->month];
    }

    public function label(): string
    {
        return "{$this->fiscal_year} · {$this->expense_item} · {$this->monthName()}";
    }

    /**
     * @return HasMany<Payment, $this>
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }
}
