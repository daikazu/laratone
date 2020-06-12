<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateColorBooksTable extends Migration
{
    private $tableName;

    public function __construct()
    {
        $this->tableName = config('laratone.table_prefix').'color_books';
    }

    public function up()
    {
        Schema::Create($this->tableName, function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name');
            $table->string('slug')->unique();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists($this->tableName);
    }
}
