<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('property_maps', function (Blueprint $table) {
            $table->decimal('price', 14, 2)->nullable()->after('land_area');
            $table->boolean('is_featured')->default(false)->after('price');
            $table->string('listing_blurb', 280)->nullable()->after('is_featured');
        });
    }

    public function down(): void
    {
        Schema::table('property_maps', function (Blueprint $table) {
            $table->dropColumn(['price', 'is_featured', 'listing_blurb']);
        });
    }
};
