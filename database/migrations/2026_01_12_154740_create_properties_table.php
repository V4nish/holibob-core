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
        Schema::create('properties', function (Blueprint $table) {
            $table->id();
            $table->foreignId('affiliate_provider_id')->constrained()->onDelete('cascade');
            $table->string('external_id');
            $table->string('name', 500);
            $table->string('slug', 500)->unique();
            $table->text('description')->nullable();
            $table->string('short_description', 1000)->nullable();
            $table->string('property_type', 100)->nullable(); // cottage, apartment, villa, etc.

            // Location
            $table->foreignId('location_id')->constrained()->onDelete('restrict');
            $table->string('address_line_1')->nullable();
            $table->string('address_line_2')->nullable();
            $table->string('postcode', 20)->nullable();
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();

            // Capacity
            $table->smallInteger('sleeps');
            $table->smallInteger('bedrooms');
            $table->smallInteger('bathrooms');

            // Pricing (indicative, real-time from affiliate)
            $table->decimal('price_from', 10, 2)->nullable();
            $table->char('price_currency', 3)->default('GBP');

            // Affiliate tracking
            $table->text('affiliate_url');
            $table->decimal('commission_rate', 5, 2)->nullable();

            // Status
            $table->boolean('is_active')->default(true);
            $table->boolean('featured')->default(false);

            // Metadata
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();

            // Indexes
            $table->index('slug');
            $table->index('location_id');
            $table->index(['affiliate_provider_id', 'external_id']);
            $table->index('is_active');
            $table->index(['latitude', 'longitude']);
            $table->index('sleeps');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('properties');
    }
};
