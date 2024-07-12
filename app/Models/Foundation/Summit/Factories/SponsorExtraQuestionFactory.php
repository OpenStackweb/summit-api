<?php namespace App\Models\Foundation\Summit\Factories;
/*
 * Copyright 2024 OpenStack Foundation
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

use App\Models\Foundation\ExtraQuestions\ExtraQuestionType;
use App\Models\Foundation\ExtraQuestions\Factories\ExtraQuestionTypeFactory;
use App\Models\Foundation\Summit\ExtraQuestions\SummitSponsorExtraQuestionType;

/**
 * Class SponsorSocialNetworkFactory
 * @package App\Models\Foundation\Summit\Factories
 */
final class SponsorExtraQuestionFactory extends ExtraQuestionTypeFactory {
  /**
   * @param array $data
   * @return ExtraQuestionType
   * @throws \models\exceptions\ValidationException
   */
  public static function build(array $data): ExtraQuestionType {
    return self::populate(self::getNewEntity(), $data);
  }

  /**
   * @param ExtraQuestionType $extra_question
   * @param array $data
   * @return ExtraQuestionType
   */
  public static function populate(
    ExtraQuestionType $extra_question,
    array $data,
  ): ExtraQuestionType {
    return parent::populate($extra_question, $data);
  }

  protected static function getNewEntity(): ExtraQuestionType {
    return new SummitSponsorExtraQuestionType();
  }
}
