<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('country_limits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('country_id')->constrained('countries')->cascadeOnDelete();
            $table->decimal('amount_eur', 10, 2);
            $table->timestamps();

            $table->unique(['project_id', 'country_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('country_limits');
    }
};
