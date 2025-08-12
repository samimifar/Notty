<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'phone_number',
        'role',
        'birth_date',
        'gender',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'birth_date' => 'date',
    ];

    public function contacts()
    {
        return $this->hasMany(Contact::class);
    }

    public function adminGroups()
    {
        return $this->hasMany(Group::class, 'admin_id');
    }

    public function groups()
    {
        return $this->belongsToMany(Group::class, 'group_members')->withTimestamps();
    }

    public function events()
    {
        return $this->hasMany(Event::class);
    }

    public function groupEvents()
    {
        return $this->hasManyThrough(Event::class, Group::class, 'id', 'group_id', null, 'event_id');
    }

    public function receivedInvites()
    {
        return $this->hasMany(GroupInvite::class, 'receiver_id');
    }
}
