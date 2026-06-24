<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('finance_entries', function (Blueprint $table) {
            $table->string('cost_category', 64)->nullable()->after('operation');
            $table->index(['project_id', 'cost_category']);
        });
    }

    public function down(): void
    {
        Schema::table('finance_entries', function (Blueprint $table) {
            $table->dropIndex(['project_id', 'cost_category']);
            $table->dropColumn('cost_category');
        });
    }
};
