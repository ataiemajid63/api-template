<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class ClearRedisCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'redis:clear';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Flush the application redis';

    /**
     * The cache manager instance.
     *
     * @var \Illuminate\Redis\RedisManager
     */
    protected $redis;

    /**
     * Create a new redis clear command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();

        $this->redis = app('redis');
    }

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        $this->redis->connection()->command('FLUSHDB');

        $this->info('Redis DB cleared!');
    }
}
