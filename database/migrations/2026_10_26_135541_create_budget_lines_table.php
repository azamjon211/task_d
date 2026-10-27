<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('budget_lines', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('fiscal_year');
            $table->string('expense_item');
            $table->unsignedTinyInteger('month');
            $table->decimal('allocated_amount', 15, 2);
            $table->timestamps();

            $table->unique(['fiscal_year', 'expense_item', 'month']);
        });

        DB::statement('ALTER TABLE budget_lines ADD CONSTRAINT budget_lines_month_range CHECK (month BETWEEN 1 AND 12)');
        DB::statement('ALTER TABLE budget_lines ADD CONSTRAINT budget_lines_allocated_non_negative CHECK (allocated_amount >= 0)');
    }

    public function down(): void
    {
        Schema::dropIfExists('budget_lines');
    }
};
