<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateColorsTable extends Migration
{

    public function up()
    {
        Schema::Create(config('laratone.table_prefix') . 'colors', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->string('lab');
            $table->string('hex');
            $table->string('rgb');
            $table->string('cmyk');

            $table->foreign('colorbook_id')
                ->references('id')
                ->on(config('laratone.table_prefix') . 'colors')
                ->onDelete('cascade');

            $table->timestamps();

        });

    }

    public function down()
    {
        Schema::dropIfExists(config('laratone.table_prefix') . 'colors');
    }

}