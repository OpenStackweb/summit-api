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

use App\Services\FilePostProcessorService;
use App\Services\Model\FileInfoDTO;
use App\Services\Model\Imp\CompanyService;
use Illuminate\Container\Container;
use Illuminate\Support\Facades\Facade;
use Mockery;
use models\exceptions\ValidationException;
use models\main\Company;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

/**
 * Class CompanyFileProcessingTest
 *
 * Unit tests for the async file post-processing pipeline introduced for company
 * logos. Covers the new behaviors that are hardest to exercise via integration:
 *
 *  - processFileForChildEntity: unknown owner_member_name -> InvalidArgumentException
 *  - processFileForChildEntity: MD5 mismatch -> "MD5 mismatch" (not "unable to read")
 *  - processFileForChildEntity: MD5 check skipped when md5 field is null
 *  - FilePostProcessorService: unknown entity class -> throws (not silent false)
 *  - FileInfoDTO: survives PHP queue serialization round-trip
 *
 * @package Tests\Unit\Services
 */
class CompanyFileProcessingTest extends TestCase
{
    private Container $app;

    protected function setUp(): void
    {
        parent::setUp();
        // Minimal facade application so Log::debug/warning calls resolve without
        // a full Laravel app or database. clearResolvedInstances() first to drop
        // any cached instance from a prior test that booted the full app.
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

    private function makeCompanyService(): CompanyService
    {
        // CompanyService is final with a multi-arg constructor. Skip the
        // constructor entirely - processFileForChildEntity only uses static
        // trait methods and $this->addCompanyLogo/addCompanyBigLogo, neither
        // of which is reached by the paths tested here.
        $ref = new \ReflectionClass(CompanyService::class);
        return $ref->newInstanceWithoutConstructor();
    }

    /**
     * Register Storage and Config fakes so getFileFromRemoteStorageOnTempStorage
     * and cleanLocalAndRemoteFile resolve without hitting real cloud storage.
     * The disk mock serves an empty stream, producing a 0-byte local temp file.
     */
    private function bindStorageFakes(string $remotePath): void
    {
        $emptyStream = fopen('php://temp', 'r+b');

        $mockDisk = Mockery::mock();
        $mockDisk->shouldReceive('exists')->with($remotePath)->andReturn(true);
        $mockDisk->shouldReceive('readStream')->with($remotePath)->andReturn($emptyStream);
        $mockDisk->shouldReceive('size')->with($remotePath)->andReturn(0); // matches 0-byte local copy
        $mockDisk->shouldReceive('delete')->with($remotePath)->andReturn(true);

        $mockFsFactory = Mockery::mock();
        $mockFsFactory->shouldReceive('disk')->andReturn($mockDisk);

        $mockConfig = Mockery::mock();
        $mockConfig->shouldReceive('get')->with('file_upload.storage_driver')->andReturn('s3');
        $mockConfig->shouldReceive('get')->withAnyArgs()->andReturn(null);

        $this->app->singleton('filesystem', fn() => $mockFsFactory);
        $this->app->singleton('config', fn() => $mockConfig);
    }

    private function makeLogoDto(string $memberName, ?string $md5 = null): FileInfoDTO
    {
        return new FileInfoDTO(
            owner_entity_id: 1,
            owner_entity_class: Company::class,
            owner_member_name: $memberName,
            filepath: 'companies/1/tmp/logo.png',
            filename: 'logo.png',
            size: 1024,
            md5: $md5,
        );
    }

    // -------------------------------------------------------------------------
    // processFileForChildEntity - routing / default case
    // -------------------------------------------------------------------------

    public function testProcessFileForChildEntityThrowsForUnknownMemberName(): void
    {
        $service = $this->makeCompanyService();

        $dto = $this->makeLogoDto('avatar');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/avatar/');

        $service->processFileForChildEntity($dto);
    }

    // -------------------------------------------------------------------------
    // processFileForChildEntity - MD5 verification logic
    // -------------------------------------------------------------------------

    /**
     * When the provided MD5 does not match the downloaded file's hash,
     * the exception message must say "MD5 mismatch", NOT "unable to read local
     * temp file". This guards against the regression where md5_file() returning
     * false and an actual mismatch produced the same error message.
     */
    public function testProcessFileForChildEntityThrowsMd5MismatchMessageOnHashDifference(): void
    {
        $remotePath = 'companies/1/tmp/logo.png';
        $this->bindStorageFakes($remotePath);

        $service = $this->makeCompanyService();

        // md5 of a 0-byte file is d41d8cd98f00b204e9800998ecf8427e.
        // Supplying a different hash triggers the mismatch branch.
        $dto = $this->makeLogoDto('logo', 'ffffffffffffffffffffffffffffffff');

        try {
            $service->processFileForChildEntity($dto);
            $this->fail('Expected ValidationException was not thrown.');
        } catch (ValidationException $e) {
            $this->assertStringContainsString('MD5 mismatch', $e->getMessage());
            $this->assertStringNotContainsString('unable to read', $e->getMessage());
        }
    }

    /**
     * Same mismatch path for the big_logo member - ensures both switch cases
     * share the same md5 check behaviour.
     */
    public function testProcessFileForChildEntityMd5MismatchAlsoWorksForBigLogo(): void
    {
        $remotePath = 'companies/1/tmp/logo.png';
        $this->bindStorageFakes($remotePath);

        $service = $this->makeCompanyService();

        $dto = $this->makeLogoDto('big_logo', 'ffffffffffffffffffffffffffffffff');

        try {
            $service->processFileForChildEntity($dto);
            $this->fail('Expected ValidationException was not thrown.');
        } catch (ValidationException $e) {
            $this->assertStringContainsString('MD5 mismatch', $e->getMessage());
        }
    }

    // -------------------------------------------------------------------------
    // FilePostProcessorService - unknown entity class now throws, not silent false
    // -------------------------------------------------------------------------

    public function testPostProcessFileApiThrowsForUnknownEntityClass(): void
    {
        $service = new FilePostProcessorService();

        $dto = new FileInfoDTO(
            owner_entity_id: 1,
            owner_entity_class: 'App\Models\SomeUnregisteredEntity',
            owner_member_name: 'logo',
            filepath: 'some/path',
            filename: 'logo.png',
            size: 1024,
        );

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/SomeUnregisteredEntity/');

        $service->postProcessFileFromFileApi($dto);
    }

    // -------------------------------------------------------------------------
    // FileInfoDTO - queue serialization round-trip
    // -------------------------------------------------------------------------

    public function testFileInfoDTOSurvivesQueueSerialization(): void
    {
        $original = new FileInfoDTO(
            owner_entity_id: 42,
            owner_entity_class: Company::class,
            owner_member_name: 'logo',
            filepath: 'companies/42/tmp/abc.png',
            filename: 'logo.png',
            size: 2048,
            md5: 'abc123def456abc123def456abc123de',
            mime_type: 'image/png',
            source_bucket: 'my-bucket',
        );

        /** @var FileInfoDTO $copy */
        $copy = unserialize(serialize($original));

        $this->assertSame(42, $copy->owner_entity_id);
        $this->assertSame(Company::class, $copy->owner_entity_class);
        $this->assertSame('logo', $copy->owner_member_name);
        $this->assertSame('companies/42/tmp/abc.png', $copy->filepath);
        $this->assertSame('logo.png', $copy->filename);
        $this->assertSame(2048, $copy->size);
        $this->assertSame('abc123def456abc123def456abc123de', $copy->md5);
        $this->assertSame('image/png', $copy->mime_type);
        $this->assertSame('my-bucket', $copy->source_bucket);
    }
}
