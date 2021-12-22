<?php

namespace App\Entities;

class UserToken extends Entity
{
    protected $id;
    protected $userId;
    protected $token;
    protected $deviceId;
    protected $deviceDensity;
    protected $deviceModel;
    protected $deviceLanguage;
    protected $networkType;
    protected $screenWidth;
    protected $screenHeight;
    protected $ip;
    protected $os;
    protected $osVersion;
    protected $apiVersion;
    protected $clientVersion;
    protected $createdAt;
    protected $updatedAt;
    protected $expiredAt;

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

    public function getToken()
    {
        return $this->token;
    }

    public function setToken($token)
    {
        $this->token = $token;
    }

    public function getDeviceId()
    {
        return $this->deviceId;
    }

    public function setDeviceId($deviceId)
    {
        $this->deviceId = $deviceId;
    }

    public function getDeviceDensity()
    {
        return $this->deviceDensity;
    }

    public function setDeviceDensity($deviceDensity)
    {
        $this->deviceDensity = $deviceDensity;
    }

    public function getDeviceModel()
    {
        return $this->deviceModel;
    }

    public function setDeviceModel($deviceModel)
    {
        $this->deviceModel = $deviceModel;
    }

    public function getDeviceLanguage()
    {
        return $this->deviceLanguage;
    }

    public function setDeviceLanguage($deviceLanguage)
    {
        $this->deviceLanguage = $deviceLanguage;
    }

    public function getNetworkType()
    {
        return $this->networkType;
    }

    public function setNetworkType($networkType)
    {
        $this->networkType = $networkType;
    }

    public function getScreenWidth()
    {
        return $this->screenWidth;
    }

    public function setScreenWidth($screenWidth)
    {
        $this->screenWidth = $screenWidth;
    }

    public function getScreenHeight()
    {
        return $this->screenHeight;
    }

    public function setScreenHeight($screenHeight)
    {
        $this->screenHeight = $screenHeight;
    }

    public function getIp()
    {
        return $this->ip;
    }

    public function setIp($ip)
    {
        $this->ip = $ip;
    }

    public function getOs()
    {
        return $this->os;
    }

    public function setOs($os)
    {
        $this->os = $os;
    }

    public function getOsVersion()
    {
        return $this->osVersion;
    }

    public function setOsVersion($osVersion)
    {
        $this->osVersion = $osVersion;
    }

    public function getApiVersion()
    {
        return $this->apiVersion;
    }

    public function setApiVersion($apiVersion)
    {
        $this->apiVersion = $apiVersion;
    }

    public function getClientVersion()
    {
        return $this->clientVersion;
    }

    public function setClientVersion($clientVersion)
    {
        $this->clientVersion = $clientVersion;
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

    public function getExpiredAt()
    {
        return $this->expiredAt;
    }

    public function setExpiredAt($expiredAt)
    {
        $this->expiredAt = $expiredAt;
    }

    public function generateToken()
    {
        $token = bin2hex(random_bytes(32));

        return $token;
    }
}
