<?php namespace models\summit;
/**
 * Copyright 2026 OpenStack Foundation
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

use Doctrine\ORM\Mapping AS ORM;
use models\utils\SilverstripeBaseModel;

/**
 * Class PendingMediaUpload
 * @package models\summit
 */
#[ORM\Table(name: 'PendingMediaUpload')]
#[ORM\Entity(repositoryClass: \App\Repositories\Summit\DoctrinePendingMediaUploadRepository::class)]
#[ORM\Index(name: 'IDX_Status', columns: ['Status'])]
class PendingMediaUpload extends SilverstripeBaseModel
{
    const STATUS_PENDING = 'Pending';
    const STATUS_PROCESSING = 'Processing';
    const STATUS_COMPLETED = 'Completed';
    const STATUS_ERROR = 'Error';
    const STATUS_PUBLIC_STORAGE_UPLOADED = 'PublicStorageUploaded';
    const STATUS_PRIVATE_STORAGE_UPLOADED = 'PrivateStorageUploaded';

    /**
     * @var int
     */
    #[ORM\Column(name: 'SummitID', type: 'integer', nullable: false)]
    private $summit_id;

    /**
     * @var int
     */
    #[ORM\Column(name: 'MediaUploadTypeID', type: 'integer', nullable: false)]
    private $media_upload_type_id;

    /**
     * @var PresentationMediaUpload
     */
    #[ORM\ManyToOne(targetEntity: \models\summit\PresentationMediaUpload::class)]
    #[ORM\JoinColumn(name: 'PresentationMediaUploadID', referencedColumnName: 'ID', nullable: false, onDelete: 'CASCADE')]
    private $media_upload;

    /**
     * @var string|null
     */
    #[ORM\Column(name: 'PublicPath', type: 'string', length: 500, nullable: true)]
    private $public_path;

    /**
     * @var string|null
     */
    #[ORM\Column(name: 'PrivatePath', type: 'string', length: 500, nullable: true)]
    private $private_path;

    /**
     * @var string
     */
    #[ORM\Column(name: 'FileName', type: 'string', length: 255, nullable: false)]
    private $file_name;

    /**
     * @var string
     */
    #[ORM\Column(name: 'TempFilePath', type: 'string', length: 500, nullable: false)]
    private $temp_file_path;

    /**
     * @var string
     */
    #[ORM\Column(name: 'Status', type: 'string', length: 30, nullable: false, options: ['default' => 'Pending'])]
    private $status;

    /**
     * @var string|null
     */
    #[ORM\Column(name: 'ErrorMessage', type: 'text', nullable: true)]
    private $error_message;

    /**
     * @var int
     */
    #[ORM\Column(name: 'Attempts', type: 'integer', nullable: false, options: ['default' => 0])]
    private $attempts;

    /**
     * @var \DateTime|null
     */
    #[ORM\Column(name: 'ProcessedDate', type: 'datetime', nullable: true)]
    private $processed_date;

    public function __construct()
    {
        parent::__construct();
        $this->status = self::STATUS_PENDING;
        $this->attempts = 0;
    }

    /**
     * @return int
     */
    public function getSummitId(): int
    {
        return $this->summit_id;
    }

    /**
     * @param int $summit_id
     */
    public function setSummitId(int $summit_id): void
    {
        $this->summit_id = $summit_id;
    }

    /**
     * @return int
     */
    public function getMediaUploadTypeId(): int
    {
        return $this->media_upload_type_id;
    }

    /**
     * @param int $media_upload_type_id
     */
    public function setMediaUploadTypeId(int $media_upload_type_id): void
    {
        $this->media_upload_type_id = $media_upload_type_id;
    }

    /**
     * @return PresentationMediaUpload
     */
    public function getMediaUpload(): PresentationMediaUpload
    {
        return $this->media_upload;
    }

    /**
     * @param PresentationMediaUpload $media_upload
     */
    public function setMediaUpload(PresentationMediaUpload $media_upload): void
    {
        $this->media_upload = $media_upload;
    }

    /**
     * @return string|null
     */
    public function getPublicPath(): ?string
    {
        return $this->public_path;
    }

    /**
     * @param string|null $public_path
     */
    public function setPublicPath(?string $public_path): void
    {
        $this->public_path = $public_path;
    }

    /**
     * @return string|null
     */
    public function getPrivatePath(): ?string
    {
        return $this->private_path;
    }

    /**
     * @param string|null $private_path
     */
    public function setPrivatePath(?string $private_path): void
    {
        $this->private_path = $private_path;
    }

    /**
     * @return string
     */
    public function getFileName(): string
    {
        return $this->file_name;
    }

    /**
     * @param string $file_name
     */
    public function setFileName(string $file_name): void
    {
        $this->file_name = $file_name;
    }

    /**
     * @return string
     */
    public function getTempFilePath(): string
    {
        return $this->temp_file_path;
    }

    /**
     * @param string $temp_file_path
     */
    public function setTempFilePath(string $temp_file_path): void
    {
        $this->temp_file_path = $temp_file_path;
    }

    /**
     * @return string
     */
    public function getStatus(): string
    {
        return $this->status;
    }

    /**
     * @param string $status
     */
    public function setStatus(string $status): void
    {
        $this->status = $status;
    }

    /**
     * @return string|null
     */
    public function getErrorMessage(): ?string
    {
        return $this->error_message;
    }

    /**
     * @param string|null $error_message
     */
    public function setErrorMessage(?string $error_message): void
    {
        $this->error_message = $error_message;
    }

    /**
     * @return int
     */
    public function getAttempts(): int
    {
        return $this->attempts;
    }

    /**
     * @param int $attempts
     */
    public function setAttempts(int $attempts): void
    {
        $this->attempts = $attempts;
    }

    /**
     * Increment the attempts counter
     */
    public function incrementAttempts(): void
    {
        $this->attempts++;
    }

    /**
     * @return \DateTime|null
     */
    public function getProcessedDate(): ?\DateTime
    {
        return $this->processed_date;
    }

    /**
     * @param \DateTime|null $processed_date
     */
    public function setProcessedDate(?\DateTime $processed_date): void
    {
        $this->processed_date = $processed_date;
    }

    /**
     * @return bool
     */
    public function isPublicStorageUploaded(): bool
    {
        return $this->status === self::STATUS_PUBLIC_STORAGE_UPLOADED;
    }

    /**
     * @return bool
     */
    public function isPrivateStorageUploaded(): bool
    {
        return $this->status === self::STATUS_PRIVATE_STORAGE_UPLOADED;
    }
}
