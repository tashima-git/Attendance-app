<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AttendanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check(); // ログイン必須
    }

    public function rules(): array
    {
        return [
            // ボタンによって送信される内容が異なるため、基本的には任意項目としつつ形式を保証
            'clock_in'     => 'nullable|date_format:H:i',
            'break_start'  => 'nullable|date_format:H:i',
            'break_end'    => 'nullable|date_format:H:i',
            'clock_out'    => 'nullable|date_format:H:i',
        ];
    }

    public function messages(): array
    {
        return [
            'clock_in.date_format'    => '出勤時刻の形式が不正です（例：09:00）。',
            'break_start.date_format' => '休憩開始時刻の形式が不正です（例：12:00）。',
            'break_end.date_format'   => '休憩終了時刻の形式が不正です（例：13:00）。',
            'clock_out.date_format'   => '退勤時刻の形式が不正です（例：18:00）。',
        ];
    }
}
