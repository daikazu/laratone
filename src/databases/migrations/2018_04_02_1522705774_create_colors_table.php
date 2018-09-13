<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateColorsTable extends Migration
{
    private $tablename;

    public function __construct()
    {
        $this->tablename = config('daikazu.laratone.table_prefix').'colors';
        $this->colorbookTablename = config('daikazu.laratone.table_prefix').'color_books';
    }

    public function up()
    {
        Schema::Create($this->tablename, function (Blueprint $table) {
            $table->increments('id');
            $table->integer('color_book_id')->unsigned();
            $table->string('name');
            $table->string('lab')->nullable();
            $table->string('hex')->nullable();
            $table->string('rgb')->nullable();
            $table->string('cmyk')->nullable();

            $table->foreign('color_book_id')
                ->references('id')
                ->on($this->colorbookTablename)
                ->onDelete('cascade');

            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists($this->tablename);
    }
}
