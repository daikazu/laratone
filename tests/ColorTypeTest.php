<?php

declare(strict_types=1);

use Daikazu\Laratone\Enums\ColorType;

test('color type has correct values', function (): void {
    expect(ColorType::LAB->value)->toBe('lab')
        ->and(ColorType::RGB->value)->toBe('rgb')
        ->and(ColorType::CMYK->value)->toBe('cmyk')
        ->and(ColorType::OKLCH->value)->toBe('oklch')
        ->and(ColorType::HEX->value)->toBe('hex');
});

test('lab color type returns correct components', function (): void {
    expect(ColorType::LAB->components())->toBe(['l', 'a', 'b']);
});

test('rgb color type returns correct components', function (): void {
    expect(ColorType::RGB->components())->toBe(['r', 'g', 'b']);
});

test('cmyk color type returns correct components', function (): void {
    expect(ColorType::CMYK->components())->toBe(['c', 'm', 'y', 'k']);
});

test('hex color type returns empty components', function (): void {
    expect(ColorType::HEX->components())->toBe([]);
});

test('lab color type returns float value type', function (): void {
    expect(ColorType::LAB->valueType())->toBe('float');
});

test('rgb color type returns int value type', function (): void {
    expect(ColorType::RGB->valueType())->toBe('int');
});

test('cmyk color type returns int value type', function (): void {
    expect(ColorType::CMYK->valueType())->toBe('int');
});

test('hex color type returns string value type', function (): void {
    expect(ColorType::HEX->valueType())->toBe('string');
});

test('oklch color type returns correct components', function (): void {
    expect(ColorType::OKLCH->components())->toBe(['l', 'c', 'h']);
});

test('oklch color type returns float value type', function (): void {
    expect(ColorType::OKLCH->valueType())->toBe('float');
});
