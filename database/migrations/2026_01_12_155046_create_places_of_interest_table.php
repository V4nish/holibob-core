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
        Schema::create('places_of_interest', function (Blueprint $table) {
            $table->id();

            $table->string('name', 500);
            $table->string('slug', 500)->unique();
            $table->foreignId('place_category_id')->constrained()->onDelete('restrict');

            $table->text('description')->nullable();

            // Location
            $table->string('address', 500)->nullable();
            $table->string('postcode', 20)->nullable();
            $table->decimal('latitude', 10, 8);
            $table->decimal('longitude', 11, 8);

            // External data
            $table->string('google_place_id')->nullable();
            $table->string('phone', 50)->nullable();
            $table->string('website', 500)->nullable();

            // Metadata
            $table->decimal('rating', 3, 2)->nullable(); // e.g., 4.5 stars
            $table->smallInteger('price_level')->nullable(); // 1-4 (£ to ££££)

            $table->boolean('is_active')->default(true);

            $table->timestamps();

            // Indexes
            $table->index(['latitude', 'longitude']);
            $table->index('place_category_id');
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('places_of_interest');
    }
};
