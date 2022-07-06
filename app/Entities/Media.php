<?php

namespace App\Entities;

use App\Enums\MediaItemType;

class Media extends Entity
{
    protected $id;
    protected $userId;
    protected $itemId;
    protected $itemType;
    protected $type;
    protected $fileName;
    protected $code;
    protected $title;
    protected $ordering;
    protected $isCover;
    protected $verifiedAt;
    protected $createdAt;
    protected $updatedAt;
    protected $deletedAt;

    public function __construct()
    {
        parent::__construct();
    }

    public function getId()
    {
        return $this->id;
    }

    public function setId($id)
    {
        $this->id = $id;
    }

    public function getUserId()
    {
        return $this->userId;
    }

    public function setUserId($userId)
    {
        $this->userId = $userId;
    }

    public function getItemId()
    {
        return $this->itemId;
    }

    public function setItemId($itemId)
    {
        $this->itemId = $itemId;
    }

    public function getItemType()
    {
        return $this->itemType;
    }

    public function setItemType($itemType)
    {
        $this->itemType = $itemType;
    }

    public function getType()
    {
        return $this->type;
    }

    public function setType($type)
    {
        $this->type = $type;
    }

    public function getFileName()
    {
        return $this->fileName;
    }

    public function setFileName($fileName)
    {
        $this->fileName = $fileName;
    }

    public function getCode()
    {
        return $this->code;
    }

    public function setCode($code)
    {
        $this->code = $code;
    }

    public function getTitle()
    {
        return $this->title;
    }

    public function setTitle($title)
    {
        $this->title = $title;
    }

    public function getOrdering()
    {
        return $this->ordering;
    }

    public function setOrdering($ordering)
    {
        $this->ordering = $ordering;
    }

    public function getIsCover()
    {
        return $this->isCover;
    }

    public function setIsCover($isCover)
    {
        $this->isCover = $isCover;
    }

    public function getVerifiedAt()
    {
        return $this->verifiedAt;
    }

    public function setVerifiedAt($verifiedAt)
    {
        $this->verifiedAt = $verifiedAt;
    }

    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    public function setCreatedAt($createdAt)
    {
        $this->createdAt = $createdAt;
    }

    public function getUpdatedAt()
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt($updatedAt)
    {
        $this->updatedAt = $updatedAt;
    }

    public function getDeletedAt()
    {
        return $this->deletedAt;
    }

    public function setDeletedAt($deletedAt)
    {
        $this->deletedAt = $deletedAt;
    }

    public function url()
    {

    }
}
