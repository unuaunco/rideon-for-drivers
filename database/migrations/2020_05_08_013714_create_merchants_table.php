<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateMerchantsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('merchants', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name',100);
            $table->longText('description');
            $table->bigInteger('integration_type')->unsigned();
            $table->foreign('integration_type')->references('id')->on('merchants_integration_types');
            $table->string('shared_secret',128);
            $table->string('squareup_id');
            $table->decimal('delivery_fee', 11, 2)->nullable();
            $table->decimal('delivery_fee_base_distance', 11, 2)->nullable();
            $table->decimal('delivery_fee_per_km', 11, 2)->nullable();
            $table->integer('user_id');
            $table->string('cuisine_type');
            $table->string('stripe_id')->nullable();
            $table->enum('invoicing_schedule',['Daily', 'Weekly', 'Monthly'])->default('Daily');
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
        Schema::dropIfExists('merchants');
    }
}
