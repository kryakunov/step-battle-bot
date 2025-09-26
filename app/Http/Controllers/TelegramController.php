<?php

namespace App\Http\Controllers;

use App\Models\Step;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
        if (isset($update['message'])) {
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

            if (!empty($text) && strpos($text, '#шаги') !== false) {


                if ($chatId !== '-1002958307681') {
                    $this->sendMessage($chatId, "Привет, $userName! Отчеты можно присылать только в публичный чат)");
                    die;
                }

                $textArr = explode(' ', $text);

                if (isset($textArr[1]) && is_numeric($textArr[1])) {
                    $steps = $textArr[1];

                    try {
                        $user = User::firstOrCreate([
                            'user_name' => $userName,
                            'user_id' => $userId,
                            'sex' => '0',
                            //'chat_id' => $chatId,
                        ]);
                    } catch (\Exception $e) {
                        file_put_contents('errors.txt', $e->getMessage() . "\n" . $userName . "\n" . $userId);
                    }

                    Step::create([
                        'user_id' => $userId,
                        'count' => $steps,
                        'chat_id' => $chatId,
                    ]);

                    $total = Step::where('user_id', $userId)->sum('count');

                    $additionalText = '';

                    if ($steps < 6000) {
                        $additionalText = 'У тебя сегодня чилл-день? ';
                    }

                    if ($steps > 15000) {
                        $additionalText = 'Ого! Как много шагов. ';
                    }

                    if ($steps > 30000) {
                        $additionalText = 'Ого! Ты сегодня рекордсмен! ';
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

            if (!empty($text) && strpos($text, '#рейтинг') !== false) {

                $results = User::select('users.user_name', DB::raw('(SELECT SUM(steps.count) FROM steps WHERE steps.user_id = users.user_id) as total_count'))
                    ->havingRaw('total_count > 0') // Опционально: только пользователи с count > 0
                    ->orderBy('total_count', 'desc')
                    ->get();

                $data = '';
                foreach ($results as $result) {
                    $data .= $result->user_name . ": " . $result->total_count . PHP_EOL;
                }

                $this->sendMessage($chatId, $data);
            }

            if (!empty($text) && strpos($text, '#рестарт') !== false) {


                if ($userId !== '349614044' || $userId !== '1775159750') {
                    $this->sendMessage($chatId, "Перезапустить бота могут только админы");
                    die;
                }

                DB::table('steps')->truncate();
                //Step::where('chat_id', $chatId)->delete();

                $this->sendMessage($chatId, "Бот перезапущен. Рейтинг обнулен");
            }

        }
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
