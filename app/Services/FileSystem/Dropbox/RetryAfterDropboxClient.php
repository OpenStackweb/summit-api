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

use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Sleep;
use Psr\Http\Message\StreamInterface;
use Spatie\Dropbox\Client as BaseDropboxClient;
use Spatie\Dropbox\UploadSessionCursor;

/**
 * Class RetryAfterDropboxClient
 *
 * Custom Dropbox client that overrides uploadChunk to add Retry-After
 * aware retry logic for HTTP 429 rate-limit responses during chunked uploads.
 * The parent's uploadChunk retries immediately on any RequestException without
 * reading the Retry-After header. This override sleeps for the indicated duration
 * plus jitter before retrying on 429, preventing thundering herd.
 *
 * @package App\Services\FileSystem\Dropbox
 */
final class RetryAfterDropboxClient extends BaseDropboxClient
{
    private const DEFAULT_RETRY_AFTER_SECONDS = 300;
    private const MIN_JITTER_MS = 100;
    private const MAX_JITTER_MS = 500;

    /**
     * Override uploadChunk to add Retry-After aware retry logic for 429 responses.
     *
     * Replicates the parent's retry loop (rewind stream, recreate LimitStream, retry)
     * but adds Retry-After header reading and sleep with jitter before retrying on 429.
     * Non-429 RequestExceptions are retried immediately (same as parent behavior).
     *
     * @param int $type UPLOAD_SESSION_START or UPLOAD_SESSION_APPEND
     * @param StreamInterface $stream
     * @param int $chunkSize
     * @param UploadSessionCursor|null $cursor
     * @return UploadSessionCursor
     * @throws \Exception
     */
    protected function uploadChunk(int $type, StreamInterface &$stream, int $chunkSize, ?UploadSessionCursor $cursor = null): UploadSessionCursor
    {
        $maximumTries = $stream->isSeekable() ? $this->maxUploadChunkRetries : 0;
        $pos = $stream->tell();

        $tries = 0;

        tryUpload:
        try {
            $tries++;
            Log::debug(sprintf("RetryAfterDropboxClient::uploadChunk type %s tries %s", $type, $tries));
            $chunkStream = new Psr7\LimitStream($stream, $chunkSize, $stream->tell());

            if ($type === self::UPLOAD_SESSION_START) {
                return $this->uploadSessionStart($chunkStream);
            }

            if ($type === self::UPLOAD_SESSION_APPEND && $cursor !== null) {
                return $this->uploadSessionAppend($chunkStream, $cursor);
            }

            throw new \Exception('Invalid type.');
        } catch (RequestException $exception) {
            Log::error($exception->getMessage());
            if ($tries < $maximumTries) {
                // If this is a 429 rate-limit, sleep for Retry-After + jitter before retrying
                if ($exception instanceof ClientException
                    && $exception->getResponse()->getStatusCode() === 429
                ) {
                    $retryAfter = (int) ($exception->getResponse()->getHeaderLine('Retry-After')
                        ?: self::DEFAULT_RETRY_AFTER_SECONDS);
                    if($retryAfter == 0)
                        $retryAfter = self::DEFAULT_RETRY_AFTER_SECONDS;
                    $jitterMs = random_int(self::MIN_JITTER_MS, self::MAX_JITTER_MS);

                    Log::warning(sprintf(
                        "RetryAfterDropboxClient::uploadChunk hit 429, Retry-After: %s seconds, attempt %s/%s",
                        $retryAfter,
                        $tries,
                        $maximumTries
                    ));

                    Sleep::for(($retryAfter * 1000) + $jitterMs)->milliseconds();
                }

                // rewind
                $stream->seek($pos, SEEK_SET);
                goto tryUpload;
            }

            throw $exception;
        }
    }
}
