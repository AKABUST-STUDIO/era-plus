<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_fields', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('erasmus_field', 8);
            $table->boolean('is_primary')->default(false);
            $table->timestamps();

            $table->unique(['project_id', 'erasmus_field']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_fields');
    }
};
