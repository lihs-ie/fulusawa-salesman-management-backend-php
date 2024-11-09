<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('authentications', function (Blueprint $table) {
            $table->uuid('identifier')->primary();
            $table->string('tokenable_id');
            $table->string('tokenable_type');
            $table->string('name');
            $table->string('token', 64)->unique()->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->string('refresh_token', 64)->unique()->nullable();
            $table->timestamp('refresh_token_expires_at')->nullable();
            $table->text('abilities')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('authentications');
    }
};
