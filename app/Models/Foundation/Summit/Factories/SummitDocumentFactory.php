<?php namespace App\Models\Foundation\Summit\Factories;
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
use models\summit\Summit;
use models\summit\SummitDocument;
/**
 * Class SummitDocumentFactory
 * @package App\Models\Foundation\Summit\Factories
 */
final class SummitDocumentFactory
{
    /**
     * @param Summit $summit
     * @param array $payload
     * @return SummitDocument
     */
    public static function build(Summit $summit, array $payload){
        return self::populate($summit, new SummitDocument(), $payload);
    }

    /**
     * @param Summit $summit
     * @param SummitDocument $document
     * @param array $payload
     * @return SummitDocument
     */
    public static function populate(Summit $summit, SummitDocument $document, array $payload):SummitDocument {
        if(isset($payload['name']))
            $document->setName(trim($payload['name']));

        if(isset($payload['label']))
            $document->setLabel(trim($payload['label']));

        if(isset($payload['show_always']))
            $document->setShowAlways(boolval($payload['show_always']));

        if(isset($payload['description']))
            $document->setDescription(trim($payload['description']));

        if(isset($payload['web_link']))
            $document->setWebLink(trim($payload['web_link']));

        return $document;
    }
}