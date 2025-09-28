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

    public function getLastReport($chatId, $userId): string
    {
        $lastItem = Step::where('chat_id', $chatId)->where('user_id', $userId)->orderBy('id', 'desc')->first();

        return $lastItem->count;
    }

    public function deleteLastReport($chatId, $userId)
    {
            Step::where('chat_id', $chatId)->where('user_id', $userId)->delete();
    }
}
