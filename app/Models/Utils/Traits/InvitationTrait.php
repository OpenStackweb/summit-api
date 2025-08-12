<?php namespace App\Models\Utils\Traits;
/**
 * Copyright 2025 OpenStack Foundation
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

trait InvitationTrait
{
    use HasTokenTrait;
    public const string Status_Pending = 'Pending';
    public const string Status_Accepted = 'Accepted';
    public const string Status_Rejected = 'Rejected';

    public const array AllowedStatus = [
        self::Status_Pending,
        self::Status_Accepted,
        self::Status_Rejected
    ];

    /**
     * @return bool
     */
    public function isAccepted(): bool
    {
        return $this->status === self::Status_Accepted;
    }

    public function isPending():bool{
        return $this->status === self::Status_Pending;
    }

    public function isRejected():bool{
        return $this->status === self::Status_Rejected;
    }

    /**
     * @return void
     * @throws ValidationException
     * @throws \DateMalformedStringException
     */
    public function markAsRejected(): void {
        $this->setStatus(self::Status_Rejected);
    }

    /**
     * @return void
     * @throws ValidationException
     */
    public function markAsAccepted(): void {
        $this->setStatus(self::Status_Accepted);
    }

    /**
     * @param string $status
     * @return void
     * @throws ValidationException
     */
    public function setStatus(string $status): void
    {
        if(!in_array($status,self::AllowedStatus))
            throw new ValidationException(sprintf("Status %s is not allowed.", $status));

        $this->action_date = $status == self::Status_Pending ?
            null : new \DateTime('now', new \DateTimeZone('UTC'));

        $this->status = $status;
    }

    /**
     * @return \DateTime|null
     */
    public function getActionDate(): ?\DateTime
    {
        return $this->action_date;
    }

    /**
     * @return string
     */
    public function getStatus(): string
    {
        return $this->status;
    }

    public function isSent(): bool
    {
        return !empty($this->hash);
    }

    /**
     * @param bool $accepted
     * @throws \Exception
     */
    public function setAccepted(bool $accepted):void
    {
        $this->status = $accepted ? self::Status_Accepted : self::Status_Rejected;
        $this->action_date = new \DateTime('now', new \DateTimeZone('UTC'));
    }

}