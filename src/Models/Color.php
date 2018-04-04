<?php

namespace Daikazu\Laratone\Models;

use Illuminate\Database\Eloquent\Model;

class Color extends Model
{
    protected $table = 'colors';

    protected $fillable = ['name', 'lab', 'hex', 'rgb', 'cmyk'];

    protected $hidden = ['colorbook_id', 'created_at', 'updated_at'];

    protected $with = ['colorbook'];


    public function __construct()
    {
        $this->table = config('daikazu.laratone.table_prefix') . $this->table;
    }

    public function colorbook()
    {
        return $this->belongsTo(Colorbook::class);
    }

    public function getLabAttribute()
    {
        $array = explode(',', $this->attributes['lab']);

        return [
            'l' => floatval($array[0]),
            'a' => floatval($array[1]),
            'b' => floatval($array[2]),
        ];
    }

    public function getRgbAttribute()
    {
        $array = explode(',', $this->attributes['rgb']);
        return [
            'r' => intval($array[0]),
            'g' => intval($array[1]),
            'b' => intval($array[2]),
        ];
    }

    public function getCmykAttribute()
    {
        $array = explode(',', $this->attributes['cmyk']);
        return [
            'c' => intval($array[0]),
            'm' => intval($array[1]),
            'y' => intval($array[2]),
            'k' => intval($array[3]),
        ];
    }


}