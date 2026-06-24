<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            $table->json('billing_address')->nullable();
            $table->string('invoice_language', 8)->default('en');
            $table->string('tax_id', 64)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            $table->dropColumn([
                'billing_address',
                'invoice_language',
                'tax_id',
            ]);
        });
    }
};
