<?php

namespace Daikazu\Laratone\Commands;

use Daikazu\Laratone\Models\Color;
use Daikazu\Laratone\Models\ColorBook;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class SeedCommand extends Command
{
    protected $signature = 'laratone:seed {name?} {--F|file=}';

    protected $description = 'Seed a Laratone Color Books';

    public function handle()
    {
        $file = $this->option('file') ? base_path($this->option('file')) : null;

        if ($file === null) {
            if ($this->argument('name')) {
                $data = $this->getJSONFileData($this->argument('name'), true);
                $this->seed($data);
            } else {

                $allColorBooks = scandir(__DIR__.'/../../colorbooks/');

                array_map(function ($v) {

                    if (!in_array($v, ['.', '..'])) {
                        $this->seed($this->getJSONFileData(str_replace('.json', '', $v), true));
                    }

                }, $allColorBooks);
            }

        } else {
            $data = $this->getJSONFileData($file);
        }

        $this->info("<options=bold,reverse;fg=green> All Files Seeded </> 🤙\n");

    }

    private function getJSONFileData($file, $byName = false)
    {
        try {
            if ($byName) {
                return json_decode(file_get_contents(__DIR__.'/../../colorbooks/'.$file.'.json'));
            }

            return json_decode(file_get_contents($file));

        } catch (\Exception $e) {
            $this->error('Color Book Not Found!');
            exit();
        }
    }

    private function seed($jsonColorBook)
    {

        $slug = Str::slug($jsonColorBook->name);

        // check if slug exists
        if (ColorBook::where('slug', $slug)->exists()) {
            $this->error('Color Book slug '.$slug.' already exists!');
            return;
        }

        $colorBook = new ColorBook();
        $colorBook->name = $jsonColorBook->name;
        $colorBook->slug = Str::slug($jsonColorBook->name);
        $colorBook->save();

        array_map(function ($value) use ($colorBook) {
            $color = new Color();
            $color->color_book_id = $colorBook->id;
            $color->name = $value->name;
            $color->hex = $value->hex;
            $color->save();
        }, $jsonColorBook->data);

        $this->info('- '.$jsonColorBook->name.' seeded.');
    }


}
