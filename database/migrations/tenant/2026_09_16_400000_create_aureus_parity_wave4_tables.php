<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->decimal('currency_rate', 16, 6)->default(1)->after('currency_id');
            $table->unsignedBigInteger('amount_company')->default(0)->after('grand_total');
        });

        Schema::table('bills', function (Blueprint $table) {
            $table->decimal('currency_rate', 16, 6)->default(1)->after('currency_id');
            $table->unsignedBigInteger('amount_company')->default(0)->after('grand_total');
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->decimal('currency_rate', 16, 6)->default(1)->after('currency_id');
            $table->unsignedBigInteger('amount_company')->default(0)->after('amount');
            $table->boolean('is_reconciled')->default(false)->after('notes');
        });

        Schema::table('sales_orders', function (Blueprint $table) {
            $table->decimal('currency_rate', 16, 6)->default(1)->after('currency_id');
        });

        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->decimal('currency_rate', 16, 6)->default(1)->after('currency_id');
        });

        Schema::create('bank_statements', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('reference')->nullable();
            $table->foreignId('journal_id')->nullable()->constrained()->nullOnDelete();
            $table->date('date')->nullable();
            $table->bigInteger('balance_start')->default(0);
            $table->bigInteger('balance_end')->default(0);
            $table->string('status')->default('open'); // open, done
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('bank_statement_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bank_statement_id')->constrained()->cascadeOnDelete();
            $table->date('date')->nullable();
            $table->string('payment_reference')->nullable();
            $table->string('partner_name')->nullable();
            $table->string('label')->nullable();
            $table->bigInteger('amount')->default(0); // signed: +in / -out, company currency minor units
            $table->boolean('is_reconciled')->default(false);
            $table->foreignId('payment_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedInteger('sort')->default(0);
            $table->timestamps();
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->foreignId('bank_statement_line_id')->nullable()->after('is_reconciled')->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('bank_statement_line_id');
            $table->dropColumn(['currency_rate', 'amount_company', 'is_reconciled']);
        });

        Schema::dropIfExists('bank_statement_lines');
        Schema::dropIfExists('bank_statements');

        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->dropColumn('currency_rate');
        });

        Schema::table('sales_orders', function (Blueprint $table) {
            $table->dropColumn('currency_rate');
        });

        Schema::table('bills', function (Blueprint $table) {
            $table->dropColumn(['currency_rate', 'amount_company']);
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn(['currency_rate', 'amount_company']);
        });
    }
};
