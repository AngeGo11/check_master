<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class StaffPhoto extends Model
{
    public $timestamps = false;
    protected function casts(): array { return ['created_at' => 'datetime', 'withdrawn_at' => 'datetime', 'size' => 'integer']; }
}
