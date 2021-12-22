<?php

namespace App\Repositories\Postgres;

use App\Entities\DebugLog;
use App\Repositories\Contracts\IDebugLogRepository;

class DebugLogRepository extends Repository implements IDebugLogRepository
{
    public function __construct()
    {
        parent::__construct();

        $this->table = 'debug_logs';
        $this->primaryKey = null;
    }

    public function insert(DebugLog $debugLog)
    {
        $this->query()->insert([
            'serial' => $debugLog->getSerial(),
            'datetime' => $debugLog->getDatetime(),
            'microtime' => $debugLog->getMicrotime(),
            'channel' => $debugLog->getChannel(),
            'level' => $debugLog->getLevel(),
            'level_name' => $debugLog->getLevelName(),
            'message' => $debugLog->getMessage(),
            'context' => $debugLog->getContext(),
            'extra' => $debugLog->getExtra(),
        ]);
    }
}
