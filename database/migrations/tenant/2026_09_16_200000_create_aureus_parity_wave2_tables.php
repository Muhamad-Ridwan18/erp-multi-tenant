<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('work_centers', function (Blueprint $table) {
            $table->id();
            $table->string('code')->nullable()->index();
            $table->string('name');
            $table->text('notes')->nullable();
            $table->unsignedInteger('default_capacity')->default(1);
            $table->decimal('costs_per_hour', 15, 2)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('bills_of_materials', function (Blueprint $table) {
            $table->id();
            $table->string('code')->nullable()->index();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->foreignId('uom_id')->nullable()->constrained('uoms')->nullOnDelete();
            $table->unsignedInteger('quantity')->default(1);
            $table->foreignId('work_center_id')->nullable()->constrained()->nullOnDelete();
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('bill_of_material_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bill_of_material_id')->constrained('bills_of_materials')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->foreignId('uom_id')->nullable()->constrained('uoms')->nullOnDelete();
            $table->unsignedInteger('quantity')->default(1);
            $table->unsignedInteger('sort')->default(0);
            $table->timestamps();
        });

        Schema::create('manufacturing_orders', function (Blueprint $table) {
            $table->id();
            $table->string('number')->unique();
            $table->string('status')->default('draft'); // draft, confirmed, done, canceled
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->foreignId('bill_of_material_id')->nullable()->constrained('bills_of_materials')->nullOnDelete();
            $table->foreignId('work_center_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('uom_id')->nullable()->constrained('uoms')->nullOnDelete();
            $table->unsignedInteger('quantity')->default(1);
            $table->unsignedInteger('qty_produced')->default(0);
            $table->foreignId('source_location_id')->nullable()->constrained('locations')->nullOnDelete();
            $table->foreignId('destination_location_id')->nullable()->constrained('locations')->nullOnDelete();
            $table->string('origin')->nullable();
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('manufacturing_order_components', function (Blueprint $table) {
            $table->id();
            $table->foreignId('manufacturing_order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->foreignId('uom_id')->nullable()->constrained('uoms')->nullOnDelete();
            $table->unsignedInteger('quantity')->default(0);
            $table->unsignedInteger('qty_consumed')->default(0);
            $table->timestamps();
        });

        Schema::create('order_points', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('location_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedInteger('min_qty')->default(0);
            $table->unsignedInteger('max_qty')->default(0);
            $table->string('trigger')->default('auto'); // auto, manual
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['product_id', 'location_id']);
        });

        Schema::table('stock_operations', function (Blueprint $table) {
            $table->foreignId('manufacturing_order_id')->nullable()->after('purchase_order_id')->constrained()->nullOnDelete();
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->string('move_type')->default('out_invoice')->after('number'); // out_invoice, out_refund
            $table->foreignId('reversed_invoice_id')->nullable()->after('sales_order_id')->constrained('invoices')->nullOnDelete();
        });

        Schema::table('bills', function (Blueprint $table) {
            $table->string('move_type')->default('in_invoice')->after('number'); // in_invoice, in_refund
            $table->foreignId('reversed_bill_id')->nullable()->after('purchase_order_id')->constrained('bills')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('bills', function (Blueprint $table) {
            $table->dropConstrainedForeignId('reversed_bill_id');
            $table->dropColumn('move_type');
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->dropConstrainedForeignId('reversed_invoice_id');
            $table->dropColumn('move_type');
        });

        Schema::table('stock_operations', function (Blueprint $table) {
            $table->dropConstrainedForeignId('manufacturing_order_id');
        });

        Schema::dropIfExists('order_points');
        Schema::dropIfExists('manufacturing_order_components');
        Schema::dropIfExists('manufacturing_orders');
        Schema::dropIfExists('bill_of_material_lines');
        Schema::dropIfExists('bills_of_materials');
        Schema::dropIfExists('work_centers');
    }
};
