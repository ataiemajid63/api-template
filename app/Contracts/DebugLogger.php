<?php

namespace App\Contracts;

interface DebugLogger
{
    /**
     * @param string $message
     * @param array $context
     */
    public function debug(string $message, array $context = []);

    public function close();
}
