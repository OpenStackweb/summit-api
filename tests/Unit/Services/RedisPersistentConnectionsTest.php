<?php namespace Tests\Unit\Services;
/**
 * Copyright 2026 OpenStack Foundation
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

use Illuminate\Redis\RedisManager;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Facade;
use Illuminate\Support\Facades\Redis;
use services\utils\RedisCacheService;
use Tests\TestCase;

/**
 * Class RedisPersistentConnectionsTest
 * REDIS_PERSISTENT keeps one socket per redis connection across requests; RedisCacheService must not close it
 * when it is destroyed at the end of a request.
 */
final class RedisPersistentConnectionsTest extends TestCase
{
    /**
     * @return array the database.php redis section as it is evaluated with the given REDIS_PERSISTENT value
     */
    private function redisConfigWith(?string $persistent): array
    {
        $previous = getenv('REDIS_PERSISTENT');
        $persistent === null ? putenv('REDIS_PERSISTENT') : putenv("REDIS_PERSISTENT=$persistent");
        unset($_ENV['REDIS_PERSISTENT'], $_SERVER['REDIS_PERSISTENT']);
        try {
            return (require base_path('config/database.php'))['redis'];
        } finally {
            $previous === false ? putenv('REDIS_PERSISTENT') : putenv("REDIS_PERSISTENT=$previous");
        }
    }

    public function testPersistentConnectionsAreOffByDefault()
    {
        $redis = $this->redisConfigWith(null);
        foreach (['default', 'cache', 'session', 'worker'] as $name) {
            $this->assertFalse($redis[$name]['persistent'], "$name must not be persistent by default");
        }
    }

    public function testEveryConnectionGetsItsOwnPersistentId()
    {
        // connections share host:port: a shared socket would let one connection's SELECT switch the database of another
        $redis = $this->redisConfigWith('true');
        $ids = [];
        foreach (['default', 'cache', 'session', 'worker'] as $name) {
            $this->assertNotEmpty($redis[$name]['persistent'], "$name must be persistent");
            $this->assertSame($redis[$name]['persistent'], $redis[$name]['persistent_id'], "predis and phpredis must use the same id for $name");
            $ids[] = $redis[$name]['persistent_id'];
        }
        $this->assertCount(4, array_unique($ids));
    }

    /**
     * @param string|bool $persistent value of database.redis.default.persistent
     * @return int connections the server accepted while a second RedisCacheService was created and destroyed
     */
    private function connectionsOpenedByASecondService($persistent): int
    {
        Config::set('database.redis.default.persistent', $persistent);
        // the redis manager copies the config when it is built
        $this->app->singleton('redis', function ($app) {
            return new RedisManager($app, config('database.redis.client', 'predis'), config('database.redis'));
        });
        Facade::clearResolvedInstance('redis');

        $received = function (): int {
            // 'cache' is never persistent here and is kept open by the manager, so reading it opens nothing new
            // predis nests the fields under their INFO section, phpredis returns them flat
            $stats = Redis::connection('cache')->info('stats');
            return (int)($stats['Stats']['total_connections_received'] ?? $stats['total_connections_received']);
        };

        $received(); // opens the cache connection
        $first = new RedisCacheService();
        $first->exists('persistent-connections-test');
        unset($first); // end of the first request
        $before = $received();

        $second = new RedisCacheService(); // the next request on the same php-fpm worker
        $second->exists('persistent-connections-test');
        unset($second);

        return $received() - $before;
    }

    public function testPersistentSocketSurvivesTheEndOfARequest()
    {
        $this->assertSame(0, $this->connectionsOpenedByASecondService('summit_api_persistence_test'));
    }

    public function testWithoutPersistenceEveryRequestOpensItsOwnConnection()
    {
        $this->assertSame(1, $this->connectionsOpenedByASecondService(false));
    }
}
