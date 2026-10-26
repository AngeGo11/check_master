<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class WorkDocumentVersion extends Model
{
    public $timestamps = false;
    protected function casts(): array { return ['number' => 'integer', 'sections' => 'array', 'size' => 'integer', 'created_at' => 'datetime', 'published_at' => 'datetime']; }
    public function document() { return $this->belongsTo(WorkDocument::class, 'work_document_id'); }
    public function entries() { return $this->hasMany(WorkDocumentEntry::class); }
}
