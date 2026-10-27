<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contract_id')->constrained();
            $table->foreignId('payment_stage_id')->constrained();
            $table->foreignId('budget_line_id')->constrained();
            $table->decimal('amount', 15, 2);
            $table->date('paid_on');
            $table->string('reference');
            $table->foreignId('created_by')->constrained('users');
            $table->timestamp('voided_at')->nullable();
            $table->foreignId('voided_by')->nullable()->constrained('users');
            $table->text('void_reason')->nullable();
            $table->timestamps();

            $table->index(['payment_stage_id', 'voided_at']);
            $table->index(['budget_line_id', 'voided_at']);
            $table->index(['contract_id', 'voided_at']);
        });

        DB::statement('ALTER TABLE payments ADD CONSTRAINT payments_amount_positive CHECK (amount > 0)');
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
