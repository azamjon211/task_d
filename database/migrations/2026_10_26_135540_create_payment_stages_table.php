<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_stages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contract_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('sequence');
            $table->string('name');
            $table->decimal('percent', 5, 2);
            $table->decimal('amount', 15, 2)->nullable();
            $table->string('condition', 30);
            $table->date('due_date')->nullable();
            $table->timestamps();

            $table->unique(['contract_id', 'sequence']);
        });

        DB::statement('ALTER TABLE payment_stages ADD CONSTRAINT payment_stages_percent_range CHECK (percent > 0 AND percent <= 100)');
        DB::statement('ALTER TABLE payment_stages ADD CONSTRAINT payment_stages_amount_non_negative CHECK (amount >= 0)');
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_stages');
    }
};
