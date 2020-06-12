<?php

namespace Daikazu\Laratone\Models;

use Illuminate\Database\Eloquent\Model;

class Color extends Model
{

    protected $table = 'colors';

    protected $fillable = ['name', 'lab', 'hex', 'rgb', 'cmyk'];
    protected $hidden = ['color_book_id', 'created_at', 'updated_at'];

    public function __construct()
    {
        $this->table = config('laratone.table_prefix') . $this->table;
    }

    public function color_book()
    {
        return $this->belongsTo(ColorBook::class, 'color_book_id', 'id');
    }

    public function getLabAttribute()
    {
        if ($this->attributes['lab']) {
            $array = explode(',', $this->attributes['lab']);

            return [
                'l' => floatval($array[0]),
                'a' => floatval($array[1]),
                'b' => floatval($array[2]),
            ];
        }
    }

    public function getRgbAttribute()
    {
        if ($this->attributes['rgb']) {
            $array = explode(',', $this->attributes['rgb']);

            return [
                'r' => intval($array[0]),
                'g' => intval($array[1]),
                'b' => intval($array[2]),
            ];
        }
    }

    public function getCmykAttribute()
    {
        if ($this->attributes['cmyk']) {
            $array = explode(',', $this->attributes['cmyk']);

            return [
                'c' => intval($array[0]),
                'm' => intval($array[1]),
                'y' => intval($array[2]),
                'k' => intval($array[3]),
            ];
        }
    }


}
