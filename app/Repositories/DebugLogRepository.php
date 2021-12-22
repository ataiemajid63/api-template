<?php

namespace App\Repositories;

use App\Entities\DebugLog;
use App\Repositories\Contracts\IDebugLogRepository;
use App\Repositories\Postgres\DebugLogRepository as DebugLogPostgresRepository;

class DebugLogRepository extends Repository implements IDebugLogRepository
{
    private $debugLogPostgresRepository;

    public function __construct(DebugLogPostgresRepository $debugLogPostgresRepository)
    {
        parent::__construct();

        $this->debugLogPostgresRepository = $debugLogPostgresRepository;
    }

    public function insert(DebugLog $debugLog)
    {
        $this->debugLogPostgresRepository->insert($debugLog);
    }
}
