<?php

namespace App\Actions\Fortify;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Laravel\Fortify\Contracts\CreatesNewUsers;
use Illuminate\Auth\Events\Registered;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules;

    /**
     * Validate and create a newly registered user.
     */
    public function create(array $input): User
    {
        Validator::make(
            $input,
            [
                'name' => ['required', 'string', 'max:255'],
                'email' => [
                    'required',
                    'string',
                    'email',
                    'max:255',
                    Rule::unique(User::class),
                ],
                'password' => $this->passwordRules(), // min:8, confirmed など
            ],
            [
                // --- ❶ カスタムメッセージ（要件通り） ---
                'name.required' => 'お名前を入力してください',
                'email.required' => 'メールアドレスを入力してください',
                'password.required' => 'パスワードを入力してください',

                // パスワードルール
                'password.min' => 'パスワードは8文字以上で入力してください',
                'password.confirmed' => 'パスワードと一致しません',
            ],
            [
                // --- ❷ 属性名の日本語化（フォールバック用） ---
                'name' => 'お名前',
                'email' => 'メールアドレス',
                'password' => 'パスワード',
                'password_confirmation' => 'パスワード確認',
            ]
        )->validate();

        // ユーザー作成
        $user = User::create([
            'name' => $input['name'],
            'email' => $input['email'],
            'password' => Hash::make($input['password']),
        ]);

        event(new Registered($user));

        return $user;
    }
}
