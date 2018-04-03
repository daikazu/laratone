<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateColorbooksTable extends Migration
{

    public function up()
    {
        Schema::Create(config('laratone.table_prefix') . 'colorbooks', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->timestamps();
        });

    }

    public function down()
    {
        Schema::dropIfExists(config('laratone.table_prefix') . 'colorbooks');
    }

}