<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('api_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            $table->string('method');
            $table->string('url');
            $table->json('payload')->nullable();
            $table->json('response')->nullable();
            $table->integer('status_code');
            $table->string('ip_address')->nullable();
            $table->string('user_agent')->nullable();
            $table->decimal('duration_ms', 10, 2)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('api_logs');
    }
};
