<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('finance_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('operation', 16);
            $table->string('cost_category', 64)->nullable();
            $table->decimal('amount', 12, 2);
            $table->string('description', 255)->nullable();
            $table->date('occurred_at');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['project_id', 'occurred_at']);
            $table->index(['project_id', 'cost_category']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('finance_entries');
    }
};
