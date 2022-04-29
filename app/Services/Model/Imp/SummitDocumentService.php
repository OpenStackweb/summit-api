<?php namespace App\Services\Model\Imp;
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
use App\Http\Utils\IFileUploader;
use App\Models\Foundation\Summit\Factories\SummitDocumentFactory;
use App\Services\Model\AbstractService;
use App\Services\Model\ISummitDocumentService;
use Illuminate\Support\Facades\Log;
use libs\utils\ITransactionService;
use models\exceptions\EntityNotFoundException;
use models\exceptions\ValidationException;
use models\main\IFolderRepository;
use models\summit\Summit;
use models\summit\SummitDocument;
/**
 * Class SummitDocumentService
 * @package App\Services\Model\Imp
 */
final class SummitDocumentService
    extends AbstractService
    implements ISummitDocumentService
{

    /**
     * @var IFileUploader
     */
    private $file_uploader;

    /**
     * @var IFolderRepository
     */
    private $folder_repository;

    /**
     * SummitDocumentService constructor.
     * @param IFileUploader $file_uploader
     * @param IFolderRepository $folder_repository
     * @param ITransactionService $tx_service
     */
    public function __construct
    (
        IFileUploader $file_uploader,
        IFolderRepository $folder_repository,
        ITransactionService $tx_service
    )
    {
        parent::__construct($tx_service);
        $this->file_uploader = $file_uploader;
        $this->folder_repository = $folder_repository;
    }

    /**
     * @param Summit $summit
     * @param array $payload
     * @return SummitDocument
     * @throws \Exception
     */
    public function addSummitDocument(Summit $summit, array $payload): SummitDocument
    {
        return $this->tx_service->transaction(function() use($summit, $payload){

            if(isset($payload['name'])){
                $former_document = $summit->getSummitDocumentByName($payload['name']);
                if(!is_null($former_document))
                    throw new ValidationException(sprintf("name %s already exists.", $payload['name']));
            }

            if(isset($payload['label'])){
                $former_document = $summit->getSummitDocumentByLabel($payload['label']);
                if(!is_null($former_document) )
                    throw new ValidationException(sprintf("label %s already exists.", $payload['label']));
            }

            $document = SummitDocumentFactory::build($summit, $payload);

            if(!$document->isShowAlways() && isset($payload['event_types'])){
                $document->clearEventTypes();
                foreach($payload['event_types'] as $event_type_id){
                    $event_type = $summit->getEventType(intval($event_type_id));
                    if(is_null($event_type)){
                        throw new EntityNotFoundException();
                    }
                    $document->addEventType($event_type);
                }
            }

            if(!$document->isShowAlways() && $document->getEventTypes()->count() == 0)
                throw new ValidationException("You need to to set at least one Activity Type.");

            $file = $payload['file'];
            $attachment = $this->file_uploader->build
            (
                $file,
                sprintf('summits/%s/documents', $summit->getId()),
                false
            );

            $document->setFile($attachment);

            if(isset($payload['selection_plan_id'])){
                $document->clearSelectionPlan();
                $selection_plan_id = intval($payload['selection_plan_id']);
                if($selection_plan_id > 0) {

                    $selection_plan = $summit->getSelectionPlanById($selection_plan_id);
                    if (is_null($selection_plan))
                        throw new EntityNotFoundException(sprintf("Selection Plan %s not found.", $selection_plan_id));

                    $document->setSelectionPlan($selection_plan);
                }
            }

            $summit->addSummitDocument($document);

            return $document;

        });
    }

    /**
     * @param Summit $summit
     * @param int $document_id
     * @param array $payload
     * @return SummitDocument
     * @throws \Exception
     */
    public function updateSummitDocument(Summit $summit, int $document_id, array $payload): SummitDocument
    {
        return $this->tx_service->transaction(function() use($summit, $document_id, $payload){

            Log::debug(sprintf("SummitDocumentService::updateSummitDocument document %s payload %s", $document_id, json_encode($payload) ));

            $document = $summit->getSummitDocumentById($document_id);
            if(is_null($document))
                throw new EntityNotFoundException();

            if(isset($payload['name'])){
                $former_document = $summit->getSummitDocumentByName($payload['name']);
                if(!is_null($former_document) && $former_document->getId() !== $document_id)
                    throw new ValidationException(sprintf("name %s already exists.", $payload['name']));
            }

            if(isset($payload['label'])){
                $former_document = $summit->getSummitDocumentByLabel($payload['label']);
                if(!is_null($former_document) && $former_document->getId() !== $document_id)
                    throw new ValidationException(sprintf("label %s already exists.", $payload['label']));
            }

            $document = SummitDocumentFactory::populate($summit, $document, $payload);

            if(!$document->isShowAlways() && isset($payload['event_types'])){
                $document->clearEventTypes();
                foreach($payload['event_types'] as $event_type_id){
                    $event_type = $summit->getEventType(intval($event_type_id));
                    if(is_null($event_type)){
                        throw new EntityNotFoundException();
                    }
                    $document->addEventType($event_type);
                }
            }

            if(!$document->isShowAlways() && $document->getEventTypes()->count() == 0)
                throw new ValidationException("You need to to set at least one Activity Type.");

            if(isset($payload['file'])){

                if($document->hasFile()){
                    // drop file
                    $attachment = $document->getFile();
                    $this->folder_repository->delete($attachment);
                    $document->clearFile();
                    $attachment = null;
                }

                $attachment = $this->file_uploader->build
                (
                    $payload['file'],
                    sprintf('summits/%s/documents', $summit->getId()),
                    false
                );

                $document->setFile($attachment);
            }

            if(isset($payload['selection_plan_id'])){
                Log::debug(sprintf("SummitDocumentService::updateSummitDocument document %s selection plan id %s", $document_id, $payload['selection_plan_id'] ));
                $document->clearSelectionPlan();
                $selection_plan_id = intval($payload['selection_plan_id']);
                if($selection_plan_id > 0) {
                    $selection_plan = $summit->getSelectionPlanById($selection_plan_id);
                    if (is_null($selection_plan))
                        throw new EntityNotFoundException(sprintf("Selection Plan %s not found.", $selection_plan_id));

                    $document->setSelectionPlan($selection_plan);
                }
            }

            return $document;
        });
    }

    /**
     * @param Summit $summit
     * @param int $document_id
     * @throws \Exception
     */
    public function deleteSummitDocument(Summit $summit, int $document_id): void
    {
        $this->tx_service->transaction(function() use($summit, $document_id){
            $document = $summit->getSummitDocumentById($document_id);
            if(is_null($document))
                throw new EntityNotFoundException();

            $summit->removeSummitDocument($document);
        });
    }

    /**
     * @param Summit $summit
     * @param int $document_id
     * @param int $event_type_id
     * @return SummitDocument
     * @throws \Exception
     */
    public function addEventTypeToSummitDocument(Summit $summit, int $document_id, int $event_type_id): SummitDocument
    {
        return $this->tx_service->transaction(function() use($summit, $document_id, $event_type_id){
            $document = $summit->getSummitDocumentById($document_id);
            if(is_null($document))
                throw new EntityNotFoundException();

            $event_type = $summit->getEventType($event_type_id);
            if(is_null($event_type))
                throw new EntityNotFoundException();

            $document->addEventType($event_type);

            return $document;
        });
    }

    /**
     * @param Summit $summit
     * @param int $document_id
     * @param int $event_type_id
     * @return SummitDocument
     * @throws \Exception
     */
    public function removeEventTypeFromSummitDocument(Summit $summit, int $document_id, int $event_type_id): SummitDocument
    {
        return $this->tx_service->transaction(function() use($summit, $document_id, $event_type_id){
            $document = $summit->getSummitDocumentById($document_id);
            if(is_null($document))
                throw new EntityNotFoundException();

            $event_type = $summit->getEventType($event_type_id);
            if(is_null($event_type))
                throw new EntityNotFoundException();

            $document->removeEventType($event_type);

            return $document;
        });
    }
}