<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Vl;

class MiscellaneousCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'misc:do';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Do whatever is required.';

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
     * @return mixed
     */
    public function handle()
    {
        $vl = new Vl;
        $output = $vl->update_art();
        $this->info($output);
    }
}
