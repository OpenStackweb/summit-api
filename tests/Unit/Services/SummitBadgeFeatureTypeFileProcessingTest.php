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

use App\Http\Utils\IFileUploader;
use App\Models\Foundation\Summit\Repositories\ISummitBadgeFeatureTypeRepository;
use App\Services\Model\AbstractService;
use App\Services\Model\FileInfoDTO;
use App\Services\Model\SummitBadgeFeatureTypeService;
use Illuminate\Container\Container;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Facade;
use libs\utils\ITransactionService;
use Mockery;
use models\exceptions\ValidationException;
use models\main\File;
use models\summit\Summit;
use models\summit\SummitBadgeFeatureType;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

/**
 * Class SummitBadgeFeatureTypeFileProcessingTest
 *
 * Unit tests for the async image post-processing of badge feature types
 * (FileProcessingJob -> FilePostProcessorService -> processFileForChildEntity).
 *
 * @package Tests\Unit\Services
 */
class SummitBadgeFeatureTypeFileProcessingTest extends TestCase
{
    private const RemotePath = 'badge-features/tmp/feature.png';

    // 1x1 transparent PNG, so UploadedFile::extension() guesses 'png' from the content
    private const PngBase64 = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==';

    private Container $app;

    protected function setUp(): void
    {
        parent::setUp();
        // Same minimal facade application as CompanyFileProcessingTest
        Facade::clearResolvedInstances();
        $this->app = new Container();
        $this->app->singleton('log', fn() => new NullLogger());
        Container::setInstance($this->app);
        Facade::setFacadeApplication($this->app);
    }

