<?php namespace App\Services\FileSystem\Dropbox;
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

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\ClientException;
use Illuminate\Support\Facades\Log;
use Spatie\Dropbox\RefreshableTokenProvider;

/**
 * Class AutoRefreshingDropBoxTokenService
 *
 * Implements Spatie's RefreshableTokenProvider to automatically refresh
 * Dropbox access tokens using an OAuth2 refresh token. When the Spatie
 * Client catches a ClientException (e.g. 401 expired token), it calls
 * refresh() on this provider — which exchanges the refresh token for a
 * new access token via Dropbox's OAuth2 token endpoint.
 *
 * @package App\Services\FileSystem\Dropbox
 */
final class AutoRefreshingDropBoxTokenService implements RefreshableTokenProvider
{
    private const TOKEN_ENDPOINT = 'https://api.dropboxapi.com/oauth2/token';

    private string $accessToken = '';

    public function __construct(
        private readonly string $appKey,
        private readonly string $appSecret,
        private readonly string $refreshToken,
    ) {
        // Eagerly fetch the first access token so getToken() is ready immediately.
        // Fail fast if credentials are invalid — avoids churning through uploads with a dead token.
        if (!$this->refreshAccessToken()) {
            throw new \RuntimeException('AutoRefreshingDropBoxTokenService: failed to obtain initial Dropbox access token. Check DROPBOX_APP_KEY, DROPBOX_APP_SECRET, and DROPBOX_REFRESH_TOKEN.');
        }
    }

    public function getToken(): string
    {
        return $this->accessToken;
    }

    /**
     * Called by Spatie\Dropbox\Client when a ClientException is caught.
     * Returns true if the token was successfully refreshed (Client will retry
     * the request), false otherwise (Client will rethrow the exception).
     */
    public function refresh(ClientException $exception): bool
    {
        $statusCode = $exception->getResponse()->getStatusCode();

        // Only refresh on 401 Unauthorized (expired/invalid token)
        if ($statusCode !== 401) {
            return false;
        }

        Log::info('AutoRefreshingDropBoxTokenService: access token expired, refreshing via OAuth2.');

        return $this->refreshAccessToken();
    }

    /**
     * Exchange the refresh token for a new access token via Dropbox OAuth2.
     */
    private function refreshAccessToken(): bool
    {
        try {
            $client = new GuzzleClient(['timeout' => 30, 'connect_timeout' => 10]);
            $response = $client->request('POST', self::TOKEN_ENDPOINT, [
                'form_params' => [
                    'grant_type'    => 'refresh_token',
                    'refresh_token' => $this->refreshToken,
                    'client_id'     => $this->appKey,
                    'client_secret' => $this->appSecret,
                ],
            ]);

            $data = json_decode($response->getBody()->getContents(), true);

            if (!isset($data['access_token'])) {
                Log::error('AutoRefreshingDropBoxTokenService: response missing access_token.');
                return false;
            }

            $this->accessToken = $data['access_token'];

            Log::info('AutoRefreshingDropBoxTokenService: access token refreshed successfully.');

            return true;
        } catch (\Exception $e) {
            Log::error(sprintf(
                'AutoRefreshingDropBoxTokenService: failed to refresh token — %s',
                $e->getMessage()
            ));
            return false;
        }
    }
}
