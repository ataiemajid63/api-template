<?php

namespace App\Http\Controllers\V1;

use App\Entities\Media;
use App\Entities\User;
use App\Enums\HttpStatusCode;
use App\Enums\MediaItemType;
use App\Enums\MediaType;
use App\Http\Controllers\Controller;
use App\Http\Response;
use App\Repositories\MediaRepository;
use App\Repositories\UserRepository;
use Aws\S3\S3Client;
use Illuminate\Http\Request;
use League\Flysystem\Filesystem;
use League\Flysystem\Adapter\Local;
use League\Flysystem\AwsS3v3\AwsS3Adapter;
use Pasoonate\Pasoonate;

class MediaController extends Controller
{
    private $mediaRepository;
    private $userRepository;

    public function __construct(MediaRepository $mediaRepository, UserRepository $userRepository)
    {
        parent::__construct();

        $this->mediaRepository = $mediaRepository;
        $this->userRepository = $userRepository;
    }

    public function storeUserAvatar(Request $request)
    {
        $validator = $this->makeValidator($request, [
            'file' => 'required|string',
        ]);

        if($validator->fails()) {
            $data = [
                'errors' => $validator->errors()
            ];

            return new Response($data, HttpStatusCode::UNPROCESSABLE_ENTITY);
        }

        $image = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $request->input('file')));

        if(imagecreatefromstring($image) === false) {
            $data = [
                'errors' => [
                    'file' => ['validation:image']
                ]
            ];

            return new Response($data, HttpStatusCode::UNPROCESSABLE_ENTITY);
        }

        /**
         * @var User $user
         */
        $user = $request->user();
        $storage = $this->makeFileSystem();
        $path = 'media/users/' . $user->getId();
        $filename = 'avatar.jpg';

        if (!$storage->has($path)) {
            $storage->createDir($path);
        }

        $saved = $storage->put("{$path}/{$filename}", $image);

        if(!$saved) {
            $data = [
                'errors' => [
                    'file' => 'not uploaded'
                ]
            ];

            return new Response($data, HttpStatusCode::INTERNAL_SERVER_ERROR);
        }

        $this->mediaRepository->deleteByItem(MediaItemType::USER, $user->getId());

        $medium = new Media();

        $medium->setId(null);
        $medium->setUserId($user->getId());
        $medium->setItemId($user->getId());
        $medium->setItemType(MediaItemType::USER);
        $medium->setType(MediaType::IMAGE);
        $medium->setFileName($filename);
        $medium->setCode(null);
        $medium->setTitle(null);
        $medium->setOrdering(0);
        $medium->setIsCover(1);
        $medium->setVerifiedAt(time());
        $medium->setCreatedAt(time());
        $medium->setUpdatedAt(time());
        $medium->setDeletedAt(null);

        $medium = $this->mediaRepository->insert($medium);

        if(is_null($medium->getId())) {
            $data = [
                'errors' => [
                    'file' => 'dont inserted media'
                ]
            ];

            return new Response($data, HttpStatusCode::INTERNAL_SERVER_ERROR);
        }

        $user->setHasAvatar(1);
        $user->setUpdatedAt(time());
        $user->setUpdatedAtJalali(Pasoonate::make()->jalali()->format('yyyy/MM/dd HH:mm:ss'));

        $this->userRepository->update($user);

        return new Response();
    }

    public function deleteUserAvatar(Request $request)
    {
        /**
         * @var User $user
         */
        $user = $request->user();

        $deleted = $this->mediaRepository->deleteByItem(MediaItemType::USER, $user->getId());

        if($deleted) {
            $user->setHasAvatar(0);
            $user->setUpdatedAt(time());
            $user->setUpdatedAtJalali(Pasoonate::make()->jalali()->format('yyyy/MM/dd HH:mm:ss'));

            $this->userRepository->update($user);
        }

        return new Response();
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
