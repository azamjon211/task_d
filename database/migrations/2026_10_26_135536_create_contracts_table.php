<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contracts', function (Blueprint $table) {
            $table->id();
            $table->string('number', 50)->unique();
            $table->string('supplier');
            $table->date('start_date');
            $table->unsignedSmallInteger('duration_days');
            $table->string('status', 20)->index();
            $table->foreignId('created_by')->constrained('users');
            $table->timestamp('activated_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });

        DB::statement('ALTER TABLE contracts ADD CONSTRAINT contracts_duration_positive CHECK (duration_days > 0)');
    }

    public function down(): void
    {
        Schema::dropIfExists('contracts');
    }
};
