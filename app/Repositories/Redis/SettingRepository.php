<?php

namespace App\Repositories\Redis;

use App\Entities\Setting;
use App\Factories\SettingFactory;
use App\Repositories\Contracts\ISettingRepository;
use Illuminate\Support\Collection;

class SettingRepository extends Repository implements ISettingRepository
{
    public function __construct()
    {
        parent::__construct();

        $this->table = 'settings';
        $this->ttl = 60 * 60; // 1 Hour
    }

    /**
     * @param Setting $setting
     */
    public function insert(Setting $setting)
    {
        $key = $this->key('id', $setting->getId());
        $value = $setting->toArray(true);

        $this->set($key, $value, $this->ttl);
    }

    public function bulkInsert(Collection $settings)
    {
        $entities = [];

        foreach($settings as $setting) {
            $key = $this->key('id', $setting->getId());

            $entities[$key] = $setting->toArray(true);
        }

        $this->setMultiple($entities, $this->ttl);
    }

    public function getAllByType($type): Collection
    {
        $key = $this->key('getAllByType', $type);
        $ids = (array)$this->get($key, []);

        $settings = collect();
        $keys = [];

        foreach($ids as $id) {
            $keys[] = $this->key('id', $id);
        }

        if(!empty($keys)) {
            $entities = $this->getMultiple($keys);

            $settings = (new SettingFactory())->makeFromArray($entities);
        }

        if(count($ids) !== $settings->count()) {
            $settings = collect();
        }

        return $settings;
    }

    public function getOneById($id) : ?Setting
    {
        $key = $this->key('id', $id);

        $entity = $this->get($key);

        return $entity ? (new SettingFactory())->makeWithArray((array)$entity) : null;
    }

    public function getOneByKeyAndType($key, $type): ?Setting
    {
        $key = $this->key('getOneByKeyAndType', "$key-$type");

        $id = $this->get($key);

        return $id ? $this->getOneById($id) : null;
    }
}
