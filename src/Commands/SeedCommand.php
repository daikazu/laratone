<?php

declare(strict_types=1);

namespace Daikazu\Laratone\Commands;

use Daikazu\Laratone\Data\ColorBookData;
use Daikazu\Laratone\Data\ColorData;
use Daikazu\Laratone\Models\Color;
use Daikazu\Laratone\Models\ColorBook;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use JsonException;
use stdClass;

final class SeedCommand extends Command
{
    protected $signature = 'laratone:seed {name?} {--F|file=}';

    protected $description = 'Seed a Laratone Color Books';

    public function handle(): int
    {
        try {
            $file = $this->option('file');
            $fileStr = is_string($file) ? $file : null;
            // Support both absolute paths and paths relative to base_path
            $filePath = $fileStr !== null
                ? ($this->isAbsolutePath($fileStr) ? $fileStr : base_path($fileStr))
                : null;

            if ($filePath === null) {
                $name = $this->argument('name');
                $nameStr = is_string($name) ? $name : null;
                if ($nameStr !== null) {
                    $data = $this->loadColorBookFile($nameStr, byName: true);
                    $colorBookData = $this->validateAndTransform($data);
                    $this->seed($colorBookData);
                } else {
                    $this->seedAllColorBooks();
                }
            } else {
                $data = $this->loadColorBookFile($filePath, byName: false);
                $colorBookData = $this->validateAndTransform($data);
                $this->seed($colorBookData);
            }

            $this->info("<options=bold,reverse;fg=green> All Files Seeded Successfully </>\n");

            return self::SUCCESS;
        } catch (Exception $e) {
            $this->error('An error occurred while seeding: ' . $e->getMessage());

            return self::FAILURE;
        }
    }

    /**
     * Determine whether a path is absolute on both Unix and Windows.
     */
    private function isAbsolutePath(string $path): bool
    {
        return str_starts_with($path, '/')
            || str_starts_with($path, '\\\\')
            || preg_match('/^[A-Za-z]:[\\\\\\/]/', $path) === 1;
    }

    private function seedAllColorBooks(): void
    {
        $colorBooksDir = __DIR__ . '/../../colorbooks/';
        $allColorBooks = glob($colorBooksDir . '*.json') ?: [];

        $this->info('Starting to seed all color books...');
        $progressBar = $this->output->createProgressBar(count($allColorBooks));
        $progressBar->start();

        foreach ($allColorBooks as $colorBook) {
            $name = basename($colorBook, '.json');
            $data = $this->loadColorBookFile($name, byName: true);
            $colorBookData = $this->validateAndTransform($data);
            $this->seed($colorBookData);
            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine(2);
    }

    /**
     * @throws JsonException
     */
    private function loadColorBookFile(string $file, bool $byName = false): stdClass
    {
        $filePath = $byName
            ? __DIR__ . '/../../colorbooks/' . $file . '.json'
            : $file;

        if (! file_exists($filePath)) {
            throw new Exception("File not found: {$filePath}");
        }

        $content = file_get_contents($filePath);
        if ($content === false) {
            throw new Exception("Could not read file: {$filePath}");
        }

        $decoded = json_decode($content, false, 512, JSON_THROW_ON_ERROR);

        if (! $decoded instanceof stdClass) {
            throw new Exception("Invalid JSON structure in file: {$filePath}");
        }

        return $decoded;
    }

    private function validateAndTransform(stdClass $data): ColorBookData
    {
        if (! isset($data->name) || trim($data->name) === '') {
            throw new Exception('Color book name is required');
        }

        if (! isset($data->data) || ! is_array($data->data)) {
            throw new Exception("Color book '{$data->name}' has no color data");
        }

        $colors = [];
        foreach ($data->data as $index => $color) {
            if (! $color instanceof stdClass) {
                throw new Exception("Color book '{$data->name}' has an invalid color at index {$index}: Expected object");
            }

            if (! isset($color->name) || ! is_string($color->name) || trim($color->name) === '') {
                throw new Exception("Color book '{$data->name}' has an invalid color at index {$index}: Name is required");
            }

            if (! isset($color->hex) || ! is_string($color->hex) || trim($color->hex) === '') {
                throw new Exception("Color book '{$data->name}' has an invalid color at index {$index}: Hex value is required");
            }

            $colors[] = ColorData::fromJson($color);
        }

        return new ColorBookData(
            name: trim($data->name),
            colors: $colors,
        );
    }

    private function seed(ColorBookData $colorBookData): void
    {
        $slug = Str::slug($colorBookData->name);

        if (ColorBook::where('slug', $slug)->exists()) {
            $this->warn("Color Book '{$colorBookData->name}' already exists. Skipping...");

            return;
        }

        $skipped = [];

        DB::transaction(function () use ($colorBookData, &$skipped): void {
            $colorBook = $this->createColorBook($colorBookData->name);

            $progressBar = $this->output->createProgressBar(count($colorBookData->colors));
            $progressBar->setFormat(' %current%/%max% [%bar%] %percent:3s%% %message%');
            $progressBar->setMessage('Seeding colors...');
            $progressBar->start();

            foreach ($colorBookData->colors as $index => $colorData) {
                $hex = $this->normalizeHex($colorData->hex);

                if ($hex === null) {
                    $skipped[] = "'{$colorData->name}' (index {$index}): invalid hex value '{$colorData->hex}'";
                    $progressBar->advance();

                    continue;
                }

                try {
                    $this->createColor($colorBook->id, $colorData, $hex);
                    $progressBar->advance();
                } catch (Exception $e) {
                    $progressBar->clear();
                    $this->error("\nError in color book '{$colorBookData->name}' at color index {$index}:");
                    $this->error($e->getMessage());
                    throw $e;
                }
            }

            $progressBar->finish();
            $this->newLine();
        });

        foreach ($skipped as $warning) {
            $this->warn("Skipped color in '{$colorBookData->name}': {$warning}");
        }

        $this->info("Seeded: {$colorBookData->name}");
    }

    private function createColorBook(string $name): ColorBook
    {
        return ColorBook::create([
            'name' => $name,
            'slug' => Str::slug($name),
        ]);
    }

    /**
     * Clean up a raw hex value, returning null when it cannot be normalized
     * to a valid 6-character hex code.
     */
    private function normalizeHex(string $rawHex): ?string
    {
        $hex = strtoupper((string) preg_replace('/[^0-9A-F]/i', '', $rawHex));

        return strlen($hex) === 6 ? $hex : null;
    }

    private function createColor(int $colorBookId, ColorData $colorData, string $hex): void
    {
        Color::create([
            'color_book_id' => $colorBookId,
            'name'          => $colorData->name,
            'hex'           => $hex,
            'lab'           => $colorData->lab,
            'rgb'           => $colorData->rgb,
            'cmyk'          => $colorData->cmyk,
        ]);
    }
}
