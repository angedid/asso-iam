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
        Schema::create('tenants', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('city')->nullable();
            $table->string('location')->nullable();
            $table->string('region')->nullable();
            $table->string('country')->nullable();
            $table->string('logo')->nullable();
            $table->string('phone_number')->nullable();
            $table->string('website')->nullable();
            $table->text('address')->nullable();
            $table->boolean('active')->default(true);
            $table->string('taxpayer_number')->nullable();
            $table->string('number_trade_register')->nullable();
            $table->bigInteger('license_id')->nullable();
            $table->uuid('owner')->nullable();
            $table->json('network')->nullable(false);
            $table->timestamps();
            $table->softDeletes();
        });


        Schema::create('users', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id')->nullable();
            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->uuid('parent_id')->nullable();
            $table->enum('type',['MERCHANT_ADMINISTRATOR', 'MERCHANT_USER', 'SELLER_USER'])->nullable(false)->default('MERCHANT_USER');
            $table->string('full_name')->nullable(false);
            $table->string('username')->nullable(false)->unique();
            $table->string('local_username')->nullable(false);
            $table->string('email')->nullable();
            $table->string('phone_number')->nullable(false);
            $table->text('photo')->nullable();
            $table->string('location')->nullable();
            $table->string('region')->nullable();
            $table->string('country')->nullable();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->boolean('active')->default(true);
            $table->json('network')->nullable();
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();
        });
        /*
         The username is unique in the global application.
         The local_username is not unique in the global application.
         However, the local_username is unique in a specific tenant.
         The user know only the local username. It the one he chooses went he creates his account.
         The username is constructed from the local_username and the tenant id following this rule:
         username = local_username.'@'.tenant_id (where the . is the concatenation operator)
        For every request, that need username, we joint to the request the tenant id, and the local username.
        A middleware catches the request, extracts the local_username and the tenant_id and the constructs
        the username and push it in the request and forward it.
         */
        Schema::table('users', function (Blueprint $table) {
            $table->unique(['tenant_id', 'local_username']);
        });


        /*Schema::create('sellers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('full_name')->nullable(false);
            $table->string('username')->unique()->nullable(false);
            $table->string('email')->nullable();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->string('phone_number')->nullable(false);
            $table->text('photo')->nullable();
            $table->string('location')->nullable();
            $table->string('region')->nullable();
            $table->string('country')->nullable();
            $table->json('network')->nullable(false);
            $table->boolean('active')->default(true);
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();
        });*/

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        /*Schema::create('seller_password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });*/

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->uuid('user_id')->nullable()->index();
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });


        Schema::create('roles', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id')->nullable(false);
            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->string('name')->nullable(false);
            $table->text('description')->nullable();
            $table->json('groups_playing_role')->nullable(false);
            $table->json('users_playing_role')->nullable(false);
            $table->json('scopes')->nullable(false);
            $table->boolean('active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::table('roles', function (Blueprint $table) {
            $table->unique(['tenant_id', 'name']);
        });

        Schema::create('scopes', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->string('description')->nullable(false);
            $table->boolean('active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('groups', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id')->nullable(false);
            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->string('name')->nullable(false);
            $table->text('description')->nullable();
            $table->json('members')->nullable(false);
            $table->boolean('active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::table('groups', function (Blueprint $table) {
            $table->unique(['tenant_id', 'name']);
        });

        Schema::create('password_reset_invitations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id')->nullable(false);
            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->string('username')->nullable(false);
            $table->string('email')->nullable(false);
            $table->timestamp('expires_at')->nullable(false);
            $table->boolean('active')->default(true);
            $table->enum('state',['PENDING', 'ACCEPTED', 'REFUSED', 'CONFIRM'])->nullable(false)->default('PENDING');
            $table->boolean('used')->default(false);
            $table->timestamp('used_at')->nullable();
            $table->text('url')->nullable();
            $table->timestamps();
        });

        Schema::create('users_registration_invitations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id')->nullable(false);
            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->string('full_name')->nullable(false);
            $table->string('email')->nullable(false);
            $table->string('phone_number')->nullable();
            $table->uuid('invited_by')->nullable(false);
            $table->foreign('invited_by')->references('id')->on('users')->onDelete('cascade');
            $table->timestamp('invited_at')->nullable(false);
            $table->timestamp('expires_at')->nullable(false);
            $table->boolean('active')->default(true);
            $table->enum('state',['PENDING', 'ACCEPTED', 'REFUSED', 'CONFIRM'])->nullable(false)->default('PENDING');
            $table->boolean('used')->default(false);
            $table->timestamp('used_at')->nullable();
            $table->text('url')->nullable(false);
            $table->timestamps();
        });

        Schema::create('sellers_invitations_to_join_networks', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id')->nullable(false);
            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->uuid('inviter_id')->nullable(false);
            $table->foreign('inviter_id')->references('id')->on('users')->onDelete('cascade');
            $table->uuid('invitee_id')->nullable(false);
            $table->foreign('invitee_id')->references('id')->on('users')->onDelete('cascade');
            $table->timestamp('invited_at')->nullable(false);
            $table->timestamp('expires_at')->nullable(false);
            $table->boolean('active')->default(true);
            $table->enum('state',['PENDING', 'ACCEPTED', 'REFUSED', 'CONFIRM'])->nullable(false)->default('PENDING');
            $table->boolean('used')->default(false);
            $table->timestamp('used_at')->nullable();
            $table->text('url')->nullable(false);
            $table->timestamps();
        });

        Schema::create('sellers_requests_to_join_networks', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id')->nullable(false);
            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->uuid('network_admin')->nullable(false);
            $table->foreign('network_admin')->references('id')->on('users')->onDelete('cascade');
            $table->uuid('requester_id')->nullable(false);
            $table->foreign('requester_id')->references('id')->on('users')->onDelete('cascade');
            $table->timestamp('invited_at')->nullable(false);
            $table->timestamp('expires_at')->nullable(false);
            $table->boolean('active')->default(true);
            $table->enum('state',['PENDING', 'ACCEPTED', 'REFUSED', 'CONFIRM'])->nullable(false)->default('PENDING');
            $table->boolean('used')->default(false);
            $table->timestamp('used_at')->nullable();
            $table->text('url')->nullable(false);
            $table->timestamps();
        });

        Schema::table('tenants', function (Blueprint $table) {
            $table->foreign('license_id')->references('id')->on('licenses')->onDelete('cascade');
            $table->foreign('owner')->references('id')->on('users')->onDelete('cascade');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->foreign('parent_id')->references('id')->on('users')->onDelete('cascade');
        });


        Schema::table('licenses', function (Blueprint $table) {
            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
        });

        /*Schema::table('licenses', function (Blueprint $table) {
            $table->foreign('assigned_to')->references('id')->on('tenants')->onDelete('cascade');
        });*/


    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tenants');
        Schema::dropIfExists('users');
        //Schema::dropIfExists('sellers');
        Schema::dropIfExists('password_reset_tokens');
        //Schema::dropIfExists('seller_password_reset_tokens');
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('roles');
        Schema::dropIfExists('scopes');
        Schema::dropIfExists('groups');
        Schema::dropIfExists('password_reset_invitations');
        Schema::dropIfExists('users_registration_invitations');
        Schema::dropIfExists('sellers_invitations_to_join_network');
        Schema::dropIfExists('sellers_requests_to_join_networks');
    }
};
