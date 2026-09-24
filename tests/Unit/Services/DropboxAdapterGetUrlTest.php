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

use App\Services\FileSystem\Dropbox\DropboxAdapter;
use GuzzleHttp\Psr7\Response;
use Illuminate\Container\Container;
use Illuminate\Support\Facades\Facade;
use Mockery;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Spatie\Dropbox\Client as DropboxClient;
use Spatie\Dropbox\Exceptions\BadRequest;

/**
 * Class DropboxAdapterGetUrlTest
 *
 * Unit tests for {@see DropboxAdapter::getUrl()}: the value handed to
 * PresentationMediaUpload serializers as `private_url`.
 *
 * @package Tests\Unit\Services
 */
class DropboxAdapterGetUrlTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // Minimal facade application so Log:: calls in the SUT resolve
        // without a full Laravel app (same setup as ProcessPendingMediaUploadsTest).
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
     * The file never reached Dropbox (e.g. the pending upload failed), so
     * sharing/create_shared_link_with_settings answers path/not_found.
     * The adapter must report "no link" as an empty string (the parent declares getUrl(): string,
     * so null is not allowed), never as a placeholder like "#"
     * that clients would treat as a real URL. AbstractFileDownloadStrategy turns it into null.
     */
    public function testGetUrlReturnsEmptyStringWhenSharedLinkCannotBeCreated(): void
    {
        $path = 'PresentationMediaUploads/Private/73/9121/missing.pptx';

        $client = Mockery::mock(DropboxClient::class);
        $client->shouldReceive('createSharedLinkWithSettings')
            ->once()
            ->with($path)
            ->andThrow(new BadRequest(new Response(409, [], json_encode([
                'error_summary' => 'path/not_found/',
                'error' => ['.tag' => 'path', 'path' => ['.tag' => 'not_found']],
            ]))));
        $client->shouldNotReceive('listSharedLinks');

        $adapter = new DropboxAdapter($client);

        $this->assertSame('', $adapter->getUrl($path));
    }

    /**
     * Anti-regression: when Dropbox creates the shared link, its URL is returned as-is.
     */
    public function testGetUrlReturnsSharedLinkUrlOnSuccess(): void
    {
        $path = 'PresentationMediaUploads/Private/73/9082/deck.pptx';
        $url = 'https://www.dropbox.com/scl/fi/abc123/deck.pptx?dl=0';

        $client = Mockery::mock(DropboxClient::class);
        $client->shouldReceive('createSharedLinkWithSettings')
            ->once()
            ->with($path)
            ->andReturn(['url' => $url]);

        $adapter = new DropboxAdapter($client);

        $this->assertSame($url, $adapter->getUrl($path));
    }
}
