<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string $total_rest_time
 * @property string $total_work_time
 */

class Attendance extends Model
{
    use HasFactory;
    protected $fillable = [
        'user_id',
        'work_date',
        'clock_in',
        'clock_out',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // 1つの勤務に対して休憩は複数（hasMany）
    public function rests()
    {
        return $this->hasMany(Rest::class);
    }

}
