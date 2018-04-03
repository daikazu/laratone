<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateColorbooksTable extends Migration
{

    private $tablename;

    public function __construct()
    {
        $this->tablename = config('laratone.table_prefix') . 'colorbooks';
    }


    public function up()
    {
        Schema::Create($this->tablename, function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->timestamps();
        });

    }

    public function down()
    {
        Schema::dropIfExists($this->tablename);
    }

}