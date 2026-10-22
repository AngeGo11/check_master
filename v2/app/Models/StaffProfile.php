<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\{BelongsTo, BelongsToMany, HasMany, HasOne};
class StaffProfile extends Model
{
    public const FIELDS = ['last_name', 'first_name', 'email', 'phone', 'gender', 'date_of_birth', 'hired_on', 'position'];
    protected $attributes = ['is_archived' => false, 'revision' => 1];
    protected function casts(): array { return ['date_of_birth' => 'immutable_date', 'hired_on' => 'immutable_date', 'is_archived' => 'boolean', 'revision' => 'integer']; }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function assignments(): HasMany { return $this->hasMany(StaffAssignment::class); }
    public function currentAssignments(): HasMany { return $this->assignments()->where('current_slot', 1); }
    public function specialties(): BelongsToMany { return $this->belongsToMany(StaffReference::class, 'staff_specialties'); }
    public function photos(): HasMany { return $this->hasMany(StaffPhoto::class); }
    public function currentPhoto(): HasOne { return $this->hasOne(StaffPhoto::class)->where('current_slot', 1); }
}
