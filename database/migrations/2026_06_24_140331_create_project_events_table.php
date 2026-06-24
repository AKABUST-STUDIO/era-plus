<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->timestamp('starts_at');
            $table->timestamp('ends_at')->nullable();
            $table->boolean('all_day')->default(false);
            $table->string('location')->nullable();
            $table->timestamps();

            $table->index(['project_id', 'starts_at']);
        });

        Schema::create('project_event_user', function (Blueprint $table) {
            $table->foreignId('project_event_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('attendee_type', 16)->default('participant');

            $table->primary(['project_event_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_event_user');
        Schema::dropIfExists('project_events');
    }
};
