<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Repositories\MediaRepository;
use Aws\S3\S3Client;
use League\Flysystem\Filesystem;
use League\Flysystem\Adapter\Local;
use League\Flysystem\AwsS3v3\AwsS3Adapter;

class MediaController extends Controller
{
    private $mediaRepository;

    public function __construct(MediaRepository $mediaRepository)
    {
        parent::__construct();

        $this->mediaRepository = $mediaRepository;
    }

    /**
     * @return Filesystem
     */
    private function makeFileSystem()
    {
        $driver = config('filesystems.default');
        $config = config("filesystems.disks.$driver");

        switch($driver) {
            case 'minio':
                $client = new S3Client([
                    'credentials' => [
                        'key'    => $config['key'],
                        'secret' => $config['secret'],
                    ],
                    'region' => $config['region'],
                    'version' => 'latest',
                    'endpoint' => $config['endpoint'],
                ]);

                $adapter = new AwsS3Adapter($client, $config['bucket']);
            break;
            case 'local':
                $adapter = new Local($config['root']);
            break;
        }

        $filesystem = new Filesystem($adapter);

        return $filesystem;
    }
}
