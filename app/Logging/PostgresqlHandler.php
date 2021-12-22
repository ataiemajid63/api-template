<?php

namespace App\Logging;

use App\Entities\DebugLog;
use App\Repositories\DebugLogRepository;
use Monolog\DateTimeImmutable as MonologDateTimeImmutable;
use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Logger;

class PostgresqlHandler extends AbstractProcessingHandler
{
    private $debugLogRepository;
    private $serial;

    public function __construct(DebugLogRepository $debugLogRepository, $serial, $level = Logger::DEBUG, bool $bubble = true)
    {
        parent::__construct($level, $bubble);

        $this->debugLogRepository = $debugLogRepository;
        $this->serial = $serial;
    }

    /**
     * {@inheritDoc}
     */
    protected function write(array $record): void
    {
        /**
         * @var MonologDateTimeImmutable $datetime
         */
        $datetime = $record['datetime'];

        $debugLog = new DebugLog();

        $debugLog->setSerial($this->serial);
        $debugLog->setDatetime($datetime->format('Y-m-d H:i:s'));
        $debugLog->setMicrotime(microtime(true));
        $debugLog->setChannel($record['channel'] ?? null);
        $debugLog->setLevel($record['level']);
        $debugLog->setLevelName($record['level_name']);
        $debugLog->setMessage($record['message']);
        $debugLog->setContext(empty($record['context']) ? null : json_encode($record['context']));
        $debugLog->setExtra(empty($record['extra']) ? null : json_encode($record['extra']));

        $this->debugLogRepository->insert($debugLog);
    }
}
