<?php

namespace App\Entities;

class Client extends Entity
{
    /**
     * @var integer
     */
    protected $id;

    /**
     * @var string
     */
    protected $title;

    /**
     * @var string
     */
    protected $token;

    /**
     * @var integer|null
     */
    protected $expiredAt;

    /**
     * @var integer
     */
    protected $createdAt;

    /**
     * @var integer
     */
    protected $updatedAt;

    /**
     * @var integer|null
     */
    protected $deletedAt;


    public function __construct()
    {
        parent::__construct();
    }

    /**
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param integer $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * @param string $title
     */
    public function setTitle($title)
    {
        $this->title = $title;
    }

    /**
     * @return string
     */
    public function getToken()
    {
        return $this->token;
    }

    /**
     * @param string $token
     */
    public function setToken($token)
    {
        $this->token = $token;
    }

    /**
     * @return integer
     */
    public function getExpiredAt()
    {
        return $this->expiredAt;
    }

    /**
     * @param integer $expiredAt
     */
    public function setExpiredAt($expiredAt)
    {
        $this->expiredAt = $expiredAt;
    }

    /**
     * @return integer
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * @param integer $createdAt
     */
    public function setCreatedAt($createdAt)
    {
        $this->createdAt = $createdAt;
    }

    /**
     * @return integer
     */
    public function getUpdatedAt()
    {
        return $this->updatedAt;
    }

    /**
     * @param integer $updatedAt
     */
    public function setUpdatedAt($updatedAt)
    {
        $this->updatedAt = $updatedAt;
    }

    /**
     * @return integer
     */
    public function getDeletedAt()
    {
        return $this->deletedAt;
    }

    /**
     * @param integer $deletedAt
     */
    public function setDeletedAt($deletedAt)
    {
        $this->deletedAt = $deletedAt;
    }

    /**
     * @return string
     */
    public function makeToken()
    {
        return password_hash(uniqid(env('APP_URL'), true), PASSWORD_BCRYPT);
    }
}
