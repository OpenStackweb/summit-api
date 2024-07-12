<?php namespace App\Models\Foundation\Summit\Factories;
/*
 * Copyright 2022 OpenStack Foundation
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

use models\main\Member;
use models\summit\SummitPresentationComment;

/**
 * Class SummitPresentationCommentFactory
 * @package App\Models\Foundation\Summit\Factories
 */
final class SummitPresentationCommentFactory {
  /**
   * @param Member $member
   * @param array $payload
   * @return SummitPresentationComment
   */
  public static function build(Member $member, array $payload): SummitPresentationComment {
    return self::populate(new SummitPresentationComment(), $payload, $member);
  }

  /**
   * @param SummitPresentationComment $comment
   * @param array $payload
   * @param Member|null $member
   * @return SummitPresentationComment
   */
  public static function populate(
    SummitPresentationComment $comment,
    array $payload,
    ?Member $member = null,
  ): SummitPresentationComment {
    if (isset($payload["body"])) {
      $comment->setBody(trim($payload["body"]));
    }

    if (!is_null($member)) {
      $comment->setCreator($member);
    }

    if (isset($payload["is_activity"])) {
      $comment->setIsActivity(boolval($payload["is_activity"]));
    }

    if (isset($payload["is_public"])) {
      $comment->setIsPublic(boolval($payload["is_public"]));
    }

    return $comment;
  }
}
