<?php

namespace App\Listeners;

use Illuminate\Database\Events\QueryExecuted;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;

class LogQueryExecuted
{
    public function handle(QueryExecuted $event)
    {
        $logger = new Logger(config('app.name'));
        $logger->pushHandler(new StreamHandler(storage_path('logs/query_executed.log'), Logger::DEBUG));

        $sql = preg_replace_array('/\?/', $event->bindings, $event->sql);

        $logger->info("Duration: {$event->time}, Sql: {$sql}");

        $logger->close();
    }
}
