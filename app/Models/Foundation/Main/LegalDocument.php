<?php namespace models\main;
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

class LegalDocument {
  /**
   * @var int
   */
  private $id;

  /**
   * @var string
   */
  private $title;

  /**
   * @var string
   */
  private $slug;

  /**
   * @var string
   */
  private $content;

  /**
   * LegalDocument constructor.
   * @param int $id
   * @param string $title
   * @param string $slug
   * @param string $content
   */
  public function __construct(int $id, string $title, string $slug, string $content) {
    $this->id = $id;
    $this->title = $title;
    $this->slug = $slug;
    $this->content = $content;
  }

  /**
   * @return int
   */
  public function getId(): int {
    return $this->id;
  }

  /**
   * @return string
   */
  public function getTitle(): string {
    return $this->title;
  }

  /**
   * @return string
   */
  public function getSlug(): string {
    return $this->slug;
  }

  /**
   * @return string
   */
  public function getContent(): string {
    return $this->content;
  }
}
