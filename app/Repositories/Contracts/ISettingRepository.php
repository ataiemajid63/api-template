<?php

namespace App\Repositories\Contracts;

use App\Entities\Setting;
use Illuminate\Support\Collection;

interface ISettingRepository
{
    /**
     * @param string $type
     *
     * @return Setting[]|Collection
     */
    public function getAllByType($type): Collection;

    /**
     * @param string $key
     * @param string $type
     *
     * @return Setting
     */
    public function getOneByKeyAndType($key, $type): ?Setting;
}
