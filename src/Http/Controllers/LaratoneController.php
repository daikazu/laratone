<?php

namespace Daikazu\Laratone\Http\Controllers;

use App\Http\Controllers\Controller;
use Daikazu\Laratone\Models\ColorBook;
use Illuminate\Support\Facades\Request;

class LaratoneController extends Controller
{

    public function colorbook($slug)
    {

        $limit = Request::input('limit', null);
        $sortOrder = Request::input('sort', 'desc');

        return ColorBook::with(['colors' => function ($query) use ($limit, $sortOrder) {

            ($sortOrder === 'desc')
                ? $query->orderBy('name', 'desc')
                : $query->orderBy('name', 'asc');

            $query->limit($limit);
        }])
            ->slug($slug)
            ->first()
            ->only('name', 'slug', 'colors');
    }


}
