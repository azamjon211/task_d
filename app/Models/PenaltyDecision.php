<?php

namespace App\Models;

use App\Casts\MoneyCast;
use App\Enums\PenaltyDecisionStatus;
use App\Support\Money;
use Carbon\CarbonImmutable;
use Database\Factories\PenaltyDecisionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property PenaltyDecisionStatus $status
 * @property CarbonImmutable $calculated_as_of
 * @property Money $calculated_amount
 * @property Money|null $approved_amount
 */
class PenaltyDecision extends Model
{
    /** @use HasFactory<PenaltyDecisionFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'status',
        'calculated_as_of',
        'calculated_amount',
        'approved_amount',
        'comment',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => PenaltyDecisionStatus::class,
            'calculated_as_of' => 'immutable_date',
            'calculated_amount' => MoneyCast::class,
            'approved_amount' => MoneyCast::class,
        ];
    }

    public function isApproved(): bool
    {
        return $this->status === PenaltyDecisionStatus::Approved;
    }

    public function effectiveAmount(): Money
    {
        return $this->approved_amount ?? $this->calculated_amount;
    }

    /**
     * @return BelongsTo<Contract, $this>
     */
    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function decider(): BelongsTo
    {
        return $this->belongsTo(User::class, 'decided_by');
    }
}
