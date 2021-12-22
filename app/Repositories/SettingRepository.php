<?php

namespace App\Repositories;

use App\Entities\Setting;
use App\Repositories\Contracts\ISettingRepository;
use App\Repositories\Mysql\SettingRepository as SettingMysqlRepository;
use App\Repositories\Redis\SettingRepository as SettingRedisRepository;
use Illuminate\Support\Collection;

class SettingRepository extends Repository implements ISettingRepository
{
    private $settingMysqlRepository;
    private $settingRedisRepository;

    public function __construct(SettingMysqlRepository $settingMysqlRepository, SettingRedisRepository $settingRedisRepository)
    {
        parent::__construct();

        $this->settingMysqlRepository = $settingMysqlRepository;
        $this->settingRedisRepository = $settingRedisRepository;
    }

    public function getAllByType($type): Collection
    {
        $settings = $this->settingRedisRepository->getAllByType($type);

        if($settings->isEmpty()) {
            $settings = $this->settingMysqlRepository->getAllByType($type);

            if($settings->isNotEmpty()) {
                $this->settingRedisRepository->bulkInsert($settings);

                $key = $this->settingRedisRepository->key('getAllByType', $type);
                $value = $settings->pluck('id')->all();
                $ttl = $this->settingRedisRepository->ttl();

                $this->settingRedisRepository->set($key, $value, $ttl);
            }
        }

        return $settings;
    }

    public function getOneByKeyAndType($key, $type): ?Setting
    {
        $setting = $this->settingRedisRepository->getOneByKeyAndType($key, $type);

        if(is_null($setting)) {
            $setting = $this->settingMysqlRepository->getOneByKeyAndType($key, $type);

            if(!is_null($setting)) {
                $this->settingRedisRepository->insert($setting);

                $key = $this->settingRedisRepository->key('getOneByKeyAndType', "$key-$type");
                $value = $setting->getId();
                $ttl = $this->settingRedisRepository->ttl();

                $this->settingRedisRepository->set($key, $value, $ttl);
            }
        }

        return $setting;
    }

    public function purgeAllCache()
    {
        $this->settingRedisRepository->purge();
    }
}
