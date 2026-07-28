<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_participant', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('country_id')->constrained('countries')->restrictOnDelete();
            $table->morphs('participable');
            $table->morphs('sending_organization', 'project_participant_sending_org_index');
            $table->timestamps();

            $table->unique(['project_id', 'participable_type', 'participable_id'], 'project_participable_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_participant');
    }
};
