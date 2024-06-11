<?php

namespace Daikazu\Laratone\Http\Controllers;

use Daikazu\Laratone\Models\ColorBook;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Request;
use Illuminate\Validation\Rule;

class LaratoneController extends Controller
{
    public function colorbook($slug)
    {
        $validated = Request::validate([
            'limit'  => 'nullable|integer',
            'sort'   => ['nullable', Rule::in(['asc', 'desc'])],
            'random' => 'nullable|boolean',
        ]);

        return ColorBook::with([
            'colors' => function ($query) use ($validated) {
                $this->applyQueryOptions($query, $validated);
            },
        ])
            ->slug($slug)
            ->first()
            ->only('name', 'slug', 'colors');
    }

    public function colorbooks()
    {
        $validated = Request::validate([
            'sort' => ['nullable', Rule::in(['asc', 'desc'])],
        ]);

        $query = ColorBook::select('name', 'slug');

        $this->applyQueryOptions($query, $validated);

        return $query->get();
    }

    private function applyQueryOptions($query, $options)
    {
        if (isset($options['random']) && $options['random']) {
            $query->inRandomOrder();
        }

        if (isset($options['sort'])) {
            $query->orderBy('name', $options['sort']);
        }

        if (isset($options['limit'])) {
            $query->limit($options['limit']);
        }
    }
}
