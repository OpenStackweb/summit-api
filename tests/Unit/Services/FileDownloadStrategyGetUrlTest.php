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

use App\Services\FileSystem\Dropbox\DropboxStorageFileDownloadStrategy;
use Illuminate\Container\Container;
use Illuminate\Support\Facades\Facade;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

/**
 * Class FileDownloadStrategyGetUrlTest
 *
 * Unit tests for {@see \App\Services\FileSystem\AbstractFileDownloadStrategy::getUrl()}
 * through the Dropbox strategy: the value serializers expose as `private_url`.
 *
 * @package Tests\Unit\Services
 */
class FileDownloadStrategyGetUrlTest extends TestCase
{
    /** @var MockInterface */
    private $cache;

    /** @var MockInterface */
    private $filesystem;

    /** @var MockInterface */
    private $config;

    protected function setUp(): void
    {
        parent::setUp();
        // Minimal facade application: Log -> NullLogger, Cache / Storage / Config -> mocks.
        Facade::clearResolvedInstances();
        $app = new Container();
        $app->singleton('log', fn() => new NullLogger());
        $this->cache = Mockery::mock();
        $this->filesystem = Mockery::mock();
        $this->config = Mockery::mock();
        $app->instance('cache', $this->cache);
        $app->instance('filesystem', $this->filesystem);
        $app->instance('config', $this->config);
        Container::setInstance($app);
        Facade::setFacadeApplication($app);
    }

    protected function tearDown(): void
    {
        Facade::setFacadeApplication(null);
        Facade::clearResolvedInstances();
        Container::setInstance(null);
        Mockery::close();
        parent::tearDown();
    }

    /**
     * The Dropbox adapter reports "no link" as an empty string (its parent forbids null).
     * The strategy must hand serializers null, and must not cache the miss.
     */
    public function testGetUrlReturnsNullAndDoesNotCacheWhenDiskHasNoUrl(): void
    {
        $path = 'PresentationMediaUploads/Private/73/9121/missing.pptx';

        $disk = Mockery::mock();
        $disk->shouldReceive('url')->once()->with($path)->andReturn('');
        $this->filesystem->shouldReceive('disk')->with('dropbox')->andReturn($disk);
        $this->cache->shouldReceive('get')->once()->andReturn(null);
        $this->cache->shouldNotReceive('add');

        $strategy = new DropboxStorageFileDownloadStrategy();

        $this->assertNull($strategy->getUrl($path));
    }

    /**
     * Anti-regression: a real URL is returned and cached for the configured lifetime.
     */
    public function testGetUrlReturnsAndCachesDiskUrl(): void
    {
        $path = 'PresentationMediaUploads/Private/73/9082/deck.pptx';
        $url = 'https://www.dropbox.com/scl/fi/abc123/deck.pptx?dl=0';

        $disk = Mockery::mock();
        $disk->shouldReceive('url')->once()->with($path)->andReturn($url);
        $this->filesystem->shouldReceive('disk')->with('dropbox')->andReturn($disk);
        $this->cache->shouldReceive('get')->once()->andReturn(null);
        $this->config->shouldReceive('get')->with('cache_api_response.file_url_lifetime', 3600)->andReturn(3600);
        $this->cache->shouldReceive('add')->once()->with(Mockery::type('string'), $url, 3600);

        $strategy = new DropboxStorageFileDownloadStrategy();

        $this->assertSame($url, $strategy->getUrl($path));
    }
}
