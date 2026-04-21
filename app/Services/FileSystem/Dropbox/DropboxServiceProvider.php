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

use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\ServiceProvider;
use League\Flysystem\Filesystem;
use App\Services\FileSystem\Dropbox\RetryAfterDropboxClient as DropboxClient;
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
            // use our custom dropbox adapter to override getUrl method
            // do not remove !

            $refreshToken = $config['refresh_token'] ?? '';
            $accessToken  = $config['authorization_token'] ?? '';
            $appKey       = $config['app_key'] ?? '';
            $appSecret    = $config['app_secret'] ?? '';

            // If a refresh token is provided, use AutoRefreshingDropBoxTokenService
            // which implements RefreshableTokenProvider — the Spatie Client will
            // automatically call refresh() on 401 and retry the request.
            // Otherwise, fall back to a static access token (string).
            $tokenOrProvider = !empty($refreshToken)
                ? new AutoRefreshingDropBoxTokenService($appKey, $appSecret, $refreshToken)
                : $accessToken;

            $adapter = new CustomDropboxAdapter(
                new DropboxClient
                (
                    $tokenOrProvider,
                    maxUploadChunkRetries:5
                )
            );

            return new FilesystemAdapter(
                new Filesystem($adapter, $config),
                $adapter,
                $config
            );
        });
    }
}
