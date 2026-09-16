<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('currencies', function (Blueprint $table) {
            $table->id();
            $table->string('code', 3)->unique();
            $table->string('name');
            $table->string('symbol', 8)->default('Rp');
            $table->decimal('rate', 16, 6)->default(1);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('uom_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->timestamps();
        });

        Schema::create('uoms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('uom_category_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('code', 20);
            $table->decimal('ratio', 16, 6)->default(1);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['uom_category_id', 'code']);
        });

        Schema::create('product_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->foreignId('parent_id')->nullable()->constrained('product_categories')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('tax_groups', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->timestamps();
        });

        Schema::create('taxes', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code', 30)->unique();
            $table->decimal('amount', 8, 2)->default(0); // percent
            $table->string('type')->default('sale'); // sale, purchase, both
            $table->string('price_include')->default('exclusive'); // exclusive, inclusive
            $table->foreignId('tax_group_id')->nullable()->constrained()->nullOnDelete();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('payment_terms', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->unsignedInteger('days')->default(0);
            $table->text('note')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('accounts', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();
            $table->string('name');
            $table->string('type'); // asset, liability, equity, income, expense
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('journals', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();
            $table->string('name');
            $table->string('type'); // sale, purchase, cash, bank, general
            $table->foreignId('default_account_id')->nullable()->constrained('accounts')->nullOnDelete();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('warehouses', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();
            $table->string('name');
            $table->string('address')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('locations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('warehouse_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('code', 40);
            $table->string('type')->default('internal'); // internal, customer, vendor, inventory
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['warehouse_id', 'code']);
        });

        Schema::create('stock_quants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('location_id')->constrained()->cascadeOnDelete();
            $table->integer('quantity')->default(0);
            $table->timestamps();
            $table->unique(['product_id', 'location_id']);
        });

        Schema::create('stock_operations', function (Blueprint $table) {
            $table->id();
            $table->string('number')->unique();
            $table->string('type'); // receipt, delivery, internal
            $table->string('status')->default('draft'); // draft, done, canceled
            $table->foreignId('source_location_id')->nullable()->constrained('locations')->nullOnDelete();
            $table->foreignId('destination_location_id')->nullable()->constrained('locations')->nullOnDelete();
            $table->foreignId('partner_customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->foreignId('partner_vendor_id')->nullable()->constrained('vendors')->nullOnDelete();
            $table->foreignId('sales_order_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('purchase_order_id')->nullable()->constrained()->nullOnDelete();
            $table->string('origin')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('done_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('stock_operation_moves', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stock_operation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->foreignId('uom_id')->nullable()->constrained('uoms')->nullOnDelete();
            $table->unsignedInteger('demand_qty')->default(0);
            $table->unsignedInteger('done_qty')->default(0);
            $table->timestamps();
        });

        Schema::table('products', function (Blueprint $table) {
            $table->string('type')->default('goods')->after('name'); // goods, service
            $table->string('barcode')->nullable()->after('sku');
            $table->foreignId('product_category_id')->nullable()->after('barcode')->constrained()->nullOnDelete();
            $table->foreignId('uom_id')->nullable()->after('unit')->constrained('uoms')->nullOnDelete();
            $table->foreignId('purchase_uom_id')->nullable()->after('uom_id')->constrained('uoms')->nullOnDelete();
            $table->unsignedBigInteger('cost')->default(0)->after('price');
            $table->decimal('weight', 12, 3)->nullable()->after('cost');
            $table->decimal('volume', 12, 3)->nullable()->after('weight');
        });

        Schema::table('vendors', function (Blueprint $table) {
            $table->foreignId('payment_term_id')->nullable()->after('notes')->constrained()->nullOnDelete();
            $table->foreignId('currency_id')->nullable()->after('payment_term_id')->constrained()->nullOnDelete();
            $table->string('tax_id')->nullable()->after('currency_id');
        });

        Schema::table('customers', function (Blueprint $table) {
            $table->foreignId('payment_term_id')->nullable()->after('notes')->constrained()->nullOnDelete();
            $table->foreignId('currency_id')->nullable()->after('payment_term_id')->constrained()->nullOnDelete();
            $table->string('tax_id')->nullable()->after('currency_id');
        });

        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->string('partner_reference')->nullable()->after('vendor_id');
            $table->foreignId('currency_id')->nullable()->after('partner_reference')->constrained()->nullOnDelete();
            $table->foreignId('payment_term_id')->nullable()->after('currency_id')->constrained()->nullOnDelete();
            $table->foreignId('destination_location_id')->nullable()->after('payment_term_id')->constrained('locations')->nullOnDelete();
            $table->timestamp('ordered_at')->nullable()->after('destination_location_id');
            $table->timestamp('planned_at')->nullable()->after('ordered_at');
            $table->string('origin')->nullable()->after('planned_at');
            $table->string('receipt_status')->default('no')->after('status'); // no, partial, full
            $table->string('billing_status')->default('no')->after('receipt_status'); // no, partial, invoiced
            $table->foreignId('buyer_id')->nullable()->after('created_by')->constrained('users')->nullOnDelete();
        });

        Schema::table('purchase_order_items', function (Blueprint $table) {
            $table->foreignId('uom_id')->nullable()->after('product_id')->constrained('uoms')->nullOnDelete();
            $table->foreignId('tax_id')->nullable()->after('tax_percent')->constrained('taxes')->nullOnDelete();
            $table->unsignedInteger('qty_received')->default(0)->after('quantity');
            $table->unsignedInteger('qty_invoiced')->default(0)->after('qty_received');
            $table->timestamp('planned_at')->nullable()->after('qty_invoiced');
        });

        Schema::table('sales_orders', function (Blueprint $table) {
            $table->string('kind')->default('order')->after('number'); // quotation, order
            $table->foreignId('currency_id')->nullable()->after('customer_id')->constrained()->nullOnDelete();
            $table->foreignId('payment_term_id')->nullable()->after('currency_id')->constrained()->nullOnDelete();
            $table->foreignId('warehouse_id')->nullable()->after('payment_term_id')->constrained()->nullOnDelete();
            $table->date('validity_date')->nullable()->after('warehouse_id');
            $table->timestamp('ordered_at')->nullable()->after('validity_date');
            $table->timestamp('commitment_date')->nullable()->after('ordered_at');
            $table->string('client_order_ref')->nullable()->after('commitment_date');
            $table->string('origin')->nullable()->after('client_order_ref');
            $table->string('delivery_status')->default('no')->after('status'); // no, partial, full
            $table->string('invoice_status')->default('no')->after('delivery_status'); // no, to_invoice, invoiced
            $table->foreignId('salesperson_id')->nullable()->after('created_by')->constrained('users')->nullOnDelete();
            $table->foreignId('quotation_id')->nullable()->after('salesperson_id')->constrained('sales_orders')->nullOnDelete();
        });

        Schema::table('sales_order_items', function (Blueprint $table) {
            $table->foreignId('uom_id')->nullable()->after('product_id')->constrained('uoms')->nullOnDelete();
            $table->foreignId('tax_id')->nullable()->after('tax_percent')->constrained('taxes')->nullOnDelete();
            $table->unsignedInteger('qty_delivered')->default(0)->after('quantity');
            $table->unsignedInteger('qty_invoiced')->default(0)->after('qty_delivered');
            $table->unsignedInteger('customer_lead')->default(0)->after('qty_invoiced');
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->date('invoice_date')->nullable()->after('sales_order_id');
            $table->date('due_date')->nullable()->after('invoice_date');
            $table->foreignId('payment_term_id')->nullable()->after('due_date')->constrained()->nullOnDelete();
            $table->foreignId('journal_id')->nullable()->after('payment_term_id')->constrained()->nullOnDelete();
            $table->foreignId('currency_id')->nullable()->after('journal_id')->constrained()->nullOnDelete();
            $table->string('reference')->nullable()->after('currency_id');
            $table->string('payment_state')->default('not_paid')->after('status');
        });

        Schema::table('invoice_items', function (Blueprint $table) {
            $table->foreignId('uom_id')->nullable()->after('product_id')->constrained('uoms')->nullOnDelete();
            $table->foreignId('tax_id')->nullable()->after('tax_percent')->constrained('taxes')->nullOnDelete();
        });

        Schema::table('bills', function (Blueprint $table) {
            $table->date('bill_date')->nullable()->after('purchase_order_id');
            $table->date('due_date')->nullable()->after('bill_date');
            $table->foreignId('payment_term_id')->nullable()->after('due_date')->constrained()->nullOnDelete();
            $table->foreignId('journal_id')->nullable()->after('payment_term_id')->constrained()->nullOnDelete();
            $table->foreignId('currency_id')->nullable()->after('journal_id')->constrained()->nullOnDelete();
            $table->string('reference')->nullable()->after('currency_id');
            $table->string('payment_state')->default('not_paid')->after('status');
        });

        Schema::table('bill_items', function (Blueprint $table) {
            $table->foreignId('uom_id')->nullable()->after('product_id')->constrained('uoms')->nullOnDelete();
            $table->foreignId('tax_id')->nullable()->after('tax_percent')->constrained('taxes')->nullOnDelete();
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->foreignId('journal_id')->nullable()->after('bill_id')->constrained()->nullOnDelete();
            $table->foreignId('currency_id')->nullable()->after('journal_id')->constrained()->nullOnDelete();
            $table->string('memo')->nullable()->after('notes');
        });

        Schema::create('journal_entries', function (Blueprint $table) {
            $table->id();
            $table->string('number')->unique();
            $table->foreignId('journal_id')->constrained()->restrictOnDelete();
            $table->date('date');
            $table->string('reference')->nullable();
            $table->string('status')->default('posted');
            $table->nullableMorphs('document');
            $table->text('narration')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('journal_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('journal_entry_id')->constrained()->cascadeOnDelete();
            $table->foreignId('account_id')->constrained()->restrictOnDelete();
            $table->string('label')->nullable();
            $table->foreignId('partner_customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->foreignId('partner_vendor_id')->nullable()->constrained('vendors')->nullOnDelete();
            $table->unsignedBigInteger('debit')->default(0);
            $table->unsignedBigInteger('credit')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('journal_items');
        Schema::dropIfExists('journal_entries');
        Schema::dropIfExists('stock_operation_moves');
        Schema::dropIfExists('stock_operations');
        Schema::dropIfExists('stock_quants');
        Schema::dropIfExists('locations');
        Schema::dropIfExists('warehouses');
        Schema::dropIfExists('journals');
        Schema::dropIfExists('accounts');
        Schema::dropIfExists('payment_terms');
        Schema::dropIfExists('taxes');
        Schema::dropIfExists('tax_groups');
        Schema::dropIfExists('product_categories');
        Schema::dropIfExists('uoms');
        Schema::dropIfExists('uom_categories');
        Schema::dropIfExists('currencies');
    }
};
