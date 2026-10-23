<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class GradePublication extends Model
{
    public $timestamps = false;
    protected function casts(): array { return ['result' => 'array', 'created_at' => 'datetime', 'number' => 'integer']; }
}
