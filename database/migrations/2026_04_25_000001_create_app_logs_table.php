<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('app_logs', function (Blueprint $table) {
            $table->id();
            $table->string('level', 20);       // error, warning, info, debug
            $table->string('channel', 50)->default('stack');
            $table->text('message');
            $table->json('context')->nullable(); // extra context data
            $table->timestamps();

            $table->index('level');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('app_logs');
    }
};
