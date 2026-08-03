<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('travel_expenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_participant_id')->constrained('project_participant')->cascadeOnDelete();
            $table->string('travel_type', 20);
            $table->string('transportation_type', 30);
            $table->string('from');
            $table->string('to');
            $table->date('date');
            $table->decimal('cost', 10, 2);
            $table->string('currency', 3)->default('EUR');
            $table->decimal('cost_eur', 10, 2);
            $table->timestamps();

            $table->index('date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('travel_expenses');
    }
};
