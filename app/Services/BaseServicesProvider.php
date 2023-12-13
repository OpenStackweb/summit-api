<?php namespace services;
/**
 * Copyright 2015 OpenStack Foundation
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
use App\Permissions\IPermissionsManager;
use App\Permissions\PermissionsManager;
use App\Services\Apis\ExternalUserApi;
use App\Services\Apis\GoogleGeoCodingAPI;
use App\Services\Apis\IEmailTemplatesApi;
use App\Services\Apis\IExternalUserApi;
use App\Services\Apis\IGeoCodingAPI;
use App\Services\Apis\IMailApi;
use App\Services\Apis\IMUXApi;
use App\Services\Apis\IPasswordlessAPI;
use App\Services\Apis\MailService;
use App\Services\Apis\MUXApi;
use App\Services\Apis\MuxCredentials;
use App\Services\Apis\PasswordlessAPI;
use App\Services\Apis\Samsung\ISamsungRegistrationAPI;
use App\Services\Apis\Samsung\SamsungRegistrationAPI;
use App\Services\Model\FolderService;
use App\Services\Model\IFolderService;
use App\Services\utils\EmailExcerptService;
use App\Services\Utils\Facades\EmailExcerpt;
use App\Services\Utils\ILockManagerService;
use App\Services\Utils\LockManagerService;
use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\ServiceProvider;
use libs\utils\ICacheService;
use libs\utils\IEncryptionService;
use libs\utils\ITransactionService;
use models\utils\SilverstripeBaseModel;
use ModelSerializers\BaseSerializerTypeSelector;
use ModelSerializers\ISerializerTypeSelector;
use services\apis\EventbriteAPI;
use services\apis\FireBaseGCMApi;
use services\apis\IEventbriteAPI;
use services\apis\IPushNotificationApi;
use services\utils\DoctrineTransactionService;
use services\utils\EncryptionService;
use services\utils\RedisCacheService;

/***
 * Class BaseServicesProvider
 * @package services
 */
final class BaseServicesProvider extends ServiceProvider
{
    protected $defer = true;

    public function boot()
    {
    }

    public function register()
    {
        App::singleton(ClientInterface::class, Client::class);

        App::singleton(ICacheService::class, RedisCacheService::class);

        App::singleton(IPermissionsManager::class, PermissionsManager::class);

        App::singleton(ITransactionService::class, function () {
            return new DoctrineTransactionService(SilverstripeBaseModel::EntityManager);
        });

        App::singleton(IEncryptionService::class, function () {
            return new EncryptionService(
                Config::get("server.ss_encrypt_key", ''),
                Config::get("server.ss_encrypt_cypher", '')
            );
        });

        // setting facade

        App::singleton('encryption', function ($app) {
            return new EncryptionService(
                Config::get("server.ss_encrypt_key", ''),
                Config::get("server.ss_encrypt_cypher", '')
            );
        });

        App::scoped(EmailExcerpt::class, function ($app) {
            return new EmailExcerptService();
        });

        App::singleton(ISerializerTypeSelector::class, BaseSerializerTypeSelector::class);

        App::singleton(IEventbriteAPI::class, function () {
            $api = new EventbriteAPI();
            $api->setCredentials(array('token' => Config::get("server.eventbrite_oauth2_personal_token", null)));
            return $api;
        });

        App::singleton(IPushNotificationApi::class, function () {
            $api = new FireBaseGCMApi(Config::get("server.firebase_gcm_server_key", null));
            return $api;
        });

        App::singleton(IGeoCodingAPI::class, function () {
            return new GoogleGeoCodingAPI
            (
                Config::get("server.google_geocoding_api_key", null)
            );
        });

        App::singleton(
            IExternalUserApi::class,
            ExternalUserApi::class
        );

        App::singleton
        (
            IFolderService::class,
            FolderService::class
        );

        App::singleton(
            IMailApi::class,
            MailService::class
        );

        App::singleton(
            IEmailTemplatesApi::class,
            MailService::class
        );

        App::singleton(
            ILockManagerService::class,
            LockManagerService::class
        );

        App::singleton(
            IPasswordlessAPI::class,
            PasswordlessAPI::class
        );

        App::singleton(
            ISamsungRegistrationAPI::class,
            function(){
                return new SamsungRegistrationAPI
                (
                    App::make( ClientInterface::class),
                    Config::get("server.samsung_registration_api_endpoint", null)
                );
            }
        );

        App::singleton(
            IMUXApi::class,
            function(){
                return new MUXApi();
            }
        );
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return [
            ClientInterface::class,
            ICacheService::class,
            IPermissionsManager::class,
            ITransactionService::class,
            ISerializerTypeSelector::class,
            IEncryptionService::class,
            IEventbriteAPI::class,
            IPushNotificationApi::class,
            IGeoCodingAPI::class,
            IExternalUserApi::class,
            IFolderService::class,
            ILockManagerService::class,
            IPasswordlessAPI::class,
            ISamsungRegistrationAPI::class,
            IMUXApi::class,
            IEmailTemplatesApi::class,
        ];
    }
}