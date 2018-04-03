<?php

namespace Daikazu\Laratone\Models;

use Illuminate\Database\Eloquent\Model;

class Color extends Model
{
    protected $table = 'colors';

    protected $fillable = ['name', 'lab', 'hex', 'rgb', 'cmyk'];


    public function __construct()
    {
        $this->table = config('tjm.laratone.table_prefix') . $this->table;
    }


    public function getLabAttribute()
    {
        $array = explode(',', $this->lab);
        return [
            'l' => floatval($array[0]),
            'a' => floatval($array[1]),
            'b' => floatval($array[2]),
        ];
    }

    public function getRgbAttribute()
    {
        $array = explode(',', $this->rgb);
        return [
            'r' => intval($array[0]),
            'g' => intval($array[1]),
            'b' => intval($array[2]),
        ];
    }

    public function getCmykAttribute()
    {
        $array = explode(',', $this->cmyk);
        return [
            'c' => intval($array[0]),
            'm' => intval($array[1]),
            'y' => intval($array[2]),
            'k' => intval($array[3]),
        ];
    }


}