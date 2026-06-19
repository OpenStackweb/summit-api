<?php namespace App\Services\Model;

use models\exceptions\EntityNotFoundException;
use models\exceptions\ValidationException;
use models\utils\IEntity;

interface IFilePostProcessorForChildEntity
{
    /**
     * @param FileInfoDTO $file_info_dto
     * @return IEntity
     * @throws EntityNotFoundException
     * @throws ValidationException
     */
    public function processFileForChildEntity(FileInfoDTO $file_info_dto): IEntity;
}
