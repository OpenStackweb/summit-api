<?php namespace App\Services\utils;

/**
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

use Illuminate\Support\Facades\Cache;
use Ramsey\Uuid\Guid\Guid;

/**
 * Class EmailExcerptService
 * @package App\Services\utils
 */
final class EmailExcerptService implements IEmailExcerptService {
  private $prefix;

  /**
   * EmailExcerptService constructor.
   */
  public function __construct($uuid) {
    $this->prefix = $uuid;
  }

  private function getReportKey(): string {
    return $this->prefix . "_email_excerpt_report";
  }

  private function getEmailCountKey(): string {
    return $this->prefix . "_sent_email_count";
  }

  /**
   * @inheritDoc
   */
  public function add(array $value): void {
    $report = $this->getReport();
    $report[] = $value;
    Cache::put($this->getReportKey(), $report);
  }

  public function addInfoMessage(string $message): void {
    $report = $this->getReport();
    $report[] = [
      "type" => IEmailExcerptService::InfoType,
      "message" => $message,
    ];
    Cache::put($this->getReportKey(), $report);
  }

  public function addErrorMessage(string $message): void {
    $report = $this->getReport();
    $report[] = [
      "type" => IEmailExcerptService::ErrorType,
      "message" => $message,
    ];
    Cache::put($this->getReportKey(), $report);
  }

  /**
   * @inheritDoc
   */
  public function clearReport(): void {
    Cache::forget($this->getReportKey());
    Cache::forget($this->getEmailCountKey());
  }

  /**
   * @inheritDoc
   */
  public function getReport(): array {
    $report = Cache::get($this->getReportKey());
    if ($report == null) {
      return [];
    }
    return $report;
  }

  public function addEmailSent(): void {
    $count = Cache::get($this->getEmailCountKey());
    if ($count == null) {
      $count = 0;
    }
    Cache::put($this->getEmailCountKey(), ++$count);
  }

  public function generateEmailCountLine(): void {
    $count = Cache::get($this->getEmailCountKey());
    if ($count == null) {
      $count = 0;
    }
    $this->addInfoMessage("Total $count email(s) sent.");
  }
}
