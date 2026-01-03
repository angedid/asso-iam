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
        Schema::create('super_admin_first_time_connections', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreign('id')->references('id')->on('super_admins')->onDelete('cascade');
            $table->boolean('has_been_connected')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('super_admin_first_time_connections');
    }
};
