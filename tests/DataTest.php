<?php

declare(strict_types=1);

use Daikazu\Laratone\Data\ColorBookData;
use Daikazu\Laratone\Data\ColorData;

// ColorData Tests

test('color data can be constructed', function (): void {
    $colorData = new ColorData(
        name: 'Test Color',
        lab: '53.23,80.11,67.22',
        hex: 'FF0000',
        rgb: '255,0,0',
        cmyk: '0,100,100,0',
    );

    expect($colorData->name)->toBe('Test Color')
        ->and($colorData->lab)->toBe('53.23,80.11,67.22')
        ->and($colorData->hex)->toBe('FF0000')
        ->and($colorData->rgb)->toBe('255,0,0')
        ->and($colorData->cmyk)->toBe('0,100,100,0');
});

test('color data allows null values', function (): void {
    $colorData = new ColorData(
        name: 'Test Color',
    );

    expect($colorData->name)->toBe('Test Color')
        ->and($colorData->lab)->toBeNull()
        ->and($colorData->hex)->toBeNull()
        ->and($colorData->rgb)->toBeNull()
        ->and($colorData->cmyk)->toBeNull();
});

test('color data can be created from json', function (): void {
    $json = (object) [
        'name' => '  Test Color  ',
        'lab'  => '53.23,80.11,67.22',
        'hex'  => 'FF0000',
        'rgb'  => '255,0,0',
        'cmyk' => '0,100,100,0',
    ];

    $colorData = ColorData::fromJson($json);

    expect($colorData->name)->toBe('Test Color') // Trimmed
        ->and($colorData->lab)->toBe('53.23,80.11,67.22')
        ->and($colorData->hex)->toBe('FF0000')
        ->and($colorData->rgb)->toBe('255,0,0')
        ->and($colorData->cmyk)->toBe('0,100,100,0');
});

test('color data from json handles missing values', function (): void {
    $json = (object) [
        'name' => 'Test Color',
    ];

    $colorData = ColorData::fromJson($json);

    expect($colorData->name)->toBe('Test Color')
        ->and($colorData->lab)->toBeNull()
        ->and($colorData->hex)->toBeNull()
        ->and($colorData->rgb)->toBeNull()
        ->and($colorData->cmyk)->toBeNull();
});

test('color data can be converted to array', function (): void {
    $colorData = new ColorData(
        name: 'Test Color',
        lab: '53.23,80.11,67.22',
        hex: 'FF0000',
        rgb: '255,0,0',
        cmyk: '0,100,100,0',
    );

    $array = $colorData->toArray();

    expect($array)->toBe([
        'name' => 'Test Color',
        'lab'  => '53.23,80.11,67.22',
        'hex'  => 'FF0000',
        'rgb'  => '255,0,0',
        'cmyk' => '0,100,100,0',
    ]);
});

// ColorBookData Tests

test('color book data can be constructed', function (): void {
    $colors = [
        new ColorData(name: 'Color 1'),
        new ColorData(name: 'Color 2'),
    ];

    $colorBookData = new ColorBookData(
        name: 'Test Book',
        colors: $colors,
    );

    expect($colorBookData->name)->toBe('Test Book')
        ->and($colorBookData->colors)->toHaveCount(2)
        ->and($colorBookData->colors[0]->name)->toBe('Color 1');
});

test('color book data can be created from json', function (): void {
    $json = (object) [
        'name' => 'Test Book',
        'data' => [
            (object) ['name' => 'Color 1', 'hex' => 'FF0000'],
            (object) ['name' => 'Color 2', 'hex' => '00FF00'],
        ],
    ];

    $colorBookData = ColorBookData::fromJson($json);

    expect($colorBookData->name)->toBe('Test Book')
        ->and($colorBookData->colors)->toHaveCount(2)
        ->and($colorBookData->colors[0])->toBeInstanceOf(ColorData::class)
        ->and($colorBookData->colors[0]->name)->toBe('Color 1')
        ->and($colorBookData->colors[1]->hex)->toBe('00FF00');
});

test('color book data from json handles empty data', function (): void {
    $json = (object) [
        'name' => 'Empty Book',
    ];

    $colorBookData = ColorBookData::fromJson($json);

    expect($colorBookData->name)->toBe('Empty Book')
        ->and($colorBookData->colors)->toBeEmpty();
});

test('color book data from json handles missing name', function (): void {
    $json = (object) [
        'data' => [],
    ];

    $colorBookData = ColorBookData::fromJson($json);

    expect($colorBookData->name)->toBe('')
        ->and($colorBookData->colors)->toBeEmpty();
});
