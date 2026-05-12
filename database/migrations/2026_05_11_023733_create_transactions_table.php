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
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->string('transaction_code')->unique();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('assigned_staff_id')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('service_type', [
                'title-verification', 'title-transfer', 'tax-declaration',
                'mortgage-annotation', 'title-cancellation', 'land-registration',
                'property-consultation', 'document-processing',
            ]);
            $table->enum('status', [
                'submitted', 'under review', 'verification ongoing',
                'processing', 'waiting for requirements', 'approved', 'released', 'rejected',
            ])->default('submitted');
            // Property details
            $table->string('property_title_number')->nullable();
            $table->string('lot_number')->nullable();
            $table->string('block_number')->nullable();
            $table->string('tax_declaration_number')->nullable();
            $table->text('property_address')->nullable();
            $table->string('property_type')->nullable();
            $table->decimal('lot_area', 10, 2)->nullable();
            $table->string('registered_owner')->nullable();
            $table->string('transfer_type')->nullable();
            // Financials
            $table->decimal('service_fee', 12, 2)->nullable();
            $table->enum('payment_status', ['unpaid', 'partial', 'paid'])->default('unpaid');
            $table->text('remarks')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
