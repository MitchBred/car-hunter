<?php

namespace App\Console\Commands;

use App\Models\Brand;
use App\Models\Generation;
use App\Models\Serie;
use Goutte\Client;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class getModels extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'scrape:models';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'scrape models';

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

        $series = Serie::all();

        $bar = $this->output->createProgressBar(count($series));
        $bar->start();

        //MODELS
        foreach ($series as $serie) {
            $this->info('Get model information');

            $generationRequest = $client->request('GET', $serie->slug);

            $getGenerationButton = $generationRequest->filter(".Chiptuning-options__item");

            $getGenerationText = $getGenerationButton->each(function ($item) {
                return $item->text();
            });

            $getGenerationUri = $generationRequest->filter('.Chiptuning-options__item')->each(function ($node) {
                $link = $node->link();
                return $link->getUri();
            });

            $generations = array_combine($getGenerationText, $getGenerationUri);

            foreach ($generations as $generation => $url) {
                Generation::updateOrCreate(
                    ['name' => $generation, 'serie_id' => $serie->id],
                    ['brand_id' => $serie->brand_id, 'slug' => $url, 'hash' => base64_encode($url)]
                );
            }
            $bar->advance();
        }
        $bar->finish();
    }
}
