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

    public function getTotalStepsByUserId($userId, $chatId): string
    {
        return Step::where('user_id', $userId)->where('chat_id', $chatId)->sum('count');
    }

    public function deleteAll($chatId)
    {
            Step::where('chat_id', $chatId)->delete();
    }
}
