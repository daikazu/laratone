<?php

namespace Daikazu\Laratone\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $color_book_id
 * @property string|null $lab
 * @property string|null $rgb
 * @property string|null $cmyk
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class Color extends Model
{
    use HasFactory;

    private const array COLOR_TYPES = [
        'lab'  => ['l', 'a', 'b'],
        'rgb'  => ['r', 'g', 'b'],
        'cmyk' => ['c', 'm', 'y', 'k'],
    ];

    protected $table = 'colors';
    protected $guarded = ['id'];
    protected $hidden = ['id', 'color_book_id', 'created_at', 'updated_at'];

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->table = config('laratone.table_prefix') . $this->table;
    }

    /**
     * Get the color book that owns the color.
     *
     * @return BelongsTo<ColorBook, Color>
     */
    public function color_book(): BelongsTo
    {
        return $this->belongsTo(ColorBook::class, 'color_book_id', 'id');
    }

    /**
     * Get the LAB color values as an array.
     *
     * @return array<string, float>|null
     */
    public function getLabAttribute(?string $value): ?array
    {
        return $this->splitColorValues($value, self::COLOR_TYPES['lab']);
    }

    /**
     * Get the RGB color values as an array.
     *
     * @return array<string, int>|null
     */
    public function getRgbAttribute(?string $value): ?array
    {
        return $this->splitColorValues($value, self::COLOR_TYPES['rgb'], 'int');
    }

    /**
     * Get the CMYK color values as an array.
     *
     * @return array<string, int>|null
     */
    public function getCmykAttribute(?string $value): ?array
    {
        return $this->splitColorValues($value, self::COLOR_TYPES['cmyk'], 'int');
    }

    /**
     * Split color values into an associative array.
     *
     * @param  array<string>  $keys
     * @return array<string, float|int>|null
     */
    private function splitColorValues(?string $value, array $keys, string $type = 'float'): ?array
    {
        if (! $value) {
            return null;
        }

        $array = explode(',', $value);

        if (count($array) !== count($keys)) {
            return null;
        }

        $array = $type === 'int'
            ? array_map('intval', $array)
            : array_map('floatval', $array);

        return array_combine($keys, $array);
    }
}
