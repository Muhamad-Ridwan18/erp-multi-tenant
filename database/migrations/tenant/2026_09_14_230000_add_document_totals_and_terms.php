<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales_orders', function (Blueprint $table) {
            $table->unsignedBigInteger('discount_total')->default(0)->after('subtotal');
            $table->unsignedBigInteger('tax_total')->default(0)->after('discount_total');
            $table->unsignedBigInteger('grand_total')->default(0)->after('tax_total');
            $table->text('terms')->nullable()->after('notes');
        });

        Schema::table('sales_order_items', function (Blueprint $table) {
            $table->unsignedTinyInteger('discount_percent')->default(0)->after('unit_price');
            $table->unsignedTinyInteger('tax_percent')->default(0)->after('discount_percent');
        });

        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->unsignedBigInteger('discount_total')->default(0)->after('subtotal');
            $table->unsignedBigInteger('tax_total')->default(0)->after('discount_total');
            $table->unsignedBigInteger('grand_total')->default(0)->after('tax_total');
            $table->text('terms')->nullable()->after('notes');
        });

        Schema::table('purchase_order_items', function (Blueprint $table) {
            $table->unsignedTinyInteger('discount_percent')->default(0)->after('unit_price');
            $table->unsignedTinyInteger('tax_percent')->default(0)->after('discount_percent');
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->unsignedBigInteger('discount_total')->default(0)->after('subtotal');
            $table->unsignedBigInteger('tax_total')->default(0)->after('discount_total');
            $table->unsignedBigInteger('grand_total')->default(0)->after('tax_total');
            $table->text('terms')->nullable()->after('notes');
        });

        Schema::table('invoice_items', function (Blueprint $table) {
            $table->unsignedTinyInteger('discount_percent')->default(0)->after('unit_price');
            $table->unsignedTinyInteger('tax_percent')->default(0)->after('discount_percent');
        });

        Schema::table('bills', function (Blueprint $table) {
            $table->unsignedBigInteger('discount_total')->default(0)->after('subtotal');
            $table->unsignedBigInteger('tax_total')->default(0)->after('discount_total');
            $table->unsignedBigInteger('grand_total')->default(0)->after('tax_total');
            $table->text('terms')->nullable()->after('notes');
        });

        Schema::table('bill_items', function (Blueprint $table) {
            $table->unsignedTinyInteger('discount_percent')->default(0)->after('unit_price');
            $table->unsignedTinyInteger('tax_percent')->default(0)->after('discount_percent');
        });
    }

    public function down(): void
    {
        Schema::table('sales_orders', function (Blueprint $table) {
            $table->dropColumn(['discount_total', 'tax_total', 'grand_total', 'terms']);
        });
        Schema::table('sales_order_items', function (Blueprint $table) {
            $table->dropColumn(['discount_percent', 'tax_percent']);
        });
        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->dropColumn(['discount_total', 'tax_total', 'grand_total', 'terms']);
        });
        Schema::table('purchase_order_items', function (Blueprint $table) {
            $table->dropColumn(['discount_percent', 'tax_percent']);
        });
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn(['discount_total', 'tax_total', 'grand_total', 'terms']);
        });
        Schema::table('invoice_items', function (Blueprint $table) {
            $table->dropColumn(['discount_percent', 'tax_percent']);
        });
        Schema::table('bills', function (Blueprint $table) {
            $table->dropColumn(['discount_total', 'tax_total', 'grand_total', 'terms']);
        });
        Schema::table('bill_items', function (Blueprint $table) {
            $table->dropColumn(['discount_percent', 'tax_percent']);
        });
    }
};
