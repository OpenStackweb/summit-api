<?php namespace App\Services\FileSystem\Swift;
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

use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use League\Flysystem\Filesystem;
use Illuminate\Support\ServiceProvider;
use OpenStack\OpenStack;
/**
 * Class SwiftServiceProvider
 * @package App\Services\FileSystem\Swift
 */
final class SwiftServiceProvider extends ServiceProvider
{
    const MAX_SKIPS = 4;

    private function shouldBypass(): bool
    {
        $skip_until =  Cache::tags(SwiftServiceProvider::class)->get('skip_until', Carbon::now());
        return Carbon::now() < $skip_until;
    }

    private function recalculateSkipDelay()
    {
        $cache = Cache::tags(SwiftServiceProvider::class);
        $skip_count = intval($cache->get('skip_count', 0));
        $skip_delay = intval($cache->get('skip_delay', 1));
        $skip_count++;
        $skip_delay = $skip_delay * pow(2, $skip_count - 1);
        $skip_until = Carbon::now()->add($skip_delay, 'minutes');
        $cache->put('skip_count', $skip_count);
        $cache->put('skip_delay', $skip_delay);
        $cache->put('skip_until', $skip_until);
        if ($skip_count > self::MAX_SKIPS) {
            $this->resetSkips();
        }
    }

    private function resetSkips()
    {
        Cache::tags(SwiftServiceProvider::class)->flush();
    }

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
    public function boot()
    {
        Storage::extend('swift', function ($app, $config) {

            try {
                if (empty($config["auth_url"]) || empty($config["region"]) || empty($config["container"])) return null;

                if ($this->shouldBypass()) return null;

                $configOptions = [
                    'authUrl' => $config["auth_url"],
                    'region' => $config["region"],
                ];

                $userName = $config["user_name"] ?? null;
                $userPassword = $config["api_key"] ?? null;

                if (!empty($userName) && !empty($userPassword)) {

                    $configOptions['user'] = [
                        'name' => $userName,
                        'password' => $userPassword,
                        'domain' => ['id' => $config["user_domain"] ?? 'default']
                    ];

                    $configOptions['scope'] = [
                        'project' => [
                            'name' => $config["project_name"],
                            'domain' => ['id' => $config["project_domain"] ?? 'default']
                        ],
                    ];
                }

                $appCredentialId = $config["app_credential_id"] ?? null;
                $appCredentialSecret = $config["app_credential_secret"] ?? null;

                if (!empty($appCredentialId) && !empty($appCredentialSecret)) {
                    $configOptions['application_credential'] = [
                        'id' => $appCredentialId,
                        'secret' => $appCredentialSecret,
                    ];
                }

                $openstackClient = new OpenStack($configOptions);

                $container = $openstackClient->objectStoreV1()->getContainer($config["container"]);

                $this->resetSkips();

                return new Filesystem(new SwiftAdapter($container));
            }
            catch(\Exception $ex){
                Log::error($ex);
                $this->recalculateSkipDelay();
                return null;
            }
        });
    }
}