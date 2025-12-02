<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AttendanceCorrectionRequestRequest extends FormRequest
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
            'clock_in'  => ['required', 'date_format:H:i', 'before:clock_out'],
            'clock_out' => ['required', 'date_format:H:i', 'after:clock_in'],

            // 休憩（動的配列）
            'breaks.*.break_start' => [
                'nullable',
                'date_format:H:i',
                'after:clock_in',
                'before:clock_out'
            ],
            'breaks.*.break_end' => [
                'nullable',
                'date_format:H:i',
                'after:breaks.*.break_start',
                'before:clock_out'
            ],

            // 備考
            'remarks' => ['required', 'string', 'max:255'],
        ];
    }

    public function messages()
    {
        return [
            // 日付
            'work_date.required' => '日付が不正です。',
            'work_date.date' => '日付が不正です。',

            // 出退勤（要件に合わせる）
            'clock_in.before'  => '出勤時間もしくは退勤時間が不適切な値です',
            'clock_out.after'  => '出勤時間もしくは退勤時間が不適切な値です',

            // 出退勤必須や形式は既存
            'clock_in.required' => '出勤時刻を入力してください。',
            'clock_in.date_format' => '出勤時刻の形式が不正です。',
            'clock_out.required' => '退勤時刻を入力してください。',
            'clock_out.date_format' => '退勤時刻の形式が不正です。',

            // 休憩開始（要件に合わせる）
            'breaks.*.break_start.after'  => '休憩時間が不適切な値です',
            'breaks.*.break_start.before' => '休憩時間が不適切な値です',

            // 休憩形式は既存
            'breaks.*.break_start.date_format' => '休憩開始の時刻形式が不正です。',

            // 休憩終了（要件に合わせる）
            'breaks.*.break_end.after'  => '休憩時間もしくは退勤時間が不適切な値です',
            'breaks.*.break_end.before' => '休憩時間もしくは退勤時間が不適切な値です',

            // 休憩終了形式は既存
            'breaks.*.break_end.date_format' => '休憩終了の時刻形式が不正です。',

            // 備考（要件に合わせる）
            'remarks.required' => '備考を記入してください',
            'remarks.max'      => '備考は255文字以内で入力してください',
        ];
    }
}
