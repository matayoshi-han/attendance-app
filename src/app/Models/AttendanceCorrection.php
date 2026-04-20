<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AttendanceCorrection extends Model
{
    use HasFactory;
    protected $fillable = [
        'attendance_id',
        'user_id',
        'updated_clock_in',
        'updated_clock_out',
        'remarks',
        'status'
    ];

    public function restCorrections()
    {
        return $this->hasMany(RestCorrection::class);
    }
}
