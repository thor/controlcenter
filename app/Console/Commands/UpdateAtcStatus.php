<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class UpdateAtcStatus extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'update:atc:status';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update ATC hours and active status';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        Artisan::call('update:atc:hours');
        Artisan::call('update:atc:activity');

        return Command::SUCCESS;
    }
}
