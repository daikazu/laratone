<?php

namespace Daikazu\Laratone\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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

    public function color_book(): BelongsTo
    {
        return $this->belongsTo(ColorBook::class, 'color_book_id', 'id');
    }

    public function getLabAttribute($value): ?array
    {
        return $this->splitColorValues($value, ['l', 'a', 'b']);
    }

    public function getRgbAttribute($value): ?array
    {
        return $this->splitColorValues($value, ['r', 'g', 'b'], 'int');
    }

    public function getCmykAttribute($value): ?array
    {
        return $this->splitColorValues($value, ['c', 'm', 'y', 'k'], 'int');
    }

    private function splitColorValues(?string $value, array $keys, string $type = 'float'): ?array
    {
        if ($value) {
            $array = explode(',', $value);

            if ($type === 'int') {
                $array = array_map('intval', $array);
            } else {
                $array = array_map('floatval', $array);
            }

            return array_combine($keys, $array);
        }

        return null;
    }
}
