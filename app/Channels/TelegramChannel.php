<?php

namespace App\Channels;

use App\Entities\User;
use Exception;
use App\Notifications\Notification;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Telegram\Bot\Api;

class TelegramChannel
{
    public function send(User $notifiable, Notification $notification)
    {
        if(app()->environment() != 'production') {
            return true;
        }

        $logger = new Logger(config('app.name'));
        $logger->pushHandler(new StreamHandler(storage_path('logs/channels.log'), Logger::DEBUG));

        if(!$message = $notification->toTelegram($notifiable)) {
            return true;
        }

        if ($to = $notifiable->routeNotificationForTelegram($notification)) {
            $message->setChatId($to);
        }

        try {
            $bot = new Api($message->getBotToken());

            if(is_array($message->getReplyMarkup())) {
                $message->setReplyMarkup($bot->replyKeyboardMarkup($message->getReplyMarkup()));
            }

            $bot->sendMessage($message->toArray(true));

            $logger->info('Telegram @CID' . $notifiable->getChatId() . ' #' . class_basename($notification));
        } catch (Exception $e) {
            $logger->error('Telegram @CID' . $notifiable->getChatId() . ' #' . class_basename($notification), [$e->getMessage()]);

            return true;
        }
    }
}