    protected function tearDown(): void
    {
        Facade::setFacadeApplication(null);
        Facade::clearResolvedInstances();
        Container::setInstance(null);
        Mockery::close();
        parent::tearDown();
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function pngContent(): string
    {
        return base64_decode(self::PngBase64);
    }

    /**
     * Binds a storage disk serving $content at RemotePath. Returns the disk mock so
     * each test can set its own expectations on delete().
     */
    private function bindStorageFakes(string $content, ?callable $onSize = null): Mockery\MockInterface
    {
        $stream = fopen('php://temp', 'r+b');
        fwrite($stream, $content);
        rewind($stream);

        $mockDisk = Mockery::mock();
        $mockDisk->shouldReceive('exists')->with(self::RemotePath)->andReturn(true);
        $mockDisk->shouldReceive('readStream')->with(self::RemotePath)->andReturn($stream);
        $mockDisk->shouldReceive('size')->with(self::RemotePath)->andReturnUsing(function () use ($content, $onSize) {
            if (!is_null($onSize)) $onSize();
            return strlen($content);
        });

        $mockFsFactory = Mockery::mock();
        $mockFsFactory->shouldReceive('disk')->andReturn($mockDisk);

        $mockConfig = Mockery::mock();
        $mockConfig->shouldReceive('get')->with('file_upload.storage_driver')->andReturn('s3');
        $mockConfig->shouldReceive('get')->withAnyArgs()->andReturn(null);

        $this->app->singleton('filesystem', fn() => $mockFsFactory);
        $this->app->singleton('config', fn() => $mockConfig);

        return $mockDisk;
    }

    private function makeImageDto(string $memberName = 'image', ?string $md5 = null): FileInfoDTO
    {
        return new FileInfoDTO(
            owner_entity_id: 1,
            owner_entity_class: SummitBadgeFeatureType::class,
            owner_member_name: $memberName,
            filepath: self::RemotePath,
            filename: 'feature.png',
            size: 1,
            md5: $md5,
            mime_type: 'image/png',
        );
    }

    /**
     * Service with feature type #1 on a summit, and a file uploader mock the test configures.
     */
    private function makeService(IFileUploader $fileUploader, ?SummitBadgeFeatureType &$feature = null): SummitBadgeFeatureTypeService
    {
        $summit = Mockery::mock(Summit::class);
        $feature = Mockery::mock(SummitBadgeFeatureType::class);
        $feature->shouldReceive('getSummit')->andReturn($summit);
        $summit->shouldReceive('getFeatureTypeById')->with(1)->andReturn($feature);

        $repo = Mockery::mock(ISummitBadgeFeatureTypeRepository::class);
        $repo->shouldReceive('getById')->with(1)->andReturn($feature);

        $tx = Mockery::mock(ITransactionService::class);
        $tx->shouldReceive('transaction')->andReturnUsing(fn($cb) => $cb());

        $ref = new \ReflectionClass(SummitBadgeFeatureTypeService::class);
        $service = $ref->newInstanceWithoutConstructor();

        foreach (['file_uploader' => $fileUploader, 'repository' => $repo] as $name => $value) {
            $prop = $ref->getProperty($name);
            $prop->setAccessible(true);
            $prop->setValue($service, $value);
        }

        $txProp = (new \ReflectionClass(AbstractService::class))->getProperty('tx_service');
        $txProp->setAccessible(true);
        $txProp->setValue($service, $tx);

        return $service;
    }

    // -------------------------------------------------------------------------
    // Tests
    // -------------------------------------------------------------------------

    public function testProcessFileForChildEntityThrowsForUnknownMemberName(): void
    {
        $service = $this->makeService(Mockery::mock(IFileUploader::class));

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/logo/');

        $service->processFileForChildEntity($this->makeImageDto('logo'));
    }

    public function testMd5MismatchFailsWithoutSettingImageAndKeepsRemoteFile(): void
    {
        $disk = $this->bindStorageFakes($this->pngContent());
        $disk->shouldNotReceive('delete');

        $uploader = Mockery::mock(IFileUploader::class);
        $uploader->shouldNotReceive('build');
        $service = $this->makeService($uploader, $feature);
        $feature->shouldNotReceive('setImage');

        try {
            $service->processFileForChildEntity($this->makeImageDto('image', 'ffffffffffffffffffffffffffffffff'));
            $this->fail('Expected ValidationException was not thrown.');
        } catch (ValidationException $e) {
            $this->assertStringContainsString('MD5 mismatch', $e->getMessage());
        }
    }

    public function testUnreadableLocalCopyIsReportedSeparatelyFromMd5Mismatch(): void
    {
        // Delete the downloaded temp copy right after the download so md5_file() cannot read it
        $disk = $this->bindStorageFakes($this->pngContent(), function () {
            $copies = glob(SummitBadgeFeatureTypeService::getLocalTmpStorage() . '/fproc_*') ?: [];
            usort($copies, fn($a, $b) => filemtime($b) <=> filemtime($a));
            if (!empty($copies)) @unlink($copies[0]);
        });
        $disk->shouldNotReceive('delete');

        $uploader = Mockery::mock(IFileUploader::class);
        $uploader->shouldNotReceive('build');
        $service = $this->makeService($uploader);

        // md5_file() warns before returning false; outside Laravel nothing converts that warning
        set_error_handler(fn() => true, E_WARNING);
        try {
            $service->processFileForChildEntity($this->makeImageDto('image', md5($this->pngContent())));
            $this->fail('Expected ValidationException was not thrown.');
        } catch (ValidationException $e) {
            $this->assertStringContainsString('unable to read', $e->getMessage());
            $this->assertStringNotContainsString('MD5 mismatch', $e->getMessage());
        } finally {
            restore_error_handler();
        }
    }

    public function testSuccessSetsImageAndCleansUpLocalAndRemoteFiles(): void
    {
        $disk = $this->bindStorageFakes($this->pngContent());
        $disk->shouldReceive('delete')->with(self::RemotePath)->once()->andReturn(true);

        $image = Mockery::mock(File::class);
        $localPath = null;
        $uploader = Mockery::mock(IFileUploader::class);
        $uploader->shouldReceive('build')
            ->once()
            ->andReturnUsing(function (UploadedFile $file, string $folder) use ($image, &$localPath) {
                $localPath = $file->getPathname();
                $this->assertSame('summit-event-images', $folder);
                return $image;
            });

        $service = $this->makeService($uploader, $feature);
        $feature->shouldReceive('setImage')->once()->with($image);

        $result = $service->processFileForChildEntity($this->makeImageDto('image', md5($this->pngContent())));

        $this->assertSame($image, $result);
        $this->assertNotNull($localPath);
        $this->assertFileDoesNotExist($localPath);
    }

    public function testUploadFailurePropagatesForRetryAndKeepsRemoteFile(): void
    {
        $disk = $this->bindStorageFakes($this->pngContent());
        $disk->shouldNotReceive('delete');

        $localPath = null;
        $uploader = Mockery::mock(IFileUploader::class);
        $uploader->shouldReceive('build')
            ->once()
            ->andReturnUsing(function (UploadedFile $file) use (&$localPath) {
                $localPath = $file->getPathname();
                throw new \RuntimeException('storage unavailable');
            });

        $service = $this->makeService($uploader, $feature);
        $feature->shouldNotReceive('setImage');

        try {
            $service->processFileForChildEntity($this->makeImageDto('image', md5($this->pngContent())));
            $this->fail('Expected RuntimeException was not thrown.');
        } catch (\RuntimeException $e) {
            // not a ValidationException, so FileProcessingJob lets it propagate and the queue retries
            $this->assertNotInstanceOf(ValidationException::class, $e);
        }

        $this->assertFileDoesNotExist($localPath);
    }

    public function testCleanupFailureAfterSuccessfulUploadDoesNotThrow(): void
    {
        $disk = $this->bindStorageFakes($this->pngContent());
        $disk->shouldReceive('delete')->andThrow(new \RuntimeException('delete failed'));

        $image = Mockery::mock(File::class);
        $uploader = Mockery::mock(IFileUploader::class);
        // a single build() call: the job succeeds, so no retry creates a second File record
        $uploader->shouldReceive('build')->once()->andReturn($image);

        $service = $this->makeService($uploader, $feature);
        $feature->shouldReceive('setImage')->once()->with($image);

        $this->assertSame($image, $service->processFileForChildEntity($this->makeImageDto('image', md5($this->pngContent()))));
    }
}
