<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * MySQL enum columns must be altered with raw SQL (Laravel's schema
     * builder doesn't support modifying enum values directly).
     *
     * Adds 'pending approval' to the transactions.status enum so the
     * two-tier approval workflow can write that value without truncating.
     */
    public function up(): void
    {
        DB::statement("
            ALTER TABLE `transactions`
            MODIFY COLUMN `status` ENUM(
                'submitted',
                'under review',
                'verification ongoing',
                'processing',
                'waiting for requirements',
                'pending approval',
                'approved',
                'released',
                'rejected'
            ) NOT NULL DEFAULT 'submitted'
        ");
    }

    public function down(): void
    {
        // Roll back to the original 8 statuses. Any rows currently sitting
        // on 'pending approval' will be cast to 'submitted' as a safe fallback.
        DB::statement("UPDATE `transactions` SET `status` = 'submitted' WHERE `status` = 'pending approval'");
        DB::statement("
            ALTER TABLE `transactions`
            MODIFY COLUMN `status` ENUM(
                'submitted',
                'under review',
                'verification ongoing',
                'processing',
                'waiting for requirements',
                'approved',
                'released',
                'rejected'
            ) NOT NULL DEFAULT 'submitted'
        ");
    }
};
