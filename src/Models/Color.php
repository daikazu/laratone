<?php

declare(strict_types=1);

namespace Daikazu\Laratone\Models;

use Daikazu\Laratone\Casts\ColorValueCast;
use Daikazu\Laratone\Enums\ColorType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $color_book_id
 * @property string $name
 * @property string|null $hex
 * @property array<string, float>|null $lab
 * @property array<string, int>|null $rgb
 * @property array<string, int>|null $cmyk
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class Color extends Model
{
    use HasFactory;

    protected $table = 'colors';

    protected $guarded = ['id'];

    protected $hidden = ['id', 'color_book_id', 'created_at', 'updated_at'];

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->table = config('laratone.table_prefix') . $this->table;
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'lab'  => ColorValueCast::forType(ColorType::LAB),
            'rgb'  => ColorValueCast::forType(ColorType::RGB),
            'cmyk' => ColorValueCast::forType(ColorType::CMYK),
        ];
    }

    /**
     * Get the color book that owns the color.
     *
     * @return BelongsTo<ColorBook, $this>
     */
    public function colorBook(): BelongsTo
    {
        return $this->belongsTo(ColorBook::class, 'color_book_id', 'id');
    }
}
