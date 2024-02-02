<?php

namespace App\Console\Commands;

use App\Models\BMWNotifier;
use App\Models\Brand;
use App\Models\Engine;
use App\Models\Generation;
use App\Models\Serie;
use Goutte\Client;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

class getBrands extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sync:cars';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'scrape brands, series, models, engines';

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
     */
    public function handle()
    {
        $client = new Client();

        $crawler = $client->request('GET', 'https://www.dyno-chiptuningfiles.com/tuning-file/');

        $brands = Brand::all();

        $bar = $this->output->createProgressBar(count($brands));
        $bar->start();

        //BRANDS->SERIES->MODELS->ENGINES
        $countBrands = $crawler->filter(".Chiptuning-brands__item")->count();

        if ($this->confirm('Scrape all brands. Do you wish to continue?')) {
            for ($i = 1; $i <= $countBrands; $i++) {
                $linkCrawler = $crawler->filter(".Chiptuning-brands__item:nth-child(" . $i . ")");
                $link = $linkCrawler->link();
                $getBrandUri = $link->getUri();

                //BRANDS
                $modelRequest = $client->request('GET', $getBrandUri);
                $getModelTitle = $modelRequest->filter('h1')->text();
                $getBrandName = substr($getModelTitle, 13);

                Brand::updateOrCreate(
                    ['name' => $getBrandName],
                    ['type' => 'cars', 'slug' => $getBrandUri, 'hash' => base64_encode($getBrandUri)]
                );

                $bar->advance();
            }
        }

        $this->info('Scrape serie information');
        Artisan::call('scrape:series');
//
//        $this->info('Scrape model information');
//        Artisan::call('scrape:models');
//
//        $this->info('Scrape engine information');
//        Artisan::call('scrape:engines');
//
//        $this->info('Scrape meta data information');
//        Artisan::call('scrape:metadata');
//
//        $this->info('The synchronization was successful!');

        $bar->finish();
    }
}
