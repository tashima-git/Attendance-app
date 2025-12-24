<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CorrectionBreakTime extends Model
{
    use HasFactory;

    protected $table = 'correction_break_times';

    /**
     * Mass assignable attributes.
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
        // 'break_start' => 'datetime:H:i',
        // 'break_end'   => 'datetime:H:i',
    ];

    /**
     * この修正申請（AttendanceCorrectionRequest）に属する
     */
    public function correctionRequest()
    {
        return $this->belongsTo(
            AttendanceCorrectionRequest::class,
            'correction_request_id'
        );
    }

    /**
     * 利便性アクセサ：休憩時間（分）
     */
    public function getMinutesAttribute()
    {
        return $this->total_break_time !== null
            ? (int) $this->total_break_time
            : 0;
    }

    /**
     * 利便性アクセサ：休憩時間（H:i）
     */
    public function getHmsAttribute()
    {
        $minutes = $this->minutes;

        if ($minutes <= 0) {
            return null;
        }

        return gmdate('H:i', $minutes * 60);
    }
}
