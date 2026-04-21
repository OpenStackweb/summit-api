<?php namespace App\Services\FileSystem\Dropbox;
/**
 * Copyright 2020 OpenStack Foundation
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 * http://www.apache.org/licenses/LICENSE-2.0
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 **/

use GrahamCampbell\GuzzleFactory\GuzzleFactory;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Middleware;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\ServiceProvider;
use League\Flysystem\Filesystem;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Spatie\Dropbox\Client as DropboxClient;
use App\Services\FileSystem\Dropbox\DropboxAdapter as CustomDropboxAdapter;

/**
 * Class DropboxServiceProvider
 * @package App\Services\FileSystem\Dropbox
 */
class DropboxServiceProvider extends ServiceProvider
{
    /**
     * Register bindings in the container.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot(): void
    {
        Storage::extend('dropbox', function ($app, $config) {
            // Build handler stack with rate-limit retry middleware
            $stack = GuzzleFactory::handler();
            $stack->before('http_errors', static::createRateLimitRetryMiddleware(), 'dropbox_rate_limit');

            // Create Guzzle client with custom handler
            $guzzleClient = new GuzzleClient(['handler' => $stack]);

            // use our custom dropbox adapter to override getUrl method
            // do not remove !
            $adapter = new CustomDropboxAdapter(
                new DropboxClient(
                    $config['authorization_token'] ?? '',
                    $guzzleClient,
                    maxUploadChunkRetries: 3
                )
            );

            return new FilesystemAdapter(
                new Filesystem($adapter, $config),
                $adapter,
                $config
            );
        });
    }

    /**
     * Create Guzzle middleware that retries 429 rate limit responses
     * with exponential backoff based on the Retry-After header.
     *
     * @param int $maxRetries Maximum number of retry attempts
     * @param int $maxDelay Maximum delay in seconds (cap for Retry-After header)
     * @return callable Guzzle middleware
     */
    public static function createRateLimitRetryMiddleware(int $maxRetries = 3, int $maxDelay = 300): callable
    {
        $decider = static function (
            int $retries,
            RequestInterface $request,
            ?ResponseInterface $response = null
        ) use ($maxRetries): bool {
            // Retry only on 429 status code
            return $retries < $maxRetries && $response && $response->getStatusCode() === 429;
        };

        $delay = static function (
            int $retries,
            ResponseInterface $response
        ) use ($maxDelay, $maxRetries): int {
            // Parse Retry-After header (in seconds)
            $retryAfter = $response->getHeaderLine('Retry-After');
            $delay = $retryAfter ? (int) $retryAfter : 0;

            // Cap at maxDelay
            $delay = min($delay, $maxDelay);

            // Log only if Laravel Log facade is available (production)
            if (class_exists(Log::class)) {
                try {
                    Log::warning(sprintf(
                        'Dropbox rate limited (429), retrying in %ds (attempt %d/%d)',
                        $delay,
                        $retries + 1,
                        $maxRetries
                    ));
                } catch (\Throwable $e) {
                    // Ignore logging errors in tests
                }
            }

            // Convert to milliseconds for Guzzle
            return $delay * 1000;
        };

        return Middleware::retry($decider, $delay);
    }
}
