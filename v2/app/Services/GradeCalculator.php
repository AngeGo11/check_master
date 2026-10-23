<?php
namespace App\Services;
use Illuminate\Validation\ValidationException;
class GradeCalculator
{
    public static function score(mixed $value, string $field = 'score'): ?int
    {
        if ($value === null || $value === '') { return null; }
        $text = str_replace(',', '.', (string) $value);
        if (! preg_match('/^(?:0|[1-9]|1[0-9]|20)(?:\.[0-9]{1,2})?$/D', $text)) { throw ValidationException::withMessages([$field => 'Indiquez une note entre 0 et 20, avec deux décimales maximum.']); }
        $parts = explode('.', $text); $score = (int) $parts[0] * 100 + (int) str_pad($parts[1] ?? '', 2, '0');
        if ($score > 2000) { throw ValidationException::withMessages([$field => 'La note ne peut pas dépasser 20.']); } return $score;
    }
    public function subjects(array $units): array
    {
        $subjects = [];
        foreach ($units as $u) { if ($u['components']) { foreach ($u['components'] as $e) { $subjects['ecue:'.$e['code']] = $e; } } else { $subjects['ue:'.$u['code']] = $u; } }
        return $subjects;
    }
    public function warnings(array $units): array
    {
        $warnings = []; $credits = array_sum(array_column($units, 'credits'));
        if ($credits !== 30) { $warnings[] = 'Le semestre totalise '.$credits.' crédits, au lieu du repère de 30. Vérifiez ce total.'; }
        foreach ($units as $u) {
            if ($u['components'] && array_sum(array_column($u['components'], 'credits')) !== $u['credits']) { $warnings[] = 'UE '.$u['code'].' : la somme des crédits ECUE diffère des crédits UE.'; }
            if ($u['hours'] < $u['credits']) { $warnings[] = 'UE '.$u['code'].' : vérifiez les crédits et le volume horaire.'; }
            foreach ($u['components'] as $e) { if ($e['name'] === 'UML' && $u['code'] === '3609' && str_contains($u['name'], 'juridique')) { $warnings[] = 'Le fichier source rattache UML à Environnement juridique. Vérifiez cette association.'; } }
        }
        return $warnings;
    }
    public function calculate(array $units, array $marks): array
    {
        $rows = []; $complete = true; $weighted = 0; $credits = 0; $earned = 0; $groups = ['major' => ['weighted' => 0, 'credits' => 0, 'complete' => true], 'minor' => ['weighted' => 0, 'credits' => 0, 'complete' => true]];
        foreach ($units as $u) {
            $subjects = $u['components'] ?: [$u]; $subtotal = 0; $weights = 0; $ready = true; $details = [];
            foreach ($subjects as $s) {
                $key = ($u['components'] ? 'ecue:' : 'ue:').$s['code']; $mark = $marks[$key] ?? null;
                $ready = $ready && $mark !== null; $subtotal += ($mark ?? 0) * $s['credits']; $weights += $s['credits'];
                $details[] = [...$s, 'key' => $key, 'score' => $mark === null ? null : $mark / 100];
            }
            $average = $ready && $weights > 0 ? $subtotal / $weights : null; $credits += $u['credits']; $complete = $complete && $ready;
            $weighted += ($average ?? 0) * $u['credits']; if ($average !== null && $average >= 1000) { $earned += $u['credits']; }
            $group = $u['credits'] >= 4 ? 'major' : 'minor'; $groups[$group]['weighted'] += ($average ?? 0) * $u['credits']; $groups[$group]['credits'] += $u['credits']; $groups[$group]['complete'] = $groups[$group]['complete'] && $ready;
            $rows[] = ['code' => $u['code'], 'name' => $u['name'], 'credits' => $u['credits'], 'group' => $group, 'average' => $average === null ? null : round($average / 100, 2), 'subjects' => $details];
        }
        $averages = []; foreach ($groups as $key => $g) { $averages[$key] = $g['complete'] && $g['credits'] ? round($g['weighted'] / $g['credits'] / 100, 2) : null; }
        return ['units' => $rows, 'complete' => $complete && count($rows) > 0, 'average' => $complete && $credits ? round($weighted / $credits / 100, 2) : null, 'credits' => $credits, 'credits_at_least_ten' => $earned, 'groups' => $averages, 'formula' => 'credits_weighted_v1'];
    }
}
