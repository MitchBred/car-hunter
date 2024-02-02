<?php

namespace App\Console\Commands;

use App\Models\Brand;
use App\Models\Serie;
use Goutte\Client;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class getSeries extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'scrape:series';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'scrape series';

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
     * @return void
     */
    public function handle()
    {
        $client = new Client();

        $brands = Brand::all();

        $bar = $this->output->createProgressBar(count($brands));
        $bar->start();

        //SERIES
        foreach ($brands as $brand) {
            $this->info('Get serie information');

            $getSerieSlug = $client->request('GET', $brand->slug);
            $getSerieButton = $getSerieSlug->filter(".Chiptuning-options__item");

            $getSerieText = $getSerieButton->each(function ($item) {
                return $item->text();
            });

            $getSerieUri = $getSerieSlug->filter('.Chiptuning-options__item')->each(function ($node) {
                $link = $node->link();
                return $link->getUri();
            });

            $series = array_combine($getSerieText, $getSerieUri);

            foreach ($series as $serie => $url) {
                Serie::updateOrCreate(
                    ['name' => $serie],
                    ['brand_id' => $brand->id, 'slug' => $url, 'hash' => base64_encode($url)]
                );
            }

            $bar->advance();
        }
        $bar->finish();
    }
}
