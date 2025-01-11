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
            $table->string('item_repository_type')->nullable();
        });

        Schema::table('qa_item_disposition_repositories', function (Blueprint $table) {
            $table->unsignedBigInteger('item_disposition_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('qa_item_dispositions', function (Blueprint $table) {
            $table->string('item_repository_type')->nullable();
        });

        Schema::table('qa_item_disposition_repositories', function (Blueprint $table) {
            $table->dropColumn('item_disposition_id');
        });
    }
};
