<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateItemVariantsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('item_variants', function (Blueprint $table) {
            $table->bigIncrements('id');
            
            $table->unsignedBigInteger('item_id');
            $table->string('name', 191);
            $table->decimal('price', 12, 2);
            $table->decimal('cost', 12, 2);
            $table->decimal('ingredient_quantity', 14, 4)->nullable();
            $table->boolean('is_default')->default(false);

            $table->timestamps();

            $table->foreign('item_id')->references('id')->on('items')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('item_variants');
    }
}
