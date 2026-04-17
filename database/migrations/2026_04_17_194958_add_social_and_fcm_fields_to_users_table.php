<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Social login fields
            $table->string('provider', 20)->nullable()->after('email');
            $table->string('provider_id')->nullable()->after('provider');

            // Make password nullable (social login users don't have password)
            $table->string('password')->nullable()->change();

            // FCM push notification token
            $table->string('fcm_token')->nullable()->after('subscription_until');

            // Admin flag for filtered notifications
            $table->boolean('is_admin')->default(false)->after('fcm_token');

            // Index for social login lookup
            $table->index(['provider', 'provider_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['provider', 'provider_id']);
            $table->dropColumn(['provider', 'provider_id', 'fcm_token', 'is_admin']);
            $table->string('password')->nullable(false)->change();
        });
    }
};
