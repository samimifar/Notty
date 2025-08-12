<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PublicEvent extends Model
{
    use HasFactory;

    protected $fillable = ['name','description','date'];
    protected $casts = ['date' => 'date'];
}
