<?php

use App\Enums\OrganizationRole;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('organization_user', function (Blueprint $table) {
            $table->string('role', 32)->default(OrganizationRole::Member->value)->after('user_id');
            $table->boolean('is_admin')->default(false)->after('role');
        });
    }

    public function down(): void
    {
        Schema::table('organization_user', function (Blueprint $table) {
            $table->dropColumn(['role', 'is_admin']);
        });
    }
};
