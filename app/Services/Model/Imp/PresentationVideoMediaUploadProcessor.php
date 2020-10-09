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

use App\Models\Utils\IStorageTypesConstants;
use App\Services\Filesystem\FileDownloadStrategyFactory;
use App\Services\Model\AbstractService;
use App\Services\Model\IPresentationVideoMediaUploadProcessor;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use libs\utils\ITransactionService;
use models\summit\ISummitEventRepository;
use models\summit\ISummitRepository;
use models\summit\Presentation;
use MuxPhp\Configuration as MuxConfig;
use MuxPhp\Api\AssetsApi as MuxAssetApi;
use GuzzleHttp\Client as GuzzleHttpClient;
use MuxPhp\Models\InputSettings as MuxInputSettings;
use MuxPhp\Models\CreateAssetRequest as MuxCreateAssetRequest;
use MuxPhp\Models\PlaybackPolicy as MuxPlaybackPolicy;
/**
 * Class PresentationVideoMediaUploadProcessor
 * @package App\Services\Model\Imp
 */
final class PresentationVideoMediaUploadProcessor
    extends AbstractService
    implements IPresentationVideoMediaUploadProcessor
{
    /**
     * @var ISummitRepository
     */
    private $summit_repository;

    /**
     * @var ISummitEventRepository
     */
    private $event_repository;

    /**
     * @var MuxAssetApi
     */
    private $assets_api;

    public function __construct
    (
        ISummitRepository $summit_repository,
        ISummitEventRepository $event_repository,
        ITransactionService $tx_service
    )
    {
        parent::__construct($tx_service);
        $this->summit_repository = $summit_repository;
        $this->event_repository = $event_repository;

        $mux_user = Config::get("mux.user", null);
        $mux_password = Config::get("mux.password", null);

        if(empty($mux_user)){
            throw new \InvalidArgumentException("missing setting mux.user");
        }
        if(empty($mux_password)){
            throw new \InvalidArgumentException("missing setting mux.password");
        }

        // Authentication Setup
        $config = MuxConfig::getDefaultConfiguration()
            ->setUsername($mux_user)
            ->setPassword($mux_password);

        // API Client Initialization
        $this->assets_api = new MuxAssetApi(
            new GuzzleHttpClient,
            $config
        );
    }

    /**
     * @param int $summit_id
     * @param string|null $mountingFolder
     * @return int
     * @throws \Exception
     */
    public function processPublishedPresentationFor(int $summit_id, ?string $mountingFolder = null): int
    {
        Log::debug(sprintf("PresentationVideoMediaUploadProcessor::processPublishedPresentationFor summit id %s mountingFolder %s", $summit_id, $mountingFolder));
        $event_ids = $this->tx_service->transaction(function() use($summit_id){
            return $this->event_repository->getPublishedEventsIdsBySummit($summit_id);
        });

        foreach($event_ids as $event_id){
            Log::warning(sprintf("PresentationVideoMediaUploadProcessor::processPublishedPresentationFor processing event %s", $event_id));
            $this->processEvent(intval($event_id), $mountingFolder);
        }

        return count($event_ids);
    }

    /**
     * @param int $event_id
     * @param string|null $mountingFolder
     * @return bool
     */
    public function processEvent(int $event_id, ?string $mountingFolder):bool{
        try {
            return $this->tx_service->transaction(function () use ($event_id, $mountingFolder) {
                try {
                    $event = $this->event_repository->getByIdExclusiveLock($event_id);
                    if (is_null($event) || !$event instanceof Presentation) {
                        Log::warning(sprintf("PresentationVideoMediaUploadProcessor::processEvent event %s not found", $event_id));
                        return false;
                    }

                    if(!$event->isPublished()){
                        Log::warning(sprintf("PresentationVideoMediaUploadProcessor::processEvent event %s not published", $event_id));
                        return false;
                    }

                    Log::debug(sprintf("PresentationVideoMediaUploadProcessor::processEvent processing event %s (%s)", $event->getTitle(), $event_id));

                    if(!empty($event->getMuxAssetId())){
                        Log::warning(sprintf("PresentationVideoMediaUploadProcessor::processEvent event %s already has assigned an asset id %s", $event_id, $event->getMuxAssetId()));
                        return false;
                    }

                    $has_video = false;
                    foreach($event->getMediaUploads() as $mediaUpload){

                        if($mediaUpload->getMediaUploadType()->isVideo()){
                            if($has_video){
                                Log::warning(sprintf("PresentationVideoMediaUploadProcessor::processEvent event %s processing media upload %s (%s) already has a video processed!.", $event_id, $mediaUpload->getId(), $mediaUpload->getFilename()));
                                continue;
                            }
                            Log::debug(sprintf("PresentationVideoMediaUploadProcessor::processEvent event %s processing media upload %s", $event_id, $mediaUpload->getId()));
                            $has_video = true;

                            $strategy = FileDownloadStrategyFactory::build($mediaUpload->getMediaUploadType()->getPrivateStorageType());
                            if (!is_null($strategy)) {
                                $assetUrl =  $strategy->getUrl($mediaUpload->getRelativePath(IStorageTypesConstants::PrivateType, $mountingFolder));
                                Log::debug(sprintf("PresentationVideoMediaUploadProcessor::processEvent event %s processing media upload %s got asset url %s", $event_id, $mediaUpload->getId(), $assetUrl));

                                // Create Asset Request
                                $input = new MuxInputSettings(["url" => $assetUrl]);
                                $createAssetRequest = new MuxCreateAssetRequest(["input" => $input, "playback_policy" => [MuxPlaybackPolicy::PUBLIC_PLAYBACK_POLICY] ]);
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
        }
        catch (\Exception $ex) {
            Log::error($ex);
            return false;
        }
    }
}