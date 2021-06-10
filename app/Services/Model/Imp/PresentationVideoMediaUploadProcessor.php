<?php namespace App\Services\Model\Imp;
/**
 * Copyright 2020 OpenStack Foundation
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

use App\Jobs\CreateVideosFromMUXAssetsForSummitJob;
use App\Mail\MUXExportExcerptMail;
use App\Models\Foundation\Summit\Factories\PresentationVideoFactory;
use App\Models\Utils\IStorageTypesConstants;
use App\Services\Apis\MuxCredentials;
use App\Services\Filesystem\FileDownloadStrategyFactory;
use App\Services\Model\AbstractService;
use App\Services\Model\IPresentationVideoMediaUploadProcessor;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use libs\utils\ITransactionService;
use models\summit\ISummitEventRepository;
use models\summit\Presentation;
use MuxPhp\ApiException;
use MuxPhp\Configuration as MuxConfig;
use MuxPhp\Api\AssetsApi as MuxAssetApi;
use MuxPhp\Api\PlaybackIDApi as MuxPlaybackIDApi;
use GuzzleHttp\Client as GuzzleHttpClient;
use MuxPhp\Models\InputSettings as MuxInputSettings;
use MuxPhp\Models\CreateAssetRequest as MuxCreateAssetRequest;
use MuxPhp\Models\PlaybackPolicy as MuxPlaybackPolicy;
use MuxPhp\Models\UpdateAssetMP4SupportRequest as MuxUpdateAssetMP4SupportRequest;

/**
 * Class PresentationVideoMediaUploadProcessor
 * @package App\Services\Model\Imp
 */
