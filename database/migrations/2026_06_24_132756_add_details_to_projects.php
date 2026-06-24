<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->date('beginning_date')->nullable();
            $table->date('end_date')->nullable();
            $table->string('project_type', 64)->nullable();
            $table->text('description')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn(['beginning_date', 'end_date', 'project_type', 'description']);
        });
    }
};
