<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Enrollment extends Model
{
    protected $attributes = ['revision' => 1];
    protected function casts(): array { return ['amount_due' => 'integer', 'revision' => 'integer', 'enrolled_on' => 'date', 'cancelled_at' => 'datetime']; }
    public function student(): BelongsTo { return $this->belongsTo(Student::class); }
    public function academicYear(): BelongsTo { return $this->belongsTo(AcademicYear::class); }
    public function studyLevel(): BelongsTo { return $this->belongsTo(StudyLevel::class); }
    public function payments(): HasMany { return $this->hasMany(TuitionPayment::class); }
    public function internship(): HasOne { return $this->hasOne(EnrollmentInternship::class); }
    public function reviews(): HasMany { return $this->hasMany(EligibilityReview::class); }
    public function latestReview(): HasOne { return $this->hasOne(EligibilityReview::class)->latestOfMany(); }

    public function scopeWithPaidAmount(Builder $query): void
    {
        $query->withSum(['payments as paid_amount' => fn ($q) => $q->whereNull('voided_at')], 'amount');
    }

    public function paidAmount(): int
    {
        return array_key_exists('paid_amount', $this->getAttributes()) ? (int) $this->getAttribute('paid_amount')
            : (int) $this->payments()->whereNull('voided_at')->sum('amount');
    }

    public function paymentStatus(): string
    {
        $paid = $this->paidAmount();
        return $paid >= $this->amount_due ? 'paid' : ($paid > 0 ? 'partial' : 'unpaid');
    }
}
