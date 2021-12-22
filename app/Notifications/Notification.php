<?php

namespace App\Notifications;

use App\Entities\KavenegarMessage;
use App\Entities\TelegramMessage;
use Illuminate\Notifications\Notification as BaseNotification;

abstract class Notification extends BaseNotification
{
    /**
     * @return KavenegarMessage
     */
    public function toKavenegar($notifiable) : ?KavenegarMessage
    {
        return null;
    }

    /**
     * @return TelegramMessage
     */
    public function toTelegram($notifiable) : ?TelegramMessage
    {
        return null;
    }
}
