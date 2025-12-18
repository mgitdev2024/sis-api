<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('stock_inventory_count', function (Blueprint $table) {

            // Single-column indexes
            $table->index('reference_number');
            $table->index('store_code');
            $table->index('store_sub_unit_short_name');
            $table->index('type');
            $table->index('status');
            $table->index('created_at');
            $table->index('reviewed_at');
            $table->index('posted_at');

            // Composite indexes
            $table->index(
                ['store_code', 'store_sub_unit_short_name', 'type'],
                'idx_invcount_storeunit_type'
            );

            $table->index(
                ['reference_number', 'store_code'],
                'idx_invcount_ref_store'
            );

            $table->index(
                ['status', 'store_code'],
                'idx_invcount_status_store'
            );
        });

        Schema::table('stock_inventory_items_count', function (Blueprint $table) {

            // Single-column indexes
            $table->index('stock_inventory_count_id');
            $table->index('item_code');
            $table->index('status');
            $table->index('created_at');

            // Composite indexes
            $table->index(
                ['stock_inventory_count_id', 'item_code'],
                'idx_invitems_countid_itemcode'
            );

            $table->index(
                ['stock_inventory_count_id', 'status'],
                'idx_invitems_countid_status'
            );
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('stock_inventory_count', function (Blueprint $table) {
            $table->dropIndex(['reference_number']);
            $table->dropIndex(['store_code']);
            $table->dropIndex(['store_sub_unit_short_name']);
            $table->dropIndex(['type']);
            $table->dropIndex(['status']);
            $table->dropIndex(['created_at']);
            $table->dropIndex(['reviewed_at']);
            $table->dropIndex(['posted_at']);

            $table->dropIndex('idx_invcount_storeunit_type');
            $table->dropIndex('idx_invcount_ref_store');
            $table->dropIndex('idx_invcount_status_store');
        });
        Schema::table('stock_inventory_items_count', function (Blueprint $table) {
            $table->dropIndex(['stock_inventory_count_id']);
            $table->dropIndex(['item_code']);
            $table->dropIndex(['status']);
            $table->dropIndex(['created_at']);

            $table->dropIndex('idx_invitems_countid_itemcode');
            $table->dropIndex('idx_invitems_countid_status');
        });

    }
};