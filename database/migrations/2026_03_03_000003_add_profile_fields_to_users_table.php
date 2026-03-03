<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('phone', 20)->nullable()->after('photo_url');
            $table->text('address')->nullable()->after('phone');
            $table->date('date_of_birth')->nullable()->after('address');
            $table->enum('gender', ['L', 'P'])->nullable()->after('date_of_birth');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['phone', 'address', 'date_of_birth', 'gender']);
        });
    }
};
