<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Attendance extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'work_date',
        'clock_in',
        'clock_out',
        'total_work_time',
        'remarks',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function breakTimes()
    {
        return $this->hasMany(BreakTime::class);
    }

    public function correctionRequests()
    {
        return $this->hasMany(AttendanceCorrectionRequest::class);
    }

    public function getBreakHmAttribute()
{
    if ($this->breakTimes->isEmpty()) {
        return "00:00";
    }

    $total = 0;
    foreach ($this->breakTimes as $bt) {
        if ($bt->break_start && $bt->break_end) {
            $total += strtotime($bt->break_end) - strtotime($bt->break_start);
        }
    }

    return gmdate('H:i', $total);
}

public function getTotalWorkTimeHmAttribute()
{
    if (!$this->clock_in || !$this->clock_out) {
        return "00:00";
    }

    $work = strtotime($this->clock_out) - strtotime($this->clock_in);

    // 休憩時間を引く
    $break = 0;
    foreach ($this->breakTimes as $bt) {
        if ($bt->break_start && $bt->break_end) {
            $break += strtotime($bt->break_end) - strtotime($bt->break_start);
        }
    }

    $total = max(0, $work - $break);

    return gmdate('H:i', $total);
}

}
