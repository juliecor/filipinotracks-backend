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
        Schema::create('property_maps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transaction_id')->constrained()->cascadeOnDelete();
            // Title details
            $table->string('title_number')->nullable();
            $table->string('lot_number')->nullable();
            $table->string('block_number')->nullable();
            $table->string('survey_plan_number')->nullable();
            $table->string('tax_declaration_number')->nullable();
            $table->string('property_type')->nullable(); // residential/commercial/agricultural/condominium
            $table->string('registered_owner')->nullable();
            $table->decimal('land_area', 12, 4)->nullable();
            // Location
            $table->string('province')->nullable();
            $table->string('city_municipality')->nullable();
            $table->string('barangay')->nullable();
            $table->string('full_address')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            // Polygon (GeoJSON stored as JSON)
            $table->json('geojson_polygon')->nullable();
            // Staff fields
            $table->text('staff_notes')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('property_maps');
    }
};
