<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_countries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('country_id')->constrained()->cascadeOnDelete();
            $table->decimal('default_travel_expense_limit', 12, 2)->nullable();
            $table->timestamps();

            $table->unique(['project_id', 'country_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_countries');
    }
};
