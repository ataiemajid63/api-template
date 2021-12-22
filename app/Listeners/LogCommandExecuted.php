<?php

namespace App\Listeners;

use App\DebugLogger;
use App\Entities\AppTraceLogActivity;
use Illuminate\Redis\Events\CommandExecuted;
use Illuminate\Support\Str;

class LogCommandExecuted
{
    public $logger;

    public function __construct(DebugLogger $logger)
    {
        $this->logger = $logger;
    }

    public function handle(CommandExecuted $event)
    {
        if($event->command === 'expire') {
            return;
        }

        $params = serialize($event->parameters);
        $pattern = '/' . Str::slug(config('app.name'), '_') . ':([a-z_]+):.*/i';
        preg_match($pattern, $params, $matches);
    }
}
