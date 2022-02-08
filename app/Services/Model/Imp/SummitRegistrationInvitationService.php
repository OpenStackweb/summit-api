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

use App\Jobs\Emails\Registration\Invitations\InviteSummitRegistrationEmail;
use App\Jobs\Emails\Registration\Invitations\ProcessRegistrationInvitationsJob;
use App\Jobs\Emails\Registration\Invitations\ReInviteSummitRegistrationEmail;
use App\Models\Foundation\Summit\Factories\SummitRegistrationInvitationFactory;
use App\Models\Foundation\Summit\Repositories\ISummitRegistrationInvitationRepository;
use App\Services\Apis\IExternalUserApi;
use App\Services\Model\AbstractService;
use App\Services\Model\dto\ExternalUserDTO;
use App\Services\Model\IMemberService;
use App\Services\Model\ISummitRegistrationInvitationService;
use App\Services\Utils\CSVReader;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use libs\utils\ITransactionService;
use models\exceptions\EntityNotFoundException;
use models\exceptions\ValidationException;
use models\main\IMemberRepository;
use models\main\Member;
use models\summit\ISummitRepository;
use models\summit\Summit;
use models\summit\SummitRegistrationInvitation;
use utils\Filter;
use utils\FilterElement;
use utils\PagingInfo;

/**
 * Class SummitRegistrationInvitationService
 * @package App\Services\Model\Imp
 */
final class SummitRegistrationInvitationService
    extends AbstractService
    implements ISummitRegistrationInvitationService

