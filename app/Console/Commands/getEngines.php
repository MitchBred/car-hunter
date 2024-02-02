<?php

namespace App\Console\Commands;

use App\Models\Engine;
use App\Models\Generation;
use Goutte\Client;
use Illuminate\Console\Command;

class getEngines extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'scrape:engines';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'scrape engines';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $client = new Client();

        $generations = Generation::all();

        $bar = $this->output->createProgressBar(count($generations));
        $bar->start();

        //ENGINES
        foreach ($generations as $generation) {
            $this->info('Get engine information');

            $generationRequest = $client->request('GET', $generation->slug);
            $getGenerationButton = $generationRequest->filter(".Chiptuning-options__item");

            $getGenerationText = $getGenerationButton->each(function ($item) {
                return $item->text();
            });

            $getGenerationUri = $generationRequest->filter('.Chiptuning-options__item')->each(function ($node) {
                $link = $node->link();
                return $link->getUri();
            });

            $generationsArray = array_combine($getGenerationText, $getGenerationUri);

            foreach ($generationsArray as $generationArray => $url) {
                Engine::updateOrCreate(
                    ['name' => $generationArray, 'model_id' => $generation->id],
                    ['brand_id' => $generation->brand_id, 'serie_id' => $generation->serie_id, 'slug' => $url, 'hash' => base64_encode($url)]
                );
            }
            $bar->advance();
        }
        $bar->finish();
    }
}
