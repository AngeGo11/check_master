<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class WorkDocumentEntry extends Model
{
    public $timestamps = false;
    protected function casts(): array { return ['snapshot' => 'array']; }
    public function version() { return $this->belongsTo(WorkDocumentVersion::class, 'work_document_version_id'); }
    public function reportVersion() { return $this->belongsTo(ReportVersion::class); }
    public function round() { return $this->belongsTo(ReportRound::class, 'report_round_id'); }
}
