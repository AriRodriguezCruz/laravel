<?php

namespace App\Console\Commands;

use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Pool;
use Illuminate\Console\Command;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Client;
use GuzzleRetry\GuzzleRetryMiddleware;

class SimplePostRequest extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'request:post';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $client = new Client(); //GuzzleHttp\Client
        try {
            $total = 100000;

            $stack = HandlerStack::create();
            $stack->push(GuzzleRetryMiddleware::factory());
            // Create the requests
            $requests = function ($total) use($client, $stack) {
                for ($i = 1; $i <= $total; $i++) {
                    yield $client->post('https://atomic.incfile.com/fakepost', ['handler' => $stack]);
                }
            };


            $pool_batch = Pool::batch($client, $requests($total));
            foreach ($pool_batch as $pool => $res) {
                if ($res instanceof RequestException) {
                    continue;
                }
            }
        } catch (RequestException $e) {
            if ($e->hasResponse()) {
                $exception = (string) $e->getResponse()->getBody();
                $exception = json_decode($exception);
                return new JsonResponse($exception, $e->getCode());
            } else {
                return new JsonResponse($e->getMessage(), 503);
            }

        }
    }
}
