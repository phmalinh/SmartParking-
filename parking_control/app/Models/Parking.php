<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Parking extends Model
{
    use HasFactory;
    protected $dates = [     
        'created_at',
        'updated_at'
        ];
   // public $timestamps = false;
    protected $fillable = [
        'car_owner','plate_number', 'action'
    ];
    protected $primaryKey = 'id';
    protected $table = 'parking_lots';
}