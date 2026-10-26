<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class WorkDocument extends Model
{
    protected $attributes = ['revision' => 1];
    protected function casts(): array { return ['revision' => 'integer']; }
    public function owner() { return $this->belongsTo(User::class, 'owner_user_id'); }
    public function student() { return $this->belongsTo(Student::class); }
    public function versions() { return $this->hasMany(WorkDocumentVersion::class); }
    public function latestVersion() { return $this->hasOne(WorkDocumentVersion::class)->latestOfMany(); }
}
