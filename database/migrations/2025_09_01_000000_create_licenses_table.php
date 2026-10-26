<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('licenses', function (Blueprint $table) {
            $table->id();
            $table->string('license_key')->unique();
            $table->uuid('assigned_to')->nullable(false);
            $table->uuid('tenant_id')->nullable();
            $table->boolean('is_trial')->default(false);
            $table->json('users')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamp('expires_at')->nullable();
            $table->json('metadata')->nullable();
            $table->bigInteger('duration')->nullable(false); //number of days
            $table->double('amount')->nullable(false);
            $table->uuid('order_id')->nullable(false);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('licenses');
    }
};
