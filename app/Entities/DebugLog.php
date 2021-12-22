<?php

namespace App\Entities;

class DebugLog extends Entity
{
    protected $serial;
    protected $datetime;
    protected $microtime;
    protected $channel;
    protected $level;
    protected $levelName;
    protected $message;
    protected $context;
    protected $extra;

    public function __construct()
    {
        parent::__construct();
    }

    public function getSerial()
    {
        return $this->serial;
    }

    public function setSerial($serial)
    {
        $this->serial = $serial;
    }

    public function getDatetime()
    {
        return $this->datetime;
    }

    public function setDatetime($datetime)
    {
        $this->datetime = $datetime;
    }

    public function getMicrotime()
    {
        return $this->microtime;
    }

    public function setMicrotime($microtime)
    {
        $this->microtime = $microtime;
    }

    public function getChannel()
    {
        return $this->channel;
    }

    public function setChannel($channel)
    {
        $this->channel = $channel;
    }

    public function getLevel()
    {
        return $this->level;
    }

    public function setLevel($level)
    {
        $this->level = $level;
    }

    public function getLevelName()
    {
        return $this->levelName;
    }

    public function setLevelName($levelName)
    {
        $this->levelName = $levelName;
    }

    public function getMessage()
    {
        return $this->message;
    }

    public function setMessage($message)
    {
        $this->message = $message;
    }

    public function getContext()
    {
        return $this->context;
    }

    public function setContext($context)
    {
        $this->context = $context;
    }

    public function getExtra()
    {
        return $this->extra;
    }

    public function setExtra($extra)
    {
        $this->extra = $extra;
    }
}
