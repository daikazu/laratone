<?php

namespace Daikazu\Laratone\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ColorBook extends Model
{
    use HasFactory;
    protected $table = 'color_books';
    protected $fillable = ['name', 'slug'];
    protected $hidden = ['created_at', 'updated_at'];

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        $this->table = config('laratone.table_prefix') . $this->table;
    }

    public function colors(): HasMany
    {
        return $this->hasMany(Color::class, 'color_book_id', 'id');
    }

    public function scopeSlug($query, ?string $slug)
    {
        return $query->where('slug', $slug);
    }
}
