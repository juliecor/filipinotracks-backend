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
        Schema::create('property_boundaries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('property_map_id')->constrained()->cascadeOnDelete();
            $table->integer('sort_order')->default(0);
            $table->string('point_from')->nullable();
            $table->string('point_to')->nullable();
            $table->string('dir1')->nullable();      // N or S
            $table->decimal('degrees', 6, 4)->nullable();
            $table->decimal('minutes', 6, 4)->nullable();
            $table->string('dir2')->nullable();      // E or W
            $table->decimal('distance', 10, 4)->nullable(); // meters
            $table->decimal('gen_lat', 10, 7)->nullable();  // computed
            $table->decimal('gen_lng', 10, 7)->nullable();  // computed
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('property_boundaries');
    }
};
