<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('items', function (Blueprint $table) {
            $table->id();
            $table->integer('book_id')->unsigned()->comment('図鑑ID');
            $table->string('name', 100)->comment('アイテム名');
            $table->integer('kind_id')->unsigned()->nullable()->comment('種類ID');
            $table->string('explanation', 1000)->nullable()->comment('説明');
            $table->timestamps();
            $table->comment('アイテム');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('items');
    }
};
