<?php

namespace App\Models;

use App\Casts\MoneyCast;
use App\Enums\StageCondition;
use App\Support\Money;
use Carbon\CarbonImmutable;
use Database\Factories\PaymentStageFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $sequence
 * @property string $percent
 * @property Money|null $amount
 * @property StageCondition $condition
 * @property CarbonImmutable|null $due_date
 */
class PaymentStage extends Model
{
    /** @use HasFactory<PaymentStageFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'sequence',
        'name',
        'percent',
        'condition',
        'due_date',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'sequence' => 'integer',
            'percent' => 'decimal:2',
            'amount' => MoneyCast::class,
            'condition' => StageCondition::class,
            'due_date' => 'immutable_date',
        ];
    }

    /**
     * @return BelongsTo<Contract, $this>
     */
    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class);
    }
}
