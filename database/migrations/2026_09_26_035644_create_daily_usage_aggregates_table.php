<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('daily_usage_aggregates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->unsignedBigInteger('total_units');
            $table->timestamps();

            $table->unique(['customer_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('daily_usage_aggregates');
    }
};
