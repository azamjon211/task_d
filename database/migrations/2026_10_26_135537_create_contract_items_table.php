<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contract_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contract_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->unsignedInteger('paid_quantity');
            $table->unsignedInteger('bonus_quantity');
            $table->decimal('unit_price', 15, 2);
            $table->timestamps();
        });

        DB::statement('ALTER TABLE contract_items ADD CONSTRAINT contract_items_quantities_valid CHECK (paid_quantity >= 0 AND bonus_quantity >= 0 AND paid_quantity + bonus_quantity > 0)');
        DB::statement('ALTER TABLE contract_items ADD CONSTRAINT contract_items_unit_price_non_negative CHECK (unit_price >= 0)');
    }

    public function down(): void
    {
        Schema::dropIfExists('contract_items');
    }
};
