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
        Schema::create('admin_asset_lists', function (Blueprint $table) {
            $table->id();
            $table->text('file');
            $table->text('keyword');
            $table->text('file_path');
            $table->text('original_file_name');
            $table->text('altered_file_name');
            $table->text('created_by_id');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('asset_lists');
    }
};
