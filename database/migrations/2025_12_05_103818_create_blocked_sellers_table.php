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
        Schema::create('blocked_sellers', function (Blueprint $table) {
            $table->uuid('tenant_id')->nullable(false);
            $table->uuid('seller_id')->nullable(false);
            $table->uuid('blocked_by')->nullable(false);
            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->foreign('seller_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('blocked_by')->references('id')->on('users')->onDelete('cascade');
            $table->timestamp('blocked_at')->nullable(false);
            $table->primary(['tenant_id', 'seller_id']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('blocked_sellers');
    }
};
