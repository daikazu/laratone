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
        $sortOrder = Request::input('sort', 'asc');
        $randomize = (bool) Request::input('random', null);

        return ColorBook::with([
            'colors' => function ($query) use ($limit, $sortOrder, $randomize) {
                if ($randomize) {
                    $query->inRandomOrder();
                } else {
                    ($sortOrder === 'asc')
                        ? $query->orderBy('name', 'asc')
                        : $query->orderBy('name', 'desc');
                }

                $query->limit($limit);
            },
        ])
            ->slug($slug)
            ->first()
            ->only('name', 'slug', 'colors');
    }

    public function colorbooks()
    {
        $sortOrder = Request::input('sort', 'asc');

        $query = ColorBook::select('name', 'slug');

        ($sortOrder === 'asc')
            ? $query->orderBy('name', 'asc')
            : $query->orderBy('name', 'desc');

        return $query->get();
    }
}
