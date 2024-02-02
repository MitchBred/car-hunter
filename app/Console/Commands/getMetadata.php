<?php

namespace App\Console\Commands;

use App\Models\Engine;
use App\Models\Metadata;
use App\Models\Stages;
use Exception;
use Goutte\Client;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class getMetadata extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'scrape:metadata';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'scrape meta data';

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

        $engines = Engine::all();

        $bar = $this->output->createProgressBar(count($engines));
        $bar->start();

        //MODELS
        foreach ($engines as $engine) {
            try {

                $engineRequest = $client->request('GET', $engine->slug);

                //HP SPECIFICATIONS
                $getHp = $engineRequest->filter(".ChiptuningComparison__col .ChiptuningComparison__number");

                $getHPStats = $getHp->each(function ($item) {
                    return $item->text();
                });

                $nameArray = ['hp_ori', 'nm_ori', 'hp_tuning', 'nm_tuning', 'hp_diff', 'nm_diff'];
                $getHPArray = array_combine($nameArray, $getHPStats);

                Stages::updateOrCreate(
                    ['engine_id' => $engine->id],
                    ['data' => $getHPArray]
                );

                //ENGINE SPECIFICATIONS
                $countEngineTd = $engineRequest->filter(".Chiptuning-specs td:nth-child(2)");

                $getEngineTdText = $countEngineTd->each(function ($item) {
                    return strtolower(str_replace(" ", "_", $item->text()));
                });

                $countEngineTr = $engineRequest->filter(".Chiptuning-specs td:nth-child(1)");

                $getEngineTrText = $countEngineTr->each(function ($item) {
                    return strtolower(str_replace(" ", "_", $item->text()));
                });

                $getTableArray = array_combine($getEngineTrText, $getEngineTdText);

                foreach ($getTableArray as $table => $value) {
                    Metadata::updateOrCreate(
                        ['morph_id' => $engine->id, 'key' => $table],
                        ['morph_type' => 'engine', 'value' => $value]
                    );
                }

                //READ METHODS
                $readMethodsSepcs = $engineRequest->filter(".Chiptuning-extra__readmethods span");
                $getReadMethods = $readMethodsSepcs->each(function ($item) {
                    return strtolower(str_replace(" ", "_", $item->text()));
                });

                foreach ($getReadMethods as $readMethod => $value) {
                    Metadata::updateOrCreate(
                        ['morph_id' => $engine->id, 'value' => $value],
                        ['key' => 'read_method', 'morph_type' => 'engine']
                    );
                }

                //ADDITINONAL OPTIONS
                $additionalOptions = $engineRequest->filter(".Chiptuning-extra__options span");
                $getAdditionalOptions = $additionalOptions->each(function ($item) {
                    return strtolower(str_replace(" ", "_", $item->text()));
                });

                foreach ($getAdditionalOptions as $additionalOption => $value) {
                    Metadata::updateOrCreate(
                        ['morph_id' => $engine->id, 'value' => $value],
                        ['key' => 'tunning_option', 'morph_type' => 'engine']
                    );
                }

                $bar->advance();
            } catch (Exception $exception) {
                Log::info("Engine scraping failed, because there is no information available {$engine->id}");
            }

            $bar->finish();
        }
    }
}
