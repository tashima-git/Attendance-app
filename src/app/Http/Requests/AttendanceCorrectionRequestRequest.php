<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AttendanceCorrectionRequestRequest extends FormRequest
{
    /**
     * 認可
     */
    public function authorize()
    {
        return true; // ログイン済みなら許可
    }

    /**
     * バリデーションルール
     */
    public function rules()
    {
return [
    'work_date' => ['required', 'date'],
    'clock_in' => 'required|date_format:H:i|before:clock_out',
    'clock_out' => 'required|date_format:H:i|after:clock_in',
    'breaks.*.break_start' => 'nullable|date_format:H:i|after:clock_in|before:clock_out',
    'breaks.*.break_end' => 'nullable|date_format:H:i|after:breaks.*.break_start|before:clock_out',
    'remarks' => 'required|string|max:255',
];
    }

    /**
     * バリデーションメッセージ
     */
    public function messages()
    {
        return [
            'attendance_id.exists'   => '存在しない勤怠です。',
            'clock_in.required'      => '出勤時刻を入力してください。',
            'clock_in.before'        => '出勤時刻は退勤時刻より前にしてください。',
            'clock_out.required'     => '退勤時刻を入力してください。',
            'clock_out.after'        => '退勤は出勤より後の時刻にしてください。',
            'breaks.*.break_start.required_with' => '休憩開始を入力してください。',
            'breaks.*.break_start.after'         => '休憩開始は出勤時刻より後にしてください。',
            'breaks.*.break_start.before'        => '休憩開始は退勤時刻より前にしてください。',
            'breaks.*.break_end.required_with'   => '休憩終了を入力してください。',
            'breaks.*.break_end.after'           => '休憩終了は休憩開始より後にしてください。',
            'breaks.*.break_end.before'          => '休憩終了は退勤時刻より前にしてください。',
            'remarks.required'     => '備考を入力してください。',
            'remarks.max'          => '備考は255文字以内で入力してください。',
        ];
    }
}
