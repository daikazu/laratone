<?php

namespace Daikazu\Laratone\Http\Controllers;

use Daikazu\Laratone\Models\ColorBook;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class LaratoneController extends Controller
{
    /**
     * Get a color book by its slug with optional filtering and sorting.
     *
     * @param  string  $slug  The slug of the color book to retrieve
     * @return JsonResponse The color book data with its colors
     *
     * @throws ValidationException If the request validation fails
     */
    public function colorbook(Request $request, string $slug): JsonResponse
    {
        $validated = $request->validate([
            'limit'  => 'nullable|integer|min:1',
            'sort'   => ['nullable', Rule::in(['asc', 'desc'])],
            'random' => 'nullable|boolean',
        ]);

        $cacheKey = "colorbook:{$slug}:" . md5(json_encode($validated));

        $colorBook = Cache::remember($cacheKey, config('laratone.cache_time'), function () use ($slug, $validated) {
            $query = ColorBook::with(['colors' => function ($query) use ($validated) {
                if (isset($validated['random']) && $validated['random']) {
                    $query->inRandomOrder();
                }

                if (isset($validated['sort'])) {
                    $query->orderBy('name', $validated['sort']);
                }

                if (isset($validated['limit'])) {
                    $query->limit($validated['limit']);
                }
            }]);

            $colorBook = $query->slug($slug)->first();

            if (! $colorBook) {
                return null;
            }

            return $colorBook->only('name', 'slug', 'colors');
        });

        if (! $colorBook) {
            return response()->json([
                'message' => 'Color book not found',
            ], 404);
        }

        return response()->json($colorBook);
    }

    /**
     * Get all color books with optional sorting.
     *
     * @return JsonResponse The list of color books
     *
     * @throws ValidationException If the request validation fails
     */
    public function colorbooks(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'sort' => ['nullable', Rule::in(['asc', 'desc'])],
        ]);

        $cacheKey = 'colorbooks:' . md5(json_encode($validated));

        $colorBooks = Cache::remember($cacheKey, config('laratone.cache_time'), function () use ($validated) {
            $query = ColorBook::select('name', 'slug');

            if (isset($validated['sort'])) {
                $query->orderBy('name', $validated['sort']);
            }

            return $query->get();
        });

        return response()->json($colorBooks);
    }
}
