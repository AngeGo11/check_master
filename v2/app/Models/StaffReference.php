<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class StaffReference extends Model
{
    public $timestamps = false;
    protected $attributes = ['is_active' => true, 'revision' => 1];
    protected function casts(): array { return ['is_active' => 'boolean', 'revision' => 'integer', 'legacy_id' => 'integer']; }
}
