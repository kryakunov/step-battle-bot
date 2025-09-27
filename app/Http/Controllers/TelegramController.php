<?php

namespace App\Http\Controllers;

use App\Repositories\StepRepository;
use App\Services\StepService;
use Longman\TelegramBot\Telegram;

class TelegramController extends Controller
{
    public function __construct(
        public readonly StepService $stepService
    )
    {}

    public function __invoke()
    {
        // Получаем сырые POST-данные от Telegram (JSON)
        $input = file_get_contents('php://input');
        $update = json_decode($input, true);

        // Проверка на пустой запрос (Telegram иногда шлет для теста)
        if (!$update || !isset($update['update_id']) || !isset($update['message'])) {
            $this->stepService->error();
        }

        $this->stepService->write($update['message']);
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
