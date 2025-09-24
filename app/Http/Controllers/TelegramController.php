<?php

namespace App\Http\Controllers;

use App\Models\Step;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Longman\TelegramBot\Telegram;

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

            if (strlen($text) < 2 && strlen($caption) > 1) {
                $text = $caption;
            }

            if (strpos($text, '#шаги') !== false) {
                // Хештег найден — выполняем код дальше

                $count = explode(' ', $text);
                if (is_numeric($count[1])) {

                    Step::create([
                        'user_id' => $userId,
                        'count' => $count[1],
                    ]);

                    $total = Step::where('user_id', $userId)->sum('count');

                    $this->sendMessage($chatId, "Привет, $userName! Ты сегодня прошел $count[1] шагов. А всего $total");
                } else {
                    $this->sendMessage($chatId, "Привет, $userName! Неверный отчет. Пришли, пожалуйста, в формате #шаги <количество>");
                }
            }

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


    public function setWebhook()
    {
        $botToken = env('TELEGRAM_TOKEN');
        $botUsername = 'sharoe';
        $webhook_url = 'https://sharoeby.ru/bot'; // Полный HTTPS URL к bot.php

        $telegram = new Telegram($botToken, $botUsername);
        $result = $telegram->setWebhook($webhook_url);

        if ($result->isOk()) {
            echo "Webhook установлен успешно: " . $result->getDescription() . "\n";
        } else {
            echo "Ошибка установки webhook: " . $result->getDescription() . "\n";
        }
    }
}
