<?php namespace App\Services\Apis\ExternalScheduleFeeds;
/**
 * Copyright 2019 OpenStack Foundation
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
use Exception;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Log;
use models\summit\Summit;
/**
 * Class AbstractExternalScheduleFeed
 * @package App\Services\Apis\ExternalScheduleFeeds
 */
abstract class AbstractExternalScheduleFeed implements IExternalScheduleFeed
{
    const CHARS_TO_REMOVE = array("-", "_");
    /**
     * @var ClientInterface
     */
    protected $client;

    /**
     * @var Summit
     */
    protected $summit;

    /**
     * AbstractExternalScheduleFeed constructor.
     * @param ClientInterface $client
     * @param Summit $summit
     */
    public function __construct(Summit $summit, ClientInterface $client)
    {
        $this->client = $client;
        $this->summit = $summit;
    }

    /**
     * @param string $url
     * @return array
     * @throws Exception
     */
    protected function get(string $url):array {
        try {
            $response = $this->client->get($url);

            if ($response->getStatusCode() !== 200)
                throw new Exception('invalid status code!');

            $json = $response->getBody()->getContents();
            return json_decode($json, true);
        }
        catch(RequestException $ex){
            Log::warning($ex->getMessage());
            throw $ex;
        }
    }

    /**
     * @param null|string $content
     * @return string
     */
    public static function convert2UTF8(?string $content):string{
        if(empty($content)) return '';
        if(!mb_check_encoding($content, 'UTF-8')
            OR !($content === mb_convert_encoding(mb_convert_encoding($content, 'UTF-32', 'UTF-8' ), 'UTF-8', 'UTF-32'))) {
            $content = mb_convert_encoding($content, 'UTF-8');
        }
        return $content;
    }

    abstract public function getDefaultSpeakerEmail(string $speakerFullName):string;


}