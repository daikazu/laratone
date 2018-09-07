<?php

namespace Daikazu\Laratone\Models;

use Illuminate\Database\Eloquent\Model;

class ColorBook extends Model
{
    protected $table = 'color_books';
    protected $fillable = ['name'];
    protected $hidden = ['created_at', 'updated_at'];

    public function __construct()
    {
        $this->table = config('daikazu.laratone.table_prefix') . $this->table;
    }

    public function colors()
    {
        return $this->hasMany(Color::class, 'color_book_id');
    }
}
