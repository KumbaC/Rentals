<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateContractsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('contracts', function (Blueprint $table) {
            $table->id();
            //$table->foreignId('user_id')->references('id')->on('users');
            $table->foreignId('property_id')->references('id')->on('properties');
            $table->foreignId('room_id')->references('id')->on('rooms');

            $table->string('tenant_name'); //NOMBRE DE ARRENDATARIO
            //$table->boolean('confirm_name')->default(0); //CONFIRMAR NOMBRE

            $table->string('tenant_nationality'); //NOMBRE DE ARRENDATARIO
            //$table->boolean('confirm_nationality')->default(0); //CONFIRMAR NOMBRE

            $table->string('tenant_identification'); //NOMBRE DE ARRENDATARIO
            //$table->boolean('confirm_identification')->default(0); //CONFIRMAR NOMBRE

            $table->date('start_date')->nullable(); //FECHA DE INICIO DEL CONTRATO
            //$table->boolean('confirm_date')->default(0); //CONFIRMAR FECHA

            $table->foreignId('type_currency_id')->references('id')->on('type_currencies'); // TIPO DE MONEDA
            //$table->boolean('confirm_type_currency')->default(0); //CONFIRMAR TIPO DE MONEDA

            $table->string('amount'); //MONTO
            $table->string('amount_writen'); //MONTO ESCRITO
            
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
        Schema::dropIfExists('contracts');
    }
}
