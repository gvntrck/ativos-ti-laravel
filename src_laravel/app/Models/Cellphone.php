<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cellphone extends Model
{
    use HasFactory;

    protected $fillable = [
        'asset_code',
        'phone_number',
        'status',
        'deleted',
        'user_name',
        'brand_model',
        'department',
        'property',
        'notes',
        'photo_url',
    ];

    protected $casts = [
        'deleted' => 'boolean',
    ];

    public function history()
    {
        return $this->hasMany(CellphoneHistory::class);
    }
}
