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
        Schema::table('qa_item_dispositions', function (Blueprint $table) {
            $table->integer('fulfilled_batch_id')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('qa_item_dispositions', function (Blueprint $table) {
            $table->dropColumn('fulfilled_batch_id');
        });
    }
};