final class PresentationVideoMediaUploadProcessor
    extends AbstractService
    implements IPresentationVideoMediaUploadProcessor
{

    const MUX_STREAM_REGEX = '/https\:\/\/stream\.mux\.com\/(.*)\.m3u8/';
    /**
     * @var ISummitEventRepository
     */
    private $event_repository;

    /**
     * @var MuxAssetApi
     */
    private $assets_api;

    /**
     * @var MuxPlaybackIDApi
     */
    private $playback_api;

    public function __construct
    (
        ISummitEventRepository $event_repository,
        ITransactionService $tx_service
    )
    {
        parent::__construct($tx_service);
        $this->event_repository = $event_repository;
        $this->assets_api = null;
    }

    /**
     * @param int $summit_id
     * @param MuxCredentials $credentials
     * @param string|null $mountingFolder
     * @return int
     * @throws \Exception
     */
    public function processPublishedPresentationFor(int $summit_id, MuxCredentials $credentials, ?string $mountingFolder = null): int
    {
        $this->_configMux($credentials);

        Log::debug(sprintf("PresentationVideoMediaUploadProcessor::processPublishedPresentationFor summit id %s mountingFolder %s", $summit_id, $mountingFolder));
        $event_ids = $this->tx_service->transaction(function () use ($summit_id) {
            return $this->event_repository->getPublishedEventsIdsBySummit($summit_id);
        });

        foreach ($event_ids as $event_id) {
            Log::warning(sprintf("PresentationVideoMediaUploadProcessor::processPublishedPresentationFor processing event %s", $event_id));
            $this->processEvent(intval($event_id), $mountingFolder);
        }

        return count($event_ids);
    }

    /**
     * @param int $event_id
     * @param string|null $mountingFolder
     * @param MuxCredentials|null $credentials
     * @return bool
     */
    public function processEvent(int $event_id, ?string $mountingFolder, ?MuxCredentials $credentials = null): bool
    {
        try {
            $this->_configMux($credentials);
            return $this->tx_service->transaction(function () use ($event_id, $mountingFolder) {
                try {
                    $event = $this->event_repository->getByIdExclusiveLock($event_id);
                    if (is_null($event) || !$event instanceof Presentation) {
                        Log::warning(sprintf("PresentationVideoMediaUploadProcessor::processEvent event %s not found", $event_id));
                        return false;
                    }

                    if (!$event->isPublished()) {
                        Log::warning(sprintf("PresentationVideoMediaUploadProcessor::processEvent event %s not published", $event_id));
                        return false;
                    }

                    Log::debug(sprintf("PresentationVideoMediaUploadProcessor::processEvent processing event %s (%s)", $event->getTitle(), $event_id));

                    if (!empty($event->getMuxAssetId())) {
                        Log::warning(sprintf("PresentationVideoMediaUploadProcessor::processEvent event %s already has assigned an asset id %s", $event_id, $event->getMuxAssetId()));
                        return false;
                    }

                    $has_video = false;
                    foreach ($event->getMediaUploads() as $mediaUpload) {

                        if ($mediaUpload->getMediaUploadType()->isVideo()) {
                            if ($has_video) {
                                Log::warning(sprintf("PresentationVideoMediaUploadProcessor::processEvent event %s processing media upload %s (%s) already has a video processed!.", $event_id, $mediaUpload->getId(), $mediaUpload->getFilename()));
                                continue;
                            }
                            Log::debug(sprintf("PresentationVideoMediaUploadProcessor::processEvent event %s processing media upload %s", $event_id, $mediaUpload->getId()));
                            $has_video = true;

                            $strategy = FileDownloadStrategyFactory::build($mediaUpload->getMediaUploadType()->getPrivateStorageType());
                            if (!is_null($strategy)) {
                                $relativePath = $mediaUpload->getRelativePath(IStorageTypesConstants::PrivateType, $mountingFolder);
                                Log::debug(sprintf("PresentationVideoMediaUploadProcessor::processEvent event %s processing media upload %s relativePath %s", $event_id, $mediaUpload->getId(), $relativePath));
                                $assetUrl = $strategy->getUrl($relativePath);
                                if ($assetUrl == '#') {
                                    Log::debug(sprintf("PresentationVideoMediaUploadProcessor::processEvent event %s processing media upload %s got asset url %s is not valid", $event_id, $mediaUpload->getId(), $assetUrl));
                                    return false;
                                }
                                Log::debug(sprintf("PresentationVideoMediaUploadProcessor::processEvent event %s processing media upload %s got asset url %s", $event_id, $mediaUpload->getId(), $assetUrl));

                                // Create Asset Request
                                $input = new MuxInputSettings(["url" => $assetUrl]);
                                $createAssetRequest = new MuxCreateAssetRequest(["input" => $input, "playback_policy" => [MuxPlaybackPolicy::PUBLIC_PLAYBACK_POLICY]]);
                                // Ingest
                                $result = $this->assets_api->createAsset($createAssetRequest);

                                // Print URL
                                $playback_id = $result->getData()->getPlaybackIds()[0]->getId();
                                $streaming_url = sprintf("https://stream.mux.com/%s.m3u8", $playback_id);
                                $asset_id = $result->getData()->getId();
                                Log::debug(sprintf("PresentationVideoMediaUploadProcessor::processEvent event %s Playback URL: %s assset id %s", $event_id, $streaming_url, $asset_id));

                                $event->setStreamingUrl($streaming_url);
                                $event->setMuxAssetId($asset_id);
                                $event->setMuxPlaybackId($playback_id);
                            }
                        }
                    }
                } catch (\Exception $ex) {
                    Log::warning($ex);
                    throw $ex;
                }
                return true;
            });
        } catch (\Exception $ex) {
            Log::error($ex);
            return false;
        }
    }

    /**
     * @param int $event_id
     * @throws \Exception
     */
    public function enableMP4Support(int $event_id, ?MuxCredentials $credentials = null): void
    {
        $this->_configMux($credentials);
        $this->tx_service->transaction(function () use ($event_id) {
            $event = $this->event_repository->getByIdExclusiveLock($event_id);
            if (is_null($event) || !$event instanceof Presentation) {
                Log::warning(sprintf("PresentationVideoMediaUploadProcessor::enableMP4Support event %s not found", $event_id));
                return;
            }
            if (!$event->isPublished()) {
                Log::warning(sprintf("PresentationVideoMediaUploadProcessor::enableMP4Support event %s not published", $event_id));
                return;
            }

            Log::debug(sprintf("PresentationVideoMediaUploadProcessor::enableMP4Support processing event %s (%s)", $event->getTitle(), $event_id));

            $assetId = $event->getMuxAssetId();
            if (empty($assetId)) {
                Log::warning(sprintf("PresentationVideoMediaUploadProcessor::enableMP4Support event %s asset id is empty", $event_id));
                return;
            }

            $this->_enableMP4Support($assetId);
        });
    }

    /**
     * @param string $assetId
     * @return \MuxPhp\Models\AssetResponse
     * @throws \MuxPhp\ApiException
     */
    private function _enableMP4Support(string $assetId)
    {
        try {
            $request = new MuxUpdateAssetMP4SupportRequest(['mp4_support' => MuxUpdateAssetMP4SupportRequest::MP4_SUPPORT_STANDARD]);
            $result = $this->assets_api->updateAssetMp4Support($assetId, $request);
            $tracks = $result->getData()->getTracks();
            Log::debug
            (
                sprintf
                (
                    "PresentationVideoMediaUploadProcessor::_enableMP4Support asset id %s enable mp4 support response %s",
                    $assetId,
                    json_encode($tracks)
                )
            );

            return $tracks;
        }
        catch(ApiException $ex){
            Log::warning($ex);
            $response = json_decode($ex->getResponseBody());
            if(count($response->error->messages) > 0){
                if($response->error->messages[0] == "Download already exists"){
                    return null;
                }
            }
            throw $ex;
        }
    }


    private function _configMux(MuxCredentials $credentials)
    {
        if (!is_null($this->assets_api)) return;

        try {
            // Authentication Setup
            $config = MuxConfig::getDefaultConfiguration()
                ->setUsername($credentials->getTokenId())
                ->setPassword($credentials->getTokenSecret());

            // API Client Initialization
            $this->assets_api = new MuxAssetApi(
                new GuzzleHttpClient,
                $config
            );

            $this->playback_api = new MuxPlaybackIDApi(
                new GuzzleHttpClient,
                $config
            );
        }
        catch (\Exception $ex){
            Log::error($ex);
            throw $ex;
        }
    }

    /**
     * @param int $summit_id
     * @param MuxCredentials $credentials
     * @param string|null $mail_to
     * @return int
     * @throws \Exception
     */
    public function processSummitEventsStreamURLs
    (
        int $summit_id,
        MuxCredentials $credentials,
        ?string $mail_to
    ): int
    {

        Log::debug
        (
            sprintf
            (
                "PresentationVideoMediaUploadProcessor::processSummitEventsStreamURLs Summit %s Credentials %s mail to %s",
                $summit_id,
                $credentials,
                $mail_to
            )
        );

        $event_ids = [];
        $excerpt = sprintf('Starting MUX Assets Enabling MP4 Support Process Summit %s.', $summit_id).PHP_EOL;

        try {
            $event_ids = $this->tx_service->transaction(function () use ($summit_id) {
                return $this->event_repository->getPublishedEventsIdsBySummit($summit_id);
            });

            $this->_configMux($credentials);


            foreach ($event_ids as $event_id) {
                Log::warning(sprintf("PresentationVideoMediaUploadProcessor::processMuxAssetsFromStreamUrl processing event %s", $event_id));
                try {
                    $this->tx_service->transaction(function () use ($event_id, $credentials, &$excerpt) {
                        try {

                            $event = $this->event_repository->getByIdExclusiveLock($event_id);
                            if (is_null($event) || !$event instanceof Presentation) {
                                Log::warning(sprintf("PresentationVideoMediaUploadProcessor::processMuxAssetsFromStreamUrl event %s not found", $event_id));
                                return false;
                            }

                            if (!$event->isPublished()) {
                                Log::warning(sprintf("PresentationVideoMediaUploadProcessor::processMuxAssetsFromStreamUrl event %s not published", $event_id));
                                $excerpt .= sprintf("event %s not published", $event_id) . PHP_EOL;
                                return false;
                            }

                            if (!empty($event->getMuxAssetId())) {
                                $excerpt .= sprintf("event %s - mux asset id (%s) - already has enabled mp4 support.", $event_id, $event->getMuxAssetId()) . PHP_EOL;
                                return false;
                            }
                            $stream_url = $event->getStreamingUrl();
                            if(empty($stream_url)){
                                Log::warning(sprintf("PresentationVideoMediaUploadProcessor::processMuxAssetsFromStreamUrl event %s stream url does not match mux format (empty)", $event_id));
                                $excerpt .= sprintf("event %s stream url does not match mux format (empty)", $event_id) . PHP_EOL;
                            }
                            // test $stream_url to check if a mux playlist format
                            if (!preg_match(self::MUX_STREAM_REGEX, $stream_url, $matches)) {
                                Log::warning(sprintf("PresentationVideoMediaUploadProcessor::processMuxAssetsFromStreamUrl event %s stream url does not match mux format (%s)", $event_id, $stream_url));
                                $excerpt .= sprintf("event %s stream url does not match mux format (%s)", $event_id, $stream_url) . PHP_EOL;
                                return false;
                            }
                            if(count($matches) < 2){
                                Log::warning(sprintf("PresentationVideoMediaUploadProcessor::processMuxAssetsFromStreamUrl event %s stream url does not match mux format (%s)", $event_id, $stream_url));
                                $excerpt .= sprintf("event %s stream url does not match mux format (%s)", $event_id, $stream_url) . PHP_EOL;
                                return false;
                            }
                            $playback_id = $matches[1];
                            $event->setMuxPlaybackId($playback_id);
                            $playbackResponse = $this->playback_api->getAssetOrLivestreamId($playback_id);
                            $asset_id = $playbackResponse->getData()->getObject()->getId();
                            $event->setMuxAssetId($asset_id);
                            $assetResponse = $this->assets_api->getAsset($asset_id);
                            $staticRenditions = $assetResponse->getData()->getStaticRenditions();
                            if (!is_null($staticRenditions)) {
                                $excerpt .= sprintf("event %s - mux asset id (%s) - has already enabled mp4 support.", $event_id, $asset_id) . PHP_EOL;
                                return false;
                            }
                            $this->_enableMP4Support($asset_id);
                            $excerpt .= sprintf("event %s - mux asset id (%s) - has been enabled mp4 support.", $event_id, $asset_id) . PHP_EOL;
                        } catch (\Exception $ex) {
                            $excerpt .= sprintf("event %s - error on enabling mp4 support.", $event_id) . PHP_EOL;
                            Log::warning($ex);
                            throw $ex;
                        }
                    });
                } catch (\Exception $ex) {
                    Log::error($ex);
                }
            }

            $excerpt .= sprintf("%s events processed.", count($event_ids)) . PHP_EOL;
        }
        catch (\Exception $ex){
            $excerpt .= sprintf("Process Error %s", $ex->getMessage()) . PHP_EOL;
            Log::error($ex);
        }

        if (!empty($mail_to))
            Mail::queue(new MUXExportExcerptMail($mail_to, "MUX Assets MP4 Enabling Process", $excerpt));

        if(count($event_ids) > 0) {
            // fire exporting
            // @see https://docs.mux.com/guides/video/download-your-videos#download-videos
            CreateVideosFromMUXAssetsForSummitJob::dispatch(
                $summit_id,
                $credentials->getTokenId(),
                $credentials->getTokenSecret(),
                $mail_to
            )->delay(now()->addMinutes(1));
        }

        return count($event_ids);
    }

    /**
     * @param int $summit_id
     * @param MuxCredentials $credentials
     * @param string|null $mail_to
     * @return int
     * @throws \Exception
     */
    public function createVideosFromMUXAssets(int $summit_id,
                                              MuxCredentials $credentials,
                                              ?string $mail_to): int
    {
        Log::debug
        (
            sprintf
            (
                "PresentationVideoMediaUploadProcessor::createVideosFromMUXAssets Summit %s Credentials %s mail to %s",
                $summit_id,
                $credentials,
                $mail_to
            )
        );
        $event_ids = [];
        $excerpt = sprintf('Starting Create Videos From MUX Assets Process Summit %s.', $summit_id).PHP_EOL;
        try {
            $event_ids = $this->tx_service->transaction(function () use ($summit_id) {
                return $this->event_repository->getPublishedEventsIdsBySummit($summit_id);
            });

            $this->_configMux($credentials);

            foreach ($event_ids as $event_id) {
                $this->tx_service->transaction(function () use ($event_id, $credentials, &$excerpt) {
                    try {

                        $event = $this->event_repository->getByIdExclusiveLock($event_id);
                        if (is_null($event) || !$event instanceof Presentation) {
                            Log::warning(sprintf("PresentationVideoMediaUploadProcessor::createVideosFromMUXAssets event %s not found", $event_id));
                            return false;
                        }

                        if (!$event->isPublished()) {
                            Log::warning(sprintf("PresentationVideoMediaUploadProcessor::createVideosFromMUXAssets event %s not published", $event_id));
                            $excerpt .= sprintf("event %s not published.", $event_id) . PHP_EOL;
                            return false;
                        }

                        if ($event->getVideosWithExternalUrls()->count() > 0) {
                            $excerpt .= sprintf("event %s already processed.", $event_id) . PHP_EOL;
                            return false;
                        }

                        $assetId = $event->getMuxAssetId();
                        if (empty($assetId)) {
                            $excerpt .= sprintf("event %s not processed.", $event_id) . PHP_EOL;
                            return false;
                        }

                        $result = $this->assets_api->getAsset($assetId);
                        $staticRenditions = $result->getData()->getStaticRenditions();

                        if (is_null($staticRenditions)) {
                            $excerpt .= sprintf("event %s - mp4 not enabled.", $event_id) . PHP_EOL;
                            return false;
                        }

                        if ($staticRenditions->getStatus() != 'ready') {
                            $excerpt .= sprintf("event %s - transcoding not ready (%s)", $event_id, $staticRenditions->getStatus()) . PHP_EOL;
                            return false;
                        }

                        $bestFile = null;

                        $fileNames = [
                            'low.mp4',
                            'medium.mp4',
                            'high.mp4',
                        ];

                        foreach ($staticRenditions->getFiles() as $file) {
                            if (is_null($bestFile)) $bestFile = $file;
                            if (array_search($bestFile->getName(), $fileNames) < array_search($file->getName(), $fileNames)) {
                                $bestFile = $file;
                            }
                        }
                        if (is_null($bestFile)) {
                            $excerpt .= sprintf("event %s - transcoding not ready (%s).", $event_id, $staticRenditions->getStatus()) . PHP_EOL;
                            return 0;
                        };
                        $newVideo = PresentationVideoFactory::build([
                            'name' => $event->getTitle(),
                            'external_url' => sprintf("https://stream.mux.com/%s/%s", $event->getMuxPlaybackId(), $bestFile->getName())
                        ]);

                        $event->addVideo($newVideo);

                    } catch (\Exception $ex) {
                        $excerpt .= sprintf("event %s - error on exporting mux asset.", $event_id) . PHP_EOL;
                        Log::warning($ex);
                        throw $ex;
                    }
                });
            }

            $excerpt .= sprintf("%s events processed.", count($event_ids)) . PHP_EOL;
        }
        catch (\Exception $ex){
            $excerpt .= sprintf("Process Error %s", $ex->getMessage()) . PHP_EOL;
            Log::error($ex);
        }
        if (!empty($mail_to))
            Mail::queue(new MUXExportExcerptMail($mail_to, "Videos Creation Process", $excerpt));

        return count($event_ids);
    }
}