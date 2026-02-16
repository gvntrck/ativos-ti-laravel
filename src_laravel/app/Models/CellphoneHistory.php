<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CellphoneHistory extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'cellphone_id',
        'event_type',
        'description',
        'photos',
        'user_id',
        'created_at'
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function cellphone()
    {
        return $this->belongsTo(Cellphone::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
