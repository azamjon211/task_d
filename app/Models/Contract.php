<?php

namespace App\Models;

use App\Enums\ContractStatus;
use App\Support\Money;
use Carbon\CarbonImmutable;
use Database\Factories\ContractFactory;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $created_by
 * @property CarbonImmutable $start_date
 * @property int $duration_days
 * @property ContractStatus $status
 */
class Contract extends Model
{
    /** @use HasFactory<ContractFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'number',
        'supplier',
        'start_date',
        'duration_days',
    ];

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'status' => 'draft',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'start_date' => 'immutable_date',
            'duration_days' => 'integer',
            'status' => ContractStatus::class,
            'activated_at' => 'immutable_datetime',
            'completed_at' => 'immutable_datetime',
        ];
    }

    public function dueDate(): CarbonImmutable
    {
        return $this->start_date->addDays($this->duration_days);
    }

    public function itemsPaidTotal(): Money
    {
        return $this->items->reduce(fn (Money $sum, ContractItem $item): Money => $sum->plus($item->paidTotal()), Money::zero());
    }

    public function isOwnedBy(User $user): bool
    {
        return $this->created_by === $user->id;
    }

    /**
     * @param  Builder<Contract>  $query
     */
    #[Scope]
    protected function visibleTo(Builder $query, User $user): void
    {
        $query->when(
            $user->isExecutor(),
            fn (Builder $query) => $query->where('created_by', $user->id),
        );
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return HasMany<ContractItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(ContractItem::class)->orderBy('id');
    }

    /**
     * @return HasMany<Delivery, $this>
     */
    public function deliveries(): HasMany
    {
        return $this->hasMany(Delivery::class)->orderBy('delivered_on')->orderBy('id');
    }

    /**
     * @return HasMany<PaymentStage, $this>
     */
    public function stages(): HasMany
    {
        return $this->hasMany(PaymentStage::class)->orderBy('sequence');
    }

    /**
     * @return HasMany<Payment, $this>
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * @return HasMany<PenaltyDecision, $this>
     */
    public function penaltyDecisions(): HasMany
    {
        return $this->hasMany(PenaltyDecision::class)->orderByDesc('id');
    }
}
