<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TuitionPayment extends Model
{
    protected function casts(): array { return ['amount' => 'integer', 'paid_on' => 'date', 'voided_at' => 'datetime']; }
    public function enrollment(): BelongsTo { return $this->belongsTo(Enrollment::class); }
}
