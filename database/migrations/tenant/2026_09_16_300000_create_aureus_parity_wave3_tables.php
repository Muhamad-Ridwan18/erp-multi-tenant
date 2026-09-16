<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('tracking')->default('none')->after('type'); // none, lot, serial
        });

        Schema::create('lots', function (Blueprint $table) {
            $table->id();
            $table->string('name')->index();
            $table->string('reference')->nullable();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->timestamp('expiration_date')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['product_id', 'name']);
        });

        Schema::table('stock_quants', function (Blueprint $table) {
            $table->unsignedBigInteger('lot_id')->nullable()->after('location_id');
        });

        Schema::table('stock_quants', function (Blueprint $table) {
            // Keep covering indexes so MySQL can drop the composite unique used by FKs.
            $table->index('product_id');
            $table->index('location_id');
        });

        Schema::table('stock_quants', function (Blueprint $table) {
            $table->dropUnique(['product_id', 'location_id']);
        });

        Schema::table('stock_quants', function (Blueprint $table) {
            $table->foreign('lot_id')->references('id')->on('lots')->nullOnDelete();
            $table->unique(['product_id', 'location_id', 'lot_id']);
        });

        Schema::table('stock_operation_moves', function (Blueprint $table) {
            $table->foreignId('lot_id')->nullable()->after('uom_id')->constrained('lots')->nullOnDelete();
        });

        Schema::create('bom_operations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bill_of_material_id')->constrained('bills_of_materials')->cascadeOnDelete();
            $table->foreignId('work_center_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->unsignedInteger('duration_minutes')->default(0);
            $table->unsignedInteger('sort')->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('work_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('manufacturing_order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('bom_operation_id')->nullable()->constrained('bom_operations')->nullOnDelete();
            $table->foreignId('work_center_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('status')->default('pending'); // pending, ready, progress, done, canceled
            $table->unsignedInteger('duration_minutes')->default(0);
            $table->unsignedInteger('sort')->default(0);
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();
        });

        Schema::create('unbuild_orders', function (Blueprint $table) {
            $table->id();
            $table->string('number')->unique();
            $table->string('status')->default('draft'); // draft, done, canceled
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->foreignId('bill_of_material_id')->nullable()->constrained('bills_of_materials')->nullOnDelete();
            $table->foreignId('manufacturing_order_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('lot_id')->nullable()->constrained('lots')->nullOnDelete();
            $table->unsignedInteger('quantity')->default(1);
            $table->foreignId('source_location_id')->nullable()->constrained('locations')->nullOnDelete();
            $table->foreignId('destination_location_id')->nullable()->constrained('locations')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamp('done_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('unbuild_orders');
        Schema::dropIfExists('work_orders');
        Schema::dropIfExists('bom_operations');

        Schema::table('stock_operation_moves', function (Blueprint $table) {
            $table->dropConstrainedForeignId('lot_id');
        });

        Schema::table('stock_quants', function (Blueprint $table) {
            $table->dropUnique(['product_id', 'location_id', 'lot_id']);
            $table->dropForeign(['lot_id']);
            $table->dropColumn('lot_id');
            $table->unique(['product_id', 'location_id']);
        });

        Schema::dropIfExists('lots');

        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('tracking');
        });
    }
};
