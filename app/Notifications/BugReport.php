<?php

namespace App\Notifications;

use App\Channels\TelegramChannel;
use App\Entities\KavenegarMessage;
use App\Entities\TelegramMessage;
use App\Enums\Severity;
use Pasoonate\Pasoonate;
use Throwable;
use Illuminate\Support\Str;

class BugReport extends Notification
{
    public $exception;

    public function __construct(Throwable $exception)
    {
        $this->exception = $exception;
    }

    public function via($notifiable)
    {
        $channels = [
            TelegramChannel::class
        ];

        return $channels;
    }

    public function toKavenegar($notifiable) : ?KavenegarMessage
    {
        return null;
    }

    public function toTelegram($notifiable) : ?TelegramMessage
    {
        $botToken = env('TELEGRAM_NOTIFY_BOT_TOKEN');

        if(is_null($botToken)) {
            return null;
        }

        $severity = '';
        $getSeverity = 'getSeverity';

        if(method_exists($this->exception, $getSeverity)) {
            $severity = Severity::getKey($this->exception->$getSeverity()) ?? $this->exception->$getSeverity();
        }

        $text = $severity
            . ' at '
            . Pasoonate::make()->jalali()->format('yyyy/MM/dd HH:mm:ss')
            . ' on '
            . app()->environment() . PHP_EOL
            . 'file: ' . $this->exception->getFile()
            . ' line: ' . $this->exception->getLine() . PHP_EOL
            . $this->exception->getMessage() . PHP_EOL
            . app('url')->full() . PHP_EOL
            . PHP_EOL
            . '#API #' . Str::studly(env('APP_NAME')) . PHP_EOL;

        $text = str_replace(["'", "*", "_", "`", "[", "]"], '', $text);

        $telegramMessage = new TelegramMessage();

        $telegramMessage->setBotToken($botToken);
        $telegramMessage->setChatId($notifiable->getChatId());
        $telegramMessage->setDisableWebPagePreview(true);
        $telegramMessage->setParseMode('HTML');
        $telegramMessage->setReplyMarkup(null);
        $telegramMessage->setReplyToMessageId(null);
        $telegramMessage->setText($text);

        return $telegramMessage;
    }
}
