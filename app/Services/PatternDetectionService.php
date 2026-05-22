<?php

namespace App\Services;

use App\Models\Absence;
use App\Models\Classe;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class PatternDetectionService
{
    /**
     * French day-of-week names indexed by Carbon dayOfWeek (0=Sunday).
     */
    private const JOURS_FR = [
        0 => 'Dimanche',
        1 => 'Lundi',
        2 => 'Mardi',
        3 => 'Mercredi',
        4 => 'Jeudi',
        5 => 'Vendredi',
        6 => 'Samedi',
    ];

    /**
     * Run all 4 detection rules on every student and return only flagged students.
     */
    public function detectAll(): array
    {
        $students = User::where('role', 'etudiant')
            ->whereNotNull('classe_id')
            ->with(['classe', 'absences.sessionAppel.module', 'absences.sessionAppel.classe', 'absences.justification'])
            ->get();

        $results = [];

        foreach ($students as $student) {
            $patterns = [];

            // Collect non-justified absences (statut = non_justifiee or rejetee)
            $nonJustifiedAbsences = $student->absences->filter(function (Absence $absence) {
                return in_array($absence->statut, ['non_justifiee', 'rejetee']);
            });

            // Rule 1 — Same day of week
            $rule1 = $this->detectSameDayOfWeek($nonJustifiedAbsences);
            $patterns = array_merge($patterns, $rule1);

            // Rule 2 — Same module
            $rule2 = $this->detectSameModule($nonJustifiedAbsences);
            $patterns = array_merge($patterns, $rule2);

            // Rule 3 — Consecutive absences (uses all absences, checks if last 3 are non-justified)
            $rule3 = $this->detectConsecutiveAbsences($student->absences);
            $patterns = array_merge($patterns, $rule3);

            // Rule 4 — High absence rate
            $rule4 = $this->detectHighAbsenceRate($student);
            $patterns = array_merge($patterns, $rule4);

            if (count($patterns) === 0) {
                continue;
            }

            // Count how many distinct rules flagged this student
            $rulesTriggered = (count($rule1) > 0 ? 1 : 0)
                + (count($rule2) > 0 ? 1 : 0)
                + (count($rule3) > 0 ? 1 : 0)
                + (count($rule4) > 0 ? 1 : 0);

            $severity = match (true) {
                $rulesTriggered >= 3 => 'eleve',
                $rulesTriggered === 2 => 'moyen',
                default => 'faible',
            };

            $results[] = [
                'etudiant_id' => $student->id,
                'nom' => $student->nom,
                'prenom' => $student->prenom,
                'classe' => $student->classe?->code ?? '-',
                'severite' => $severity,
                'patterns' => $patterns,
            ];
        }

        // Sort by severity: eleve first, then moyen, then faible
        usort($results, function ($a, $b) {
            $order = ['eleve' => 0, 'moyen' => 1, 'faible' => 2];
            return ($order[$a['severite']] ?? 3) <=> ($order[$b['severite']] ?? 3);
        });

        return $results;
    }

    /**
     * Rule 1 — Same day of week: flag if any day appears 2+ times.
     */
    private function detectSameDayOfWeek(Collection $absences): array
    {
        $patterns = [];

        $grouped = $absences->groupBy(function (Absence $absence) {
            $date = $absence->sessionAppel?->date;
            if (!$date) {
                return null;
            }
            return Carbon::parse($date)->dayOfWeek;
        })->forget(null);

        foreach ($grouped as $dayOfWeek => $group) {
            $count = $group->count();
            if ($count >= 2) {
                $jour = self::JOURS_FR[$dayOfWeek] ?? $dayOfWeek;
                $patterns[] = "Absent {$count} fois le {$jour}";
            }
        }

        return $patterns;
    }

    /**
     * Rule 2 — Same module: flag if any module appears 3+ times.
     */
    private function detectSameModule(Collection $absences): array
    {
        $patterns = [];

        $grouped = $absences->groupBy(function (Absence $absence) {
            return $absence->sessionAppel?->module_id;
        })->forget(null);

        foreach ($grouped as $moduleId => $group) {
            $count = $group->count();
            if ($count >= 3) {
                $module = $group->first()->sessionAppel->module;
                $code = $module?->code ?? '?';
                $intitule = $module?->intitule ?? '?';
                $patterns[] = "Absent {$count} fois en {$code} — {$intitule}";
            }
        }

        return $patterns;
    }

    /**
     * Rule 3 — Consecutive absences: if the last 3 absences are all non-justified.
     */
    private function detectConsecutiveAbsences(Collection $allAbsences): array
    {
        $sorted = $allAbsences->sortByDesc(function (Absence $absence) {
            return $absence->sessionAppel?->date?->format('Y-m-d') . ' ' . ($absence->sessionAppel?->heure_debut ?? '00:00');
        })->values();

        if ($sorted->count() < 3) {
            return [];
        }

        $lastThree = $sorted->take(3);

        $allNonJustified = $lastThree->every(function (Absence $absence) {
            return in_array($absence->statut, ['non_justifiee', 'rejetee']);
        });

        if ($allNonJustified) {
            return ['Absent lors des 3 dernières séances consécutives'];
        }

        return [];
    }

    /**
     * Rule 4 — High absence rate: (total_absences / total_sessions_in_class) * 100 > 30%.
     */
    private function detectHighAbsenceRate(User $student): array
    {
        if (!$student->classe_id) {
            return [];
        }

        $totalSessions = $student->classe->sessionsAppel()->count();

        if ($totalSessions === 0) {
            return [];
        }

        $totalAbsences = $student->absences->count();
        $rate = round(($totalAbsences / $totalSessions) * 100, 1);

        if ($rate > 30) {
            $classeCode = $student->classe->code;
            return ["Taux d'absence de {$rate}% dans {$classeCode}"];
        }

        return [];
    }
}
