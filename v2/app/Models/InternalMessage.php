<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
class InternalMessage extends Model
{
    public const PRIORITIES = ['low', 'normal', 'high', 'urgent'];
    public const CATEGORIES = ['general', 'memoire', 'commission', 'evaluation', 'deadline'];
    protected function casts(): array { return ['draft_recipients' => 'array', 'sent_at' => 'datetime', 'revision' => 'integer', 'sender_revision' => 'integer']; }
    public function recipients(): HasMany { return $this->hasMany(MessageRecipient::class); }
}
