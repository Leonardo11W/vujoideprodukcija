<?php

namespace Modules\Frontend\Console\Commands;

use Illuminate\Console\Command;

class FrontendCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:FrontendCommand';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Frontend Command description';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        return Command::SUCCESS;
    }
}
