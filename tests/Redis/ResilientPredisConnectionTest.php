<?php namespace Tests\Redis;
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

use App\Redis\ResilientPredisConnection;
use Mockery;
use Predis\Client;
use Predis\Connection\ConnectionException;
use Predis\Connection\NodeConnectionInterface;
use Tests\TestCase;

/**
 * Class ResilientPredisConnectionTest
 */
final class ResilientPredisConnectionTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    private function createConnectionException(): ConnectionException
    {
        $nodeConnection = Mockery::mock(NodeConnectionInterface::class);
        return new ConnectionException($nodeConnection, 'Error while reading line from the server.');
    }

    public function testIdempotentCommandRetriesAndSucceeds(): void
    {
        $client = Mockery::mock(Client::class);
        $client->shouldReceive('get')
            ->once()
            ->andThrow($this->createConnectionException());
        $client->shouldReceive('disconnect')->once();
        $client->shouldReceive('get')
            ->once()
            ->andReturn('value');

        $connection = new ResilientPredisConnection($client, 2, 1);
        $result = $connection->command('get', ['key']);

        $this->assertEquals('value', $result);
    }

    public function testNonIdempotentCommandDoesNotRetry(): void
    {
        $client = Mockery::mock(Client::class);
        $client->shouldReceive('lpush')
            ->once()
            ->andThrow($this->createConnectionException());
        $client->shouldNotReceive('disconnect');

        $connection = new ResilientPredisConnection($client, 2, 1);

        $this->expectException(ConnectionException::class);
        $connection->command('lpush', ['queue', 'payload']);
    }

    public function testEvalCommandDoesNotRetry(): void
    {
        $client = Mockery::mock(Client::class);
        $client->shouldReceive('eval')
            ->once()
            ->andThrow($this->createConnectionException());
        $client->shouldNotReceive('disconnect');

        $connection = new ResilientPredisConnection($client, 2, 1);

        $this->expectException(ConnectionException::class);
        $connection->command('eval', ['return 1', 0]);
    }

    public function testRetriesExhaustedThrowsException(): void
    {
        $client = Mockery::mock(Client::class);
        $client->shouldReceive('get')
            ->times(3) // 1 initial + 2 retries
            ->andThrow($this->createConnectionException());
        $client->shouldReceive('disconnect')->twice();

        $connection = new ResilientPredisConnection($client, 2, 1);

        $this->expectException(ConnectionException::class);
        $connection->command('get', ['key']);
    }

    public function testNoRetryOnNonConnectionException(): void
    {
        $client = Mockery::mock(Client::class);
        $client->shouldReceive('get')
            ->once()
            ->andThrow(new \Predis\Response\ServerException('ERR unknown command'));
        $client->shouldNotReceive('disconnect');

        $connection = new ResilientPredisConnection($client, 2, 1);

        $this->expectException(\Predis\Response\ServerException::class);
        $connection->command('get', ['key']);
    }

    public function testSuccessfulCommandDoesNotRetry(): void
    {
        $client = Mockery::mock(Client::class);
        $client->shouldReceive('get')
            ->once()
            ->andReturn('value');
        $client->shouldNotReceive('disconnect');

        $connection = new ResilientPredisConnection($client, 2, 1);
        $result = $connection->command('get', ['key']);

        $this->assertEquals('value', $result);
    }

    public function testZeroRetryLimitBehavesLikeStockConnection(): void
    {
        $client = Mockery::mock(Client::class);
        $client->shouldReceive('get')
            ->once()
            ->andThrow($this->createConnectionException());
        $client->shouldNotReceive('disconnect');

        $connection = new ResilientPredisConnection($client, 0, 1);

        $this->expectException(ConnectionException::class);
        $connection->command('get', ['key']);
    }

    public function testIdempotentWriteCommandRetries(): void
    {
        $client = Mockery::mock(Client::class);
        $client->shouldReceive('set')
            ->once()
            ->andThrow($this->createConnectionException());
        $client->shouldReceive('disconnect')->once();
        $client->shouldReceive('set')
            ->once()
            ->andReturn('OK');

        $connection = new ResilientPredisConnection($client, 2, 1);
        $result = $connection->command('set', ['key', 'value']);

        $this->assertEquals('OK', $result);
    }

    public function testRetrySucceedsOnSecondRetry(): void
    {
        $exception = $this->createConnectionException();

        $client = Mockery::mock(Client::class);
        // initial call fails
        $client->shouldReceive('get')
            ->once()
            ->andThrow($exception);
        // 1st retry fails
        $client->shouldReceive('get')
            ->once()
            ->andThrow($exception);
        // 2nd retry succeeds
        $client->shouldReceive('get')
            ->once()
            ->andReturn('recovered');
        $client->shouldReceive('disconnect')->twice();

        $connection = new ResilientPredisConnection($client, 2, 1);
        $result = $connection->command('get', ['key']);

        $this->assertEquals('recovered', $result);
    }

    public function testIncrCommandDoesNotRetry(): void
    {
        $client = Mockery::mock(Client::class);
        $client->shouldReceive('incr')
            ->once()
            ->andThrow($this->createConnectionException());
        $client->shouldNotReceive('disconnect');

        $connection = new ResilientPredisConnection($client, 2, 1);

        $this->expectException(ConnectionException::class);
        $connection->command('incr', ['counter']);
    }
}
