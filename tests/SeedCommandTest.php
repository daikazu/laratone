<?php

declare(strict_types=1);

use Daikazu\Laratone\Models\ColorBook;

// Helper function to get the package root path
function packagePath(string $path = ''): string
{
    return dirname(__DIR__) . ($path ? DIRECTORY_SEPARATOR . $path : '');
}

test('seed command seeds color book from custom file', function (): void {
    $this->artisan('laratone:seed', [
        '--file' => packagePath('tests/fixtures/colorbooks/test-colorbook.json'),
    ])->assertSuccessful();

    $colorBook = ColorBook::where('slug', 'test-color-book')->first();

    expect($colorBook)->not->toBeNull()
        ->and($colorBook->name)->toBe('Test Color Book')
        ->and($colorBook->colors)->toHaveCount(3);
});

test('seed command skips existing color books', function (): void {
    // First seed
    $this->artisan('laratone:seed', [
        '--file' => packagePath('tests/fixtures/colorbooks/test-colorbook.json'),
    ])->assertSuccessful();

    // Second seed should skip
    $this->artisan('laratone:seed', [
        '--file' => packagePath('tests/fixtures/colorbooks/test-colorbook.json'),
    ])->assertSuccessful();

    // Should still have only one color book
    expect(ColorBook::where('slug', 'test-color-book')->count())->toBe(1);
});

test('seed command fails for non-existent file', function (): void {
    $this->artisan('laratone:seed', [
        '--file' => packagePath('tests/fixtures/colorbooks/non-existent.json'),
    ])->assertFailed();
});

test('seed command fails for invalid color book data', function (): void {
    $this->artisan('laratone:seed', [
        '--file' => packagePath('tests/fixtures/colorbooks/invalid-colorbook.json'),
    ])->assertFailed();
});

test('seed command creates colors with correct values', function (): void {
    $this->artisan('laratone:seed', [
        '--file' => packagePath('tests/fixtures/colorbooks/test-colorbook.json'),
    ])->assertSuccessful();

    $colorBook = ColorBook::where('slug', 'test-color-book')->first();
    $redColor = $colorBook->colors->where('name', 'Test Red')->first();

    expect($redColor->hex)->toBe('FF0000')
        ->and($redColor->rgb)->toBe(['r' => 255, 'g' => 0, 'b' => 0])
        ->and($redColor->lab)->toBe(['l' => 53.23, 'a' => 80.11, 'b' => 67.22])
        ->and($redColor->cmyk)->toBe(['c' => 0, 'm' => 100, 'y' => 100, 'k' => 0]);
});

test('seed command cleans hex values', function (): void {
    // Create a test file with dirty hex values
    $testFile = packagePath('tests/fixtures/colorbooks/dirty-hex.json');
    file_put_contents($testFile, json_encode([
        'name' => 'Dirty Hex Book',
        'data' => [
            ['name' => 'Color 1', 'hex' => '#ff0000'],
            ['name' => 'Color 2', 'hex' => 'FF00FF'],
        ],
    ]));

    $this->artisan('laratone:seed', [
        '--file' => $testFile,
    ])->assertSuccessful();

    $colorBook = ColorBook::where('slug', 'dirty-hex-book')->first();

    expect($colorBook->colors->first()->hex)->toBe('FF0000')
        ->and($colorBook->colors->last()->hex)->toBe('FF00FF');

    // Cleanup
    unlink($testFile);
});
