<?php namespace Tests;
/*
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

use App\Console\Commands\SummitJsonGenerator;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use LaravelDoctrine\ORM\Facades\Registry;
use libs\utils\ICacheService;
use Libs\ModelSerializers\IModelSerializer;
use Mockery;
use models\summit\ISummitRepository;
use ModelSerializers\SerializerRegistry;
use services\model\ISummitService;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

/**
 * Stand-in for a Summit entity, only what the command reads from it.
 */
class JsonGeneratorFakeSummit
{
    public function __construct(private int $id, private bool $active = false) {}
    public function getId(): int { return $this->id; }
    public function getIdentifier(): int { return $this->id; }
    public function getName(): string { return "summit {$this->id}"; }
    public function isActive(): bool { return $this->active; }
}

/**
 * Serializer that returns whatever the test queued for the summit id (a Throwable is thrown).
 */
class JsonGeneratorFakeSerializer implements IModelSerializer
{
    public static array $results = [];

    public function __construct(private $summit, $context = null) {}

    // SerializerDecorator asks the wrapped serializer for its default fields/relations
    public function getAllowedRelations(): array { return []; }
    public function getAllowedFields(): array { return []; }

    public function serialize($expand = null, array $fields = [], array $relations = [], array $params = [])
    {
        $result = self::$results[$this->summit->getId()] ?? null;
        if ($result instanceof \Throwable) throw $result;
        return $result;
    }
}

/**
 * summit:json-generator: one cache payload per summit, and one bad summit never stops the others.
 *
 * @package Tests
 */
final class SummitJsonGeneratorCommandTest extends TestCase
{
    private array $cache = [];
    private $em;
    private $repository;
    private $original_registry;

    public function setUp(): void
    {
        parent::setUp();
        $this->cache = [];
        JsonGeneratorFakeSerializer::$results = [];

        // The real registry constructor boots the whole app (doctrine, apcu); swap in a bare
        // instance that only knows how to route the fake summit class to the fake serializer.
        $class = new \ReflectionClass(SerializerRegistry::class);
        $instance_prop = $class->getProperty('instance');
        $instance_prop->setAccessible(true);
        $this->original_registry = $instance_prop->getValue();

        $bare = $class->newInstanceWithoutConstructor();
        $cache_prop = $class->getProperty('resolve_cache');
        $cache_prop->setAccessible(true);
        $cache_prop->setValue($bare, [JsonGeneratorFakeSummit::class => JsonGeneratorFakeSerializer::class]);
        $instance_prop->setValue(null, $bare);

        $this->em = Mockery::mock(EntityManagerInterface::class);
        $this->em->shouldReceive('clear');
        $this->bind_entity_manager($this->em);

        $this->repository = Mockery::mock(ISummitRepository::class);
    }

    /**
     * IlluminateRegistry is final, so the facade root is swapped for a plain fake.
     */
    private function bind_entity_manager($em): void
    {
        $this->app->instance(ManagerRegistry::class, new class($em) {
            public function __construct(private $em) {}
            public function getManager($name = null) { return $this->em; }
        });
        Registry::clearResolvedInstance(ManagerRegistry::class);
    }

    public function tearDown(): void
    {
        Mockery::close();
        $prop = new \ReflectionProperty(SerializerRegistry::class, 'instance');
        $prop->setAccessible(true);
        $prop->setValue(null, $this->original_registry);
        parent::tearDown();
    }

    /**
     * @param JsonGeneratorFakeSummit[] $summits
     */
    private function run_command(array $summits): string
    {
        $by_id = [];
        foreach ($summits as $s) $by_id[$s->getId()] = $s;
        $this->repository->shouldReceive('getAvailables')->andReturn($summits);
        $this->repository->shouldReceive('getById')->andReturnUsing(fn($id) => $by_id[$id] ?? null);

        $cache = Mockery::mock(ICacheService::class);
        $cache->shouldReceive('setSingleValue')->andReturnUsing(function ($key, $value, $ttl = 0) {
            $this->cache[$key] = $value;
            return true;
        });

        $command = new SummitJsonGenerator($this->repository, Mockery::mock(ISummitService::class), $cache);
        $command->setLaravel($this->app);
        $output = new BufferedOutput();
        $this->assertSame(0, $command->run(new ArrayInput([]), $output));
        return $output->fetch();
    }

    public function testActiveSummitWritesSamePayloadToCurrentAndIdKeys(): void
    {
        JsonGeneratorFakeSerializer::$results[1] = ['id' => 1, 'schedule' => [1, 2, 3]];
        $this->run_command([new JsonGeneratorFakeSummit(1, true)]);

        $key_id = '/api/v1/summits/1.expand=schedule';
        $key_current = '/api/v1/summits/current.expand=schedule';
        $this->assertSame($this->cache[$key_current], $this->cache[$key_id]);
        $this->assertSame(
            ['id' => 1, 'schedule' => [1, 2, 3]],
            json_decode(gzinflate($this->cache[$key_id]), true)
        );
        $this->assertArrayHasKey($key_id . '.generated', $this->cache);
        $this->assertArrayHasKey($key_current . '.generated', $this->cache);
    }

    public function testInactiveSummitDoesNotTouchCurrentKey(): void
    {
        JsonGeneratorFakeSerializer::$results[2] = ['id' => 2];
        $this->run_command([new JsonGeneratorFakeSummit(2, false)]);

        $this->assertArrayHasKey('/api/v1/summits/2.expand=schedule', $this->cache);
        $this->assertArrayNotHasKey('/api/v1/summits/current.expand=schedule', $this->cache);
    }

    public function testNullSerializationSkipsOnlyThatSummit(): void
    {
        JsonGeneratorFakeSerializer::$results[1] = null;
        JsonGeneratorFakeSerializer::$results[2] = ['id' => 2];
        $this->run_command([new JsonGeneratorFakeSummit(1), new JsonGeneratorFakeSummit(2)]);

        $this->assertArrayNotHasKey('/api/v1/summits/1.expand=schedule', $this->cache);
        $this->assertArrayHasKey('/api/v1/summits/2.expand=schedule', $this->cache);
    }

    public function testExceptionInOneSummitDoesNotStopTheOthers(): void
    {
        JsonGeneratorFakeSerializer::$results[1] = new \RuntimeException('boom');
        JsonGeneratorFakeSerializer::$results[2] = ['id' => 2];
        $this->run_command([new JsonGeneratorFakeSummit(1), new JsonGeneratorFakeSummit(2)]);

        $this->assertArrayNotHasKey('/api/v1/summits/1.expand=schedule', $this->cache);
        $this->assertArrayHasKey('/api/v1/summits/2.expand=schedule', $this->cache);
    }

    public function testEntityManagerIsClearedAfterEverySummit(): void
    {
        $this->em = Mockery::mock(EntityManagerInterface::class);
        // once up front after reading the ids + once per summit
        $this->em->shouldReceive('clear')->times(3);
        $this->bind_entity_manager($this->em);

        JsonGeneratorFakeSerializer::$results[1] = ['id' => 1];
        JsonGeneratorFakeSerializer::$results[2] = new \RuntimeException('boom');
        $this->run_command([new JsonGeneratorFakeSummit(1), new JsonGeneratorFakeSummit(2)]);
    }

    public function testLogsPeakMemory(): void
    {
        JsonGeneratorFakeSerializer::$results[1] = ['id' => 1];
        $this->assertStringContainsString('peak memory usage', $this->run_command([new JsonGeneratorFakeSummit(1)]));
    }
}
