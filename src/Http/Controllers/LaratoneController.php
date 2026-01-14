<?php

declare(strict_types=1);

namespace Daikazu\Laratone\Http\Controllers;

use Daikazu\Laratone\Http\Requests\ColorBookRequest;
use Daikazu\Laratone\Models\ColorBook;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\Rule;

final class LaratoneController extends Controller
{
    /**
     * Get a color book by its slug with optional filtering and sorting.
     *
     * @param  string  $slug  The slug of the color book to retrieve
     * @return JsonResponse The color book data with its colors
     */
    public function colorbook(ColorBookRequest $request, string $slug): JsonResponse
    {
        $isRandom = $request->isRandom();

        // Don't cache random results as they should be different each time
        if ($isRandom) {
            $colorBook = $this->fetchColorBook($slug, $request);
        } else {
            $cacheKey = "colorbook:{$slug}:" . md5(json_encode($request->validated()));

            $colorBook = Cache::remember(
                $cacheKey,
                $this->cacheTime(),
                fn () => $this->fetchColorBook($slug, $request)
            );
        }

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
     */
    public function colorbooks(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'sort' => ['nullable', Rule::in(['asc', 'desc'])],
        ]);

        $cacheKey = 'colorbooks:' . md5(json_encode($validated));

        $colorBooks = Cache::remember($cacheKey, $this->cacheTime(), function () use ($validated) {
            $query = ColorBook::select('name', 'slug');

            if (isset($validated['sort'])) {
                $query->orderBy('name', $validated['sort']);
            }

            return $query->get();
        });

        return response()->json($colorBooks);
    }

    /**
     * Fetch a color book with its colors.
     *
     * @return array<string, mixed>|null
     */
    private function fetchColorBook(string $slug, ColorBookRequest $request): ?array
    {
        $query = ColorBook::with(['colors' => function ($query) use ($request): void {
            if ($request->isRandom()) {
                $query->inRandomOrder();
            }

            $sortDirection = $request->sortDirection();
            if ($sortDirection !== null) {
                $query->orderBy('name', $sortDirection);
            }

            $limit = $request->limit();
            if ($limit !== null) {
                $query->limit($limit);
            }
        }]);

        $colorBook = $query->slug($slug)->first();

        return $colorBook?->only('name', 'slug', 'colors');
    }

    /**
     * Get the configured cache time.
     */
    private function cacheTime(): int
    {
        return (int) config('laratone.cache_time', 3600);
    }
}
