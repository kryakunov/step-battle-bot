<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class TelegramController extends Controller
{
    public function __invoke()
    {
        // Получаем сырые POST-данные от Telegram (JSON)
        $input = file_get_contents('php://input');
        $update = json_decode($input, true);

        // Проверка на пустой запрос (Telegram иногда шлет для теста)
        if (!$update || !isset($update['update_id'])) {
            http_response_code(200);
            echo 'OK';
            exit;
        }

        // Обработка новых сообщений
        if (isset($update['message']))
        {
            $message = $update['message'];

            $chatId = $message['chat']['id'];
            $text = $message['text'] ?? '';
            $caption = $message['caption'] ?? 'no';
            $userName = $message['from']['first_name'] ?? ($message['from']['username'] ?? 'Unknown');
            $date = $message['date'] ?? '';

            $userId = $message['from']['id'] ?? '';

            $this->sendMessage($chatId, "Привет, $userName! $userId, $date Сообщение залогировано: $text, ");


            // Определение типа контента, если не текст
//            if (empty($logData['text'])) {
//                if (isset($message['photo'])) {
//                    $logData['content_type'] = 'photo';
//                    $logData['text'] = $message['text'] . 'Фото (размер: ' . end($message['photo'])['file_size'] . ' байт)';
//                } elseif (isset($message['document'])) {
//                    $logData['content_type'] = 'document';
//                    $logData['text'] = 'Файл: ' . ($message['document']['file_name'] ?? 'Без имени');
//                } elseif (isset($message['sticker'])) {
//                    $logData['content_type'] = 'sticker';
//                    $logData['text'] = 'Стикер';
//                } elseif (isset($message['voice'])) {
//                    $logData['content_type'] = 'voice';
//                    $logData['text'] = 'Голосовое сообщение';
//                } // Добавьте другие типы по необходимости (video, location и т.д.)
            }


            // Опционально: Автоматический ответ (эхо-бот)
//            if (!empty($text) && strpos($text, '/start') !== 0) {  // Не отвечаем на /start, чтобы не зациклить
//                sendMessage($chatId, "Сообщение залогировано: $text");
//            }

    }

    protected function sendMessage($chatId, $message): bool
    {
        $botToken = env('TELEGRAM_TOKEN');
        $botApiUrl = "https://api.telegram.org/bot{$botToken}/sendMessage";

        Http::post($botApiUrl, [
            'chat_id' => $chatId,
            'text' => $message,
        ]);

        return true;
    }
}
