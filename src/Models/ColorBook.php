<?php

namespace Daikazu\Laratone\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
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

    /**
     * Get the colors associated with the color book.
     *
     * @return HasMany<Color>
     */
    public function colors(): HasMany
    {
        return $this->hasMany(Color::class, 'color_book_id', 'id');
    }

    /**
     * Scope a query to only include color books with a specific slug.
     *
     * @param  Builder  $query
     */
    public function scopeSlug($query, ?string $slug): Builder
    {
        return $query->where('slug', $slug);
    }
}
