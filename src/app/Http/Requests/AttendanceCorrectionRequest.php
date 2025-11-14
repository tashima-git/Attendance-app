<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AttendanceCorrectionRequest extends FormRequest
{
    public function authorize()
    {
        return true; // ログイン済みなら許可
    }

    public function rules()
    {
        return [
            'attendance_id' => 'required|exists:attendances,id',
            'clock_in' => 'required|date_format:H:i|before:clock_out',
            'break_start' => 'required|date_format:H:i|after:clock_in|before:clock_out',
            'break_end' => 'required|date_format:H:i|after:break_start|before:clock_out',
            'clock_out' => 'required|date_format:H:i|after:clock_in',
            'remarks' => 'required|string|max:255',
        ];
    }

    public function messages()
    {
        return [
            'clock_in.required' => '出勤時刻を入力してください。',
            'clock_in.before' => '出勤時刻は退勤時刻より前にしてください。',
            'break_start.after' => '休憩開始は出勤より後の時刻にしてください。',
            'break_end.after' => '休憩終了は休憩開始より後の時刻にしてください。',
            'clock_out.after' => '退勤は出勤より後の時刻にしてください。',
            'remarks.required' => '備考を入力してください。',
        ];
    }
}
