<?php

namespace App\Http\Middleware;

use App\Contracts\Client;
use App\Http\Response;
use Illuminate\Http\Request;

class ClientAuthenticate
{
    /**
     * The client instance
     *
     * @var Client
     */
    protected $client;

    /**
     * Create a new middleware instance.
     *
     * @param  Client $client
     * @return void
     */
    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    /**
     * Handle an incoming request.
     *
     * @param  Request $request
     * @param  \Closure $next
     * @return mixed
     */
    public function handle(Request $request, \Closure $next)
    {
        if (!$this->client->check()) {
            $data = [
                'message' => 'Forbidden or expired client token'
            ];

            return new Response($data, Response::HTTP_FORBIDDEN);
        }

        // app('logger')->debug($request->path(), $request->input());

        $response = $next($request);

        // app('logger')->debug('response');
        // app('logger')->close();

        return $response;
    }
}
