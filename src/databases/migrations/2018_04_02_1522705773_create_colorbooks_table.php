<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateColorbooksTable extends Migration
{
    private $tablename;

    public function __construct()
    {
        $this->tablename = config('daikazu.laratone.table_prefix').'colorbooks';
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
