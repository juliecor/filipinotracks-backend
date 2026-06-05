<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Adds an internal-only `staff_notes` field on transactions.
     * Visible to admin + staff in the UI, stripped from client responses.
     * Used for investigative findings, follow-up reminders, internal flags etc.
     */
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->text('staff_notes')->nullable()->after('remarks');
            $table->unsignedBigInteger('staff_notes_updated_by')->nullable()->after('staff_notes');
            $table->timestamp('staff_notes_updated_at')->nullable()->after('staff_notes_updated_by');

            $table->foreign('staff_notes_updated_by')
                ->references('id')->on('users')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropForeign(['staff_notes_updated_by']);
            $table->dropColumn(['staff_notes', 'staff_notes_updated_by', 'staff_notes_updated_at']);
        });
    }
};
