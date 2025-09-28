<?php

namespace App\Services;

use App\Models\User;
use App\Repositories\StepRepository;
use App\Repositories\UserRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class StepService
{
    public function __construct(
        private readonly StepRepository $stepRepository,
        private readonly UserRepository $userRepository,
    )
    {}

    public function handle($message)
    {
        $chatId = $message['chat']['id'];
        $text = $message['text'] ?? '';
        $caption = $message['caption'] ?? 'no';
        $userName = $message['from']['first_name'] ?? ($message['from']['username'] ?? 'Unknown');
        $userLogin = $message['from']['username'] ?? null;
        $userId = $message['from']['id'] ?? '';

        // Если есть картинка - берем текст из описания картинки
        if (strlen($text) < 2 && strlen($caption) > 1) {
            $text = $caption;
        }

        if (empty($text)) {
            $this->error();
        }

        if (strpos($text, '#шаги') !== false) {

            // если кто-то пытается прислать отчет в личку бота
            if ($chatId > 0) {
                $this->sendMessage($chatId, "Привет, $userName! Хорошая попытка ;) Отчеты можно присылать только в публичный чат");
                die;
            }

            $textArr = explode(' ', $text);

            if (isset($textArr[1]) && is_numeric($textArr[1])) {
                $steps = $textArr[1];

                try {

                    $user = $this->userRepository->updateOrCreate($userId, $userLogin, $userName);

                } catch (\Exception $e) {
                    file_put_contents('errors.txt', $e->getMessage() . "\n" . $userName . "\n" . $userId);
                }

                $this->stepRepository->write($userId, $steps, $chatId);

                $total = $this->stepRepository->getTotalStepsByUserId($userId, $chatId);

                $additionalText = '';

                if ($steps < 6000) {
                    $additionalText = 'У тебя сегодня чилл-день? ';
                }

                if ($steps > 15000) {
                    $additionalText = 'Ого! Как много шагов. ';
                }

                if ($steps > 30000) {
                    $additionalText = 'Ого! Ты сегодня рекордсмен! 🏆';
                }

                if ($user->sex == '1') {
                    $this->sendMessage($chatId, "Привет, $userName! $additionalText Отчет принят. Ты сегодня прошел $steps шагов. А всего за неделю находил $total шагов");
                } elseif ($user->sex == '2') {
                    $this->sendMessage($chatId, "Привет, $userName! $additionalText Отчет принят. Ты сегодня прошла $steps шагов. А всего за неделю находила $total шагов");
                } else {
                    $this->sendMessage($chatId, "Привет, $userName! $additionalText Отчет принят. Ты сегодня прошел(-шла) $steps шагов. А всего за неделю находил(-ла) $total шагов");
                }
            } else {
                $this->sendMessage($chatId, "Привет, $userName! Неверный отчет. Пришли, пожалуйста, в формате #шаги <количество>");
            }
        }

        if (strpos($text, '#рейтинг') !== false) {

            $sql = "
                    SELECT
                        users.user_name,
                        (SELECT SUM(steps.count) FROM steps WHERE steps.user_id = users.user_id AND steps.chat_id = " . $chatId . ") AS total_count,
                        (SELECT COUNT(*) FROM steps WHERE steps.user_id = users.user_id AND steps.chat_id = " . $chatId . ") AS records_count
                    FROM users
                    HAVING total_count > 0
                    ORDER BY total_count DESC
                ";

            $results = DB::select($sql);

            $sql = "SELECT sum(count) as count FROM steps WHERE steps.chat_id = " . $chatId;
            $sum = DB::select($sql);

            $sum = number_format($sum[0]->count, 0, '', ' ');
            $data = 'Всего пройдено шагов: ' . $sum . PHP_EOL . PHP_EOL;

            foreach ($results as $result) {

                //$result->total_count = number_format($result->total_count, 0, '', ' ');
                $data .= $result->user_name . ": <b>" . $result->total_count . "</b> (<i>" . $result->records_count . " отчетов</i>)" . PHP_EOL;
            }

            $this->sendMessage($chatId, $data);
        }

        if (strpos($text, '#отменить') !== false) {


            try {
                $lastItem = $this->stepRepository->getLastReport($chatId, $userId);

                $this->stepRepository->deleteLastReport($chatId, $userId);

                $this->sendMessage($chatId, "$userName, твой последний отчет на $lastItem шагов удален");

            } catch (\Exception $e) {
                $this->sendMessage($chatId, "Неизвестная ошибка " . $e->getMessage());

            }
        }

        if (strpos($text, '#забывашки') !== false) {
            die;
            $day = date('d');

            $sql = "SELECT DISTINCT s.user_id, u.user_name
                FROM steps s
                INNER JOIN users u ON u.user_id = s.user_id
                WHERE NOT EXISTS (
                    SELECT 1
                    FROM steps s2
                    WHERE s2.user_id = s.user_id
                      AND DAY(s2.created_at) = ' . $day . ')
                ";

            $items = DB::select($sql);

            $data = 'Сегодня свои отчеты нам забыли прислать: ' . PHP_EOL;
            foreach ($items as $item) {
                $data .= $item->user_name . PHP_EOL;
            }

            $this->sendMessage($chatId, $data);
        }

        if (strpos($text, '#рестарт') !== false) {

            if ($userId !== '349614044' || $userId !== '1775159750') {
                $this->sendMessage($chatId, "Перезапустить бота могут только админы");
                die;
            }

            try {
                $this->stepRepository->deleteAll($chatId);
            } catch (\Exception $e) {
                $this->sendMessage($chatId, "Ошибка при перезапуске бота " . $e->getMessage());
                die;
            }

            $this->sendMessage($chatId, "Бот перезапущен. Рейтинг обнулен");
        }
    }

    protected function sendMessage($chatId, $message): bool
    {
        $botToken = env('TELEGRAM_TOKEN');
        $botApiUrl = "https://api.telegram.org/bot{$botToken}/sendMessage";

        Http::post($botApiUrl, [
            'chat_id' => $chatId,
            'text' => $message,
            'parse_mode' => 'HTML'
        ]);

        return true;
    }

    public function error(): void
    {
        http_response_code(200);
        echo 'OK';
        exit;
    }
}
