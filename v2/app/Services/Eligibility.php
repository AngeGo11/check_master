<?php

namespace App\Services;

use App\Models\Enrollment;

class Eligibility
{
    public function evaluate(Enrollment $enrollment): array
    {
        $enrollment->loadMissing(['student', 'academicYear', 'internship', 'latestReview']);
        $student = $enrollment->student;
        $year = $enrollment->academicYear;
        $stage = $enrollment->internship;
        $paid = $enrollment->paidAmount();
        $context = ! $student->is_archived && ! $enrollment->cancelled_at && $year->status === 'current'
            && (int) $student->student_status_id !== 2 && (int) $enrollment->student_status_id !== 2;
        $criteria = [
            ['key' => 'tuition', 'label' => 'Scolarité à jour', 'met' => $paid >= $enrollment->amount_due,
                'detail' => max(0, $enrollment->amount_due - $paid).' FCFA restant à payer sur cette inscription.'],
            ['key' => 'level', 'label' => 'Niveau Master 2', 'met' => (int) $enrollment->study_level_id === 5 && (int) $student->study_level_id === 5,
                'detail' => 'Le niveau de l’inscription et celui du dossier étudiant doivent être Master 2.'],
            ['key' => 'internship', 'label' => 'Stage déclaré', 'met' => $stage !== null,
                'detail' => $stage ? 'Déclaration de stage enregistrée.' : 'Aucun stage déclaré pour cette inscription.'],
        ];
        $result = $context && collect($criteria)->every(fn ($criterion) => $criterion['met']) ? 'eligible' : 'ineligible';
        $fingerprint = hash('sha256', json_encode([
            $enrollment->revision, $student->revision, $year->revision, $context, $criteria,
        ], JSON_THROW_ON_ERROR));
        $review = $enrollment->latestReview;
        $current = $review && hash_equals($review->fingerprint, $fingerprint);

        return [
            'criteria' => $criteria, 'context_valid' => $context, 'computed_result' => $result,
            'fingerprint' => $fingerprint, 'status' => $result,
            'review' => $review ? [...$review->only('id', 'result', 'reason'), 'created_at' => $review->created_at->toIso8601String(), 'current' => (bool) $current] : null,
        ];
    }
}
