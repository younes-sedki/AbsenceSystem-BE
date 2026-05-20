<?php

namespace App\Console\Commands;

use App\Models\Absence;
use Illuminate\Console\Command;

class AutoRejeterAbsences extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'absences:auto-rejeter';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reject non justified absences whose deadline has expired.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $count = Absence::where('statut', 'non_justifiee')
            ->where('date_limite', '<=', now())
            ->update(['statut' => 'rejetee']);

        $this->info("{$count} absence(s) rejetee(s).");

        return self::SUCCESS;
    }
}
