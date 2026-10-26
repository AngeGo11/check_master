<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class PersonalArchive extends Model
{
    protected $attributes = ['revision' => 1, 'active' => true];
    protected function casts(): array { return ['revision' => 'integer', 'active' => 'boolean']; }
    public function reportVersion() { return $this->belongsTo(ReportVersion::class); }
    public function version() { return $this->belongsTo(WorkDocumentVersion::class, 'work_document_version_id'); }
    public function entry() { return $this->belongsTo(WorkDocumentEntry::class, 'work_document_entry_id'); }
}
