<?php

namespace Daikazu\Laratone\Models;

use Illuminate\Database\Eloquent\Model;

class ColorBook extends Model
{
    protected $table = 'color_books';
    protected $fillable = ['name', 'slug'];
    protected $hidden = ['created_at', 'updated_at'];

    public function __construct()
    {
        $this->table = config('laratone.table_prefix').$this->table;
    }

    public function colors()
    {
        return $this->hasMany(Color::class, 'color_book_id');
    }

    public function scopeSlug($query, $slug)
    {
        return $query->where('slug', $slug);
    }
}
