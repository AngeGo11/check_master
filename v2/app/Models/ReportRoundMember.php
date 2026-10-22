<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ReportRoundMember extends Model
{
    public $timestamps = false;
    public function vote(): HasOne { return $this->hasOne(ReportVote::class); }
}
