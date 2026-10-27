<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('penalty_decisions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contract_id')->constrained();
            $table->string('status', 20);
            $table->date('calculated_as_of');
            $table->decimal('calculated_amount', 15, 2);
            $table->decimal('approved_amount', 15, 2)->nullable();
            $table->text('comment')->nullable();
            $table->foreignId('decided_by')->constrained('users');
            $table->timestamps();

            $table->index(['contract_id', 'id']);
        });

        DB::statement('ALTER TABLE penalty_decisions ADD CONSTRAINT penalty_decisions_calculated_non_negative CHECK (calculated_amount >= 0)');
        DB::statement('ALTER TABLE penalty_decisions ADD CONSTRAINT penalty_decisions_approved_positive CHECK (approved_amount > 0)');
    }

    public function down(): void
    {
        Schema::dropIfExists('penalty_decisions');
    }
};
