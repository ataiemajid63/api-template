<?php

namespace App\Factories;

use App\Entities\Setting;

class SettingFactory extends Factory
{
    public function __construct()
    {
        parent::__construct();
    }

    public function make(\stdClass $entity)
    {
        $setting = new Setting();

        $setting->setId($entity->id ?? null);
        $setting->setType($entity->type ?? null);
        $setting->setKey($entity->key ?? null);
        $setting->setValue($entity->value ?? null);
        $setting->setUserId($entity->user_id ?? null);
        $setting->setClientId($entity->client_id ?? null);
        $setting->setCreatedAt($entity->created_at ?? null);
        $setting->setUpdatedAt($entity->updated_at ?? null);

        return $setting;
    }
}
