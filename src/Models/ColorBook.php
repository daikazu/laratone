<?php
namespace Daikazu\Laratone\Models;

use Illuminate\Database\Eloquent\Model;

class Colorbook extends Model
{

    protected $table = 'colorbooks';


    protected $fillable =['name'];


    public function __construct()
    {
        $this->table = config('laratone.table_prefix') . $this->table;
    }









}