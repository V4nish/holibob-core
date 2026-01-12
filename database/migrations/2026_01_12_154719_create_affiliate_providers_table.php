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
        Schema::create('affiliate_providers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('adapter_class'); // PHP class handling this provider

            // Configuration (JSON)
            $table->json('config')->nullable(); // API keys, FTP credentials, file paths, etc.

            // Sync settings
            $table->string('sync_frequency', 50)->default('daily'); // daily, hourly, manual
            $table->timestamp('last_sync_at')->nullable();
            $table->timestamp('next_sync_at')->nullable();

            // Status
            $table->boolean('is_active')->default(true);

            $table->timestamps();

            // Indexes
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('affiliate_providers');
    }
};
