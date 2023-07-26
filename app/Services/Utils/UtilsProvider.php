<?php namespace App\Services\Utils;

/**
 * Copyright 2023 OpenStack Foundation
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
use App\Services\Model\Strategies\PromoCodes\IPromoCodeGenerator;
use App\Services\Model\Strategies\PromoCodes\PromoCodeGenerator;
use App\Services\Utils\Security\EncryptionKeysGenerator;
use App\Services\Utils\Security\IEncryptionKeysGenerator;
use Illuminate\Support\Facades\App;
use Illuminate\Support\ServiceProvider;
use libs\utils\ICacheService;

/**
 * Class UtilsProvider
 * @package App\Services\Utils
 */
final class UtilsProvider extends ServiceProvider
{
    protected $defer = false;

    public function boot()
    {
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        App::singleton(IPromoCodeGenerator::class, function () {
            return new PromoCodeGenerator(
                App::make(ICacheService::class),
                PromoCodeGenerator::Length
            );
        });

        App::singleton(IEncryptionKeysGenerator::class, function () {
            return new EncryptionKeysGenerator();
        });
    }
}