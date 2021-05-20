<?php namespace models\summit;
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
use App\Models\Foundation\ExtraQuestions\ExtraQuestionAnswer;
use Doctrine\ORM\Mapping AS ORM;
/**
 * @ORM\Entity
 * @ORM\Table(name="PresentationExtraQuestionAnswer")
 * Class PresentationExtraQuestionAnswer
 * @package App\ModelSerializers\Summit\Presentation\ExtraQuestions
 */
class PresentationExtraQuestionAnswer
    extends ExtraQuestionAnswer
{

    /**
     * @ORM\ManyToOne(targetEntity="models\summit\Presentation", inversedBy="extra_question_answers")
     * @ORM\JoinColumn(name="PresentationID", referencedColumnName="ID")
     * @var Presentation
     */
    private $presentation;

    /**
     * @return Presentation
     */
    public function getPresentation(): Presentation
    {
        return $this->presentation;
    }

    /**
     * @param Presentation $presentation
     */
    public function setPresentation(Presentation $presentation): void
    {
        $this->presentation = $presentation;
    }

    public function clearPresentation(){
        $this->presentation = null;
    }

}