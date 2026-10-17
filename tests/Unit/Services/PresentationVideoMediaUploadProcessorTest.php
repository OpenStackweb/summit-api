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

use App\Models\Utils\IStorageTypesConstants;
use App\Services\Apis\MuxCredentials;
use App\Services\Model\Imp\PresentationVideoMediaUploadProcessor;
use Illuminate\Container\Container;
use Illuminate\Support\Facades\Facade;
use libs\utils\ITransactionService;
use Mockery;
use models\summit\ISummitEventRepository;
use models\summit\Presentation;
use MuxPhp\Api\AssetsApi as MuxAssetApi;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

/**
 * Class PresentationVideoMediaUploadProcessorTest
 *
 * Unit tests for {@see PresentationVideoMediaUploadProcessor::processEvent()}.
 *
 * @package Tests\Unit\Services
 */
class PresentationVideoMediaUploadProcessorTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // Minimal facade application: Log -> NullLogger, Cache / Storage -> mocks
        // (same setup as FileDownloadStrategyGetUrlTest).
        Facade::clearResolvedInstances();
        $app = new Container();
        $app->singleton('log', fn() => new NullLogger());
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
     * The video never reached the private storage (e.g. its pending upload failed), so the
     * download strategy has no URL for it and returns null. The event must be skipped without
     * asking MUX to ingest an asset that has no input URL.
     */
    public function testProcessEventSkipsMuxIngestWhenPrivateStorageHasNoUrl(): void
    {
        $event_id = 9121;
        $path = 'PresentationMediaUploads/Private/73/9121/missing.mp4';

        $disk = Mockery::mock();
        $disk->shouldReceive('url')->once()->with($path)->andReturn('');
        $filesystem = Mockery::mock();
        $filesystem->shouldReceive('disk')->with('dropbox')->andReturn($disk);
        $cache = Mockery::mock();
        $cache->shouldReceive('get')->once()->andReturn(null);
        $cache->shouldNotReceive('add');
        Container::getInstance()->instance('filesystem', $filesystem);
        Container::getInstance()->instance('cache', $cache);

        $mediaUploadType = Mockery::mock();
        $mediaUploadType->shouldReceive('isVideo')->andReturn(true);
        $mediaUploadType->shouldReceive('getPrivateStorageType')->andReturn(IStorageTypesConstants::DropBox);

        $mediaUpload = Mockery::mock();
        $mediaUpload->shouldReceive('getId')->andReturn(1);
        $mediaUpload->shouldReceive('getFilename')->andReturn('missing.mp4');
        $mediaUpload->shouldReceive('getMediaUploadType')->andReturn($mediaUploadType);
        $mediaUpload->shouldReceive('getRelativePath')->with(IStorageTypesConstants::PrivateType, null)->andReturn($path);

        $event = Mockery::mock(Presentation::class);
        $event->shouldReceive('isPublished')->andReturn(true);
        $event->shouldReceive('getTitle')->andReturn('Test Presentation');
        $event->shouldReceive('getMuxAssetId')->andReturn(null);
        $event->shouldReceive('getMediaUploads')->andReturn([$mediaUpload]);
        $event->shouldNotReceive('setMuxAssetId');
        $event->shouldNotReceive('setStreamingUrl');
        $event->shouldNotReceive('setMuxPlaybackId');

        $eventRepository = Mockery::mock(ISummitEventRepository::class);
        $eventRepository->shouldReceive('getByIdExclusiveLock')->with($event_id)->andReturn($event);

        $txService = Mockery::mock(ITransactionService::class);
        $txService->shouldReceive('transaction')->once()->andReturnUsing(function ($callback) {
            return $callback();
        });

        $muxIngestRequested = false;
        $assetsApi = Mockery::mock(MuxAssetApi::class);
        $assetsApi->shouldReceive('createAsset')->andReturnUsing(function () use (&$muxIngestRequested) {
            $muxIngestRequested = true;
            throw new \RuntimeException('MUX must not be asked to ingest an asset without an input URL');
        });

        $processor = new PresentationVideoMediaUploadProcessor($eventRepository, $txService);
        // Inject the MUX client so no real API client is built.
        $prop = (new \ReflectionClass($processor))->getProperty('assets_api');
        $prop->setAccessible(true);
        $prop->setValue($processor, $assetsApi);

        $res = $processor->processEvent($event_id, null, new MuxCredentials('token-id', 'token-secret'));

        $this->assertFalse($res);
        $this->assertFalse($muxIngestRequested, 'MUX asset creation was requested without an input URL');
    }
}
