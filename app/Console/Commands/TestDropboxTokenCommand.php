<?php namespace App\Console\Commands;
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

use App\Services\FileSystem\Dropbox\AutoRefreshingDropBoxTokenService;
use App\Services\FileSystem\Dropbox\RetryAfterDropboxClient as DropboxClient;
use Illuminate\Console\Command;

/**
 * Class TestDropboxTokenCommand
 *
 * Quick diagnostic command to verify the Dropbox OAuth2 refresh token
 * flow is working. Instantiates AutoRefreshingDropBoxTokenService,
 * obtains an access token, then calls the Dropbox API to list the
 * root folder as proof.
 *
 * @package App\Console\Commands
 */
class TestDropboxTokenCommand extends Command
{
    protected $signature = 'dropbox:test-token';

    protected $description = 'Test Dropbox OAuth2 token refresh and verify API access';

    public function handle(): int
    {
        $appKey       = config('filesystems.disks.dropbox.app_key', '');
        $appSecret    = config('filesystems.disks.dropbox.app_secret', '');
        $refreshToken = config('filesystems.disks.dropbox.refresh_token', '');

        if (empty($appKey) || empty($appSecret) || empty($refreshToken)) {
            $this->error('Missing config. Ensure DROPBOX_APP_KEY, DROPBOX_APP_SECRET, and DROPBOX_REFRESH_TOKEN are set in .env');
            return 1;
        }

        $this->info('1. Creating AutoRefreshingDropBoxTokenService...');

        try {
            $tokenService = new AutoRefreshingDropBoxTokenService($appKey, $appSecret, $refreshToken);
        } catch (\Exception $e) {
            $this->error("   Failed to obtain access token: {$e->getMessage()}");
            return 1;
        }

        $accessToken = $tokenService->getToken();

        if (empty($accessToken)) {
            $this->error('   Token service returned an empty access token.');
            return 1;
        }

        $this->info('   Access token obtained: ' . substr($accessToken, 0, 12) . '...');

        $this->info('2. Creating DropboxClient and listing root folder...');

        try {
            $client = new DropboxClient($tokenService);
            $result = $client->listFolder('');

            $entries = $result['entries'] ?? [];
            $this->info("   Success! Found {$this->countEntries($entries)} entries in root folder.");

            foreach (array_slice($entries, 0, 5) as $entry) {
                $tag  = $entry['.tag'] ?? 'unknown';
                $name = $entry['name'] ?? '?';
                $this->line("   [{$tag}] {$name}");
            }

            if (count($entries) > 5) {
                $this->line('   ... and ' . (count($entries) - 5) . ' more');
            }
        } catch (\Spatie\Dropbox\Exceptions\BadRequest $e) {
            $this->error("   API call failed: BadRequest");
            $this->error("   Dropbox error code: " . ($e->dropboxCode ?? 'none'));
            $this->error("   Message: " . ($e->getMessage() ?: '(empty)'));
            // Rewind and re-read the response body
            $e->response->getBody()->rewind();
            $this->error("   Response body: " . $e->response->getBody()->getContents());
            $this->error("   HTTP status: " . $e->response->getStatusCode());
            return 1;
        } catch (\GuzzleHttp\Exception\ClientException $e) {
            $this->error("   API call failed: " . get_class($e));
            $this->error("   Response body: " . $e->getResponse()->getBody()->getContents());
            return 1;
        } catch (\Exception $e) {
            $this->error("   API call failed: " . get_class($e) . " — {$e->getMessage()}");
            return 1;
        }

        $this->info('3. Token refresh flow is working correctly.');

        return 0;
    }

    private function countEntries(array $entries): int
    {
        return count($entries);
    }
}
