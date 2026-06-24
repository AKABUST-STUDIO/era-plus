<?php

use App\Enums\SubscriptionTier;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('organizations', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();

            // Cashier customer columns
            $table->string('stripe_id')->nullable()->index();
            $table->string('pm_type')->nullable();
            $table->string('pm_last_four', 4)->nullable();
            $table->timestamp('trial_ends_at')->nullable();

            // Pricing
            $table->string('subscription_tier', 16)->default(SubscriptionTier::Free->value);
            $table->unsignedInteger('extra_project_seats')->default(0);

            // Billing
            $table->json('billing_address')->nullable();
            $table->string('invoice_language', 8)->default('en');
            $table->string('tax_id', 64)->nullable();

            // Security
            $table->boolean('enforce_two_factor')->default(false);
            $table->boolean('enforce_email_verification')->default(false);

            $table->timestamps();
            $table->softDeletes();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->foreign('default_organization_id')->references('id')->on('organizations')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['default_organization_id']);
        });
        Schema::dropIfExists('organizations');
    }
};
