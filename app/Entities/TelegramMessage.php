<?php

namespace App\Entities;

class TelegramMessage extends Entity
{
    protected $chatId;
    protected $parseMode;
    protected $replyToMessageId;
    protected $disableWebPagePreview;
    protected $replyMarkup;
    protected $text;

    protected $botToken;

    public function getChatId()
    {
        return $this->chatId;
    }

    public function setChatId($chatId)
    {
        $this->chatId = $chatId;
    }

    public function getParseMode()
    {
        return $this->parseMode;
    }

    public function setParseMode($parseMode)
    {
        $this->parseMode = $parseMode;
    }

    public function getReplyToMessageId()
    {
        return $this->replyToMessageId;
    }

    public function setReplyToMessageId($replyToMessageId)
    {
        $this->replyToMessageId = $replyToMessageId;
    }

    public function getDisableWebPagePreview()
    {
        return $this->disableWebPagePreview;
    }

    public function setDisableWebPagePreview($disableWebPagePreview)
    {
        $this->disableWebPagePreview = $disableWebPagePreview;
    }

    public function getReplyMarkup()
    {
        return $this->replyMarkup;
    }

    public function setReplyMarkup($replyMarkup)
    {
        $this->replyMarkup = $replyMarkup;
    }

    public function getText()
    {
        return $this->text;
    }

    public function setText($text)
    {
        $this->text = $text;
    }

    public function getBotToken()
    {
        return $this->botToken;
    }

    public function setBotToken($botToken)
    {
        $this->botToken = $botToken;
    }
}
