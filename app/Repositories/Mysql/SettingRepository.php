<?php

namespace App\Repositories\Mysql;

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
    }

    public function getAllByType($type): Collection
    {
        $query = $this->query();

        $query->where('type', $type);

        $entities = $query->get();

        return (new SettingFactory())->makeFromCollection($entities);
    }

    public function getOneByKeyAndType($key, $type): ?Setting
    {
        $query = $this->query();

        $query->where('key', $key);
        $query->where('type', $type);

        $entity = $query->first();

        return $entity ? (new SettingFactory())->make($entity) : null;
    }
}
