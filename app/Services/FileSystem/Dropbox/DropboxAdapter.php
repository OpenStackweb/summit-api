<?php namespace App\Services\FileSystem\Dropbox;
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
use Illuminate\Support\Facades\Log;
use Spatie\FlysystemDropbox\DropboxAdapter as BaseDropboxAdapter;
use Spatie\Dropbox\Exceptions\BadRequest as BadRequestException;
use Exception;
/**
 * Class DropboxAdapter
 * @package App\Services\FileSystem\Dropbox
 */
final class DropboxAdapter extends BaseDropboxAdapter
{
    /*
     * This method returns a public URL for the given path in Dropbox.
     * this is overloaded to retrieve a URL with the preview mode enabled.
     */
    public function getUrl(string $path): string
    {
        Log::debug(sprintf("DropboxAdapter::getUrl %s", $path));
        $client = $this->client;
        try {
            // default visibility is RequestedVisibility.public.
            $res = $client->createSharedLinkWithSettings($path);
            return $res['url'];
        }
        catch (BadRequestException $ex){
            Log::warning(sprintf("DropboxAdapter::getUrl %s code %s", $ex->getMessage(), $ex->dropboxCode));
            if($ex->dropboxCode === 'shared_link_already_exists')
            {
                try {
                    Log::debug(sprintf("DropboxAdapter::getUrl getting list of shared links for %s", $path));
                    $res = $client->listSharedLinks($path);
                    foreach ($res as $entry) {
                        Log::debug(sprintf("DropboxAdapter::getUrl getting list of shared links for %s entry %s", $path, json_encode($entry)));
                        if($entry['.tag'] === 'file' )
                            return $entry['url'];
                    }
                }
                catch (Exception $ex){
                    Log::warning($ex);
                }
            }
        }
        catch (Exception $ex){
            Log::error($ex);
        }
        return '#';
    }
}
