<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private $tableName;
    private $colorBookTableName;

    public function __construct()
    {
        $this->tableName = config('laratone.table_prefix') . 'colors';
        $this->colorBookTableName = config('laratone.table_prefix') . 'color_books';
    }

    public function up()
    {
        Schema::create('laratone_table', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('color_book_id')->unsigned();
            $table->string('name');
            $table->string('lab')->nullable();
            $table->string('hex')->nullable();
            $table->string('rgb')->nullable();
            $table->string('cmyk')->nullable();
            $table->foreign('color_book_id')
                ->references('id')
                ->on($this->colorBookTableName)
                ->onDelete('cascade');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists($this->tableName);
    }
};
