<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->string('project_reference', 64)->nullable();
            $table->string('erasmus_field', 8)->nullable();
            $table->string('erasmus_key_action', 8)->nullable();
            $table->string('erasmus_action', 16)->nullable();
            $table->string('erasmus_managing_body', 8)->nullable();
            $table->string('status', 16)->default('draft');
            $table->unsignedSmallInteger('call_year')->nullable();
            $table->date('beginning_date')->nullable();
            $table->date('end_date')->nullable();
            $table->unsignedSmallInteger('duration_months')->nullable();
            $table->decimal('requested_grant', 12, 2)->nullable();
            $table->decimal('awarded_grant', 12, 2)->nullable();
            $table->string('google_calendar_id')->nullable()->index();
            $table->string('google_calendar_channel_id')->nullable()->unique();
            $table->string('google_calendar_channel_resource_id')->nullable();
            $table->dateTime('google_calendar_channel_expires_at')->nullable();
            $table->string('location')->nullable();
            $table->string('timezone', 64)->default('UTC');
            $table->timestamps();

            $table->unique(['organization_id', 'slug']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('projects');
    }
};
