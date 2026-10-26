<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class RetakeEvent extends Model
{
    public $timestamps = false;
    protected function casts(): array { return ['score' => 'integer', 'created_at' => 'datetime']; }
}
