<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_event_attendees', function (Blueprint $table): void {
            $table->id();
            $table->foreignUuid('project_event_id')->constrained('project_events')->cascadeOnDelete();
            $table->foreignId('project_participant_id')->constrained('project_participant')->cascadeOnDelete();
            $table->string('response_status', 16)->default('needsAction');
            $table->timestamps();

            $table->unique(['project_event_id', 'project_participant_id'], 'activity_participant_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_event_attendees');
    }
};
