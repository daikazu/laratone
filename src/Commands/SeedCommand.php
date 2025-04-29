<?php

namespace Daikazu\Laratone\Commands;

use Daikazu\Laratone\Models\Color;
use Daikazu\Laratone\Models\ColorBook;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class SeedCommand extends Command
{
    protected $signature = 'laratone:seed {name?} {--F|file=}';

    protected $description = 'Seed a Laratone Color Books';

    public function handle(): int
    {
        try {
            $file = $this->option('file') ? base_path($this->option('file')) : null;

            if ($file === null) {
                if ($this->argument('name')) {
                    $data = $this->getJSONFileData($this->argument('name'), true);
                    $this->validateColorBookData($data);
                    $this->seed($data);
                } else {
                    $allColorBooks = array_diff(scandir(__DIR__ . '/../../colorbooks/'), ['.', '..']);

                    $this->info('Starting to seed all color books...');
                    $progressBar = $this->output->createProgressBar(count($allColorBooks));
                    $progressBar->start();

                    foreach ($allColorBooks as $colorBook) {
                        $data = $this->getJSONFileData(str_replace('.json', '', $colorBook), true);
                        $this->validateColorBookData($data);
                        $this->seed($data);
                        $progressBar->advance();
                    }

                    $progressBar->finish();
                    $this->newLine(2);
                }
            } else {
                $data = $this->getJSONFileData($file);
                $this->validateColorBookData($data);
                $this->seed($data);
            }

            $this->info("<options=bold,reverse;fg=green> All Files Seeded Successfully </> 🤙\n");

            return self::SUCCESS;
        } catch (Exception $e) {
            $this->error('An error occurred while seeding: ' . $e->getMessage());

            return self::FAILURE;
        }
    }

    private function getJSONFileData($file, $byName = false): object
    {
        try {
            $filePath = $byName 
                ? __DIR__ . '/../../colorbooks/' . $file . '.json'
                : $file;

            if (!file_exists($filePath)) {
                throw new Exception("File not found: {$filePath}");
            }

            $content = file_get_contents($filePath);
            if ($content === false) {
                throw new Exception("Could not read file: {$filePath}");
            }

            // Debug the raw content
            $this->info("Debug - Raw file content:");
            $this->line(substr($content, 0, 500) . "...");

            $data = json_decode($content, false, 512, JSON_THROW_ON_ERROR);
            
            // Debug the parsed data structure
            $this->info("Debug - Data structure:");
            $this->line("Name: " . ($data->name ?? 'not set'));
            $this->line("Data count: " . count($data->data ?? []));
            if (isset($data->data[0])) {
                $this->line("First color: " . json_encode($data->data[0]));
            }

            return $data;
        } catch (Exception $e) {
            $this->error('Error processing color book: ' . $e->getMessage());
            throw $e;
        }
    }

    private function validateColorBookData(object $data): object
    {
        // Debug the data structure
        $this->info("Debug - Validating color book:");
        $this->line("Name: " . ($data->name ?? 'not set'));
        $this->line("Data count: " . count($data->data ?? []));
        
        if (isset($data->data[0])) {
            $this->line("First color in validation: " . json_encode($data->data[0]));
        }

        // Create a new object to store validated data
        $validatedData = new \stdClass();
        $validatedData->name = $data->name;
        $validatedData->data = [];

        // Validate each color individually
        foreach ($data->data as $index => $color) {
            // Debug the current color
            $this->info("Debug - Validating color at index {$index}:");
            $this->line(json_encode($color));

            if (!isset($color->name) || trim($color->name) === '') {
                $this->error("Debug - Invalid color at index {$index}:");
                $this->error("Raw color data: " . json_encode($color));
                throw new Exception("Color book '{$data->name}' has an invalid color at index {$index}: Name is empty or not set");
            }

            // Create a new object for each color
            $validatedColor = new \stdClass();
            $validatedColor->name = trim($color->name);
            $validatedColor->lab = $color->lab ?? null;
            $validatedColor->hex = $color->hex ?? null;
            $validatedColor->rgb = $color->rgb ?? null;
            $validatedColor->cmyk = $color->cmyk ?? null;

            // Debug the validated color
            $this->info("Debug - Validated color at index {$index}:");
            $this->line(json_encode($validatedColor));

            $validatedData->data[] = $validatedColor;
        }

        // Debug the final validated data
        $this->info("Debug - Final validated data:");
        $this->line("Name: " . $validatedData->name);
        $this->line("Data count: " . count($validatedData->data));
        if (isset($validatedData->data[0])) {
            $this->line("First validated color: " . json_encode($validatedData->data[0]));
        }

        return $validatedData;
    }

    private function seed(object $jsonColorBook): void
    {
        $slug = Str::slug($jsonColorBook->name);

        if (ColorBook::where('slug', $slug)->exists()) {
            $this->warn("Color Book '{$jsonColorBook->name}' already exists. Skipping...");
            return;
        }

        try {
            // Validate the data first
            $validatedData = $this->validateColorBookData($jsonColorBook);

            DB::transaction(function () use ($validatedData) {
                $colorBook = $this->createColorBook(name: $validatedData->name);

                $progressBar = $this->output->createProgressBar(count($validatedData->data));
                $progressBar->setFormat(' %current%/%max% [%bar%] %percent:3s%% %message%');
                $progressBar->setMessage('Seeding colors...');
                $progressBar->start();

                foreach ($validatedData->data as $index => $color) {
                    try {
                        $this->info("Debug - Processing color at index {$index}:");
                        $this->line(json_encode($color));

                        $this->createColor($colorBook->id, $color);
                        $progressBar->advance();
                    } catch (\Exception $e) {
                        $progressBar->clear();
                        $this->error("\nError in color book '{$validatedData->name}' at color index {$index}:");
                        $this->error($e->getMessage());
                        throw $e;
                    }
                }

                $progressBar->finish();
                $this->newLine();
            });

            $this->info("✓ {$validatedData->name} seeded successfully.");
        } catch (\Exception $e) {
            $this->error("\nFailed to seed color book '{$jsonColorBook->name}':");
            $this->error($e->getMessage());
            throw $e;
        }
    }

    private function createColorBook(string $name): ColorBook
    {
        return ColorBook::create([
            'name' => $name,
            'slug' => Str::slug($name),
        ]);
    }

    private function createColor(int $colorBookId, object $value): void
    {
        $this->info("Debug - Creating color with data:");
        $this->line(json_encode($value));

        if (!isset($value->name) || trim($value->name) === '') {
            throw new Exception("Cannot create color with empty name for color book ID: {$colorBookId}");
        }

        try {
            // Clean up hex value if it exists
            $hex = !empty($value->hex) ? strtoupper(preg_replace('/[^0-9A-F]/', '', $value->hex)) : null;

            Color::create([
                'color_book_id' => $colorBookId,
                'name'          => trim($value->name),
                'hex'           => $hex,
                'lab'           => $value->lab,
                'rgb'           => $value->rgb,
                'cmyk'          => $value->cmyk,
            ]);
        } catch (\Exception $e) {
            $colorBook = ColorBook::find($colorBookId);
            throw new Exception("Failed to create color '{$value->name}' in color book '{$colorBook->name}': " . $e->getMessage());
        }
    }
}