{
    /**
     * @var IMemberRepository
     */
    private $member_repository;

    /**
     * @var IExternalUserApi
     */
    private $external_user_api;

    /**
     * @var IMemberService
     */
    private $member_service;

    /**
     * @var ISummitRegistrationInvitationRepository
     */
    private $invitation_repository;

    /**
     * @var ISummitRepository
     */
    private $summit_repository;

    /**
     * @param IExternalUserApi $external_user_api
     * @param IMemberService $member_service
     * @param ISummitRegistrationInvitationRepository $invitation_repository
     * @param IMemberRepository $member_repository
     * @param ISummitRepository $summit_repository
     * @param ITransactionService $tx_service
     */
    public function __construct
    (
        IExternalUserApi                        $external_user_api,
        IMemberService                          $member_service,
        ISummitRegistrationInvitationRepository $invitation_repository,
        IMemberRepository                       $member_repository,
        ISummitRepository                       $summit_repository,
        ITransactionService                     $tx_service
    )
    {
        parent::__construct($tx_service);
        $this->member_repository = $member_repository;
        $this->summit_repository = $summit_repository;
        $this->external_user_api = $external_user_api;
        $this->invitation_repository = $invitation_repository;
        $this->member_service = $member_service;
    }

    /**
     * @param Summit $summit
     * @param UploadedFile $csv_file
     * @throws ValidationException
     */
    public function importInvitationData(Summit $summit, UploadedFile $csv_file): void
    {
        Log::debug(sprintf("SummitRegistrationInvitationService::importInvitationData - summit %s", $summit->getId()));

        $allowed_extensions = ['txt'];

        if (!in_array($csv_file->extension(), $allowed_extensions)) {
            throw new ValidationException("File does not has a valid extension ('csv').");
        }

        $csv_data = File::get($csv_file->getRealPath());

        if (empty($csv_data))
            throw new ValidationException("File content is empty.");

        $reader = CSVReader::buildFrom($csv_data);

        // check needed columns (headers names)
        /***********************************************************
         * columns
         * email (mandatory)
         * first_name (mandatory)
         * last_name (mandatory)
         * allowed_ticket_types (optional)
         ***********************************************************/

        if (!$reader->hasColumn("email"))
            throw new ValidationException("File is missing email column.");
        if (!$reader->hasColumn("first_name"))
            throw new ValidationException("File is missing first_name column.");
        if (!$reader->hasColumn("last_name"))
            throw new ValidationException("File is missing last_name column.");

        foreach ($reader as $idx => $row) {
            try {

                Log::debug(sprintf("SummitRegistrationInvitationService::importInvitationData processing row %s", json_encode($row)));
                if (isset($row['allowed_ticket_types']) && is_string($row['allowed_ticket_types'])) {
                    $row['allowed_ticket_types'] = empty($row['allowed_ticket_types']) ? [] : explode('|', $row['allowed_ticket_types']);
                }
                $this->add($summit, $row);
            } catch (\Exception $ex) {
                Log::warning($ex);
                $summit = $this->summit_repository->getById($summit->getId());
            }
        }
    }

    /**
     * @inheritDoc
     */
    public function delete(Summit $summit, int $invitation_id): void
    {
        $this->tx_service->transaction(function () use ($summit, $invitation_id) {
            $invitation = $summit->getSummitRegistrationInvitationById($invitation_id);
            if (is_null($invitation)) {
                throw new EntityNotFoundException("Invitation not found.");
            }

            $summit->removeRegistrationInvitation($invitation);
        });
    }

    /**
     * @inheritDoc
     */
    public function add(Summit $summit, array $payload): SummitRegistrationInvitation
    {
        return $this->tx_service->transaction(function () use ($summit, $payload) {

            $email = trim($payload['email']);
            Log::debug(sprintf("SummitRegistrationInvitationService::add trying to add email %s", $email));
            $allowed_ticket_types = $payload['allowed_ticket_types'] ?? [];
            $former_invitation = $summit->getSummitRegistrationInvitationByEmail($email);
            if (!is_null($former_invitation)) {
                throw new ValidationException(sprintf("Email %s already has been invited for summit %s", $email, $summit->getId()));
            }

            $invitation = SummitRegistrationInvitationFactory::build($payload);
            foreach ($allowed_ticket_types as $ticket_type_id) {
                $ticket_type = $summit->getTicketTypeById(intval($ticket_type_id));
                Log::debug(sprintf("SummitRegistrationInvitationService::add trying to add ticket %s for invitation email %s", $ticket_type_id, $email));
                if (is_null($ticket_type)) {
                    throw new ValidationException
                    (
                        sprintf
                        (
                            "SummitRegistrationInvitationService::add ticket type %s does not exists on summit %s.",
                            $ticket_type_id,
                            $summit->getId()
                        )
                    );
                }
                $invitation->addTicketType($ticket_type);
            }

            $invitation = $this->setInvitationMember($invitation, $email);
            Log::debug(sprintf("SummitRegistrationInvitationService::add adding invitation for email %s to summit %s", $email, $summit->getName()));
            $summit->addRegistrationInvitation($invitation);

            return $invitation;
        });
    }

    /**
     * @param SummitRegistrationInvitation $invitation
     * @param string $email
     * @return SummitRegistrationInvitation
     * @throws \Exception
     */
    private function setInvitationMember(SummitRegistrationInvitation $invitation, string $email): SummitRegistrationInvitation
    {
        return $this->tx_service->transaction(function () use ($invitation, $email) {
            // try to get local user
            $member = $this->member_repository->getByEmail($email);
            // try to get an user externally , user does not exist locally
            if (is_null($member)) {
                // check if user exists by email at idp
                Log::debug(sprintf("SummitRegistrationInvitationService::setInvitationMember - trying to get member %s from user api", $email));
                $user = $this->external_user_api->getUserByEmail($email);
                // check if primary email is the same if not disregard
                $primary_email = is_null($user) ? null : $user['email'] ?? null;
                if (strcmp(strtolower($primary_email), strtolower($email)) !== 0) {
                    Log::debug
                    (
                        sprintf
                        (
                            "SummitRegistrationInvitationService::setInvitationMember primary email %s differs from order owner email %s",
                            $primary_email,
                            $email
                        )
                    );

                    // email are not equals , then is not the user bc primary emails differs ( could be a
                    // match on a secondary email)
                    $user = null; // set null on user and proceed to emit a registration request.
                }

                if (!is_null($user)) {
                    Log::debug
                    (
                        sprintf
                        (
                            "SummitRegistrationInvitationService::setInvitationMember - Creating a local user for %s",
                            $email
                        )
                    );
                    $external_id = $user['id'];
                    try {

                        // we have an user on idp
                        // possible race condition
                        $member = $this->member_service->registerExternalUser
                        (
                            new ExternalUserDTO
                            (
                                $external_id,
                                $user['email'],
                                $user['first_name'],
                                $user['last_name'],
                                boolval($user['active']),
                                boolval($user['email_verified'])
                            )
                        );
                    } catch (\Exception $ex) {
                        Log::warning($ex);
                        // race condition lost
                        $member = $this->member_repository->getByExternalIdExclusiveLock(intval($external_id));
                        $invitation = $this->invitation_repository->getByIdExclusiveLock($invitation->getId());
                    }
                }
            }

            if (!is_null($member))
                $invitation->setMember($member);

            return $invitation;
        });
    }

    /**
     * @inheritDoc
     */
    public function update(Summit $summit, int $invitation_id, array $payload): SummitRegistrationInvitation
    {
        return $this->tx_service->transaction(function () use ($summit, $payload, $invitation_id) {
            $invitation = $summit->getSummitRegistrationInvitationById($invitation_id);
            if (is_null($invitation))
                throw new EntityNotFoundException(sprintf("Invitation %s not found at Summit %s", $invitation_id, $summit->getId()));

            if (isset($payload['email'])) {
                $email = trim($payload['email']);
                $former_invitation = $summit->getSummitRegistrationInvitationByEmail($email);
                if (!is_null($former_invitation) && $former_invitation->getId() !== $invitation_id) {
                    throw new ValidationException(sprintf("Email %s already has been invited for summit %s", $email, $summit->getId()));
                }
            }

            $invitation = SummitRegistrationInvitationFactory::populate($invitation, $payload);

            if (isset($payload['email'])) {
                $email = trim($payload['email']);
                $invitation = $this->setInvitationMember($invitation, $email);
            }

            $allowed_ticket_types = $payload['allowed_ticket_types'] ?? [];
            $invitation->clearTicketTypes();
            foreach ($allowed_ticket_types as $ticket_type_id) {
                $ticket_type = $summit->getTicketTypeById(intval($ticket_type_id));
                if (is_null($ticket_type)) {
                    throw new ValidationException
                    (
                        sprintf
                        (
                            "SummitRegistrationInvitationService::add ticket type %s does not exists on summit %s.",
                            $ticket_type_id,
                            $summit->getId()
                        )
                    );
                }
                $invitation->addTicketType($ticket_type);
            }

            return $invitation;
        });
    }

    /**
     * @param Member $current_member
     * @param string $token
     * @return SummitRegistrationInvitation
     * @throws \Exception
     */
    public function getInvitationByToken(Member $current_member, string $token): SummitRegistrationInvitation
    {
        return $this->tx_service->transaction(function () use ($current_member, $token) {

            $invitation = $this->invitation_repository->getByHashExclusiveLock(SummitRegistrationInvitation::HashConfirmationToken($token));

            if (is_null($invitation))
                throw new EntityNotFoundException("Invitation not found.");
            Log::debug(sprintf("got invitation %s for email %s", $invitation->getId(), $invitation->getEmail()));
            if (strtolower($invitation->getEmail()) !== strtolower($current_member->getEmail()))
                throw new ValidationException(sprintf(
                    "This invitation was sent to %s but you logged in 
                    as %s. Please log out, reaccept the invite, and log in with 
                    the email address used for the invite."
                    , $invitation->getEmail(), $current_member->getEmail()));

            $invitation->setMember($current_member);

            if ($invitation->isAccepted()) {
                throw new ValidationException("This Invitation is already accepted.");
            }

            return $invitation;
        });
    }

    /**
     * @param Summit $summit
     * @param string $email
     * @return SummitRegistrationInvitation|null
     * @throws \Exception
     */
    public function getInvitationByEmail(Summit $summit, string $email): ?SummitRegistrationInvitation
    {
        return $this->tx_service->transaction(function () use ($summit, $email) {
            if (!$summit->isInviteOnlyRegistration())
                throw new ValidationException(sprintf("Summit %s is not invite only.", $summit->getId()));

            return $summit->getSummitRegistrationInvitationByEmail($email);
        });
    }

    /**
     * @inheritDoc
     */
    public function deleteAll(Summit $summit): void
    {
        $this->tx_service->transaction(function () use ($summit) {
            $summit->clearRegistrationInvitations();
        });
    }

    /**
     * @param Summit $summit
     * @param array $payload
     * @param Filter|null $filter
     */
    public function triggerSend(Summit $summit, array $payload, $filter = null): void
    {
        ProcessRegistrationInvitationsJob::dispatch($summit, $payload, $filter);
    }

    /**
     * @inheritDoc
     */
    public function send(int $summit_id, array $payload, Filter $filter = null): void
    {
        $flow_event = trim($payload['email_flow_event']);

        Log::debug(sprintf("SummitRegistrationInvitationService::send summit id %s flow_event %s filter %s", $summit_id, $flow_event, $filter->__toString()));

        $page = 1;
        $count = 0;

        $to_exclude = [
            1120,
            1121,
            1123,
            1124,
            1125,
            1126,
            1127,
            1129,
            1130,
            1134,
            1136,
            1137,
            1140,
            1145,
            1146,
            1151,
            1152,
            1153,
            1154,
            1155,
            1156,
            1157,
            1158,
            1159,
            1160,
            1161,
            1163,
            1164,
            1165,
            1166,
            1168,
            1170,
            1171,
            1172,
            1173,
            1174,
            1175,
            1177,
            1178,
            1179,
            1181,
            1182,
            1184,
            1185,
            1186,
            1187,
            1188,
            1190,
            1191,
            1192,
            1195,
            1196,
            1197,
            1199,
            1200,
            1201,
            1202,
            1203,
            1204,
            1205,
            1206,
            1207,
            1208,
            1209,
            1210,
            1211,
            1213,
            1214,
            1215,
            1217,
            1218,
            1219,
            1220,
            1221,
            1222,
            1223,
            1225,
            1226,
            1227,
            1228,
            1229,
            1230,
            1231,
            1232,
            1235,
            1236,
            1237,
            1238,
            1239,
            1240,
            1241,
            1242,
            1243,
            1244,
            1245,
            1246,
            1247,
            1249,
            1250,
            1252,
            1253,
            1256,
            1257,
            1258,
            1259,
            1260,
            1262,
            1263,
            1265,
            1266,
            1270,
            1272,
            1273,
            1274,
            1275,
            1276,
            1277,
            1278,
            1279,
            1281,
            1282,
            1283,
            1285,
            1286,
            1287,
            1288,
            1289,
            1290,
            1291,
            1292,
            1293,
            1294,
            1295,
            1296,
            1297,
            1298,
            1299,
            1300,
            1301,
            1302,
            1304,
            1305,
            1306,
            1307,
            1309,
            1310,
            1311,
            1314,
            1315,
            1317,
            1318,
            1319,
            1322,
            1323,
            1324,
            1325,
            1326,
            1327,
            1328,
            1329,
            1330,
            1331,
            1333,
            1334,
            1335,
            1337,
            1338,
            1339,
            1340,
            1342,
            1343,
            1344,
            1345,
            1346,
            1347,
            1348,
            1349,
            1350,
            1351,
            1352,
            1354,
            1356,
            1357,
            1358,
            1359,
            1360,
            1361,
            1362,
            1363,
            1364,
            1365,
            1366,
            1368,
            1369,
            1370,
            1372,
            1373,
            1375,
            1376,
            1377,
            1378,
            1379,
            1380,
            1381,
            1382,
            1383,
            1385,
            1386,
            1388,
            1390,
            1391,
            1392,
            1393,
            1394,
            1395,
            1397,
            1399,
            1400,
            1401,
            1402,
            1403,
            1404,
            1405,
        ];

        while (true) {
            Log::debug(sprintf("SummitRegistrationInvitationService::send summit id %s flow_event %s filter %s processing page %s", $summit_id, $flow_event, $filter->__toString(), $page));
            $ids = $this->tx_service->transaction(function () use ($summit_id, $payload, $filter, $page) {
                if (isset($payload['invitations_ids'])) {
                    Log::debug(sprintf("SummitRegistrationInvitationService::send summit id %s invitations_ids %s", $summit_id, json_encode($payload['invitations_ids'])));
                    return $payload['invitations_ids'];
                }
                Log::debug(sprintf("SummitRegistrationInvitationService::send summit id %s getting by filter", $summit_id));
                if (is_null($filter)) {
                    $filter = new Filter();
                }
                if (!$filter->hasFilter("summit_id"))
                    $filter->addFilterCondition(FilterElement::makeEqual('summit_id', $summit_id));
                return $this->invitation_repository->getAllIdsByPage(new PagingInfo($page, 100), $filter);
            });

            Log::debug(sprintf("SummitRegistrationInvitationService::send summit id %s flow_event %s filter %s page %s got %s records", $summit_id, $flow_event, $filter->__toString(), $page, count($ids)));
            if (!count($ids)) {
                Log::debug(sprintf("SummitRegistrationInvitationService::send summit id %s page is empty, ending processing.", $summit_id));
                break;
            }
            foreach ($ids as $invitation_id) {

                if (in_array($invitation_id, $to_exclude)) {
                    Log::debug(sprintf("SummitRegistrationInvitationService::send excluding id %s", $invitation_id));
                }

                $res = $this->tx_service->transaction(function () use ($flow_event, $invitation_id) {

                    Log::debug(sprintf("SummitRegistrationInvitationService::send processing invitation id  %s", $invitation_id));

                    $invitation = $this->invitation_repository->getByIdExclusiveLock(intval($invitation_id));
                    if (is_null($invitation) || !$invitation instanceof SummitRegistrationInvitation) return null;

                    $summit = $invitation->getSummit();

                    while (true) {
                        $invitation->generateConfirmationToken();
                        $former_invitation = $this->invitation_repository->getByHashAndSummit($invitation->getHash(), $summit);
                        if (is_null($former_invitation) || $former_invitation->getId() == $invitation->getId()) break;
                    }

                    return $invitation;
                });

                // send email
                if ($flow_event == InviteSummitRegistrationEmail::EVENT_SLUG && !is_null($res))
                    InviteSummitRegistrationEmail::dispatch($res);
                if ($flow_event == ReInviteSummitRegistrationEmail::EVENT_SLUG && !is_null($res))
                    ReInviteSummitRegistrationEmail::dispatch($res);
                $count++;
            }

            $page++;
        }

        Log::debug(sprintf("SummitRegistrationInvitationService::send summit id %s flow_event %s filter %s had processed %s records", $summit_id, $flow_event, $filter->__toString(), $count));
    }
}