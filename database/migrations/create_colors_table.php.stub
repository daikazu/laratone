<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private readonly string $tableName;

    private readonly string $colorBookTableName;

    public function __construct()
    {
        $this->tableName = config('laratone.table_prefix') . 'colors';
        $this->colorBookTableName = config('laratone.table_prefix') . 'color_books';
    }

    public function up(): void
    {
        Schema::create($this->tableName, function (Blueprint $table): void {
            $table->id();
            $table->foreignId('color_book_id')
                ->index()
                ->constrained($this->colorBookTableName)
                ->cascadeOnDelete();
            $table->string('name');
            $table->string('hex');
            $table->string('lab')->nullable();
            $table->string('rgb')->nullable();
            $table->string('cmyk')->nullable();
            $table->string('oklch')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists($this->tableName);
    }
};
