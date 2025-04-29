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

            if (! file_exists($filePath)) {
                throw new Exception("File not found: {$filePath}");
            }

            $content = file_get_contents($filePath);
            if ($content === false) {
                throw new Exception("Could not read file: {$filePath}");
            }

            $data = json_decode($content);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception("Invalid JSON in file: {$filePath}");
            }

            return $data;
        } catch (Exception $e) {
            $this->error('Error processing color book: ' . $e->getMessage());
            throw $e;
        }
    }

    private function validateColorBookData(object $data): void
    {
        $validator = Validator::make((array) $data, [
            'name'        => 'required|string',
            'data'        => 'required|array',
            'data.*.name' => 'required|string',
            'data.*.hex'  => 'nullable|string|regex:/^#[0-9A-F]{6}$/i',
        ]);

        if ($validator->fails()) {
            throw new Exception('Invalid color book data: ' . implode(', ', $validator->errors()->all()));
        }
    }

    private function seed(object $jsonColorBook): void
    {
        $slug = Str::slug($jsonColorBook->name);

        if (ColorBook::where('slug', $slug)->exists()) {
            $this->warn("Color Book '{$jsonColorBook->name}' already exists. Skipping...");

            return;
        }

        DB::transaction(function () use ($jsonColorBook) {
            $colorBook = $this->createColorBook(name: $jsonColorBook->name);

            $progressBar = $this->output->createProgressBar(count($jsonColorBook->data));
            $progressBar->setFormat(' %current%/%max% [%bar%] %percent:3s%% %message%');
            $progressBar->setMessage('Seeding colors...');
            $progressBar->start();

            foreach ($jsonColorBook->data as $color) {
                $this->createColor(colorBookId: $colorBook->id, value: $color);
                $progressBar->advance();
            }

            $progressBar->finish();
            $this->newLine();
        });

        $this->info("✓ {$jsonColorBook->name} seeded successfully.");
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
        Color::create([
            'color_book_id' => $colorBookId,
            'name'          => $value->name,
            'hex'           => $value?->hex,
            'lab'           => $value?->lab,
            'rgb'           => $value?->rgb,
            'cmyk'          => $value?->cmyk,
        ]);
    }
}
