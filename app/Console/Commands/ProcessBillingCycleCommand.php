<?php

namespace App\Console\Commands;

use App\Jobs\GenerateCycleInvoicesJob;
use Illuminate\Console\Command;

class ProcessBillingCycleCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'billing:process-cycle';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Dispatch the billing cycle invoice generation job';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        GenerateCycleInvoicesJob::dispatch();
        $this->info('Billing cycle job dispatched successfully.');
    }
}
