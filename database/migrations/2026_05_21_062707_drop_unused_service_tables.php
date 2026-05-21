<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Drops legacy per-service tables that are no longer used.
     * All transactions now live in the unified `transactions` table with a
     * `service_type` field. The standalone tables for title-transfer,
     * tax-declaration and mortgage-annotation have been retired by request.
     */
    public function up(): void
    {
        Schema::dropIfExists('title_transfers');
        Schema::dropIfExists('tax_declarations');
        Schema::dropIfExists('mortgages');
    }

    /**
     * Down — intentionally a no-op. These tables had no production data
     * and recreating empty stubs would only confuse future maintainers.
     */
    public function down(): void
    {
        // no-op
    }
};
