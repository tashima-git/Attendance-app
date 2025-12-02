<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Auth;

class LoginRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'email'     => ['required', 'email'],
            'password'  => ['required', 'string'],
        ];
    }

    public function messages()
    {
        return [
            // 未入力
            'email.required'    => 'メールアドレスを入力してください',
            'password.required' => 'パスワードを入力してください',

            // email の形式
            'email.email'       => 'メールアドレスの形式が不正です',
        ];
    }

    /**
     * 認証失敗時のレスポンス（日本語化）
     */
    protected function failedLoginResponse()
    {
        throw ValidationException::withMessages([
            'email' => ['ログイン情報が登録されていません'],
        ]);
    }

    /**
     * Fortify が呼び出す認証用メソッド
     */
    public function authenticate()
    {
        if (! Auth::attempt($this->only('email', 'password'), $this->boolean('remember'))) {
            $this->failedLoginResponse();
        }
    }
}
