<?php

namespace App;

use App\Contracts\DebugLogger as DebugLoggerContract;
use App\Logging\PostgresqlHandler;
use App\Repositories\DebugLogRepository;
use Monolog\Handler\NullHandler;
use Monolog\Logger;

/**
 * @property-read string $serial
 * @property-read Logger $logger
 */
class DebugLogger implements DebugLoggerContract
{
    public $serial;
    public $logger;

    public function __construct()
    {
        $this->serial = uniqid();

        $this->logger = new Logger(config('app.name'));

        $handler = new PostgresqlHandler(app(DebugLogRepository::class), $this->serial, Logger::DEBUG, true);
        $nullHendler = new NullHandler();

        $this->logger->pushHandler(config('logging.debug') ? $handler : $nullHendler);
    }

    public function debug($message, array $context = [])
    {
        $this->logger->debug($message, $context);
    }

    public function close()
    {
        $this->logger->close();
    }
}
