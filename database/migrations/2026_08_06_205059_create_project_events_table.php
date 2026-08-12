<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_events', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('google_event_id')->nullable()->index();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('location')->nullable();
            $table->dateTime('starts_at');
            $table->dateTime('ends_at');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('google_updated_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_events');
    }
};
