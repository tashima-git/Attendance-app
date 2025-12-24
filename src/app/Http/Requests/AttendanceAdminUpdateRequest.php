<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AttendanceAdminUpdateRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'work_date' => ['required', 'date'],
            // 出勤 & 退勤
            'clock_in'  => ['nullable', 'date_format:H:i', 'before:clock_out'],
            'clock_out' => ['nullable', 'date_format:H:i', 'after:clock_in'],

            // 休憩（動的配列）
            'breaks.*.break_start' => [
                'nullable',
                'date_format:H:i',
                'after_or_equal:clock_in',
                'before_or_equal:clock_out',
            ],
            'breaks.*.break_end' => [
                'nullable',
                'date_format:H:i',
                'after_or_equal:breaks.*.break_start',
                'before_or_equal:clock_out',
            ],

            // 備考
            'remarks' => ['required', 'string', 'max:255'],
        ];
    }

    public function messages()
    {
        return [
            // 出退勤チェック
            'clock_in.before'  => '出勤時間もしくは退勤時間が不適切な値です',
            'clock_out.after'  => '出勤時間もしくは退勤時間が不適切な値です',
            'clock_in.date_format' => '出勤時刻の形式が不正です。',
            'clock_out.date_format' => '退勤時刻の形式が不正です。',

            // 休憩開始
            'breaks.*.break_start.after_or_equal'  => '休憩時間が不適切な値です',
            'breaks.*.break_start.before_or_equal' => '休憩時間が不適切な値です',
            'breaks.*.break_start.date_format' => '休憩開始の時刻形式が不正です。',

            // 休憩終了
            'breaks.*.break_end.after_or_equal'  => '休憩時間もしくは退勤時間が不適切な値です',
            'breaks.*.break_end.before_or_equal' => '休憩時間もしくは退勤時間が不適切な値です',
            'breaks.*.break_end.date_format' => '休憩終了の時刻形式が不正です。',

            // 備考
            'remarks.required' => '備考を記入してください',
            'remarks.max'      => '備考は255文字以内で入力してください',
        ];
    }
}
