<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Group extends Model
{
    use HasFactory;

    protected $fillable = ['admin_id','event_id','tag','description'];

    public function admin()
    {
        return $this->belongsTo(User::class, 'admin_id');
    }

    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    public static function createForEvent(Event $event, User $admin, $tag = null, $description = null)
    {
        return self::create([
            'admin_id'    => $admin->id,
            'event_id'    => $event->id,
            'tag'         => $tag,
            'description' => $description,
        ]);
    }

    public function members()
    {
        return $this->belongsToMany(User::class, 'group_members')->withTimestamps();
    }

    public function invites()
    {
        return $this->hasMany(GroupInvite::class);
    }
}
