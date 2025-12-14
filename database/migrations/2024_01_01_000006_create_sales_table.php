<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateSalesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('sales', function (Blueprint $table) {
            $table->bigIncrements('id');
            
            $table->string('invoice_no', 191)->unique()->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->decimal('total_amount', 14, 2);
            $table->decimal('total_cost', 14, 2);
            $table->decimal('paid_amount', 14, 2);
            $table->decimal('due_amount', 14, 2);
            $table->string('payment_status', 50)->default('due');
            $table->text('note')->nullable();

            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('sales');
    }
}
