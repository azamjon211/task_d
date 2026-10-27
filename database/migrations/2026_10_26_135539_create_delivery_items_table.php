<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('delivery_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('delivery_id')->constrained()->cascadeOnDelete();
            $table->foreignId('contract_item_id')->constrained();
            $table->unsignedInteger('paid_quantity');
            $table->unsignedInteger('bonus_quantity');
            $table->timestamps();
        });

        DB::statement('ALTER TABLE delivery_items ADD CONSTRAINT delivery_items_quantities_valid CHECK (paid_quantity >= 0 AND bonus_quantity >= 0 AND paid_quantity + bonus_quantity > 0)');
    }

    public function down(): void
    {
        Schema::dropIfExists('delivery_items');
    }
};
