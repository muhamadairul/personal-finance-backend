<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subscription_logs', function (Blueprint $table) {
            $table->string('xendit_invoice_id')->nullable()->after('type');
            $table->string('xendit_invoice_url', 500)->nullable()->after('xendit_invoice_id');
            $table->string('payment_method')->nullable()->after('xendit_invoice_url');
            $table->string('payment_channel')->nullable()->after('payment_method');
            $table->string('status')->default('pending')->after('payment_channel');
            $table->string('plan_id')->nullable()->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('subscription_logs', function (Blueprint $table) {
            $table->dropColumn([
                'xendit_invoice_id',
                'xendit_invoice_url',
                'payment_method',
                'payment_channel',
                'status',
                'plan_id',
            ]);
        });
    }
};
