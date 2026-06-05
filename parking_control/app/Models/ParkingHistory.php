<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ParkingHistory extends Model
{
    use HasFactory;
    
    protected $table = 'parking_history';
    
    protected $fillable = [
        'plate_number',
        'car_owner',
        'action',
        'action_time',
        'notes'
    ];
    
    protected $casts = [
        'action_time' => 'datetime'
    ];
    
    protected $dates = [
        'created_at',
        'updated_at',
        'action_time'
    ];
}
