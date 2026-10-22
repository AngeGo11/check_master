<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class MessageRecipient extends Model
{
    public $timestamps = false;
    protected function casts(): array { return ['read_at' => 'datetime', 'revision' => 'integer']; }
}
