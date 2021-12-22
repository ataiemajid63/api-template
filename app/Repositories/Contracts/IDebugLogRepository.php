<?php

namespace App\Repositories\Contracts;

use App\Entities\DebugLog;

interface IDebugLogRepository
{
    public function insert(DebugLog $debugLog);
}
