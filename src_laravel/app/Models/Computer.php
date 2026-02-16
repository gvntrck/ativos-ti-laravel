<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Computer extends Model
{
    use HasFactory;

    protected $fillable = [
        'type',
        'hostname',
        'status',
        'deleted',
        'user_name',
        'location',
        'property',
        'specs',
        'notes',
        'photo_url',
    ];

    protected $casts = [
        'deleted' => 'boolean',
    ];

    public function history()
    {
        return $this->hasMany(ComputerHistory::class);
    }
}
