<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('travel_expense_ai_extractions', function (Blueprint $table) {
            $table->id();
            $table->uuid('session_id');
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
            $table->string('target', 20);
            $table->string('fingerprint', 40)->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->json('file_paths');
            $table->json('extracted_data')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();

            $table->unique(['session_id', 'target']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('travel_expense_ai_extractions');
    }
};
