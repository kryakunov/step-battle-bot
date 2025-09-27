<?php

namespace App\Repositories;

use App\Models\User;

class UserRepository
{
    public function updateOrCreate($userId, $userLogin, $userName): User
    {
        $user = User::firstOrCreate([
            'user_id' => $userId,
        ],
        [
            'user_login' => $userLogin,
            'user_name' => $userName,
            'sex' => '0',
        ]);

        return $user;
    }
}
