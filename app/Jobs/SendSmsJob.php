<?php

namespace App\Jobs;

use App\Models\Absence;
use App\Services\SmsService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SendSmsJob implements ShouldQueue
{
    use Queueable;

    public function __construct(public string $type, public int $absenceId)
    {
    }

    public function handle(SmsService $sms): void
    {
        $absence = Absence::with(['etudiant', 'sessionAppel.module'])->findOrFail($this->absenceId);

        match ($this->type) {
            'absence_detectee' => $sms->sendAbsenceAlert($absence->etudiant, $absence->sessionAppel),
            'rappel_justification' => $sms->sendReminder($absence),
            'justification_acceptee' => $sms->sendJustificationResult($absence, true),
            'justification_rejetee' => $sms->sendJustificationResult($absence, false),
            default => null,
        };
    }
}
