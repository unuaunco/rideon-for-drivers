<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTransactionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('user_id')->unsigned();
            $table->foreign('user_id')->references('id')->on('users');
            $table->enum('type',['Invoice', 'Payout'])->nullable();
            $table->enum('status',['Failed', 'Pending', 'Complete'])->default('Pending');
            $table->string('status_description')->nullable();
            $table->string('description')->nullable();
            $table->decimal('amount', 11, 2);
            $table->decimal('amount_with_tax', 11, 2)->nullable();
            $table->integer('currency')->unsigned();
            $table->foreign('currency')->references('id')->on('currency');
            $table->date('calculation_date')->nullable();
            $table->string('object_link')->nullable();
            $table->string('object_id')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('transactions');
    }
}
