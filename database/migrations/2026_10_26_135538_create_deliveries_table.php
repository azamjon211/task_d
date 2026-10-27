<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('deliveries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contract_id')->constrained();
            $table->date('delivered_on');
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();

            $table->index(['contract_id', 'delivered_on']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('deliveries');
    }
};
