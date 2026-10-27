<?php

namespace App\Models;

use App\Casts\MoneyCast;
use App\Support\Money;
use Carbon\CarbonImmutable;
use Database\Factories\PaymentFactory;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

/**
 * @property int $payment_stage_id
 * @property int $budget_line_id
 * @property Money $amount
 * @property CarbonImmutable $paid_on
 * @property CarbonImmutable|null $voided_at
 */
class Payment extends Model
{
    /** @use HasFactory<PaymentFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'payment_stage_id',
        'budget_line_id',
        'amount',
        'paid_on',
        'reference',
    ];

    private const array VOID_COLUMNS = ['voided_at', 'voided_by', 'void_reason', 'updated_at'];

    protected static function booted(): void
    {
        static::deleting(function (): never {
            throw new LogicException("To'lov reestridan yozuv o'chirilmaydi, uni bekor qiling.");
        });

        static::updating(function (Payment $payment): void {
            $changesOtherColumns = array_diff(array_keys($payment->getDirty()), self::VOID_COLUMNS) !== [];

            if ($changesOtherColumns || $payment->getOriginal('voided_at') !== null) {
                throw new LogicException("To'lov reestri yozuvi o'zgartirilmaydi.");
            }
        });
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount' => MoneyCast::class,
            'paid_on' => 'immutable_date',
            'voided_at' => 'immutable_datetime',
        ];
    }

    public function isVoided(): bool
    {
        return $this->voided_at !== null;
    }

    /**
     * @param  Builder<Payment>  $query
     */
    #[Scope]
    protected function active(Builder $query): void
    {
        $query->whereNull('voided_at');
    }

    /**
     * @return BelongsTo<Contract, $this>
     */
    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class);
    }

    /**
     * @return BelongsTo<PaymentStage, $this>
     */
    public function stage(): BelongsTo
    {
        return $this->belongsTo(PaymentStage::class, 'payment_stage_id');
    }

    /**
     * @return BelongsTo<BudgetLine, $this>
     */
    public function budgetLine(): BelongsTo
    {
        return $this->belongsTo(BudgetLine::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function voider(): BelongsTo
    {
        return $this->belongsTo(User::class, 'voided_by');
    }
}
