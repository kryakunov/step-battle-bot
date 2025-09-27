<?php

namespace App\Repositories;

use App\Models\Step;

class StepRepository
{
    public function write($userId, $steps, $chatId): void
    {
        Step::create([
            'user_id' => $userId,
            'count' => $steps,
            'chat_id' => $chatId,
        ]);
    }

    public function getTotalStepsByUserId($userId): Step
    {
        return Step::where('user_id', $userId)->sum('count');
    }
}
