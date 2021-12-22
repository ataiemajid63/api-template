<?php

namespace App\Entities;

use App\Enums\KavenegarType;

class KavenegarMessage extends Entity
{
    /**
     * The message content.
     *
     * @var string
     */
    private $content;

    /**
     * The phone number the message should be sent from.
     *
     * @var string
     */
    private $from;

    /**
     * The type of message. verification code or text
     *
     * @var string
     */
    private $type;

    /**
     * Create a new message instance.
     *
     * @param  string  $content
     * @return void
     */
    public function __construct($content = '', $type = KavenegarType::TEXT)
    {
        $this->content = $content;
        $this->type = $type;
    }

    /**
     * Get the message content.
     *
     * @return string
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * Set the message content.
     *
     * @param  string  $content
     * @return void
     */
    public function setContent($content)
    {
        $this->content = $content;
    }

    /**
     * get the phone number the message should be sent from.
     *
     * @return string
     */
    public function getFrom()
    {
        return $this->from;
    }

    /**
     * Set the phone number the message should be sent from.
     *
     * @param  string  $from
     * @return void
     */
    public function setFrom($from)
    {
        $this->from = $from;
    }

    /**
     *
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param  string  $type
     * @return void
     */
    public function setType($type)
    {
        $this->type = $type;
    }
}
