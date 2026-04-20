<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Rest extends Model
{
    use HasFactory;
    protected $fillable = [
        'attendance_id',
        'break_start',
        'break_end',
    ];

    // 勤務（Attendance）との紐付け
    public function attendance()
    {
        return $this->belongsTo(Attendance::class);
    }
}
