<?php

use App\Helpers\SchemaHelper;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('store_receiving_items_cache', function (Blueprint $table) {
            $table->id();
            $table->string('reference_number');
            $table->string('store_code'); // C001
            $table->longText('scanned_items'); // {"bid":1,"item_code":"CR 12","q":1},{"bid":1,"item_code":"CR 12","q":1}
            $table->tinyInteger('receive_type'); // 0 = scan 1 = manual
            SchemaHelper::addCommonColumns($table);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('store_receiving_items_cache');
    }
};
