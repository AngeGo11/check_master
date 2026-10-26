<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class DocumentEvent extends Model
{
    public $timestamps = false;
    protected function casts(): array { return ['metadata' => 'array', 'created_at' => 'datetime']; }
}
