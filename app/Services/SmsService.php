<?php

namespace App\Services;

use App\Models\Absence;
use App\Models\SessionAppel;
use App\Models\SmsLog;
use App\Models\User;
use Throwable;
use Twilio\Rest\Client;

class SmsService
{
    public function sendAbsenceAlert(User $etudiant, SessionAppel $session): void
    {
        $message = "Absence detectee le {$session->date->toDateString()} en {$session->module->code}. Justification sous 7 jours.";
        $this->send($etudiant, $message, 'absence_detectee');
    }

    public function sendReminder(Absence $absence): void
    {
        $message = "Rappel: votre justification d'absence doit etre deposee avant {$absence->date_limite->toDateString()}.";
        $this->send($absence->etudiant, $message, 'rappel_justification');
    }

    public function sendJustificationResult(Absence $absence, bool $accepted): void
    {
        $message = $accepted ? 'Votre justification a ete acceptee.' : 'Votre justification a ete rejetee.';
        $this->send($absence->etudiant, $message, $accepted ? 'justification_acceptee' : 'justification_rejetee');
    }

    public function sendParentReminder(Absence $absence): SmsLog
    {
        $absence->loadMissing(['etudiant', 'sessionAppel.module']);

        $student = $absence->etudiant;
        $date = $absence->sessionAppel?->date?->toDateString() ?? 'inconnue';
        $module = $absence->sessionAppel?->module?->code ?? 'module inconnu';
        $message = "Rappel: absence de {$student?->prenom} {$student?->nom} le {$date} en {$module}. Merci de justifier l'absence.";

        $parentPhone = $student?->parent_telephone ?? $student?->telephone;
        $recipientName = $student
            ? trim("Parent de {$student->prenom} {$student->nom}")
            : 'Parent';

        return $this->sendToPhone($parentPhone, $recipientName, $message, 'rappel_justification');
    }

    private function send(User $recipient, string $message, string $type): SmsLog
    {
        return $this->sendToPhone(
            $recipient->telephone,
            trim("{$recipient->prenom} {$recipient->nom}"),
            $message,
            $type
        );
    }

    private function sendToPhone(?string $phone, string $recipientName, string $message, string $type): SmsLog
    {
        $status = 'echoue';

        try {
            if ($phone && config('services.twilio.sid') && config('services.twilio.token') && config('services.twilio.from')) {
                (new Client(config('services.twilio.sid'), config('services.twilio.token')))
                    ->messages
                    ->create($phone, ['from' => config('services.twilio.from'), 'body' => $message]);
                $status = 'envoye';
            }
        } catch (Throwable) {
            $status = 'echoue';
        }

        return SmsLog::create([
            'destinataire_nom' => $recipientName,
            'telephone' => $phone,
            'message' => $message,
            'type' => $type,
            'statut' => $status,
            'sent_at' => now(),
        ]);
    }
}
