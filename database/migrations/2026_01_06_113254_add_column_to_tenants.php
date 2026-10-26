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
        Schema::table('tenants', function (Blueprint $table) {
            $table->text('private_key_path')->nullable()->after('network');
            $table->text('public_key_path')->nullable()->nullable()->after('network');
        });
        Schema::table('users', function (Blueprint $table) {
            $table->text('private_key_path')->nullable()->after('network');
            $table->text('public_key_path')->nullable()->after('network');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            //
        });
        Schema::table('users', function (Blueprint $table) {
            //
        });
    }
};
