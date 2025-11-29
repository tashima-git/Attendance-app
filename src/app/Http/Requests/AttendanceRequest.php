<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AttendanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'clock_in'     => 'nullable|date_format:H:i',
            'clock_out'    => 'nullable|date_format:H:i',
            'break_start'  => 'nullable|date_format:H:i',
            'break_end'    => 'nullable|date_format:H:i',
            'note'         => 'required|string', // 備考必須
        ];
    }

    public function messages(): array
    {
        return [
            'clock_in.date_format'     => '出勤時刻の形式が不正です（例：09:00）。',
            'clock_out.date_format'    => '退勤時刻の形式が不正です（例：18:00）。',
            'break_start.date_format'  => '休憩開始時刻の形式が不正です（例：12:00）。',
            'break_end.date_format'    => '休憩終了時刻の形式が不正です（例：13:00）。',
            'note.required'            => '備考を記入してください',
        ];
    }

    /** 
     * 追加バリデーション（仕様に基づくロジック）
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {

            $clockIn  = $this->input('clock_in');
            $clockOut = $this->input('clock_out');
            $breakStart = $this->input('break_start');
            $breakEnd   = $this->input('break_end');

            // ① 出勤 > 退勤 のチェック
            if ($clockIn && $clockOut && $clockIn > $clockOut) {
                $validator->errors()->add(
                    'clock_in',
                    '出勤時間もしくは退勤時間が不適切な値です'
                );
            }

            // ② 休憩開始：出勤前・退勤後チェック
            if ($breakStart) {
                if ($clockIn && $breakStart < $clockIn) {
                    $validator->errors()->add(
                        'break_start',
                        '休憩時間が不適切な値です'
                    );
                }
                if ($clockOut && $breakStart > $clockOut) {
                    $validator->errors()->add(
                        'break_start',
                        '休憩時間が不適切な値です'
                    );
                }
            }

            // ③ 休憩終了：退勤後チェック
            if ($breakEnd) {
                if ($clockOut && $breakEnd > $clockOut) {
                    $validator->errors()->add(
                        'break_end',
                        '休憩時間もしくは退勤時間が不適切な値です'
                    );
                }

                // ④ 休憩開始より前はNG
                if ($breakStart && $breakEnd < $breakStart) {
                    $validator->errors()->add(
                        'break_end',
                        '休憩時間が不適切な値です'
                    );
                }
            }
        });
    }
}
