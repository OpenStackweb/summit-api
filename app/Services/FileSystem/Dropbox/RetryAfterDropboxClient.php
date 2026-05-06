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
 * Custom Dropbox client that extends Spatie's Client with two overrides:
 *
 * 1. uploadChunk — adds Retry-After-aware retry logic for HTTP 429 rate-limit
 *    responses. The parent retries immediately; this override sleeps for the
 *    Retry-After header value plus jitter before retrying on 429.
 *
 * 2. uploadChunked — adds bounded-loop safeguards around the parent's unbounded
 *    while (!$stream->eof()) loop, which can loop indefinitely when the
 *    cursor/stream desyncs (body unconsumed on retry, feof() boundary edge,
 *    Guzzle retry middleware consuming stream position, etc.). Three layers:
 *      - Per-iteration diagnostics (source pos, cursor offset, delta)
 *      - Stuck-cursor detection: abort after 3 consecutive zero-advance iterations
 *      - Iteration cap: abort if iterations exceed max(expected_chunks * 2, 50)
 *    See docs/plans/2026-05-06-process-pending-media-uploads-dropbox-chunk-loop.md
 *
 * @package App\Services\FileSystem\Dropbox
 */
final class RetryAfterDropboxClient extends BaseDropboxClient
{
    private const DEFAULT_RETRY_AFTER_SECONDS = 300;
    private const MIN_JITTER_MS = 100;
    private const MAX_JITTER_MS = 500;

    private const STUCK_CURSOR_THRESHOLD = 3;
    private const ITERATION_CAP_MINIMUM = 50;

    /**
     * Override uploadChunked to add stuck-cursor detection and an iteration cap.
     *
     * Matches parent signature exactly. Preserves the START → APPEND* → FINISH
     * protocol but wraps the APPEND loop with:
     *   - Per-iteration debug logging (source position, cursor offset, delta)
     *   - Stuck-cursor detection: throws after STUCK_CURSOR_THRESHOLD consecutive
     *     iterations where cursor->offset does not advance
     *   - Iteration cap: throws if iterations exceed max(expected_chunks * 2, 50)
     *
     * @param string   $path
     * @param mixed    $contents  resource|string|StreamInterface
     * @param string   $mode
     * @param int|null $chunkSize
     * @return array   Dropbox file metadata
     * @throws \RuntimeException on stuck-cursor or iteration-cap exceeded
     */
    public function uploadChunked(string $path, mixed $contents, string $mode = 'add', ?int $chunkSize = null): array
    {
        if ($chunkSize === null || $chunkSize > $this->maxChunkSize) {
            $chunkSize = $this->maxChunkSize;
        }

        $stream = $this->getStream($contents);

        // Compute expected chunk count for the iteration cap.
        $streamSize = $stream->getSize();
        $expectedChunks = ($streamSize !== null && $chunkSize > 0)
            ? (int) ceil($streamSize / $chunkSize)
            : 0;
        $iterationCap = max($expectedChunks * 2, self::ITERATION_CAP_MINIMUM);

        Log::debug(sprintf(
            "RetryAfterDropboxClient::uploadChunked start path=%s streamSize=%s chunkSize=%s expectedChunks=%s cap=%s",
            $path, $streamSize ?? 'unknown', $chunkSize, $expectedChunks, $iterationCap
        ));

        $cursor = $this->uploadChunk(self::UPLOAD_SESSION_START, $stream, $chunkSize, null);

        $iteration = 0;
        $noProgressCount = 0;
        $lastCursorOffset = $cursor->offset;

        while (! $stream->eof()) {
            $iteration++;
            $sourcePosBefore = $stream->tell();

            $cursor = $this->uploadChunk(self::UPLOAD_SESSION_APPEND, $stream, $chunkSize, $cursor);

            $sourcePosAfter = $stream->tell();
            $delta = $cursor->offset - $lastCursorOffset;

            Log::debug(sprintf(
                "RetryAfterDropboxClient::uploadChunked iteration=%d sourceBefore=%d sourceAfter=%d cursor.offset=%d delta=%d noProgress=%d cap=%d",
                $iteration, $sourcePosBefore, $sourcePosAfter, $cursor->offset, $delta, $noProgressCount, $iterationCap
            ));

            if ($delta === 0) {
                $noProgressCount++;
                // Log at WARNING so it's visible even if DEBUG is suppressed in future
                Log::warning(sprintf(
                    "RetryAfterDropboxClient::uploadChunked zero-progress iteration=%d cursor.offset=%d sourceBefore=%d sourceAfter=%d noProgressCount=%d/%d path=%s",
                    $iteration, $cursor->offset, $sourcePosBefore, $sourcePosAfter, $noProgressCount, self::STUCK_CURSOR_THRESHOLD, $path
                ));
                if ($noProgressCount >= self::STUCK_CURSOR_THRESHOLD) {
                    throw new \RuntimeException(sprintf(
                        "RetryAfterDropboxClient::uploadChunked stuck — cursor.offset did not advance for %d consecutive iterations at offset %d sourceBefore=%d sourceAfter=%d (path=%s)",
                        self::STUCK_CURSOR_THRESHOLD, $cursor->offset, $sourcePosBefore, $sourcePosAfter, $path
                    ));
                }
            } else {
                $noProgressCount = 0;
            }

            $lastCursorOffset = $cursor->offset;

            if ($iteration > $iterationCap) {
                throw new \RuntimeException(sprintf(
                    "RetryAfterDropboxClient::uploadChunked exceeded max iterations (%d > cap %d) for streamSize=%s chunkSize=%d path=%s",
                    $iteration, $iterationCap, $streamSize ?? 'unknown', $chunkSize, $path
                ));
            }
        }

        Log::debug(sprintf(
            "RetryAfterDropboxClient::uploadChunked finishing path=%s cursor.offset=%d iterations=%d",
            $path, $cursor->offset, $iteration
        ));

        return $this->uploadSessionFinish('', $cursor, $path, $mode);
    }

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
