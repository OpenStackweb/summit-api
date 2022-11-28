<?php namespace App\Models\Foundation\Summit\Factories;
/**
 * Copyright 2021 OpenStack Foundation
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

use models\exceptions\ValidationException;
use models\summit\Presentation;
use models\summit\PresentationLink;

/**
 * Class PresentationFactory
 * @package App\Models\Foundation\Summit\Factories
 */
final class PresentationFactory
{
    public static function build(array $payload):Presentation{
        return self::populate(new Presentation(), $payload);
    }

    /**
     * @param Presentation $presentation
     * @param array $payload
     * @param false $only_presentation_data
     * @return Presentation
     * @throws ValidationException
     */
    public static function populate(Presentation $presentation, array $payload, $only_presentation_data = false):Presentation{

        if(!$only_presentation_data) {
            if (isset($payload['title']))
                $presentation->setTitle(html_entity_decode(trim($payload['title'])));

            if (isset($payload['description']))
                $presentation->setAbstract(html_entity_decode(trim($payload['description'])));

            if (isset($payload['social_description']))
                $presentation->setSocialSummary(strip_tags(trim($payload['social_description'])));

            $event_type = $presentation->getType();
            if (isset($payload['level']) && !is_null($event_type))
                $presentation->setLevel($payload['level']);
        }

        if (isset($payload['will_all_speakers_attend']))
            $presentation->setWillAllSpeakersAttend(boolval($payload['will_all_speakers_attend']));

        if (isset($payload['attendees_expected_learnt']))
            $presentation->setAttendeesExpectedLearnt(html_entity_decode($payload['attendees_expected_learnt']));

        $presentation->setAttendingMedia(isset($payload['attending_media']) ?
            filter_var($payload['attending_media'], FILTER_VALIDATE_BOOLEAN) : 0);

        if (isset($payload['to_record']))
            $presentation->setToRecord(boolval($payload['to_record']));

        if (isset($payload['disclaimer_accepted']) && !empty($payload['disclaimer_accepted'])) {
            $disclaimer_accepted = boolval($payload['disclaimer_accepted']);
            if ($disclaimer_accepted && !$presentation->isDisclaimerAccepted()) {
                $presentation->setDisclaimerAcceptedDate
                (
                    new \DateTime('now', new \DateTimeZone('UTC'))
                );
            }
        }

        if(isset($payload['custom_order'])){
            $presentation->setCustomOrder(intval($payload['custom_order']));
        }

        // links

        if (isset($payload['links'])) {

            if (count($payload['links']) > Presentation::MaxAllowedLinks) {
                throw new ValidationException(trans(
                    'validation_errors.PresentationService.saveOrUpdatePresentation.MaxAllowedLinks',
                    [
                        'max_allowed_links' => Presentation::MaxAllowedLinks
                    ]));
            }

            $presentation->clearLinks();
            foreach ($payload['links'] as $link) {
                $presentationLink = new PresentationLink();
                $presentationLink->setName(trim($link));
                $presentationLink->setLink(trim($link));
                $presentation->addLink($presentationLink);
            }
        }

        // extra questions

        $extra_questions = $payload['extra_questions'] ?? [];

        if (count($extra_questions)) {
            $res = $presentation->hadCompletedExtraQuestions($extra_questions);
            if (!$res) {
                throw new ValidationException("You neglected to fill in all mandatory questions for the Presentation.");
            }
        }
        return $presentation;
    }
}