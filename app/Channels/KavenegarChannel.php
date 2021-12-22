<?php

namespace App\Channels;

use App\Entities\KavenegarMessage;
use App\Entities\User;
use App\Enums\KavenegarType;
use App\Notifications\Notification;
use GuzzleHttp\Client;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;

class KavenegarChannel
{
    public function send(User $notifiable, Notification $notification)
    {
        if(app()->environment() != 'production') {
            return true;
        }

        if (!$to = $notifiable->routeNotificationForKavenegar($notification)) {
            return true;
        }

        if(!$message = $notification->toKavenegar($notifiable)) {
            return true;
        }

        switch($message->getType()) {
            case KavenegarType::VERIFICATION_CODE:
                return $this->verifyLookup($to, $message);
            case KavenegarType::TEXT:
                return $this->smsSend($to, $message);
        }
    }

    public function verifyLookup($to, KavenegarMessage $message)
    {
        $url = config('service.kavenegar.url');
        $key = config('service.kavenegar.key');
        $template = config('service.kavenegar.template');
        $text = urlencode($message->getContent());

        $logger = new Logger(config('app.name'));
        $logger->pushHandler(new StreamHandler(storage_path('logs/channels.log'), Logger::DEBUG));

        try {
            $client = new Client();

            $response = $client->get("{$url}/v1/{$key}/verify/lookup.json?receptor={$to}&token={$text}&template={$template}");

            $result = json_decode($response->getBody()->getContents());

            if($result->retrun && $result->return->status != 200) {
                $logger->error('Kavenegar @To' . $to . ' #' . $result->return->status, [$result->return->message]);
            }

            $logger->info('Kavenegar @To' . $to . ' #' . $result->return->status, [$result->return->message]);

            return $result;

        } catch (\Exception $e){
            $logger->error('Kavenegar @To' . $to, [$e->getMessage()]);
        }

        return true;
    }

    public function smsSend($to, KavenegarMessage $message)
    {
        $url = config('service.kavenegar.url');
        $key = config('service.kavenegar.key');
        $from = config('service.kavenegar.number');
        $text = urlencode($message->getContent());

        $logger = new Logger(config('app.name'));
        $logger->pushHandler(new StreamHandler(storage_path('logs/channels.log'), Logger::DEBUG));

        try {
            $client = new Client();

            $response = $client->get("{$url}/v1/{$key}/sms/send.json?receptor={$to}&message={$text}&sender={$from}");

            $result = json_decode($response->getBody()->getContents());

            if($result->retrun && $result->return->status != 200) {
                $logger->error('Kavenegar @To' . $to . ' #' . $result->return->status, [$result->return->message]);
            }

            $logger->info('Kavenegar @To' . $to . ' #' . $result->return->status, [$result->return->message]);

            return $result;

        } catch (\Exception $e){
            $logger->error('Kavenegar @To' . $to, [$e->getMessage()]);
        }

        return true;
    }
}
