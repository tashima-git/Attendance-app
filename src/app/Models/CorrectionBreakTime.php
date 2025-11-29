<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CorrectionBreakTime extends Model
{
    use HasFactory;

    protected $table = 'correction_break_times';

    /**
     * mass assignable
     *
     * @var array
     */
    protected $fillable = [
        'correction_request_id',
        'break_start',
        'break_end',
        'total_break_time',
    ];

    protected $casts = [
        'total_break_time' => 'integer',
        // 'break_start' and 'break_end' are kept as strings by default (TIME),
        // you can uncomment the lines below if you want Carbon instances (and your DB returns them correctly)
        // 'break_start' => 'datetime:H:i',
        // 'break_end'   => 'datetime:H:i',
    ];

    /**
     * この修正申請（AttendanceCorrectionRequest）に属する
     */
    public function correctionRequest()
    {
        return $this->belongsTo(AttendanceCorrectionRequest::class, 'correction_request_id');
    }

    /**
     * 利便性メソッド：休憩時間（分）を返す
     */
    public function getMinutesAttribute()
    {
        return $this->total_break_time !== null ? (int) $this->total_break_time : 0;
    }

    /**
     * 利便性メソッド：休憩時間を H:i 形式で返す
     */
    public function getHmsAttribute()
    {
        $minutes = $this->minutes;
        if ($minutes <= 0) {
            return null;
        }

        $seconds = $minutes * 60;
        return gmdate('H:i', $seconds);
    }
}
