<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ComputerHistory extends Model
{
    use HasFactory;

    // Disables updated_at since history is append-only usually, but migration has it. 
    // If migration has only created_at, update this. Migration had params.
    // Migration: table->timestamp('created_at')->useCurrent(); No updated_at.
    public $timestamps = false;

    protected $fillable = [
        'computer_id',
        'event_type',
        'description',
        'photos',
        'user_id',
        'created_at'
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function computer()
    {
        return $this->belongsTo(Computer::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
