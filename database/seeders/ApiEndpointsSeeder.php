<?php namespace Database\Seeders;
/**
 * Copyright 2015 OpenStack Foundation
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

use App\Security\ElectionScopes;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Config;
use App\Models\ResourceServer\ApiEndpoint;
use LaravelDoctrine\ORM\Facades\EntityManager;
use App\Security\SummitScopes;
use App\Security\OrganizationScopes;
use App\Security\MemberScopes;
use App\Models\Foundation\Main\IGroup;
use App\Security\CompanyScopes;
use App\Security\SponsoredProjectScope;

/**
 * Class ApiEndpointsSeeder
 */
class ApiEndpointsSeeder extends Seeder
{

    public function run()
    {

        $this->seedSummitEndpoints();
        $this->seedAuditLogEndpoints();
        $this->seedMemberEndpoints();
        $this->seedTagsEndpoints();
        $this->seedCompaniesEndpoints();
        $this->seedSponsoredProjectsEndpoints();
        $this->seedGroupsEndpoints();
        $this->seedOrganizationsEndpoints();
        $this->seedTrackQuestionTemplateEndpoints();
        $this->seedRegistrationOrderEndpoints();
        $this->seedAttendeeTicketsEndpoints();
        $this->seedAttendeeBadgesEndpoints();
        $this->seedSummitAdministratorGroupsEndpoints();
        $this->seedSummitMediaFileTypeEndpoints();
        $this->seedElectionsEndpoints();
    }

    private function seedAttendeeBadgesEndpoints()
    {
        $current_realm = Config::get('app.scope_base_realm');

        $this->seedApiEndpoints('summits', [
            // admin
            [
                'name' => 'get-all-badges-by-summit',
                'route' => '/api/v1/summits/{id}/badges',
                'http_method' => 'GET',
                'scopes' => [
                    sprintf(SummitScopes::ReadAllSummitData, $current_realm),
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                    IGroup::SummitRegistrationAdmins
                ]
            ],
            [
                'name' => 'get-all-badges-by-summit-csv',
                'route' => '/api/v1/summits/{id}/badges/csv',
                'http_method' => 'GET',
                'scopes' => [
                    sprintf(SummitScopes::ReadAllSummitData, $current_realm),
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                    IGroup::SummitRegistrationAdmins
                ]
            ],
        ]);
    }

    private function seedAttendeeTicketsEndpoints()
    {
        $current_realm = Config::get('app.scope_base_realm');

        $this->seedApiEndpoints('summits', [
            // admin
            [
                'name' => 'get-all-tickets-by-summit',
                'route' => '/api/v1/summits/{id}/tickets',
                'http_method' => 'GET',
                'scopes' => [
                    sprintf(SummitScopes::ReadAllSummitData, $current_realm),
                    sprintf(SummitScopes::ReadRegistrationOrders, $current_realm),
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                    IGroup::SummitRegistrationAdmins,
                    IGroup::BadgePrinters,
                ]
            ],
            [
                'name' => 'get-all-external-tickets-by-summit',
                'route' => '/api/v1/summits/{id}/tickets/external',
                'http_method' => 'GET',
                'scopes' => [
                    sprintf(SummitScopes::ReadAllSummitData, $current_realm),
                    sprintf(SummitScopes::ReadRegistrationOrders, $current_realm),
                ],
                'authz_groups' => [
                    IGroup::BadgePrinters,
                ]
            ],
            [
                'name' => 'get-all-tickets-by-summit-csv',
                'route' => '/api/v1/summits/{id}/tickets/csv',
                'http_method' => 'GET',
                'scopes' => [
                    sprintf(SummitScopes::ReadAllSummitData, $current_realm),
                    sprintf(SummitScopes::ReadRegistrationOrders, $current_realm),
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                    IGroup::SummitRegistrationAdmins
                ]
            ],
            [
                'name' => 'import-ticket-data',
                'route' => '/api/v1/summits/{id}/tickets/csv',
                'http_method' => 'POST',
                'scopes' => [
                    sprintf(SummitScopes::WriteSummitData, $current_realm),
                    sprintf(SummitScopes::WriteRegistrationData, $current_realm),
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                    IGroup::SummitRegistrationAdmins
                ]
            ],
            [
                'name' => 'get-import-ticket-data-template',
                'route' => '/api/v1/summits/{id}/tickets/csv/template',
                'http_method' => 'GET',
                'scopes' => [
                    sprintf(SummitScopes::WriteSummitData, $current_realm),
                    sprintf(SummitScopes::WriteRegistrationData, $current_realm),
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                    IGroup::SummitRegistrationAdmins
                ]
            ],
            [
                'name' => 'ingest-external-ticket-data',
                'route' => '/api/v1/summits/{id}/tickets/ingest',
                'http_method' => 'POST',
                'scopes' => [
                    sprintf(SummitScopes::WriteSummitData, $current_realm),
                    sprintf(SummitScopes::WriteRegistrationData, $current_realm),
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                    IGroup::SummitRegistrationAdmins
                ]
            ],
            [
                'name' => 'get-ticket-by-id-or-number',
                'route' => '/api/v1/summits/{id}/tickets/{ticket_id}',
                'http_method' => 'GET',
                'scopes' => [
                    sprintf(SummitScopes::ReadAllSummitData, $current_realm),
                    sprintf(SummitScopes::ReadRegistrationOrders, $current_realm),
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                    IGroup::SummitRegistrationAdmins,
                    IGroup::BadgePrinters,
                    IGroup::SummitAccessControl
                ]
            ],
            [
                'name' => 'refund-ticket',
                'route' => '/api/v1/summits/{id}/tickets/{ticket_id}/refund',
                'http_method' => 'DELETE',
                'scopes' => [
                    sprintf(SummitScopes::WriteSummitData, $current_realm),
                    sprintf(SummitScopes::UpdateRegistrationOrders, $current_realm),
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                    IGroup::SummitRegistrationAdmins
                ]
            ],
            [
                'name' => 'get-order-approved-refunds',
                'route' => '/api/v1/summits/{id}/orders/{order_id}/tickets/all/refund-requests/approved',
                'http_method' => 'GET',
                'scopes' => [
                    sprintf(SummitScopes::ReadAllSummitData, $current_realm),
                    sprintf(SummitScopes::ReadRegistrationOrders, $current_realm),
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                    IGroup::SummitRegistrationAdmins
                ]
            ],
            [
                'name' => 'get-ticket-by-id-or-number-badge',
                'route' => '/api/v1/summits/{id}/tickets/{ticket_id}/badge',
                'http_method' => 'GET',
                'scopes' => [
                    sprintf(SummitScopes::ReadAllSummitData, $current_realm),
                    sprintf(SummitScopes::ReadRegistrationOrders, $current_realm),
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                    IGroup::SummitRegistrationAdmins
                ]
            ],
            [
                'name' => 'add-ticket-by-id-or-number-badge',
                'route' => '/api/v1/summits/{id}/tickets/{ticket_id}/badge',
                'http_method' => 'POST',
                'scopes' => [
                    sprintf(SummitScopes::WriteSummitData, $current_realm),
                    sprintf(SummitScopes::UpdateRegistrationOrdersBadges, $current_realm),
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                    IGroup::SummitRegistrationAdmins
                ]
            ],
            [
                'name' => 'update-ticket-badge-type',
                'route' => '/api/v1/summits/{id}/tickets/{ticket_id}/badge/current/type/{type_id}',
                'http_method' => 'PUT',
                'scopes' => [
                    sprintf(SummitScopes::WriteSummitData, $current_realm),
                    sprintf(SummitScopes::UpdateRegistrationOrdersBadges, $current_realm),
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                    IGroup::SummitRegistrationAdmins
                ]
            ],
            [
                'name' => 'add-ticket-badge-feature',
                'route' => '/api/v1/summits/{id}/tickets/{ticket_id}/badge/current/features/{feature_id}',
                'http_method' => 'PUT',
                'scopes' => [
                    sprintf(SummitScopes::WriteSummitData, $current_realm),
                    sprintf(SummitScopes::UpdateRegistrationOrdersBadges, $current_realm),
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                    IGroup::SummitRegistrationAdmins
                ]
            ],
            [
                'name' => 'remove-ticket-badge-feature',
                'route' => '/api/v1/summits/{id}/tickets/{ticket_id}/badge/current/features/{feature_id}',
                'http_method' => 'DELETE',
                'scopes' => [
                    sprintf(SummitScopes::WriteSummitData, $current_realm),
                    sprintf(SummitScopes::UpdateRegistrationOrdersBadges, $current_realm),
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                    IGroup::SummitRegistrationAdmins
                ]
            ],
            [
                'name' => 'print-ticket-badge',
                'route' => '/api/v1/summits/{id}/tickets/{ticket_id}/badge/current/{view_type}/print',
                'http_method' => 'PUT',
                'scopes' => [
                    sprintf(SummitScopes::WriteSummitData, $current_realm),
                    sprintf(SummitScopes::PrintRegistrationOrdersBadges, $current_realm),
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                    IGroup::SummitRegistrationAdmins,
                    IGroup::BadgePrinters,
                ]
            ],
            [
                'name' => 'can-print-ticket-badge',
                'route' => '/api/v1/summits/{id}/tickets/{ticket_id}/badge/current/{view_type}/print',
                'http_method' => 'GET',
                'scopes' => [
                    sprintf(SummitScopes::ReadAllSummitData, $current_realm),
                    sprintf(SummitScopes::PrintRegistrationOrdersBadges, $current_realm),
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                    IGroup::SummitRegistrationAdmins,
                    IGroup::BadgePrinters,
                ]
            ],
            [
                'name' => 'print-ticket-badge-default',
                'route' => '/api/v1/summits/{id}/tickets/{ticket_id}/badge/current/print',
                'http_method' => 'PUT',
                'scopes' => [
                    sprintf(SummitScopes::WriteSummitData, $current_realm),
                    sprintf(SummitScopes::PrintRegistrationOrdersBadges, $current_realm),
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                    IGroup::SummitRegistrationAdmins,
                    IGroup::BadgePrinters,
                ]
            ],
            [
                'name' => 'can-print-ticket-badge-default',
                'route' => '/api/v1/summits/{id}/tickets/{ticket_id}/badge/current/print',
                'http_method' => 'GET',
                'scopes' => [
                    sprintf(SummitScopes::ReadAllSummitData, $current_realm),
                    sprintf(SummitScopes::PrintRegistrationOrdersBadges, $current_realm),
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                    IGroup::SummitRegistrationAdmins,
                    IGroup::BadgePrinters,
                ]
            ],
            [
                'name' => 'get-badge-prints',
                'route' => '/api/v1/summits/{id}/tickets/{ticket_id}/badge/current/prints',
                'http_method' => 'GET',
                'scopes' => [
                    sprintf(SummitScopes::ReadAllSummitData, $current_realm),
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                    IGroup::SummitRegistrationAdmins,
                ]
            ],
            [
                'name' => 'delete-badge-prints',
                'route' => '/api/v1/summits/{id}/tickets/{ticket_id}/badge/current/prints',
                'http_method' => 'DELETE',
                'scopes' => [
                    sprintf(SummitScopes::WriteSummitData, $current_realm),
                    sprintf(SummitScopes::UpdateRegistrationOrders, $current_realm),
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                    IGroup::SummitRegistrationAdmins
                ]
            ],
            [
                'name' => 'get-badge-prints-csv',
                'route' => '/api/v1/summits/{id}/tickets/{ticket_id}/badge/current/prints/csv',
                'http_method' => 'GET',
                'scopes' => [
                    sprintf(SummitScopes::ReadAllSummitData, $current_realm),
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                    IGroup::SummitRegistrationAdmins,
                ]
            ],

            [
                'name' => 'delete-ticket-badge',
                'route' => '/api/v1/summits/{id}/tickets/{ticket_id}/badge/current',
                'http_method' => 'DELETE',
                'scopes' => [
                    sprintf(SummitScopes::WriteSummitData, $current_realm),
                    sprintf(SummitScopes::UpdateRegistrationOrders, $current_realm),
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                    IGroup::SummitRegistrationAdmins
                ]
            ],
        ]);
    }

    private function seedRegistrationOrderEndpoints()
    {

        $current_realm = Config::get('app.scope_base_realm');

        $this->seedApiEndpoints('summits', [
            // admin
            [
                'name' => 'get-all-orders-by-summit',
                'route' => '/api/v1/summits/{id}/orders',
                'http_method' => 'GET',
                'scopes' => [
                    sprintf(SummitScopes::ReadAllSummitData, $current_realm),
                    sprintf(SummitScopes::ReadRegistrationOrders, $current_realm),
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                    IGroup::SummitRegistrationAdmins
                ]
            ],
            [
                'name' => 'get-all-orders-by-summit-csv',
                'route' => '/api/v1/summits/{id}/orders/csv',
                'http_method' => 'GET',
                'scopes' => [
                    sprintf(SummitScopes::ReadAllSummitData, $current_realm),
                    sprintf(SummitScopes::ReadRegistrationOrders, $current_realm),
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                    IGroup::SummitRegistrationAdmins
                ]
            ],
            [
                'name' => 'create-single-ticket-registration-order',
                'route' => '/api/v1/summits/{id}/orders',
                'http_method' => 'POST',
                'scopes' => [
                    sprintf(SummitScopes::WriteSummitData, $current_realm),
                    sprintf(SummitScopes::CreateOfflineRegistrationOrders, $current_realm),
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                    IGroup::SummitRegistrationAdmins
                ]
            ],
            [
                'name' => 'get-registration-order',
                'route' => '/api/v1/summits/{id}/orders/{order_id}',
                'http_method' => 'GET',
                'scopes' => [
                    sprintf(SummitScopes::ReadAllSummitData, $current_realm),
                    sprintf(SummitScopes::ReadRegistrationOrders, $current_realm),
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                    IGroup::SummitRegistrationAdmins
                ]
            ],
            [
                'name' => 'delete-registration-order',
                'route' => '/api/v1/summits/{id}/orders/{order_id}',
                'http_method' => 'DELETE',
                'scopes' => [
                    sprintf(SummitScopes::WriteSummitData, $current_realm),
                    sprintf(SummitScopes::DeleteRegistrationOrders, $current_realm),
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                    IGroup::SummitRegistrationAdmins
                ]
            ],
            [
                'name' => 'update-registration-order',
                'route' => '/api/v1/summits/{id}/orders/{order_id}',
                'http_method' => 'PUT',
                'scopes' => [
                    sprintf(SummitScopes::WriteSummitData, $current_realm),
                    sprintf(SummitScopes::UpdateRegistrationOrders, $current_realm),
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                    IGroup::SummitRegistrationAdmins
                ]
            ],
            // purchase flow
            [
                'name' => 'reserve-registration-order',
                'route' => '/api/v1/summits/{id}/orders/reserve',
                'http_method' => 'POST',
                'scopes' => [
                    sprintf(SummitScopes::CreateRegistrationOrders, $current_realm)
                ],
            ],
            [
                'name' => 'checkout-registration-order',
                'route' => '/api/v1/summits/{id}/orders/{hash}/checkout',
                'http_method' => 'PUT',
                'scopes' => [
                    sprintf(SummitScopes::UpdateMyRegistrationOrders, $current_realm)
                ],
            ],
            [
                'name' => 'get-ticket-by-order-hash',
                'route' => '/api/v1/summits/{id}/orders/{hash}/tickets/mine',
                'http_method' => 'GET',
                'scopes' => [
                    sprintf(SummitScopes::ReadMyRegistrationOrders, $current_realm)
                ],
            ],
            [
                'name' => 'delete-my-registration-order',
                'route' => '/api/v1/summits/{id}/orders/{hash}',
                'http_method' => 'DELETE',
                'scopes' => [
                    sprintf(SummitScopes::DeleteMyRegistrationOrders, $current_realm)
                ],
            ],
            [
                'name' => 'get-all-my-registration-order',
                'route' => '/api/v1/summits/all/orders/me',
                'http_method' => 'GET',
                'scopes' => [
                    sprintf(SummitScopes::ReadMyRegistrationOrders, $current_realm),
                ],
            ],
            [
                'name' => 'get-all-my-registration-orders-by-summit',
                'route' => '/api/v1/summits/{id}/orders/me',
                'http_method' => 'GET',
                'scopes' => [
                    sprintf(SummitScopes::ReadMyRegistrationOrders, $current_realm),
                ],
            ],
            [
                'name' => 'get-all-my-tickets',
                'route' => '/api/v1/summits/all/orders/all/tickets/me',
                'http_method' => 'GET',
                'scopes' => [
                    sprintf(SummitScopes::ReadMyRegistrationOrders, $current_realm),
                ],
            ],
            [
                'name' => 'get-all-my-tickets-by-summit',
                'route' => '/api/v1/summits/{id}/orders/all/tickets/me',
                'http_method' => 'GET',
                'scopes' => [
                    sprintf(SummitScopes::ReadMyRegistrationOrders, $current_realm),
                ],
            ],
            [
                'name' => 'update-my-ticket',
                'route' => '/api/v1/summits/all/orders/all/tickets/{ticket_id}',
                'http_method' => 'PUT',
                'scopes' => [
                    sprintf(SummitScopes::UpdateMyRegistrationOrders, $current_realm),
                ],
            ],
            [
                'name' => 'get-my-ticket-pdf',
                'route' => '/api/v1/summits/all/orders/all/tickets/{ticket_id}/pdf',
                'http_method' => 'GET',
                'scopes' => [
                    sprintf(SummitScopes::ReadMyRegistrationOrders, $current_realm),
                ],
            ],
            [
                'name' => 'update-my-order',
                'route' => '/api/v1/summits/all/orders/{order_id}',
                'http_method' => 'PUT',
                'scopes' => [
                    sprintf(SummitScopes::UpdateMyRegistrationOrders, $current_realm),
                ],
            ],
            [
                'name' => 'get-my-order-by-id',
                'route' => '/api/v1/summits/all/orders/{order_id}',
                'http_method' => 'GET',
                'scopes' => [
                    sprintf(SummitScopes::ReadMyRegistrationOrders, $current_realm),
                ],
            ],
            [
                'name' => 'get-my-tickets-by-order-id',
                'route' => '/api/v1/summits/all/orders/{order_id}/tickets',
                'http_method' => 'GET',
                'scopes' => [
                    sprintf(SummitScopes::ReadMyRegistrationOrders, $current_realm),
                ],
            ],
            [
                'name' => 'resend-order',
                'route' => '/api/v1/summits/all/orders/{order_id}/resend',
                'http_method' => 'PUT',
                'scopes' => [
                    sprintf(SummitScopes::WriteSummitData, $current_realm),
                    sprintf(SummitScopes::UpdateRegistrationOrders, $current_realm),
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                    IGroup::SummitRegistrationAdmins
                ]
            ],
            [
                'name' => 'revoke-attendee-from-my-order',
                'route' => '/api/v1/summits/all/orders/{order_id}/tickets/{ticket_id}/attendee',
                'http_method' => 'DELETE',
                'scopes' => [
                    sprintf(SummitScopes::UpdateMyRegistrationOrders, $current_realm),
                ],
            ],
            [
                'name' => 'assign-attendee-from-my-order',
                'route' => '/api/v1/summits/all/orders/{order_id}/tickets/{ticket_id}/attendee',
                'http_method' => 'PUT',
                'scopes' => [
                    sprintf(SummitScopes::UpdateMyRegistrationOrders, $current_realm),
                ],
            ],
            [
                'name' => 'reinvite-attendee-from-order',
                'route' => '/api/v1/summits/all/orders/{order_id}/tickets/{ticket_id}/attendee/reinvite',
                'http_method' => 'PUT',
                'scopes' => [
                    sprintf(SummitScopes::UpdateMyRegistrationOrders, $current_realm),
                    sprintf(SummitScopes::WriteSummitData, $current_realm),
                    sprintf(SummitScopes::UpdateRegistrationOrders, $current_realm),
                ],
            ],
            [
                'name' => 'request-refund-ticket',
                'route' => '/api/v1/summits/all/orders/{order_id}/tickets/{ticket_id}/refund',
                'http_method' => 'DELETE',
                'scopes' => [
                    sprintf(SummitScopes::UpdateMyRegistrationOrders, $current_realm),
                ],
            ],
            [
                'name' => 'request-refund-order',
                'route' => '/api/v1/summits/all/orders/{order_id}/refund',
                'http_method' => 'DELETE',
                'scopes' => [
                    sprintf(SummitScopes::UpdateMyRegistrationOrders, $current_realm),
                ],
            ],
            [
                'name' => 'cancel-refund-ticket-request',
                'route' => '/api/v1/summits/all/orders/{order_id}/tickets/{ticket_id}/refund/cancel',
                'http_method' => 'DELETE',
                'scopes' => [
                    sprintf(SummitScopes::WriteSummitData, $current_realm),
                    sprintf(SummitScopes::UpdateRegistrationOrders, $current_realm),
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                    IGroup::SummitRegistrationAdmins
                ]
            ],
            [
                'name' => 'get-my-ticket-by-id',
                'route' => '/api/v1/summits/all/orders/{order_id}/tickets/{ticket_id}',
                'http_method' => 'GET',
                'scopes' => [
                    sprintf(SummitScopes::ReadMyRegistrationOrders, $current_realm),
                ],
            ],
            [
                'name' => 'get-my-ticket-pdf-by-order-id',
                'route' => '/api/v1/summits/all/orders/{order_id}/tickets/{ticket_id}/pdf',
                'http_method' => 'GET',
                'scopes' => [
                    sprintf(SummitScopes::ReadMyRegistrationOrders, $current_realm),
                ],
            ],
            [
                'name' => 'get-ticket-pdf-admin',
                'route' => '/api/v1/summits/{id}/orders/{order_id}/tickets/{ticket_id}/pdf',
                'http_method' => 'GET',
                'scopes' => [
                    sprintf(SummitScopes::ReadAllSummitData, $current_realm),
                    sprintf(SummitScopes::ReadRegistrationOrders, $current_realm),
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                    IGroup::SummitRegistrationAdmins
                ]
            ],
            [
                'name' => 'update-ticket',
                'route' => '/api/v1/summits/{id}/orders/{order_id}/tickets/{ticket_id}',
                'http_method' => 'PUT',
                'scopes' => [
                    sprintf(SummitScopes::WriteSummitData, $current_realm),
                    sprintf(SummitScopes::UpdateRegistrationOrders, $current_realm),
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                    IGroup::SummitRegistrationAdmins
                ]
            ],
            [
                'name' => 'activate-ticket',
                'route' => '/api/v1/summits/{id}/orders/{order_id}/tickets/{ticket_id}/activate',
                'http_method' => 'PUT',
                'scopes' => [
                    sprintf(SummitScopes::WriteSummitData, $current_realm),
                    sprintf(SummitScopes::UpdateRegistrationOrders, $current_realm),
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                    IGroup::SummitRegistrationAdmins
                ]
            ],
            [
                'name' => 'deactivate-ticket',
                'route' => '/api/v1/summits/{id}/orders/{order_id}/tickets/{ticket_id}/activate',
                'http_method' => 'DELETE',
                'scopes' => [
                    sprintf(SummitScopes::WriteSummitData, $current_realm),
                    sprintf(SummitScopes::UpdateRegistrationOrders, $current_realm),
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                    IGroup::SummitRegistrationAdmins
                ]
            ],
            [
                'name' => 'delegate-ticket',
                'route' => '/api/v1/summits/{id}/orders/{order_id}/tickets/{ticket_id}/delegate',
                'http_method' => 'PUT',
                'scopes' => [
                    sprintf(SummitScopes::UpdateMyRegistrationOrders, $current_realm),
                ]
            ],
            [
                'name' => 'add-ticket-2-order',
                'route' => '/api/v1/summits/{id}/orders/{order_id}/tickets',
                'http_method' => 'POST',
                'scopes' => [
                    sprintf(SummitScopes::WriteSummitData, $current_realm),
                    sprintf(SummitScopes::UpdateRegistrationOrders, $current_realm),
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                    IGroup::SummitRegistrationAdmins
                ]
            ],
            // registration invitations
            [
                'name' => 'ingest-registration-invitations',
                'route' => '/api/v1/summits/{id}/registration-invitations/csv',
                'http_method' => 'POST',
                'scopes' => [
                    sprintf(SummitScopes::WriteSummitData, $current_realm),
                    sprintf(SummitScopes::WriteRegistrationInvitations, $current_realm),
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                    IGroup::SummitRegistrationAdmins
                ]
            ],
            [
                'name' => 'add-registration-invitation',
                'route' => '/api/v1/summits/{id}/registration-invitations',
                'http_method' => 'POST',
                'scopes' => [
                    sprintf(SummitScopes::WriteSummitData, $current_realm),
                    sprintf(SummitScopes::WriteRegistrationInvitations, $current_realm),
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                    IGroup::SummitRegistrationAdmins
                ]
            ],
            [
                'name' => 'send-registration-invitations',
                'route' => '/api/v1/summits/{id}/registration-invitations/all/send',
                'http_method' => 'PUT',
                'scopes' => [
                    sprintf(SummitScopes::WriteSummitData, $current_realm),
                    sprintf(SummitScopes::WriteRegistrationInvitations, $current_realm),
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                    IGroup::SummitRegistrationAdmins
                ]
            ],
            [
                'name' => 'get-registration-invitations-csv',
                'route' => '/api/v1/summits/{id}/registration-invitations/csv',
                'http_method' => 'GET',
                'scopes' => [
                    sprintf(SummitScopes::ReadAllSummitData, $current_realm),
                    sprintf(SummitScopes::ReadRegistrationInvitations, $current_realm),
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                    IGroup::SummitRegistrationAdmins
                ]
            ],
            [
                'name' => 'get-registration-invitations',
                'route' => '/api/v1/summits/{id}/registration-invitations',
                'http_method' => 'GET',
                'scopes' => [
                    sprintf(SummitScopes::ReadAllSummitData, $current_realm),
                    sprintf(SummitScopes::ReadRegistrationInvitations, $current_realm),
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                    IGroup::SummitRegistrationAdmins
                ]
            ],
            [
                'name' => 'get-registration-my-invitation',
                'route' => '/api/v1/summits/{id}/registration-invitations/me',
                'http_method' => 'GET',
                'scopes' => [
                    sprintf(SummitScopes::ReadMyRegistrationInvitations, $current_realm),
                ],
            ],
            [
                'name' => 'get-registration-invitation-by-id',
                'route' => '/api/v1/summits/{id}/registration-invitations/{invitation_id}',
                'http_method' => 'GET',
                'scopes' => [
                    sprintf(SummitScopes::ReadAllSummitData, $current_realm),
                    sprintf(SummitScopes::ReadRegistrationInvitations, $current_realm),
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                    IGroup::SummitRegistrationAdmins
                ]
            ],
            [
                'name' => 'delete-registration-invitation-by-id',
                'route' => '/api/v1/summits/{id}/registration-invitations/{invitation_id}',
                'http_method' => 'DELETE',
                'scopes' => [
                    sprintf(SummitScopes::WriteSummitData, $current_realm),
                    sprintf(SummitScopes::WriteRegistrationInvitations, $current_realm),
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                    IGroup::SummitRegistrationAdmins
                ]
            ],
            [
                'name' => 'update-registration-invitation',
                'route' => '/api/v1/summits/{id}/registration-invitations/{invitation_id}',
                'http_method' => 'PUT',
                'scopes' => [
                    sprintf(SummitScopes::WriteSummitData, $current_realm),
                    sprintf(SummitScopes::WriteRegistrationInvitations, $current_realm),
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                    IGroup::SummitRegistrationAdmins
                ]
            ],
            [
                'name' => 'delete-registration-all',
                'route' => '/api/v1/summits/{id}/registration-invitations/all',
                'http_method' => 'DELETE',
                'scopes' => [
                    sprintf(SummitScopes::WriteSummitData, $current_realm),
                    sprintf(SummitScopes::WriteRegistrationInvitations, $current_realm),
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                    IGroup::SummitRegistrationAdmins
                ]
            ],
            [
                'name' => 'get-registration-invitation-by-token',
                'route' => '/api/v1/summits/all/registration-invitations/{token}',
                'http_method' => 'GET',
                'scopes' => [
                    sprintf(SummitScopes::ReadMyRegistrationInvitations, $current_realm),
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                    IGroup::SummitRegistrationAdmins
                ]
            ],
            // submission invitations
            [
                'name' => 'ingest-submission-invitations',
                'route' => '/api/v1/summits/{id}/submission-invitations/csv',
                'http_method' => 'POST',
                'scopes' => [
                    sprintf(SummitScopes::WriteSummitData, $current_realm),
                    sprintf(SummitScopes::WriteSubmissionInvitations, $current_realm),
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                    IGroup::SummitRegistrationAdmins
                ]
            ],
            [
                'name' => 'add-submission-invitation',
                'route' => '/api/v1/summits/{id}/submission-invitations',
                'http_method' => 'POST',
                'scopes' => [
                    sprintf(SummitScopes::WriteSummitData, $current_realm),
                    sprintf(SummitScopes::WriteSubmissionInvitations, $current_realm),
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                    IGroup::SummitRegistrationAdmins
                ]
            ],
            [
                'name' => 'send-submission-invitations',
                'route' => '/api/v1/summits/{id}/submission-invitations/all/send',
                'http_method' => 'PUT',
                'scopes' => [
                    sprintf(SummitScopes::WriteSummitData, $current_realm),
                    sprintf(SummitScopes::WriteSubmissionInvitations, $current_realm),
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                    IGroup::SummitRegistrationAdmins
                ]
            ],
            [
                'name' => 'get-submission-invitations-csv',
                'route' => '/api/v1/summits/{id}/submission-invitations/csv',
                'http_method' => 'GET',
                'scopes' => [
                    sprintf(SummitScopes::ReadAllSummitData, $current_realm),
                    sprintf(SummitScopes::ReadSubmissionInvitations, $current_realm),
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                    IGroup::SummitRegistrationAdmins
                ]
            ],
            [
                'name' => 'get-submission-invitations',
                'route' => '/api/v1/summits/{id}/submission-invitations',
                'http_method' => 'GET',
                'scopes' => [
                    sprintf(SummitScopes::ReadAllSummitData, $current_realm),
                    sprintf(SummitScopes::ReadSubmissionInvitations, $current_realm),
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                    IGroup::SummitRegistrationAdmins
                ]
            ],
            [
                'name' => 'get-submission-invitation-by-id',
                'route' => '/api/v1/summits/{id}/submission-invitations/{invitation_id}',
                'http_method' => 'GET',
                'scopes' => [
                    sprintf(SummitScopes::ReadAllSummitData, $current_realm),
                    sprintf(SummitScopes::ReadSubmissionInvitations, $current_realm),
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                    IGroup::SummitRegistrationAdmins
                ]
            ],
            [
                'name' => 'delete-submission-invitation-by-id',
                'route' => '/api/v1/summits/{id}/submission-invitations/{invitation_id}',
                'http_method' => 'DELETE',
                'scopes' => [
                    sprintf(SummitScopes::WriteSummitData, $current_realm),
                    sprintf(SummitScopes::WriteSubmissionInvitations, $current_realm),
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                    IGroup::SummitRegistrationAdmins
                ]
            ],
            [
                'name' => 'update-submission-invitation',
                'route' => '/api/v1/summits/{id}/submission-invitations/{invitation_id}',
                'http_method' => 'PUT',
                'scopes' => [
                    sprintf(SummitScopes::WriteSummitData, $current_realm),
                    sprintf(SummitScopes::WriteSubmissionInvitations, $current_realm),
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                    IGroup::SummitRegistrationAdmins
                ]
            ],
            [
                'name' => 'delete-submission-invitations-all',
                'route' => '/api/v1/summits/{id}/submission-invitations/all',
                'http_method' => 'DELETE',
                'scopes' => [
                    sprintf(SummitScopes::WriteSummitData, $current_realm),
                    sprintf(SummitScopes::WriteSubmissionInvitations, $current_realm),
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                    IGroup::SummitRegistrationAdmins
                ]
            ],

        ]);
    }

    private function seedSummitEndpoints()
    {
        $current_realm = Config::get('app.scope_base_realm');

        $this->seedApiEndpoints('summits', [
            // summits
            [
                'name' => 'get-summits',
                'route' => '/api/v1/summits',
                'http_method' => 'GET',
                'scopes' => [
                    sprintf(SummitScopes::ReadSummitData, $current_realm),
                    sprintf(SummitScopes::ReadAllSummitData, $current_realm),
                ],
            ],
            [
                'name' => 'get-summits-all',
                'route' => '/api/v1/summits/all',
                'http_method' => 'GET',
                'scopes' => [
                    sprintf(SummitScopes::ReadAllSummitData, $current_realm)
                ],
            ],
            [
                'name' => 'get-summits-all-by-id-slug',
                'route' => '/api/v1/summits/all/{id}',
                'http_method' => 'GET',
                'scopes' => [
                    sprintf(SummitScopes::ReadAllSummitData, $current_realm),
                ],
            ],
            [
                'name' => 'get-summits-all-by-id-slug-registration-stats',
                'route' => '/api/v1/summits/all/{id}/registration-stats',
                'http_method' => 'GET',
                'scopes' => [
                    sprintf(SummitScopes::ReadAllSummitData, $current_realm),
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                    IGroup::SummitRegistrationAdmins,
                ]
            ],
            [
                'name' => 'get-attendees-check-ins-over-time-stats',
                'route' => '/api/v1/summits/all/{id}/registration-stats/check-ins',
                'http_method' => 'GET',
                'scopes' => [
                    sprintf(SummitScopes::ReadAllSummitData, $current_realm),
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                    IGroup::SummitRegistrationAdmins,
                ]
            ],
            [
                'name' => 'get-attendees-purchased-tickets-over-time-stats',
                'route' => '/api/v1/summits/all/{id}/registration-stats/purchased-tickets',
                'http_method' => 'GET',
                'scopes' => [
                    sprintf(SummitScopes::ReadAllSummitData, $current_realm),
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                    IGroup::SummitRegistrationAdmins,
                ]
            ],

            [
                'name' => 'get-summit-cached',
                'route' => '/api/v1/summits/{id}',
                'http_method' => 'GET',
                'scopes' => [
                    sprintf(SummitScopes::ReadSummitData, $current_realm),
                    sprintf(SummitScopes::ReadAllSummitData, $current_realm)
                ],
            ],
            [
                'name' => 'get-summit-non-cached',
                'route' => '/api/v2/summits/{id}',
                'http_method' => 'GET',
                'scopes' => [
                    sprintf(SummitScopes::ReadSummitData, $current_realm),
                    sprintf(SummitScopes::ReadAllSummitData, $current_realm)
                ],
            ],
            [
                'name' => 'add-summit',
                'route' => '/api/v1/summits',
                'http_method' => 'POST',
                'scopes' => [
                    sprintf(SummitScopes::WriteSummitData, $current_realm),
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                ]
            ],
            [
                'name' => 'update-summit',
                'route' => '/api/v1/summits/{id}',
                'http_method' => 'PUT',
                'scopes' => [
                    sprintf(SummitScopes::WriteSummitData, $current_realm),
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                ]
            ],
            [
                'name' => 'add-summit-logo',
                'route' => '/api/v1/summits/{id}/logo',
                'http_method' => 'POST',
                'scopes' => [
                    sprintf(SummitScopes::WriteSummitData, $current_realm),
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                ]
            ],
            [
                'name' => 'delete-summit-logo',
                'route' => '/api/v1/summits/{id}/logo',
                'http_method' => 'DELETE',
                'scopes' => [
                    sprintf(SummitScopes::WriteSummitData, $current_realm),
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                ]
            ],
            [
                'name' => 'add-summit-logo-secondary',
                'route' => '/api/v1/summits/{id}/logo/secondary',
                'http_method' => 'POST',
                'scopes' => [
                    sprintf(SummitScopes::WriteSummitData, $current_realm),
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                ]
            ],
            [
                'name' => 'delete-summit-logo-secondary',
                'route' => '/api/v1/summits/{id}/logo/secondary',
                'http_method' => 'DELETE',
                'scopes' => [
                    sprintf(SummitScopes::WriteSummitData, $current_realm),
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                ]
            ],
            [
                'name' => 'delete-summit',
                'route' => '/api/v1/summits/{id}',
                'http_method' => 'DELETE',
                'scopes' => [
                    sprintf(SummitScopes::WriteSummitData, $current_realm),
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                ]
            ],
            // schedule settings
            [
                'name' => 'get-schedule-settings',
                'route' => '/api/v1/summits/{id}/schedule-settings',
                'http_method' => 'GET',
                'scopes' => [
                    sprintf(SummitScopes::ReadSummitData, $current_realm),
                    sprintf(SummitScopes::ReadAllSummitData, $current_realm)
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                ]
            ],
            [
                'name' => 'add-schedule-setting',
                'route' => '/api/v1/summits/{id}/schedule-settings',
                'http_method' => 'POST',
                'scopes' => [
                    sprintf(SummitScopes::WriteSummitData, $current_realm),
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                ]
            ],
            [
                'name' => 'seed-schedule-settings',
                'route' => '/api/v1/summits/{id}/schedule-settings/seed',
                'http_method' => 'POST',
                'scopes' => [
                    sprintf(SummitScopes::WriteSummitData, $current_realm),
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                ]
            ],
            [
                'name' => 'update-schedule-setting',
                'route' => '/api/v1/summits/{id}/schedule-settings/{config_id}',
                'http_method' => 'PUT',
                'scopes' => [
                    sprintf(SummitScopes::WriteSummitData, $current_realm),
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                ]
            ],
            [
                'name' => 'delete-schedule-setting',
                'route' => '/api/v1/summits/{id}/schedule-settings/{config_id}',
                'http_method' => 'DELETE',
                'scopes' => [
                    sprintf(SummitScopes::WriteSummitData, $current_realm),
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                ]
            ],
            [
                'name' => 'get-schedule-setting',
                'route' => '/api/v1/summits/{id}/schedule-settings/{config_id}',
                'http_method' => 'GET',
                'scopes' => [
                    sprintf(SummitScopes::ReadSummitData, $current_realm),
                    sprintf(SummitScopes::ReadAllSummitData, $current_realm)
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                ]
            ],
            [
                'name' => 'add-schedule-setting-filter',
                'route' => '/api/v1/summits/{id}/schedule-settings/{config_id}/filters',
                'http_method' => 'POST',
                'scopes' => [
                    sprintf(SummitScopes::WriteSummitData, $current_realm),
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                ]
            ],
            [
                'name' => 'update-schedule-setting-filter',
                'route' => '/api/v1/summits/{id}/schedule-settings/{config_id}/filters/{filter_id}',
                'http_method' => 'PUT',
                'scopes' => [
                    sprintf(SummitScopes::WriteSummitData, $current_realm),
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                ]
            ],
            // bookable rooms attributes types
            [
                'name' => 'get-summit-bookable-room-attribute-types',
                'route' => '/api/v1/summits/{id}/bookable-room-attribute-types',
                'http_method' => 'GET',
                'scopes' => [
                    sprintf(SummitScopes::ReadSummitData, $current_realm),
                    sprintf(SummitScopes::ReadAllSummitData, $current_realm)
                ],
            ],
            [
                'name' => 'get-summit-bookable-room-attribute-types-by-id',
                'route' => '/api/v1/summits/{id}/bookable-room-attribute-types/{type_id}',
                'http_method' => 'GET',
                'scopes' => [
                    sprintf(SummitScopes::ReadSummitData, $current_realm),
                    sprintf(SummitScopes::ReadAllSummitData, $current_realm)
                ],
            ],
            [
                'name' => 'update-summit-bookable-room-attribute-type',
                'route' => '/api/v1/summits/{id}/bookable-room-attribute-types/{type_id}',
                'http_method' => 'PUT',
                'scopes' => [
                    sprintf(SummitScopes::WriteSummitData, $current_realm),
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                ]
            ],
            [
                'name' => 'delete-summit-bookable-room-attribute-type',
                'route' => '/api/v1/summits/{id}/bookable-room-attribute-types/{type_id}',
                'http_method' => 'DELETE',
                'scopes' => [
                    sprintf(SummitScopes::WriteSummitData, $current_realm),
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                ]
            ],
            [
                'name' => 'add-summit-bookable-room-attribute-type',
                'route' => '/api/v1/summits/{id}/bookable-room-attribute-types',
                'http_method' => 'POST',
                'scopes' => [
                    sprintf(SummitScopes::WriteSummitData, $current_realm),
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                ]
            ],
            [
                'name' => 'get-summit-bookable-room-attribute-type-values',
                'route' => '/api/v1/summits/{id}/bookable-room-attribute-types/{type_id}/values',
                'http_method' => 'GET',
                'scopes' => [
                    sprintf(SummitScopes::ReadSummitData, $current_realm),
                    sprintf(SummitScopes::ReadAllSummitData, $current_realm)
                ],
            ],
            [
                'name' => 'add-summit-bookable-room-attribute-type-values',
                'route' => '/api/v1/summits/{id}/bookable-room-attribute-types/{type_id}/values',
                'http_method' => 'POST',
                'scopes' => [
                    sprintf(SummitScopes::WriteSummitData, $current_realm),
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                ]
            ],
            [
                'name' => 'get-summit-bookable-room-attribute-type-value',
                'route' => '/api/v1/summits/{id}/bookable-room-attribute-types/{type_id}/values/{value_id}',
                'http_method' => 'GET',
                'scopes' => [
                    sprintf(SummitScopes::ReadSummitData, $current_realm),
                    sprintf(SummitScopes::ReadAllSummitData, $current_realm)
                ],
            ],
            [
                'name' => 'update-summit-bookable-room-attribute-type-value',
                'route' => '/api/v1/summits/{id}/bookable-room-attribute-types/{type_id}/values/{value_id}',
                'http_method' => 'PUT',
                'scopes' => [
                    sprintf(SummitScopes::WriteSummitData, $current_realm),
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                ]
            ],
            [
                'name' => 'delete-summit-bookable-room-attribute-type-value',
                'route' => '/api/v1/summits/{id}/bookable-room-attribute-types/{type_id}/values/{value_id}',
                'http_method' => 'DELETE',
                'scopes' => [
                    sprintf(SummitScopes::WriteSummitData, $current_realm),
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                ]
            ],
            // access level types
            [
                'name' => 'get-access-level-types',
                'route' => '/api/v1/summits/{id}/access-level-types',
                'http_method' => 'GET',
                'scopes' => [
                    sprintf(SummitScopes::ReadSummitData, $current_realm),
                    sprintf(SummitScopes::ReadAllSummitData, $current_realm)
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                    IGroup::SummitRegistrationAdmins,
                ]
            ],
            [
                'name' => 'add-access-level-types',
                'route' => '/api/v1/summits/{id}/access-level-types',
                'http_method' => 'POST',
                'scopes' => [
                    sprintf(SummitScopes::WriteSummitData, $current_realm),
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                    IGroup::SummitRegistrationAdmins,
                ]
            ],
            [
                'name' => 'get-access-level-type',
                'route' => '/api/v1/summits/{id}/access-level-types/{level_id}',
                'http_method' => 'GET',
                'scopes' => [
                    sprintf(SummitScopes::ReadSummitData, $current_realm),
                    sprintf(SummitScopes::ReadAllSummitData, $current_realm)
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                    IGroup::SummitRegistrationAdmins,
                ]
            ],

            [
                'name' => 'update-access-level-type',
                'route' => '/api/v1/summits/{id}/access-level-types/{level_id}',
                'http_method' => 'PUT',
                'scopes' => [
                    sprintf(SummitScopes::WriteSummitData, $current_realm),
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                    IGroup::SummitRegistrationAdmins,
                ]
            ],
            [
                'name' => 'delete-access-level-type',
                'route' => '/api/v1/summits/{id}/access-level-types/{level_id}',
                'http_method' => 'DELETE',
                'scopes' => [
                    sprintf(SummitScopes::WriteSummitData, $current_realm),
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                    IGroup::SummitRegistrationAdmins,
                ]
            ],
            // payment gateway profiles
            [
                'name' => 'get-payment-gateway-profiles',
                'route' => '/api/v1/summits/{id}/payment-gateway-profiles',
                'http_method' => 'GET',
                'scopes' => [
                    sprintf(SummitScopes::ReadAllSummitData, $current_realm),
                    sprintf(SummitScopes::ReadPaymentProfiles, $current_realm)
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                    IGroup::SummitRegistrationAdmins,
                ]
            ],
            [
                'name' => 'add-payment-gateway-profile',
                'route' => '/api/v1/summits/{id}/payment-gateway-profiles',
                'http_method' => 'POST',
                'scopes' => [
                    sprintf(SummitScopes::WriteSummitData, $current_realm),
                    sprintf(SummitScopes::WritePaymentProfiles, $current_realm),
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                    IGroup::SummitRegistrationAdmins,
                ]
            ],
            [
                'name' => 'get-payment-gateway-profile',
                'route' => '/api/v1/summits/{id}/payment-gateway-profiles/{payment_profile_id}',
                'http_method' => 'GET',
                'scopes' => [
                    sprintf(SummitScopes::ReadAllSummitData, $current_realm),
                    sprintf(SummitScopes::ReadPaymentProfiles, $current_realm)
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                    IGroup::SummitRegistrationAdmins,
                ]
            ],
            [
                'name' => 'update-payment-gateway-profile',
                'route' => '/api/v1/summits/{id}/payment-gateway-profiles/{payment_profile_id}',
                'http_method' => 'PUT',
                'scopes' => [
                    sprintf(SummitScopes::WriteSummitData, $current_realm),
                    sprintf(SummitScopes::WritePaymentProfiles, $current_realm),
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                    IGroup::SummitRegistrationAdmins,
                ]
            ],
            [
                'name' => 'delete-payment-gateway-profile',
                'route' => '/api/v1/summits/{id}/payment-gateway-profiles/{payment_profile_id}',
                'http_method' => 'DELETE',
                'scopes' => [
                    sprintf(SummitScopes::WriteSummitData, $current_realm),
                    sprintf(SummitScopes::WritePaymentProfiles, $current_realm),
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                    IGroup::SummitRegistrationAdmins,
                ]
            ],
            // tax types
            [
                'name' => 'get-tax-types',
                'route' => '/api/v1/summits/{id}/tax-types',
                'http_method' => 'GET',
                'scopes' => [
                    sprintf(SummitScopes::ReadSummitData, $current_realm),
                    sprintf(SummitScopes::ReadAllSummitData, $current_realm)
                ],
            ],
            [
                'name' => 'add-tax-types',
                'route' => '/api/v1/summits/{id}/tax-types',
                'http_method' => 'POST',
                'scopes' => [
                    sprintf(SummitScopes::WriteSummitData, $current_realm),
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                    IGroup::SummitRegistrationAdmins,
                ]
            ],
            [
                'name' => 'get-tax-type',
                'route' => '/api/v1/summits/{id}/tax-types/{tax_id}',
                'http_method' => 'GET',
                'scopes' => [
                    sprintf(SummitScopes::ReadSummitData, $current_realm),
                    sprintf(SummitScopes::ReadAllSummitData, $current_realm)
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                    IGroup::SummitRegistrationAdmins,
                ]
            ],
            [
                'name' => 'update-tax-type',
                'route' => '/api/v1/summits/{id}/tax-types/{tax_id}',
                'http_method' => 'PUT',
                'scopes' => [
                    sprintf(SummitScopes::WriteSummitData, $current_realm),
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                    IGroup::SummitRegistrationAdmins,
                ]
            ],
            [
                'name' => 'delete-tax-type',
                'route' => '/api/v1/summits/{id}/tax-types/{tax_id}',
                'http_method' => 'DELETE',
                'scopes' => [
                    sprintf(SummitScopes::WriteSummitData, $current_realm),
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                    IGroup::SummitRegistrationAdmins,
                ]
            ],
            [
                'name' => 'add-tax-type-2-ticket-type',
                'route' => '/api/v1/summits/{id}/tax-types/{tax_id}/ticket-types/{ticket_type_id}',
                'http_method' => 'PUT',
                'scopes' => [
                    sprintf(SummitScopes::WriteSummitData, $current_realm),
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                    IGroup::SummitRegistrationAdmins,
                ]
            ],
            [
                'name' => 'remove-tax-type-from-ticket-type',
                'route' => '/api/v1/summits/{id}/tax-types/{tax_id}/ticket-types/{ticket_type_id}',
                'http_method' => 'DELETE',
                'scopes' => [
                    sprintf(SummitScopes::WriteSummitData, $current_realm),
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                    IGroup::SummitRegistrationAdmins,
                ]
            ],
            // features types
            [
                'name' => 'get-feature-types',
                'route' => '/api/v1/summits/{id}/badge-feature-types',
                'http_method' => 'GET',
                'scopes' => [
                    sprintf(SummitScopes::ReadSummitData, $current_realm),
                    sprintf(SummitScopes::ReadAllSummitData, $current_realm)
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                    IGroup::SummitRegistrationAdmins,
                ]
            ],
            [
                'name' => 'add-feature-type',
                'route' => '/api/v1/summits/{id}/badge-feature-types',
                'http_method' => 'POST',
                'scopes' => [
                    sprintf(SummitScopes::WriteSummitData, $current_realm),
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                    IGroup::SummitRegistrationAdmins,
                ]
            ],
            [
                'name' => 'get-feature-type',
                'route' => '/api/v1/summits/{id}/badge-feature-types/{feature_id}',
                'http_method' => 'GET',
                'scopes' => [
                    sprintf(SummitScopes::ReadSummitData, $current_realm),
                    sprintf(SummitScopes::ReadAllSummitData, $current_realm)
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                    IGroup::SummitRegistrationAdmins,
                ]
            ],
            [
                'name' => 'update-feature-type',
                'route' => '/api/v1/summits/{id}/badge-feature-types/{feature_id}',
                'http_method' => 'PUT',
                'scopes' => [
                    sprintf(SummitScopes::WriteSummitData, $current_realm),
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                    IGroup::SummitRegistrationAdmins,
                ]
            ],
            [
                'name' => 'delete-feature-type',
                'route' => '/api/v1/summits/{id}/badge-feature-types/{feature_id}',
                'http_method' => 'DELETE',
                'scopes' => [
                    sprintf(SummitScopes::WriteSummitData, $current_realm),
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                    IGroup::SummitRegistrationAdmins,
                ]
            ],
            [
                'name' => 'add-feature-type-image',
                'route' => '/api/v1/summits/{id}/badge-feature-types/{feature_id}/image',
                'http_method' => 'POST',
                'scopes' => [
                    sprintf(SummitScopes::WriteSummitData, $current_realm),
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                    IGroup::SummitRegistrationAdmins,
                ]
            ],
            [
                'name' => 'delete-feature-type-image',
                'route' => '/api/v1/summits/{id}/badge-feature-types/{feature_id}/image',
                'http_method' => 'DELETE',
                'scopes' => [
                    sprintf(SummitScopes::WriteSummitData, $current_realm),
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                    IGroup::SummitRegistrationAdmins,
                ]
            ],
            // refund-policies
            [
                'name' => 'get-refund-policies',
                'route' => '/api/v1/summits/{id}/refund-policies',
                'http_method' => 'GET',
                'scopes' => [
                    sprintf(SummitScopes::ReadSummitData, $current_realm),
                    sprintf(SummitScopes::ReadAllSummitData, $current_realm)
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                    IGroup::SummitRegistrationAdmins,
                ]
            ],
            [
                'name' => 'add-refund-policy',
                'route' => '/api/v1/summits/{id}/refund-policies',
                'http_method' => 'POST',
                'scopes' => [
                    sprintf(SummitScopes::WriteSummitData, $current_realm),
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                    IGroup::SummitRegistrationAdmins,
                ]
            ],
            [
                'name' => 'get-refund-policy',
                'route' => '/api/v1/summits/{id}/refund-policies/{policy_id}',
                'http_method' => 'GET',
                'scopes' => [
                    sprintf(SummitScopes::ReadSummitData, $current_realm),
                    sprintf(SummitScopes::ReadAllSummitData, $current_realm)
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                    IGroup::SummitRegistrationAdmins,
                ]
            ],
            [
                'name' => 'update-refund-policy',
                'route' => '/api/v1/summits/{id}/refund-policies/{policy_id}',
                'http_method' => 'PUT',
                'scopes' => [
                    sprintf(SummitScopes::WriteSummitData, $current_realm),
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                    IGroup::SummitRegistrationAdmins,
                ]
            ],
            [
                'name' => 'delete-refund-policy',
                'route' => '/api/v1/summits/{id}/refund-policies/{policy_id}',
                'http_method' => 'DELETE',
                'scopes' => [
                    sprintf(SummitScopes::WriteSummitData, $current_realm),
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                    IGroup::SummitRegistrationAdmins,
                ]
            ],
            // sponsorship-types
            [
                'name' => 'get-summit-sponsorship-types',
                'route' => '/api/v1/summits/{id}/sponsorships-types',
                'http_method' => 'GET',
                'scopes' => [
                    sprintf(SummitScopes::ReadSummitData, $current_realm),
                    sprintf(SummitScopes::ReadAllSummitData, $current_realm)
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                ]
            ],
            [
                'name' => 'add-summit-sponsorship-type',
                'route' => '/api/v1/summits/{id}/sponsorships-types',
                'http_method' => 'POST',
                'scopes' => [
                    sprintf(SummitScopes::WriteSummitData, $current_realm),
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                ]
            ],
            [
                'name' => 'get-summit-sponsorship-type',
                'route' => '/api/v1/summits/{id}/sponsorships-types/{type_id}',
                'http_method' => 'GET',
                'scopes' => [
                    sprintf(SummitScopes::ReadSummitData, $current_realm),
                    sprintf(SummitScopes::ReadAllSummitData, $current_realm)
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                ]
            ],
            [
                'name' => 'update-summit-sponsorship-type',
                'route' => '/api/v1/summits/{id}/sponsorships-types/{type_id}',
                'http_method' => 'PUT',
                'scopes' => [
                    sprintf(SummitScopes::WriteSummitData, $current_realm),
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                ]
            ],
            [
                'name' => 'delete-summit-sponsorship-type',
                'route' => '/api/v1/summits/{id}/sponsorships-types/{type_id}',
                'http_method' => 'DELETE',
                'scopes' => [
                    sprintf(SummitScopes::WriteSummitData, $current_realm),
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                ]
            ],
            [
                'name' => 'add-summit-sponsorship-type-badge-image',
                'route' => '/api/v1/summits/{id}/sponsorships-types/{type_id}/badge-image',
                'http_method' => 'POST',
                'scopes' => [
                    sprintf(SummitScopes::WriteSummitData, $current_realm),
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                ]
            ],
            [
                'name' => 'delete-summit-sponsorship-type-badge-image',
                'route' => '/api/v1/summits/{id}/sponsorships-types/{type_id}/badge-image',
                'http_method' => 'DELETE',
                'scopes' => [
                    sprintf(SummitScopes::WriteSummitData, $current_realm),
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                ]
            ],
            // sponsors
            [
                'name' => 'get-sponsors',
                'route' => '/api/v1/summits/{id}/sponsors',
                'http_method' => 'GET',
                'scopes' => [
                    sprintf(SummitScopes::ReadSummitData, $current_realm),
                    sprintf(SummitScopes::ReadAllSummitData, $current_realm)
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                    IGroup::Sponsors,
                    IGroup::SponsorExternalUsers,
                ]
            ],
            [
                'name' => 'add-sponsor',
                'route' => '/api/v1/summits/{id}/sponsors',
                'http_method' => 'POST',
                'scopes' => [
                    sprintf(SummitScopes::WriteSummitData, $current_realm),
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                ]
            ],
            [
                'name' => 'get-sponsor',
                'route' => '/api/v1/summits/{id}/sponsors/{sponsor_id}',
                'http_method' => 'GET',
                'scopes' => [
                    sprintf(SummitScopes::ReadSummitData, $current_realm),
                    sprintf(SummitScopes::ReadAllSummitData, $current_realm)
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                    IGroup::Sponsors,
                    IGroup::SponsorExternalUsers,
                ]
            ],
            [
                'name' => 'update-sponsor',
                'route' => '/api/v1/summits/{id}/sponsors/{sponsor_id}',
                'http_method' => 'PUT',
                'scopes' => [
                    sprintf(SummitScopes::WriteSummitData, $current_realm),
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                ]
            ],
            [
                'name' => 'delete-sponsor',
                'route' => '/api/v1/summits/{id}/sponsors/{sponsor_id}',
                'http_method' => 'DELETE',
                'scopes' => [
                    sprintf(SummitScopes::WriteSummitData, $current_realm),
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                ]
            ],
            [
                'name' => 'add-sponsor-header-image',
                'route' => '/api/v1/summits/{id}/sponsors/{sponsor_id}/header-image',
                'http_method' => 'POST',
                'scopes' => [
                    sprintf(SummitScopes::WriteSummitData, $current_realm),
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                ]
            ],
            [
                'name' => 'delete-sponsor-header-image',
                'route' => '/api/v1/summits/{id}/sponsors/{sponsor_id}/header-image',
                'http_method' => 'DELETE',
                'scopes' => [
                    sprintf(SummitScopes::WriteSummitData, $current_realm),
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                ]
            ],
            [
                'name' => 'add-sponsor-header-image-mobile',
                'route' => '/api/v1/summits/{id}/sponsors/{sponsor_id}/header-image/mobile',
                'http_method' => 'POST',
                'scopes' => [
                    sprintf(SummitScopes::WriteSummitData, $current_realm),
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                ]
            ],
            [
                'name' => 'delete-sponsor-header-image-mobile',
                'route' => '/api/v1/summits/{id}/sponsors/{sponsor_id}/header-image/mobile',
                'http_method' => 'DELETE',
                'scopes' => [
                    sprintf(SummitScopes::WriteSummitData, $current_realm),
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                ]
            ],
            [
                'name' => 'add-sponsor-carousel-advertise-image',
                'route' => '/api/v1/summits/{id}/sponsors/{sponsor_id}/carousel-advertise-image',
                'http_method' => 'POST',
                'scopes' => [
                    sprintf(SummitScopes::WriteSummitData, $current_realm),
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                ]
            ],
            [
                'name' => 'delete-sponsor-carousel-advertise-image',
                'route' => '/api/v1/summits/{id}/sponsors/{sponsor_id}/carousel-advertise-image',
                'http_method' => 'DELETE',
                'scopes' => [
                    sprintf(SummitScopes::WriteSummitData, $current_realm),
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                ]
            ],
            [
                'name' => 'add-sponsor-side-image',
                'route' => '/api/v1/summits/{id}/sponsors/{sponsor_id}/side-image',
                'http_method' => 'POST',
                'scopes' => [
                    sprintf(SummitScopes::WriteSummitData, $current_realm),
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                ]
            ],
            [
                'name' => 'delete-sponsor-side-image',
                'route' => '/api/v1/summits/{id}/sponsors/{sponsor_id}/side-image',
                'http_method' => 'DELETE',
                'scopes' => [
                    sprintf(SummitScopes::WriteSummitData, $current_realm),
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                ]
            ],
            // ads
            [
                'name' => 'get-sponsor-ads',
                'route' => '/api/v1/summits/{id}/sponsors/{sponsor_id}/ads',
                'http_method' => 'GET',
                'scopes' => [
                    sprintf(SummitScopes::ReadSummitData, $current_realm),
                    sprintf(SummitScopes::ReadAllSummitData, $current_realm)
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                    IGroup::Sponsors,
                    IGroup::SponsorExternalUsers,
                ]
            ],
            [
                'name' => 'add-sponsor-ad',
                'route' => '/api/v1/summits/{id}/sponsors/{sponsor_id}/ads',
                'http_method' => 'POST',
                'scopes' => [
                    sprintf(SummitScopes::WriteSummitData, $current_realm),
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                ]
            ],
            [
                'name' => 'get-sponsor-ad',
                'route' => '/api/v1/summits/{id}/sponsors/{sponsor_id}/ads/{ad_id}',
                'http_method' => 'GET',
                'scopes' => [
                    sprintf(SummitScopes::ReadSummitData, $current_realm),
                    sprintf(SummitScopes::ReadAllSummitData, $current_realm)
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                ]
            ],
            [
                'name' => 'update-sponsor-ad',
                'route' => '/api/v1/summits/{id}/sponsors/{sponsor_id}/ads/{ad_id}',
                'http_method' => 'PUT',
                'scopes' => [
                    sprintf(SummitScopes::WriteSummitData, $current_realm),
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                ]
            ],
            [
                'name' => 'delete-sponsor-ad',
                'route' => '/api/v1/summits/{id}/sponsors/{sponsor_id}/ads/{ad_id}',
                'http_method' => 'DELETE',
                'scopes' => [
                    sprintf(SummitScopes::WriteSummitData, $current_realm),
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                ]
            ],
            [
                'name' => 'add-sponsor-ad-image',
                'route' => '/api/v1/summits/{id}/sponsors/{sponsor_id}/ads/{ad_id}/image',
                'http_method' => 'POST',
                'scopes' => [
                    sprintf(SummitScopes::WriteSummitData, $current_realm),
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                ]
            ],
            [
                'name' => 'delete-sponsor-ad-image',
                'route' => '/api/v1/summits/{id}/sponsors/{sponsor_id}/ads/{ad_id}/image',
                'http_method' => 'DELETE',
                'scopes' => [
                    sprintf(SummitScopes::WriteSummitData, $current_realm),
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                ]
            ],
            // materials
            [
                'name' => 'get-sponsor-materials',
                'route' => '/api/v1/summits/{id}/sponsors/{sponsor_id}/materials',
                'http_method' => 'GET',
                'scopes' => [
                    sprintf(SummitScopes::ReadSummitData, $current_realm),
                    sprintf(SummitScopes::ReadAllSummitData, $current_realm)
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                    IGroup::Sponsors,
                ]
            ],
            [
                'name' => 'add-sponsor-material',
                'route' => '/api/v1/summits/{id}/sponsors/{sponsor_id}/materials',
                'http_method' => 'POST',
                'scopes' => [
                    sprintf(SummitScopes::WriteSummitData, $current_realm),
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                ]
            ],
            [
                'name' => 'get-sponsor-material',
                'route' => '/api/v1/summits/{id}/sponsors/{sponsor_id}/materials/{material_id}',
                'http_method' => 'GET',
                'scopes' => [
                    sprintf(SummitScopes::ReadSummitData, $current_realm),
                    sprintf(SummitScopes::ReadAllSummitData, $current_realm)
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                ]
            ],
            [
                'name' => 'update-sponsor-material',
                'route' => '/api/v1/summits/{id}/sponsors/{sponsor_id}/materials/{material_id}',
                'http_method' => 'PUT',
                'scopes' => [
                    sprintf(SummitScopes::WriteSummitData, $current_realm),
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                ]
            ],
            [
                'name' => 'delete-sponsor-material',
                'route' => '/api/v1/summits/{id}/sponsors/{sponsor_id}/materials/{material_id}',
                'http_method' => 'DELETE',
                'scopes' => [
                    sprintf(SummitScopes::WriteSummitData, $current_realm),
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                ]
            ],
            // social networks
            [
                'name' => 'get-sponsor-social-networks',
                'route' => '/api/v1/summits/{id}/sponsors/{sponsor_id}/social-networks',
                'http_method' => 'GET',
                'scopes' => [
                    sprintf(SummitScopes::ReadSummitData, $current_realm),
                    sprintf(SummitScopes::ReadAllSummitData, $current_realm)
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                    IGroup::Sponsors,
                ]
            ],
            [
                'name' => 'add-sponsor-social-network',
                'route' => '/api/v1/summits/{id}/sponsors/{sponsor_id}/social-networks',
                'http_method' => 'POST',
                'scopes' => [
                    sprintf(SummitScopes::WriteSummitData, $current_realm),
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                ]
            ],
            [
                'name' => 'get-sponsor-social-network',
                'route' => '/api/v1/summits/{id}/sponsors/{sponsor_id}/social-networks/{social_network_id}',
                'http_method' => 'GET',
                'scopes' => [
                    sprintf(SummitScopes::ReadSummitData, $current_realm),
                    sprintf(SummitScopes::ReadAllSummitData, $current_realm)
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                ]
            ],
            [
                'name' => 'update-sponsor-social-network',
                'route' => '/api/v1/summits/{id}/sponsors/{sponsor_id}/social-networks/{social_network_id}',
                'http_method' => 'PUT',
                'scopes' => [
                    sprintf(SummitScopes::WriteSummitData, $current_realm),
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                ]
            ],
            [
                'name' => 'delete-sponsor-social-network',
                'route' => '/api/v1/summits/{id}/sponsors/{sponsor_id}/social-networks/{social_network_id}',
                'http_method' => 'DELETE',
                'scopes' => [
                    sprintf(SummitScopes::WriteSummitData, $current_realm),
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                ]
            ],
            // extra questions
            [
                'name' => 'get-sponsor-extra-questions',
                'route' => '/api/v1/summits/{id}/sponsors/{sponsor_id}/extra-questions',
                'http_method' => 'GET',
                'scopes' => [
                    sprintf(SummitScopes::ReadSummitData, $current_realm),
                    sprintf(SummitScopes::ReadAllSummitData, $current_realm)
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                    IGroup::Sponsors,
                ]
            ],
            [
                'name' => 'add-sponsor-extra-question',
                'route' => '/api/v1/summits/{id}/sponsors/{sponsor_id}/extra-questions',
                'http_method' => 'POST',
                'scopes' => [
                    sprintf(SummitScopes::WriteSummitData, $current_realm),
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                    IGroup::Sponsors,
                    IGroup::SponsorExternalUsers,
                ]
            ],
            [
                'name' => 'get-sponsor-extra-question',
                'route' => '/api/v1/summits/{id}/sponsors/{sponsor_id}/extra-questions/{extra_question_id}',
                'http_method' => 'GET',
                'scopes' => [
                    sprintf(SummitScopes::ReadSummitData, $current_realm),
                    sprintf(SummitScopes::ReadAllSummitData, $current_realm)
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                    IGroup::Sponsors,
                    IGroup::SponsorExternalUsers,
                ]
            ],
            [
                'name' => 'update-sponsor-extra-question',
                'route' => '/api/v1/summits/{id}/sponsors/{sponsor_id}/extra-questions/{extra_question_id}',
                'http_method' => 'PUT',
                'scopes' => [
                    sprintf(SummitScopes::WriteSummitData, $current_realm),
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                    IGroup::Sponsors,
                    IGroup::SponsorExternalUsers,
                ]
            ],
            [
                'name' => 'delete-sponsor-extra-question',
                'route' => '/api/v1/summits/{id}/sponsors/{sponsor_id}/extra-questions/{extra_question_id}',
                'http_method' => 'DELETE',
                'scopes' => [
                    sprintf(SummitScopes::WriteSummitData, $current_realm),
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                    IGroup::Sponsors,
                    IGroup::SponsorExternalUsers,
                ]
            ],
            [
                'name' => 'get-sponsor-extra-questions-metadata',
                'route' => '/api/v1/summits/{id}/sponsors/all/extra-questions/metadata',
                'http_method' => 'GET',
                'scopes' => [
                    sprintf(SummitScopes::ReadSummitData, $current_realm),
                    sprintf(SummitScopes::ReadAllSummitData, $current_realm)
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                    IGroup::Sponsors,
                    IGroup::SponsorExternalUsers,
                ]
            ],
            [
                'name' => 'add-sponsor-extra-question-value',
                'route' => '/api/v1/summits/{id}/sponsors/{sponsor_id}/extra-questions/{extra_question_id}/values',
                'http_method' => 'POST',
                'scopes' => [
                    sprintf(SummitScopes::WriteSummitData, $current_realm),
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                    IGroup::Sponsors,
                    IGroup::SponsorExternalUsers,
                ]
            ],
            [
                'name' => 'update-sponsor-extra-question-value',
                'route' => '/api/v1/summits/{id}/sponsors/{sponsor_id}/extra-questions/{extra_question_id}/values/{value_id}',
                'http_method' => 'PUT',
                'scopes' => [
                    sprintf(SummitScopes::WriteSummitData, $current_realm),
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                    IGroup::Sponsors,
                    IGroup::SponsorExternalUsers,
                ]
            ],
            [
                'name' => 'delete-sponsor-extra-question-value',
                'route' => '/api/v1/summits/{id}/sponsors/{sponsor_id}/extra-questions/{extra_question_id}/values/{value_id}',
                'http_method' => 'DELETE',
                'scopes' => [
                    sprintf(SummitScopes::WriteSummitData, $current_realm),
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                    IGroup::Sponsors,
                    IGroup::SponsorExternalUsers,
                ]
            ],
            // lead report settings
            [
                'name' => 'get-sponsor-report-settings-metadata',
                'route' => '/api/v1/summits/{id}/sponsors/{sponsor_id}/lead-report-settings/metadata',
                'http_method' => 'GET',
                'scopes' => [
                    sprintf(SummitScopes::ReadSummitData, $current_realm),
                    sprintf(SummitScopes::ReadAllSummitData, $current_realm)
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                    IGroup::Sponsors,
                    IGroup::SponsorExternalUsers,
                ]
            ],
            [
                'name' => 'add-sponsor-report-settings',
                'route' => '/api/v1/summits/{id}/sponsors/{sponsor_id}/lead-report-settings',
                'http_method' => 'POST',
                'scopes' => [
                    sprintf(SummitScopes::WriteSummitData, $current_realm),
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                ]
            ],
            [
                'name' => 'update-sponsor-report-settings',
                'route' => '/api/v1/summits/{id}/sponsors/{sponsor_id}/lead-report-settings',
                'http_method' => 'PUT',
                'scopes' => [
                    sprintf(SummitScopes::WriteSummitData, $current_realm),
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                ]
            ],
            //
            [
                'name' => 'add-sponsor-user',
                'route' => '/api/v1/summits/{id}/sponsors/{sponsor_id}/users/{member_id}',
                'http_method' => 'PUT',
                'scopes' => [
                    sprintf(SummitScopes::WriteSummitData, $current_realm),
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                ]
            ],
            [
                'name' => 'delete-sponsor-user',
                'route' => '/api/v1/summits/{id}/sponsors/{sponsor_id}/users/{member_id}',
                'http_method' => 'DELETE',
                'scopes' => [
                    sprintf(SummitScopes::WriteSummitData, $current_realm),
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                ]
            ],
            [
                'name' => 'share-my-user-info-with-sponsor',
                'route' => '/api/v1/summits/{id}/sponsors/{sponsor_id}/user-info-grants/me',
                'http_method' => 'POST',
                'scopes' => [
                    sprintf(SummitScopes::WriteMyBadgeScan, $current_realm),
                ]
            ],
            // sponsorship-types
            [
                'name' => 'get-sponsorship-types',
                'route' => '/api/v1/sponsorship-types',
                'http_method' => 'GET',
                'scopes' => [
                    sprintf(SummitScopes::ReadSummitData, $current_realm),
                    sprintf(SummitScopes::ReadAllSummitData, $current_realm)
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                ]
            ],
            [
                'name' => 'add-sponsorship-type',
                'route' => '/api/v1/sponsorship-types',
                'http_method' => 'POST',
                'scopes' => [
                    sprintf(SummitScopes::WriteSummitData, $current_realm),
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                ]
            ],
            [
                'name' => 'get-sponsorship-type',
                'route' => '/api/v1/sponsorship-types/{id}',
                'http_method' => 'GET',
                'scopes' => [
                    sprintf(SummitScopes::ReadSummitData, $current_realm),
                    sprintf(SummitScopes::ReadAllSummitData, $current_realm)
                ],
            ],
            [
                'name' => 'update-sponsorship-type',
                'route' => '/api/v1/sponsorship-types/{id}',
                'http_method' => 'PUT',
                'scopes' => [
                    sprintf(SummitScopes::WriteSummitData, $current_realm),
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                ]
            ],
            [
                'name' => 'delete-sponsorship-type',
                'route' => '/api/v1/sponsorship-types/{id}',
                'http_method' => 'DELETE',
                'scopes' => [
                    sprintf(SummitScopes::WriteSummitData, $current_realm),
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                ]
            ],

            // order-extra-questions
            [
                'name' => 'get-order-extra-questions',
                'route' => '/api/v1/summits/{id}/order-extra-questions',
                'http_method' => 'GET',
                'scopes' => [
                    sprintf(SummitScopes::ReadSummitData, $current_realm),
                    sprintf(SummitScopes::ReadAllSummitData, $current_realm)
                ]
            ],
            [
                'name' => 'get-order-extra-questions-metadata',
                'route' => '/api/v1/summits/{id}/order-extra-questions/metadata',
                'http_method' => 'GET',
                'scopes' => [
                    sprintf(SummitScopes::ReadSummitData, $current_realm),
                    sprintf(SummitScopes::ReadAllSummitData, $current_realm)
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                    IGroup::SummitRegistrationAdmins,
                ]
            ],
            [
                'name' => 'add-order-extra-question',
                'route' => '/api/v1/summits/{id}/order-extra-questions',
                'http_method' => 'POST',
                'scopes' => [
                    sprintf(SummitScopes::WriteSummitData, $current_realm),
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                    IGroup::SummitRegistrationAdmins,
                ]
            ],
            [
                'name' => 'seed-default-order-extra-questions',
                'route' => '/api/v1/summits/{id}/order-extra-questions/seed-defaults',
                'http_method' => 'POST',
                'scopes' => [
                    sprintf(SummitScopes::WriteSummitData, $current_realm),
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                    IGroup::SummitRegistrationAdmins,
                ]
            ],
            [
                'name' => 'get-order-extra-question',
                'route' => '/api/v1/summits/{id}/order-extra-questions/{question_id}',
                'http_method' => 'GET',
                'scopes' => [
                    sprintf(SummitScopes::ReadSummitData, $current_realm),
                    sprintf(SummitScopes::ReadAllSummitData, $current_realm)
                ]
            ],
            [
                'name' => 'update-order-extra-question',
                'route' => '/api/v1/summits/{id}/order-extra-questions/{question_id}',
                'http_method' => 'PUT',
                'scopes' => [
                    sprintf(SummitScopes::WriteSummitData, $current_realm),
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                    IGroup::SummitRegistrationAdmins,
                ]
            ],
            [
                'name' => 'delete-order-extra-question',
                'route' => '/api/v1/summits/{id}/order-extra-questions/{question_id}',
                'http_method' => 'DELETE',
                'scopes' => [
                    sprintf(SummitScopes::WriteSummitData, $current_realm),
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                    IGroup::SummitRegistrationAdmins,
                ]
            ],
            [
                'name' => 'add-order-extra-question-value',
                'route' => '/api/v1/summits/{id}/order-extra-questions/{question_id}/values',
                'http_method' => 'POST',
                'scopes' => [
                    sprintf(SummitScopes::WriteSummitData, $current_realm),
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                ]
            ],
            [
                'name' => 'update-order-extra-question-value',
                'route' => '/api/v1/summits/{id}/order-extra-questions/{question_id}/values/{value_id}',
                'http_method' => 'PUT',
                'scopes' => [
                    sprintf(SummitScopes::WriteSummitData, $current_realm),
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                    IGroup::SummitRegistrationAdmins,
                ]
            ],
            [
                'name' => 'delete-order-extra-question-value',
                'route' => '/api/v1/summits/{id}/order-extra-questions/{question_id}/values/{value_id}',
                'http_method' => 'DELETE',
                'scopes' => [
                    sprintf(SummitScopes::WriteSummitData, $current_realm),
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                    IGroup::SummitRegistrationAdmins,
                ]
            ],
            // sub question rules
            [
                'name' => 'get-sub-question-rules',
                'route' => '/api/v1/summits/{id}/order-extra-questions/{question_id}/sub-question-rules',
                'http_method' => 'GET',
                'scopes' => [
                    sprintf(SummitScopes::ReadSummitData, $current_realm),
                    sprintf(SummitScopes::ReadAllSummitData, $current_realm)
                ]
            ],
            [
                'name' => 'add-sub-question-rule',
                'route' => '/api/v1/summits/{id}/order-extra-questions/{question_id}/sub-question-rules',
                'http_method' => 'POST',
                'scopes' => [
                    sprintf(SummitScopes::WriteSummitData, $current_realm),
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                    IGroup::SummitRegistrationAdmins,
                ]
            ],
            [
                'name' => 'update-sub-question-rule',
                'route' => '/api/v1/summits/{id}/order-extra-questions/{question_id}/sub-question-rules/{rule_id}',
                'http_method' => 'PUT',
                'scopes' => [
                    sprintf(SummitScopes::WriteSummitData, $current_realm),
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                    IGroup::SummitRegistrationAdmins,
                ]
            ],
            [
                'name' => 'delete-sub-question-rule',
                'route' => '/api/v1/summits/{id}/order-extra-questions/{question_id}/sub-question-rules/{rule_id}',
                'http_method' => 'DELETE',
                'scopes' => [
                    sprintf(SummitScopes::WriteSummitData, $current_realm),
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                    IGroup::SummitRegistrationAdmins,
                ]
            ],
            [
                'name' => 'get-sub-question-rule',
                'route' => '/api/v1/summits/{id}/order-extra-questions/{question_id}/sub-question-rules/{rule_id}',
                'http_method' => 'GET',
                'scopes' => [
                    sprintf(SummitScopes::ReadSummitData, $current_realm),
                    sprintf(SummitScopes::ReadAllSummitData, $current_realm)
                ]
            ],
            // badge types
            [
                'name' => 'get-badge-types',
                'route' => '/api/v1/summits/{id}/badge-types',
                'http_method' => 'GET',
                'scopes' => [
                    sprintf(SummitScopes::ReadSummitData, $current_realm),
                    sprintf(SummitScopes::ReadAllSummitData, $current_realm)
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                    IGroup::SummitRegistrationAdmins,
                ]
            ],
            [
                'name' => 'add-badge-type',
                'route' => '/api/v1/summits/{id}/badge-types',
                'http_method' => 'POST',
                'scopes' => [
                    sprintf(SummitScopes::WriteSummitData, $current_realm),
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                    IGroup::SummitRegistrationAdmins,
                ]
            ],
            [
                'name' => 'get-badge-type',
                'route' => '/api/v1/summits/{id}/badge-types/{badge_type_id}',
                'http_method' => 'GET',
                'scopes' => [
                    sprintf(SummitScopes::ReadSummitData, $current_realm),
                    sprintf(SummitScopes::ReadAllSummitData, $current_realm)
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                    IGroup::SummitRegistrationAdmins,
                ]
            ],
            [
                'name' => 'update-badge-type',
                'route' => '/api/v1/summits/{id}/badge-types/{badge_type_id}',
                'http_method' => 'PUT',
                'scopes' => [
                    sprintf(SummitScopes::WriteSummitData, $current_realm),
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                    IGroup::SummitRegistrationAdmins,
                ]
            ],
            [
                'name' => 'delete-badge-type',
                'route' => '/api/v1/summits/{id}/badge-types/{badge_type_id}',
                'http_method' => 'DELETE',
                'scopes' => [
                    sprintf(SummitScopes::WriteSummitData, $current_realm),
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                    IGroup::SummitRegistrationAdmins,
                ]
            ],
            [
                'name' => 'add-access-level-type-2-badge-type',
                'route' => '/api/v1/summits/{id}/badge-types/{badge_type_id}/access-levels/{access_level_id}',
                'http_method' => 'PUT',
                'scopes' => [
                    sprintf(SummitScopes::WriteSummitData, $current_realm),
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                    IGroup::SummitRegistrationAdmins,
                ]
            ],
            [
                'name' => 'remove-access-level-type-from-badge-type',
                'route' => '/api/v1/summits/{id}/badge-types/{badge_type_id}/access-levels/{access_level_id}',
                'http_method' => 'DELETE',
                'scopes' => [
                    sprintf(SummitScopes::WriteSummitData, $current_realm),
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                    IGroup::SummitRegistrationAdmins,
                ]
            ],
            [
                'name' => 'add-feature-2-badge-type',
                'route' => '/api/v1/summits/{id}/badge-types/{badge_type_id}/features/{feature_id}',
                'http_method' => 'PUT',
                'scopes' => [
                    sprintf(SummitScopes::WriteSummitData, $current_realm),
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                    IGroup::SummitRegistrationAdmins,
                ]
            ],
            [
                'name' => 'remove-feature-from-badge-type',
                'route' => '/api/v1/summits/{id}/badge-types/{badge_type_id}/features/{feature_id}',
                'http_method' => 'DELETE',
                'scopes' => [
                    sprintf(SummitScopes::WriteSummitData, $current_realm),
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                    IGroup::SummitRegistrationAdmins,
                ]
            ],
            [
                'name' => 'add-view-2-badge-type',
                'route' => '/api/v1/summits/{id}/badge-types/{badge_type_id}/view-types/{badge_view_type_id}',
                'http_method' => 'PUT',
                'scopes' => [
                    sprintf(SummitScopes::WriteSummitData, $current_realm),
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                    IGroup::SummitRegistrationAdmins,
                ]
            ],
            [
                'name' => 'remove-view-from-badge-type',
                'route' => '/api/v1/summits/{id}/badge-types/{badge_type_id}/view-types/{badge_view_type_id}',
                'http_method' => 'DELETE',
                'scopes' => [
                    sprintf(SummitScopes::WriteSummitData, $current_realm),
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                    IGroup::SummitRegistrationAdmins,
                ]
            ],
            // badge view types
            [
                'name' => 'get-badge-view-types',
                'route' => '/api/v1/summits/{id}/badge-view-types',
                'http_method' => 'GET',
                'scopes' => [
                    sprintf(SummitScopes::ReadSummitData, $current_realm),
                    sprintf(SummitScopes::ReadAllSummitData, $current_realm)
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                    IGroup::SummitRegistrationAdmins,
                ]
            ],
            [
                'name' => 'add-badge-view-type',
                'route' => '/api/v1/summits/{id}/badge-view-types',
                'http_method' => 'POST',
                'scopes' => [
                    sprintf(SummitScopes::WriteSummitData, $current_realm),
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                    IGroup::SummitRegistrationAdmins,
                ]
            ],
            [
                'name' => 'get-badge-view-type',
                'route' => '/api/v1/summits/{id}/badge-view-types/{badge_view_type_id}',
                'http_method' => 'GET',
                'scopes' => [
                    sprintf(SummitScopes::ReadSummitData, $current_realm),
                    sprintf(SummitScopes::ReadAllSummitData, $current_realm)
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                    IGroup::SummitRegistrationAdmins,
                ]
            ],
            [
                'name' => 'update-badge-view-type',
                'route' => '/api/v1/summits/{id}/badge-view-types/{badge_view_type_id}',
                'http_method' => 'PUT',
                'scopes' => [
                    sprintf(SummitScopes::WriteSummitData, $current_realm),
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                    IGroup::SummitRegistrationAdmins,
                ]
            ],
            [
                'name' => 'delete-badge-view-type',
                'route' => '/api/v1/summits/{id}/badge-view-types/{badge_view_type_id}',
                'http_method' => 'DELETE',
                'scopes' => [
                    sprintf(SummitScopes::WriteSummitData, $current_realm),
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                    IGroup::SummitRegistrationAdmins,
                ]
            ],
            // attendees
            [
                'name' => 'get-attendees',
                'route' => '/api/v1/summits/{id}/attendees',
                'http_method' => 'GET',
                'scopes' => [
                    sprintf(SummitScopes::ReadSummitData, $current_realm),
                    sprintf(SummitScopes::ReadAllSummitData, $current_realm)
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                    IGroup::SummitRegistrationAdmins,
                ]
            ],
            [
                'name' => 'get-attendees-csv',
                'route' => '/api/v1/summits/{id}/attendees/csv',
                'http_method' => 'GET',
                'scopes' => [
                    sprintf(SummitScopes::ReadSummitData, $current_realm),
                    sprintf(SummitScopes::ReadAllSummitData, $current_realm)
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                    IGroup::SummitRegistrationAdmins,
                ]
            ],
            [
                'name' => 'attendees-send-email',
                'route' => '/api/v1/summits/{id}/attendees/all/send',
                'http_method' => 'PUT',
                'scopes' => [
                    sprintf(SummitScopes::WriteSummitData, $current_realm),
                    sprintf(SummitScopes::WriteAttendeesData, $current_realm),
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                    IGroup::SummitRegistrationAdmins,
                ]
            ],
            [
                'name' => 'get-own-attendee',
                'route' => '/api/v1/summits/{id}/attendees/me',
                'http_method' => 'GET',
                'scopes' => [
                    sprintf(SummitScopes::ReadSummitData, $current_realm),
                    sprintf(SummitScopes::ReadAllSummitData, $current_realm)
                ],
            ],
            [
                'name' => 'get-own-attendee-allowed-extra-questions',
                'route' => '/api/v1/summits/{id}/attendees/me/allowed-extra-questions',
                'http_method' => 'GET',
                'scopes' => [
                    sprintf(SummitScopes::ReadSummitData, $current_realm),
                    sprintf(SummitScopes::ReadAllSummitData, $current_realm)
                ],
            ],
            [
                'name' => 'get-attendee',
                'route' => '/api/v1/summits/{id}/attendees/{attendee_id}',
                'http_method' => 'GET',
                'scopes' => [
                    sprintf(SummitScopes::ReadSummitData, $current_realm),
                    sprintf(SummitScopes::ReadAllSummitData, $current_realm)
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                    IGroup::SummitRegistrationAdmins,
                ]
            ],
            [
                'name' => 'get-my-related-attendees',
                'route' => '/api/v1/summits/{id}/attendees/{attendee_id}/me',
                'http_method' => 'GET',
                'scopes' => [
                    sprintf(SummitScopes::ReadSummitData, $current_realm),
                    sprintf(SummitScopes::ReadAllSummitData, $current_realm)
                ],
            ],
            [
                'name' => 'delete-attendee',
                'route' => '/api/v1/summits/{id}/attendees/{attendee_id}',
                'http_method' => 'DELETE',
                'scopes' => [
                    sprintf(SummitScopes::WriteSummitData, $current_realm),
                    sprintf(SummitScopes::WriteAttendeesData, $current_realm),
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                    IGroup::SummitRegistrationAdmins,
                ]
            ],
            [
                'name' => 'update-attendee',
                'route' => '/api/v1/summits/{id}/attendees/{attendee_id}',
                'http_method' => 'PUT',
                'scopes' => [
                    sprintf(SummitScopes::WriteSummitData, $current_realm),
                    sprintf(SummitScopes::WriteAttendeesData, $current_realm),
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                    IGroup::SummitRegistrationAdmins,
                ]
            ],
            [
                'name' => 'attendee-virtual-check-in',
                'route' => '/api/v1/summits/{id}/attendees/{attendee_id}/virtual-check-in',
                'http_method' => 'PUT',
                'scopes' => [
                    sprintf(SummitScopes::WriteSummitData, $current_realm),
                    sprintf(SummitScopes::DoVirtualCheckIn, $current_realm),
                ],
            ],
            [
                'name' => 'get-attendee-schedule',
                'route' => '/api/v1/summits/{id}/attendees/{attendee_id}/schedule',
                'http_method' => 'GET',
                'scopes' => [
                    sprintf(SummitScopes::ReadSummitData, $current_realm),
                    sprintf(SummitScopes::ReadAllSummitData, $current_realm)
                ],
            ],
            [
                'name' => 'add-event-attendee-schedule',
                'route' => '/api/v1/summits/{id}/attendees/{attendee_id}/schedule/{event_id}',
                'http_method' => 'POST',
                'scopes' => [sprintf(SummitScopes::WriteSummitData, $current_realm)],
            ],
            [
                'name' => 'delete-event-attendee-schedule',
                'route' => '/api/v1/summits/{id}/attendees/{attendee_id}/schedule/{event_id}',
                'http_method' => 'DELETE',
                'scopes' => [sprintf(SummitScopes::WriteSummitData, $current_realm)],
            ],
            array(
                'name' => 'checking-event-attendee-schedule',
                'route' => '/api/v1/summits/{id}/attendees/{attendee_id}/schedule/{event_id}/check-in',
                'http_method' => 'PUT',
                'scopes' => [sprintf(SummitScopes::WriteSummitData, $current_realm)],
            ),
            [
                'name' => 'get-attendee-allowed-extra-questions',
                'route' => '/api/v1/summits/{id}/attendees/{attendee_id}/allowed-extra-questions',
                'http_method' => 'GET',
                'scopes' => [
                    sprintf(SummitScopes::ReadSummitData, $current_realm),
                    sprintf(SummitScopes::ReadAllSummitData, $current_realm)
                ]
            ],
            [
                'name' => 'add-attendee',
                'route' => '/api/v1/summits/{id}/attendees',
                'http_method' => 'POST',
                'scopes' => [
                    sprintf(SummitScopes::WriteSummitData, $current_realm),
                    sprintf(SummitScopes::WriteAttendeesData, $current_realm),
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                    IGroup::SummitRegistrationAdmins,
                ]
            ],
            [
                'name' => 'add-attendee-ticket',
                'route' => '/api/v1/summits/{id}/attendees/{attendee_id}/tickets',
                'http_method' => 'POST',
                'scopes' => [
                    sprintf(SummitScopes::WriteSummitData, $current_realm),
                    sprintf(SummitScopes::WriteAttendeesData, $current_realm),
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                    IGroup::SummitRegistrationAdmins,
                ]
            ],
            [
                'name' => 'delete-attendee-ticket',
                'route' => '/api/v1/summits/{id}/attendees/{attendee_id}/tickets/{ticket_id}',
                'http_method' => 'DELETE',
                'scopes' => [
                    sprintf(SummitScopes::WriteSummitData, $current_realm),
                    sprintf(SummitScopes::WriteAttendeesData, $current_realm),
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                    IGroup::SummitRegistrationAdmins,
                ]
            ],
            [
                'name' => 'reassign-attendee-ticket',
                'route' => '/api/v1/summits/{id}/attendees/{attendee_id}/tickets/{ticket_id}/reassign',
                'http_method' => 'PUT',
                'scopes' => [
                    sprintf(SummitScopes::WriteSummitData, $current_realm),
                    sprintf(SummitScopes::WriteAttendeesData, $current_realm),
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                    IGroup::SummitRegistrationAdmins,
                ]
            ],
            [
                'name' => 'reassign-attendee-ticket-by-member',
                'route' => '/api/v1/summits/{id}/attendees/{attendee_id}/tickets/{ticket_id}/reassign/{other_member_id}',
                'http_method' => 'PUT',
                'scopes' => [
                    sprintf(SummitScopes::WriteSummitData, $current_realm),
                    sprintf(SummitScopes::WriteAttendeesData, $current_realm),
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                    IGroup::SummitRegistrationAdmins,
                ]
            ],
            // attendee notes
            [
                'name' => 'get-all-attendee-notes',
                'route' => '/api/v1/summits/{id}/attendees/all/notes',
                'http_method' => 'GET',
                'scopes' => [
                    sprintf(SummitScopes::ReadAllSummitData, $current_realm),
                    sprintf(SummitScopes::ReadAttendeeNotesData, $current_realm)
                ],
            ],
            [
                'name' => 'get-all-attendee-notes-csv',
                'route' => '/api/v1/summits/{id}/attendees/all/notes/csv',
                'http_method' => 'GET',
                'scopes' => [
                    sprintf(SummitScopes::ReadAllSummitData, $current_realm),
                    sprintf(SummitScopes::ReadAttendeeNotesData, $current_realm)
                ],
            ],
            [
                'name' => 'get-attendee-notes',
                'route' => '/api/v1/summits/{id}/attendees/{attendee_id}/notes',
                'http_method' => 'GET',
                'scopes' => [
                    sprintf(SummitScopes::ReadAllSummitData, $current_realm),
                    sprintf(SummitScopes::ReadAttendeeNotesData, $current_realm)
                ],
            ],
            [
                'name' => 'get-attendee-notes-csv',
                'route' => '/api/v1/summits/{id}/attendees/{attendee_id}/notes/csv',
                'http_method' => 'GET',
                'scopes' => [
                    sprintf(SummitScopes::ReadAllSummitData, $current_realm),
                    sprintf(SummitScopes::ReadAttendeeNotesData, $current_realm)
                ],
            ],
            [
                'name' => 'get-attendee-note',
                'route' => '/api/v1/summits/{id}/attendees/{attendee_id}/notes/{note_id}',
                'http_method' => 'GET',
                'scopes' => [
                    sprintf(SummitScopes::ReadAllSummitData, $current_realm),
                    sprintf(SummitScopes::ReadAttendeeNotesData, $current_realm)
                ],
            ],
            [
                'name' => 'add-attendee-note',
                'route' => '/api/v1/summits/{id}/attendees/{attendee_id}/notes',
                'http_method' => 'POST',
                'scopes' => [
                    sprintf(SummitScopes::WriteSummitData, $current_realm),
                    sprintf(SummitScopes::WriteAttendeeNotesData, $current_realm)
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                    IGroup::SummitRegistrationAdmins,
                ]
            ],
            [
                'name' => 'update-attendee-note',
                'route' => '/api/v1/summits/{id}/attendees/{attendee_id}/notes/{note_id}',
                'http_method' => 'PUT',
                'scopes' => [
                    sprintf(SummitScopes::WriteSummitData, $current_realm),
                    sprintf(SummitScopes::WriteAttendeeNotesData, $current_realm)
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                    IGroup::SummitRegistrationAdmins,
                ]
            ],
            [
                'name' => 'delete-attendee-note',
                'route' => '/api/v1/summits/{id}/attendees/{attendee_id}/notes/{note_id}',
                'http_method' => 'DELETE',
                'scopes' => [
                    sprintf(SummitScopes::WriteSummitData, $current_realm),
                    sprintf(SummitScopes::WriteAttendeeNotesData, $current_realm)
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                    IGroup::SummitRegistrationAdmins,
                ]
            ],
            // submitters
            [
                'name' => 'get-submitters',
                'route' => '/api/v1/summits/{id}/submitters',
                'http_method' => 'GET',
                'scopes' => [
                    sprintf(SummitScopes::ReadSummitData, $current_realm),
                    sprintf(SummitScopes::ReadAllSummitData, $current_realm)
                ],
            ],
            [
                'name' => 'get-submitters-csv',
                'route' => '/api/v1/summits/{id}/submitters/csv',
                'http_method' => 'GET',
                'scopes' => [
                    sprintf(SummitScopes::ReadSummitData, $current_realm),
                    sprintf(SummitScopes::ReadAllSummitData, $current_realm)
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                ]
            ],
            [
                'name' => 'send bulk submitters emails',
                'route' => '/api/v1/summits/{id}/submitters/all/send',
                'http_method' => 'PUT',
                'scopes' => [
                    sprintf(SummitScopes::WriteSummitData, $current_realm),
                    sprintf(SummitScopes::WriteSpeakersData, $current_realm),
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                    IGroup::SummitRegistrationAdmins
                ]
            ],
            // speakers
            [
                'name' => 'get-speakers',
                'route' => '/api/v1/summits/{id}/speakers',
                'http_method' => 'GET',
                'scopes' => [
                    sprintf(SummitScopes::ReadSummitData, $current_realm),
                    sprintf(SummitScopes::ReadAllSummitData, $current_realm)
                ],
            ],
            [
                'name' => 'get-speakers-csv',
                'route' => '/api/v1/summits/{id}/speakers/csv',
                'http_method' => 'GET',
                'scopes' => [
                    sprintf(SummitScopes::ReadSummitData, $current_realm),
                    sprintf(SummitScopes::ReadAllSummitData, $current_realm)
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                ]
            ],
            [
                'name' => 'get-speakers-on-schedule',
                'route' => '/api/v1/summits/{id}/speakers/on-schedule',
                'http_method' => 'GET',
                'scopes' => [
                    sprintf(SummitScopes::ReadSummitData, $current_realm),
                    sprintf(SummitScopes::ReadAllSummitData, $current_realm)
                ],
            ],
            [
                'name' => 'add-speaker-by-summit',
                'route' => '/api/v1/summits/{id}/speakers',
                'http_method' => 'POST',
                'scopes' => [
                    sprintf(SummitScopes::WriteSpeakersData, $current_realm),
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                    IGroup::SummitRegistrationAdmins,
                ]
            ],
            [
                'name' => 'update-speaker-by-summit',
                'route' => '/api/v1/summits/{id}/speakers/{speaker_id}',
                'http_method' => 'PUT',
                'scopes' => [
                    sprintf(SummitScopes::WriteSpeakersData, $current_realm),
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                    IGroup::SummitRegistrationAdmins,
                ]
            ],
            [
                'name' => 'add-speaker-photo',
                'route' => '/api/v1/speakers/{speaker_id}/photo',
                'http_method' => 'POST',
                'scopes' => [
                    sprintf(SummitScopes::WriteSpeakersData, $current_realm),
                ],
            ],
            [
                'name' => 'delete-speaker-photo',
                'route' => '/api/v1/speakers/{speaker_id}/photo',
                'http_method' => 'DELETE',
                'scopes' => [
                    sprintf(SummitScopes::WriteSpeakersData, $current_realm),
                ],
            ],
            [
                'name' => 'add-speaker-big-photo',
                'route' => '/api/v1/speakers/{speaker_id}/big-photo',
                'http_method' => 'POST',
                'scopes' => [
                    sprintf(SummitScopes::WriteSpeakersData, $current_realm),
                ],
            ],
            [
                'name' => 'delete-speaker-big-photo',
                'route' => '/api/v1/speakers/{speaker_id}/big-photo',
                'http_method' => 'DELETE',
                'scopes' => [
                    sprintf(SummitScopes::WriteSpeakersData, $current_realm),
                ],
            ],
            [
                'name' => 'add-speaker',
                'route' => '/api/v1/speakers',
                'http_method' => 'POST',
                'scopes' => [
                    sprintf(SummitScopes::WriteSpeakersData, $current_realm),
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                ]
            ],
            [
                'name' => 'update-speaker',
                'route' => '/api/v1/speakers/{speaker_id}',
                'http_method' => 'PUT',
                'scopes' => [
                    sprintf(SummitScopes::WriteSpeakersData, $current_realm),
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                ]
            ],
            [
                'name' => 'delete-speaker',
                'route' => '/api/v1/speakers/{speaker_id}',
                'http_method' => 'DELETE',
                'scopes' => [
                    sprintf(SummitScopes::WriteSpeakersData, $current_realm),
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                ]
            ],
            [
                'name' => 'get-all-speakers',
                'route' => '/api/v1/speakers',
                'http_method' => 'GET',
                'scopes' => [
                    sprintf(SummitScopes::ReadSummitData, $current_realm),
                    sprintf(SummitScopes::ReadAllSummitData, $current_realm)
                ],
            ],
            [
                'name' => 'get-speakers-active-involvements',
                'route' => '/api/v1/speakers/active-involvements',
                'http_method' => 'GET',
                'scopes' => [
                    sprintf(SummitScopes::ReadSummitData, $current_realm),
                    sprintf(SummitScopes::ReadAllSummitData, $current_realm)
                ],
            ],
            [
                'name' => 'get-speakers-organizational-roles',
                'route' => '/api/v1/speakers/organizational-roles',
                'http_method' => 'GET',
                'scopes' => [
                    sprintf(SummitScopes::ReadSummitData, $current_realm),
                    sprintf(SummitScopes::ReadAllSummitData, $current_realm)
                ],
            ],
            [
                'name' => 'get-speaker',
                'route' => '/api/v1/speakers/{speaker_id}',
                'http_method' => 'GET',
                'scopes' => [
                    sprintf(SummitScopes::ReadSummitData, $current_realm),
                    sprintf(SummitScopes::ReadAllSummitData, $current_realm)
                ],
            ],
            [
                'name' => 'get-my-speaker',
                'route' => '/api/v1/speakers/me',
                'http_method' => 'GET',
                'scopes' => [
                    sprintf(SummitScopes::ReadMySpeakersData, $current_realm),
                    sprintf(SummitScopes::ReadSummitData, $current_realm),
                    sprintf(SummitScopes::ReadAllSummitData, $current_realm)
                ],
            ],
            [
                'name' => 'request-edit-speaker-permission',
                'route' => '/api/v1/speakers/{speaker_id}/edit-permission',
                'http_method' => 'PUT',
                'scopes' => [
                    sprintf(SummitScopes::WriteSpeakersData, $current_realm),
                    sprintf(SummitScopes::WriteSummitData, $current_realm)
                ],
            ],
            [
                'name' => 'get-edit-speaker-permission',
                'route' => '/api/v1/speakers/{speaker_id}/edit-permission',
                'http_method' => 'GET',
                'scopes' => [
                    sprintf(SummitScopes::ReadSummitData, $current_realm),
                    sprintf(SummitScopes::ReadAllSummitData, $current_realm)
                ],
            ],
            [
                'name' => 'get-my-speaker-presentations-by-role-by-selection-plan',
                'route' => '/api/v1/speakers/me/presentations/{role}/selection-plans/{selection_plan_id}',
                'http_method' => 'GET',
                'scopes' => [
                    sprintf(SummitScopes::ReadSummitData, $current_realm),
                    sprintf(SummitScopes::ReadAllSummitData, $current_realm)
                ],

            ],
            [
                'name' => 'get-my-speaker-presentations-by-role-by-summit',
                'route' => '/api/v1/speakers/me/presentations/{role}/summits/{summit_id}',
                'http_method' => 'GET',
                'scopes' => [
                    sprintf(SummitScopes::ReadSummitData, $current_realm),
                    sprintf(SummitScopes::ReadAllSummitData, $current_realm)
                ],

            ],
            [
                'name' => 'add-speaker-2-my-presentation',
                'route' => '/api/v1/speakers/me/presentations/{presentation_id}/speakers/{speaker_id}',
                'http_method' => 'PUT',
                'scopes' => [
                    sprintf(SummitScopes::WriteSummitData, $current_realm),
                    sprintf(SummitScopes::WriteSpeakersData, $current_realm),
                    sprintf(SummitScopes::WriteMySpeakersData, $current_realm)
                ],
            ],
            [
                'name' => 'remove-speaker-from-my-presentation',
                'route' => '/api/v1/speakers/me/presentations/{presentation_id}/speakers/{speaker_id}',
                'http_method' => 'DELETE',
                'scopes' => [
                    sprintf(SummitScopes::WriteSummitData, $current_realm),
                    sprintf(SummitScopes::WriteSpeakersData, $current_realm),
                    sprintf(SummitScopes::WriteMySpeakersData, $current_realm)
                ],
            ],
            [
                'name' => 'add-moderator-2-my-presentation',
                'route' => '/api/v1/speakers/me/presentations/{presentation_id}/moderators/{speaker_id}',
                'http_method' => 'PUT',
                'scopes' => [
                    sprintf(SummitScopes::WriteSummitData, $current_realm),
                    sprintf(SummitScopes::WriteSpeakersData, $current_realm),
                    sprintf(SummitScopes::WriteMySpeakersData, $current_realm)
                ],
            ],
            [
                'name' => 'remove-moderators-from-my-presentation',
                'route' => '/api/v1/speakers/me/presentations/{presentation_id}/moderators/{speaker_id}',
                'http_method' => 'DELETE',
                'scopes' => [
                    sprintf(SummitScopes::WriteSummitData, $current_realm),
                    sprintf(SummitScopes::WriteSpeakersData, $current_realm),
                    sprintf(SummitScopes::WriteMySpeakersData, $current_realm)
                ],
            ],
            [
                'name' => 'create-my-speaker',
                'route' => '/api/v1/speakers/me',
                'http_method' => 'POST',
                'scopes' => [
                    sprintf(SummitScopes::WriteMySpeakersData, $current_realm),
                ],
            ],
            [
                'name' => 'update-my-speaker',
                'route' => '/api/v1/speakers/me',
                'http_method' => 'PUT',
                'scopes' => [
                    sprintf(SummitScopes::WriteMySpeakersData, $current_realm),
                ],
            ],
            [
                'name' => 'add-my-speaker-photo',
                'route' => '/api/v1/speakers/me/photo',
                'http_method' => 'POST',
                'scopes' => [
                    sprintf(SummitScopes::WriteMySpeakersData, $current_realm),
                ],
            ],
            [
                'name' => 'delete-my-speaker-photo',
                'route' => '/api/v1/speakers/me/photo',
                'http_method' => 'DELETE',
                'scopes' => [
                    sprintf(SummitScopes::WriteMySpeakersData, $current_realm),
                ],
            ],
            [
                'name' => 'add-my-speaker-big-photo',
                'route' => '/api/v1/speakers/me/big-photo',
                'http_method' => 'POST',
                'scopes' => [
                    sprintf(SummitScopes::WriteMySpeakersData, $current_realm),
                ],
            ],
            [
                'name' => 'delete-my-speaker-big-photo',
                'route' => '/api/v1/speakers/me/big-photo',
                'http_method' => 'DELETE',
                'scopes' => [
                    sprintf(SummitScopes::WriteMySpeakersData, $current_realm),
                ],
            ],
            [
                'name' => 'merge-speakers',
                'route' => '/api/v1/speakers/merge/{speaker_from_id}/{speaker_to_id}',
                'http_method' => 'PUT',
                'scopes' => [
                    sprintf(SummitScopes::WriteSpeakersData, $current_realm),
                ],
            ],
            [
                'name' => 'get-speaker-by-summit',
                'route' => '/api/v1/summits/{id}/speakers/{speaker_id}',
                'http_method' => 'GET',
                'scopes' => [
                    sprintf(SummitScopes::ReadSpeakersData, $current_realm),
                    sprintf(SummitScopes::ReadAllSummitData, $current_realm)
                ],
            ],
            [
                'name' => 'get-my/speaker-by-summit',
                'route' => '/api/v1/summits/{id}/speakers/me',
                'http_method' => 'GET',
                'scopes' => [
                    sprintf(SummitScopes::ReadMySpeakersData, $current_realm),
                    sprintf(SummitScopes::ReadAllSummitData, $current_realm)
                ],
            ],
            [
                'name' => 'add-speaker-feedback',
                'route' => '/api/v1/summits/{id}/speakers/{speaker_id}/presentations/{presentation_id}/feedback',
                'http_method' => 'POST',
                'scopes' => [sprintf(SummitScopes::WriteSummitData, $current_realm)],
            ],
            [
                'name' => 'send bulk speakers emails',
                'route' => '/api/v1/summits/{id}/speakers/all/send',
                'http_method' => 'PUT',
                'scopes' => [
                    sprintf(SummitScopes::WriteSummitData, $current_realm),
                    sprintf(SummitScopes::WriteSpeakersData, $current_realm),
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                    IGroup::SummitRegistrationAdmins
                ]
            ],
            // events
            [
                'name' => 'get-events',
                'route' => '/api/v1/summits/{id}/events',
                'http_method' => 'GET',
                'scopes' => [
                    sprintf(SummitScopes::ReadSummitData, $current_realm),
                    sprintf(SummitScopes::ReadAllSummitData, $current_realm)
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                ]
            ],
            [
                'name' => 'get-events-csv',
                'route' => '/api/v1/summits/{id}/events/csv',
                'http_method' => 'GET',
                'scopes' => [
                    sprintf(SummitScopes::ReadSummitData, $current_realm),
                    sprintf(SummitScopes::ReadAllSummitData, $current_realm)
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                ]
            ],
            [
                'name' => 'import-events-csv',
                'route' => '/api/v1/summits/{id}/events/csv',
                'http_method' => 'POST',
                'scopes' => [
                    sprintf(SummitScopes::WriteSummitData, $current_realm),
                    sprintf(SummitScopes::WriteEventData, $current_realm)
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                ]
            ],
            [
                'name' => 'get-published-events',
                'route' => '/api/v1/summits/{id}/events/published',
                'http_method' => 'GET',
                'scopes' => [
                    sprintf(SummitScopes::ReadSummitData, $current_realm),
                    sprintf(SummitScopes::ReadAllSummitData, $current_realm)
                ],
            ],
            [
                'name' => 'get-published-events-tags',
                'route' => '/api/v1/summits/{id}/events/all/published/tags',
                'http_method' => 'GET',
                'scopes' => [
                    sprintf(SummitScopes::ReadSummitData, $current_realm),
                    sprintf(SummitScopes::ReadAllSummitData, $current_realm)
                ],
            ],
            [
                'name' => 'get-schedule-empty-spots',
                'route' => '/api/v1/summits/{id}/events/published/empty-spots',
                'http_method' => 'GET',
                'scopes' => [
                    sprintf(SummitScopes::ReadSummitData, $current_realm),
                    sprintf(SummitScopes::ReadAllSummitData, $current_realm)
                ],
            ],
            [
                'name' => 'get-unpublished-events',
                'route' => '/api/v1/summits/{id}/events/unpublished',
                'http_method' => 'GET',
                'scopes' => [
                    sprintf(SummitScopes::ReadSummitData, $current_realm),
                    sprintf(SummitScopes::ReadAllSummitData, $current_realm)
                ],
            ],
            [
                'name' => 'get-all-events',
                'route' => '/api/v1/summits/events',
                'http_method' => 'GET',
                'scopes' => [
                    sprintf(SummitScopes::ReadSummitData, $current_realm),
                    sprintf(SummitScopes::ReadAllSummitData, $current_realm)
                ],
            ],
            [
                'name' => 'get-all-published-events',
                'route' => '/api/v1/summits/events/published',
                'http_method' => 'GET',
                'scopes' => [
                    sprintf(SummitScopes::ReadSummitData, $current_realm),
                    sprintf(SummitScopes::ReadAllSummitData, $current_realm)
                ],
            ],
            [
                'name' => 'get-event',
                'route' => '/api/v1/summits/{id}/events/{event_id}',
                'http_method' => 'GET',
                'scopes' => [
                    sprintf(SummitScopes::ReadSummitData, $current_realm),
                    sprintf(SummitScopes::ReadAllSummitData, $current_realm)
                ],
            ],
            [
                'name' => 'get-published-event',
                'route' => '/api/v1/summits/{id}/events/{event_id}/published',
                'http_method' => 'GET',
                'scopes' => [
                    sprintf(SummitScopes::ReadSummitData, $current_realm),
                    sprintf(SummitScopes::ReadAllSummitData, $current_realm)
                ],
            ],
            [
                'name' => 'get-published-event-tokens',
                'route' => '/api/v1/summits/{id}/events/{event_id}/published/tokens',
                'http_method' => 'GET',
                'scopes' => [
                    sprintf(SummitScopes::ReadSummitData, $current_realm),
                    sprintf(SummitScopes::ReadAllSummitData, $current_realm)
                ],
            ],
            [
                'name' => 'get-published-event-streaming-info',
                'route' => '/api/v1/summits/{id}/events/{event_id}/published/streaming-info',
                'http_method' => 'GET',
                'scopes' => [
                    sprintf(SummitScopes::ReadSummitData, $current_realm),
                    sprintf(SummitScopes::ReadAllSummitData, $current_realm)
                ],
            ],
            [
                'name' => 'get-published-event-media-uploads',
                'route' => '/api/v1/summits/{id}/events/{event_id}/published/media-uploads',
                'http_method' => 'GET',
                'scopes' => [
                    sprintf(SummitScopes::ReadSummitData, $current_realm),
                    sprintf(SummitScopes::ReadAllSummitData, $current_realm)
                ],
            ],
            [
                'name' => 'share-email-published-event',
                'route' => '/api/v1/summits/{id}/events/{event_id}/published/mail',
                'http_method' => 'POST',
                'scopes' => [
                    sprintf(SummitScopes::SendMyScheduleMail, $current_realm)
                ],
                // 5 request per day
                'rate_limit' => 5,
                'rate_limit_decay' => 1440
            ],
            [
                'name' => 'add-event',
                'route' => '/api/v1/summits/{id}/events',
                'http_method' => 'POST',
                'scopes' => [sprintf(SummitScopes::WriteEventData, $current_realm)],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                    IGroup::SummitRegistrationAdmins,
                ]
            ],
            [
                'name' => 'update-event',
                'route' => '/api/v1/summits/{id}/events/{event_id}',
                'http_method' => 'PUT',
                'scopes' => [
                    sprintf(SummitScopes::WriteSummitData, $current_realm),
                    sprintf(SummitScopes::WriteEventData, $current_realm)
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                    IGroup::SummitRegistrationAdmins,
                    IGroup::TrackChairs,
                    IGroup::TrackChairsAdmins,
                ]
            ],
            [
                'name' => 'update-event-live-info',
                'route' => '/api/v1/summits/{id}/events/{event_id}/live-info',
                'http_method' => 'PUT',
                'scopes' => [sprintf(SummitScopes::WriteEventData, $current_realm)],
            ],
            [
                'name' => 'add-event-image',
                'route' => '/api/v1/summits/{id}/events/{event_id}/image',
                'http_method' => 'POST',
                'scopes' => [sprintf(SummitScopes::WriteEventData, $current_realm)],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                    IGroup::SummitRegistrationAdmins,
                ]
            ],
            [
                'name' => 'delete-event-image',
                'route' => '/api/v1/summits/{id}/events/{event_id}/image',
                'http_method' => 'DELETE',
                'scopes' => [sprintf(SummitScopes::WriteEventData, $current_realm)],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                    IGroup::SummitRegistrationAdmins,
                ]
            ],
            [
                'name' => 'clone-event',
                'route' => '/api/v1/summits/{id}/events/{event_id}/clone',
                'http_method' => 'POST',
                'scopes' => [sprintf(SummitScopes::WriteEventData, $current_realm)],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                    IGroup::SummitRegistrationAdmins,
                ]
            ],
            [
                'name' => 'update-events',
                'route' => '/api/v1/summits/{id}/events',
                'http_method' => 'PUT',
                'scopes' => [sprintf(SummitScopes::WriteEventData, $current_realm)],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                    IGroup::SummitRegistrationAdmins,
                ]
            ],
            [
                'name' => 'publish-event',
                'route' => '/api/v1/summits/{id}/events/{event_id}/publish',
                'http_method' => 'PUT',
                'scopes' => [sprintf(SummitScopes::PublishEventData, $current_realm)],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                    IGroup::SummitRegistrationAdmins,
                ]
            ],
            [
                'name' => 'publish-events',
                'route' => '/api/v1/summits/{id}/events/publish',
                'http_method' => 'PUT',
                'scopes' => [sprintf(SummitScopes::PublishEventData, $current_realm)],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                    IGroup::SummitRegistrationAdmins,
                ]
            ],
            [
                'name' => 'unpublish-event',
                'route' => '/api/v1/summits/{id}/events/{event_id}/publish',
                'http_method' => 'DELETE',
                'scopes' => [sprintf(SummitScopes::PublishEventData, $current_realm)],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                    IGroup::SummitRegistrationAdmins,
                ]
            ],
            [
                'name' => 'unpublish-events',
                'route' => '/api/v1/summits/{id}/events/publish',
                'http_method' => 'DELETE',
                'scopes' => [sprintf(SummitScopes::PublishEventData, $current_realm)],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                    IGroup::SummitRegistrationAdmins,
                ]
            ],
            [
                'name' => 'delete-event',
                'route' => '/api/v1/summits/{id}/events/{event_id}',
                'http_method' => 'DELETE',
                'scopes' => [sprintf('%s/summits/delete-event', $current_realm)],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                    IGroup::SummitRegistrationAdmins,
                ]
            ],
            [
                'name' => 'add-event-attachment',
                'route' => '/api/v1/summits/{id}/events/{event_id}/attachment',
                'http_method' => 'POST',
                'scopes' => [sprintf(SummitScopes::WriteSummitData, $current_realm)],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                    IGroup::SummitRegistrationAdmins,
                ]
            ],
            [
                'name' => 'add-event-feedback',
                'route' => '/api/v1/summits/{id}/members/{member_id}/schedule/{event_id}/feedback',
                'http_method' => 'POST',
                'scopes' => [
                    sprintf(SummitScopes::AddMyEventFeedback, $current_realm),
                ],
            ],
            [
                'name' => 'update-event-feedback',
                'route' => '/api/v1/summits/{id}/members/{member_id}/schedule/{event_id}/feedback',
                'http_method' => 'PUT',
                'scopes' => [
                    sprintf(SummitScopes::AddMyEventFeedback, $current_realm),
                ],
            ],
            [
                'name' => 'delete-my-event-feedback',
                'route' => '/api/v1/summits/{id}/members/{member_id}/schedule/{event_id}/feedback',
                'http_method' => 'DELETE',
                'scopes' => [
                    sprintf(SummitScopes::DeleteMyEventFeedback, $current_realm),
                ],
            ],
            [
                'name' => 'get-event-feedback-by-member',
                'route' => '/api/v1/summits/{id}/members/{member_id}/schedule/{event_id}/feedback',
                'http_method' => 'GET',
                'scopes' => [
                    sprintf(SummitScopes::MeRead, $current_realm),
                    sprintf(MemberScopes::ReadMyMemberData, $current_realm)
                ],
            ],
            [
                'name' => 'add-event-feedback-v2',
                'route' => '/api/v2/summits/{id}/events/{event_id}/feedback',
                'http_method' => 'POST',
                'scopes' => [
                    sprintf(SummitScopes::WriteSummitData, $current_realm),
                    sprintf(SummitScopes::AddMyEventFeedback, $current_realm)
                ],
            ],
            [
                'name' => 'update-event-feedback-v2',
                'route' => '/api/v2/summits/{id}/events/{event_id}/feedback',
                'http_method' => 'PUT',
                'scopes' => [
                    sprintf(SummitScopes::WriteSummitData, $current_realm),
                    sprintf(SummitScopes::AddMyEventFeedback, $current_realm),
                ],
            ],
            [
                'name' => 'get-event-feedback',
                'route' => '/api/v1/summits/{id}/events/{event_id}/feedback',
                'http_method' => 'GET',
                'scopes' => [
                    sprintf(SummitScopes::ReadAllSummitData, $current_realm),
                    sprintf(SummitScopes::ReadSummitData, $current_realm),
                ],
            ],
            [
                'name' => 'get-event-feedback-csv',
                'route' => '/api/v1/summits/{id}/events/{event_id}/feedback/csv',
                'http_method' => 'GET',
                'scopes' => [
                    sprintf(SummitScopes::ReadAllSummitData, $current_realm),
                    sprintf(SummitScopes::ReadSummitData, $current_realm),
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                ]
            ],
            [
                'name' => 'delete-event-feedback',
                'route' => '/api/v1/summits/{id}/events/{event_id}/feedback/{feedback_id}',
                'http_method' => 'DELETE',
                'scopes' => [
                    sprintf(SummitScopes::WriteSummitData, $current_realm),
                    sprintf(SummitScopes::WriteEventData, $current_realm)
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                ]
            ],
            [
                'name' => 'delete-rsvp',
                'route' => '/api/v1/summits/{id}/attendees/{attendee_id}/schedule/{event_id}/rsvp',
                'http_method' => 'DELETE',
                'scopes' => [
                    sprintf(SummitScopes::WriteSummitData, $current_realm),
                    sprintf(SummitScopes::DeleteMyRSVP, $current_realm),
                ],

            ],
            [
                'name' => 'upgrade-event',
                'route' => '/api/v1/summits/{id}/events/{event_id}/type/{type_id}/upgrade',
                'http_method' => 'PUT',
                'scopes' => [sprintf(SummitScopes::WriteEventData, $current_realm)],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                ]
            ],
            // locations
            [
                'name' => 'get-locations',
                'route' => '/api/v1/summits/{id}/locations',
                'http_method' => 'GET',
                'scopes' => [
                    sprintf(SummitScopes::ReadSummitData, $current_realm),
                    sprintf(SummitScopes::ReadAllSummitData, $current_realm)
                ],
            ],
            [
                'name' => 'add-location',
                'route' => '/api/v1/summits/{id}/locations',
                'http_method' => 'POST',
                'scopes' => [
                    sprintf(SummitScopes::WriteSummitData, $current_realm),
                    sprintf(SummitScopes::WriteLocationsData, $current_realm)
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                ]
            ],
            [
                'name' => 'update-location',
                'route' => '/api/v1/summits/{id}/locations/{location_id}',
                'http_method' => 'PUT',
                'scopes' => [
                    sprintf(SummitScopes::WriteSummitData, $current_realm),
                    sprintf(SummitScopes::WriteLocationsData, $current_realm)
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                ]
            ],
            [
                'name' => 'delete-location',
                'route' => '/api/v1/summits/{id}/locations/{location_id}',
                'http_method' => 'DELETE',
                'scopes' => [
                    sprintf(SummitScopes::WriteSummitData, $current_realm),
                    sprintf(SummitScopes::WriteLocationsData, $current_realm)
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                ]
            ],
            [
                'name' => 'get-locations-metadata',
                'route' => '/api/v1/summits/{id}/locations/metadata',
                'http_method' => 'GET',
                'scopes' => [
                    sprintf(SummitScopes::ReadSummitData, $current_realm),
                    sprintf(SummitScopes::ReadAllSummitData, $current_realm)
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                ]
            ],
            // maps
            [
                'name' => 'add-location-map',
                'route' => '/api/v1/summits/{id}/locations/{location_id}/maps',
                'http_method' => 'POST',
                'scopes' => [
                    sprintf(SummitScopes::WriteSummitData, $current_realm),
                    sprintf(SummitScopes::WriteLocationsData, $current_realm)
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                ]
            ],
            [
                'name' => 'update-location-map',
                'route' => '/api/v1/summits/{id}/locations/{location_id}/maps/{map_id}',
                'http_method' => 'PUT',
                'scopes' => [
                    sprintf(SummitScopes::WriteSummitData, $current_realm),
                    sprintf(SummitScopes::WriteLocationsData, $current_realm)
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                ]
            ],
            [
                'name' => 'get-location-map',
                'route' => '/api/v1/summits/{id}/locations/{location_id}/maps/{map_id}',
                'http_method' => 'GET',
                'scopes' => [
                    sprintf(SummitScopes::ReadSummitData, $current_realm),
                    sprintf(SummitScopes::ReadAllSummitData, $current_realm)
                ],
            ],
            [
                'name' => 'delete-location-map',
                'route' => '/api/v1/summits/{id}/locations/{location_id}/maps/{map_id}',
                'http_method' => 'DELETE',
                'scopes' => [
                    sprintf(SummitScopes::WriteSummitData, $current_realm),
                    sprintf(SummitScopes::WriteLocationsData, $current_realm)
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                ]
            ],
            // images
            [
                'name' => 'add-location-image',
                'route' => '/api/v1/summits/{id}/locations/{location_id}/images',
                'http_method' => 'POST',
                'scopes' => [
                    sprintf(SummitScopes::WriteSummitData, $current_realm),
                    sprintf(SummitScopes::WriteLocationsData, $current_realm)
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                ]
            ],
            [
                'name' => 'update-location-image',
                'route' => '/api/v1/summits/{id}/locations/{location_id}/images/{image_id}',
                'http_method' => 'PUT',
                'scopes' => [
                    sprintf(SummitScopes::WriteSummitData, $current_realm),
                    sprintf(SummitScopes::WriteLocationsData, $current_realm)
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                ]
            ],
            [
                'name' => 'get-location-image',
                'route' => '/api/v1/summits/{id}/locations/{location_id}/images/{image_id}',
                'http_method' => 'GET',
                'scopes' => [
                    sprintf(SummitScopes::ReadSummitData, $current_realm),
                    sprintf(SummitScopes::ReadAllSummitData, $current_realm)
                ],
            ],
            [
                'name' => 'delete-location-image',
                'route' => '/api/v1/summits/{id}/locations/{location_id}/images/{image_id}',
                'http_method' => 'DELETE',
                'scopes' => [
                    sprintf(SummitScopes::WriteSummitData, $current_realm),
                    sprintf(SummitScopes::WriteLocationsData, $current_realm)
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                ]
            ],
            // banners
            [
                'name' => 'get-location-banners',
                'route' => '/api/v1/summits/{id}/locations/{location_id}/banners',
                'http_method' => 'GET',
                'scopes' => [
                    sprintf(SummitScopes::ReadSummitData, $current_realm),
                    sprintf(SummitScopes::ReadAllSummitData, $current_realm)
                ],
            ],
            [
                'name' => 'add-location-banner',
                'route' => '/api/v1/summits/{id}/locations/{location_id}/banners',
                'http_method' => 'POST',
                'scopes' => [
                    sprintf(SummitScopes::WriteSummitData, $current_realm),
                    sprintf(SummitScopes::WriteLocationsData, $current_realm),
                    sprintf(SummitScopes::WriteLocationBannersData, $current_realm)
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                ]
            ],
            [
                'name' => 'update-location-banner',
                'route' => '/api/v1/summits/{id}/locations/{location_id}/banners/{banner_id}',
                'http_method' => 'PUT',
                'scopes' => [
                    sprintf(SummitScopes::WriteSummitData, $current_realm),
                    sprintf(SummitScopes::WriteLocationsData, $current_realm),
                    sprintf(SummitScopes::WriteLocationBannersData, $current_realm)
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                ]
            ],
            [
                'name' => 'delete-location-banner',
                'route' => '/api/v1/summits/{id}/locations/{location_id}/banners/{banner_id}',
                'http_method' => 'DELETE',
                'scopes' => [
                    sprintf(SummitScopes::WriteSummitData, $current_realm),
                    sprintf(SummitScopes::WriteLocationsData, $current_realm),
                    sprintf(SummitScopes::WriteLocationBannersData, $current_realm)
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                ]
            ],
            [
                'name' => 'copy-location',
                'route' => '/api/v1/summits/{id}/locations/copy/{target_summit_id}',
                'http_method' => 'POST',
                'scopes' => [
                    sprintf(SummitScopes::WriteSummitData, $current_realm),
                    sprintf(SummitScopes::WriteLocationsData, $current_realm),
                    sprintf(SummitScopes::WriteLocationBannersData, $current_realm)
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                ]
            ],
            // venues
            [
                'name' => 'get-venues',
                'route' => '/api/v1/summits/{id}/locations/venues',
                'http_method' => 'GET',
                'scopes' => [
                    sprintf(SummitScopes::ReadSummitData, $current_realm),
                    sprintf(SummitScopes::ReadAllSummitData, $current_realm)
                ],
            ],
            [
                'name' => 'get-all-venues-rooms',
                'route' => '/api/v1/summits/{id}/locations/venues/all/rooms',
                'http_method' => 'GET',
                'scopes' => [
                    sprintf(SummitScopes::ReadSummitData, $current_realm),
                    sprintf(SummitScopes::ReadAllSummitData, $current_realm)
                ],
            ],
            [
                'name' => 'get-all-venues-bookable-rooms',
                'route' => '/api/v1/summits/{id}/locations/venues/all/bookable-rooms',
                'http_method' => 'GET',
                'scopes' => [
                    sprintf(SummitScopes::ReadSummitData, $current_realm),
                    sprintf(SummitScopes::ReadAllSummitData, $current_realm)
                ],
            ],
            [
                'name' => 'add-venue',
                'route' => '/api/v1/summits/{id}/locations/venues',
                'http_method' => 'POST',
                'scopes' => [
                    sprintf(SummitScopes::WriteSummitData, $current_realm),
                    sprintf(SummitScopes::WriteLocationsData, $current_realm)
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                ]
            ],
            [
                'name' => 'update-venue',
                'route' => '/api/v1/summits/{id}/locations/venues/{venue_id}',
                'http_method' => 'PUT',
                'scopes' => [
                    sprintf(SummitScopes::WriteSummitData, $current_realm),
                    sprintf(SummitScopes::WriteLocationsData, $current_realm)
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                ]
            ],
            // floors
            [
                'name' => 'get-venue-floor',
                'route' => '/api/v1/summits/{id}/locations/venues/{venue_id}/floors/{floor_id}',
                'http_method' => 'GET',
                'scopes' => [
                    sprintf(SummitScopes::ReadSummitData, $current_realm),
                    sprintf(SummitScopes::ReadAllSummitData, $current_realm)
                ],
            ],
            [
                'name' => 'add-venue-floor',
                'route' => '/api/v1/summits/{id}/locations/venues/{venue_id}/floors',
                'http_method' => 'POST',
                'scopes' => [
                    sprintf(SummitScopes::WriteSummitData, $current_realm),
                    sprintf(SummitScopes::WriteLocationsData, $current_realm)
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                ]
            ],
            [
                'name' => 'update-venue-floor',
                'route' => '/api/v1/summits/{id}/locations/venues/{venue_id}/floors/{floor_id}',
                'http_method' => 'PUT',
                'scopes' => [
                    sprintf(SummitScopes::WriteSummitData, $current_realm),
                    sprintf(SummitScopes::WriteLocationsData, $current_realm)
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                ]
            ],
            [
                'name' => 'delete-venue-floor',
                'route' => '/api/v1/summits/{id}/locations/venues/{venue_id}/floors/{floor_id}',
                'http_method' => 'DELETE',
                'scopes' => [
                    sprintf(SummitScopes::WriteSummitData, $current_realm),
                    sprintf(SummitScopes::WriteLocationsData, $current_realm)
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                ]
            ],
            // rsvp templates
            [
                'name' => 'get-rsvp-templates',
                'route' => '/api/v1/summits/{id}/rsvp-templates',
                'http_method' => 'GET',
                'scopes' => [
                    sprintf(SummitScopes::ReadAllSummitData, $current_realm)
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                ]
            ],
            [
                'name' => 'get-rsvp-template',
                'route' => '/api/v1/summits/{id}/rsvp-templates/{template_id}',
                'http_method' => 'GET',
                'scopes' => [
                    sprintf(SummitScopes::ReadAllSummitData, $current_realm)
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                ]
            ],
            [
                'name' => 'add-rsvp-template',
                'route' => '/api/v1/summits/{id}/rsvp-templates',
                'http_method' => 'POST',
                'scopes' => [
                    sprintf(SummitScopes::WriteSummitData, $current_realm),
                    sprintf(SummitScopes::WriteRSVPTemplateData, $current_realm)
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                ]
            ],
            [
                'name' => 'get-rsvp-template-question-metadata',
                'route' => '/api/v1/summits/{id}/rsvp-templates/questions/metadata',
                'http_method' => 'GET',
                'scopes' => [
                    sprintf(SummitScopes::ReadAllSummitData, $current_realm)
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                ]
            ],
            [
                'name' => 'update-rsvp-template',
                'route' => '/api/v1/summits/{id}/rsvp-templates/{template_id}',
                'http_method' => 'PUT',
                'scopes' => [
                    sprintf(SummitScopes::WriteSummitData, $current_realm),
                    sprintf(SummitScopes::WriteRSVPTemplateData, $current_realm)
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                ]
            ],
            [
                'name' => 'delete-rsvp-template',
                'route' => '/api/v1/summits/{id}/rsvp-templates/{template_id}',
                'http_method' => 'DELETE',
                'scopes' => [
                    sprintf(SummitScopes::WriteSummitData, $current_realm),
                    sprintf(SummitScopes::WriteRSVPTemplateData, $current_realm)
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                ]
            ],
            // rsvp template questions
            [
                'name' => 'get-rsvp-template-question',
                'route' => '/api/v1/summits/{id}/rsvp-templates/{template_id}/questions/{question_id}',
                'http_method' => 'GET',
                'scopes' => [
                    sprintf(SummitScopes::ReadAllSummitData, $current_realm)
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                ]
            ],
            [
                'name' => 'add-rsvp-template-question',
                'route' => '/api/v1/summits/{id}/rsvp-templates/{template_id}/questions',
                'http_method' => 'POST',
                'scopes' => [
                    sprintf(SummitScopes::WriteSummitData, $current_realm),
                    sprintf(SummitScopes::WriteRSVPTemplateData, $current_realm)
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                ]
            ],
            [
                'name' => 'update-rsvp-template-question',
                'route' => '/api/v1/summits/{id}/rsvp-templates/{template_id}/questions/{question_id}',
                'http_method' => 'PUT',
                'scopes' => [
                    sprintf(SummitScopes::WriteSummitData, $current_realm),
                    sprintf(SummitScopes::WriteRSVPTemplateData, $current_realm)
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                ]
            ],
            [
                'name' => 'delete-rsvp-template-question',
                'route' => '/api/v1/summits/{id}/rsvp-templates/{template_id}/questions/{question_id}',
                'http_method' => 'DELETE',
                'scopes' => [
                    sprintf(SummitScopes::WriteSummitData, $current_realm),
                    sprintf(SummitScopes::WriteRSVPTemplateData, $current_realm)
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                ]
            ],
            // multi value questions
            [
                'name' => 'add-rsvp-template-question-value',
                'route' => '/api/v1/summits/{id}/rsvp-templates/{template_id}/questions/{question_id}/values',
                'http_method' => 'POST',
                'scopes' => [
                    sprintf(SummitScopes::WriteSummitData, $current_realm),
                    sprintf(SummitScopes::WriteRSVPTemplateData, $current_realm)
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                ]
            ],
            [
                'name' => 'get-rsvp-template-question-value',
                'route' => '/api/v1/summits/{id}/rsvp-templates/{template_id}/questions/{question_id}/values/{value_id}',
                'http_method' => 'GET',
                'scopes' => [
                    sprintf(SummitScopes::ReadAllSummitData, $current_realm)
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                ]
            ],
            [
                'name' => 'update-rsvp-template-question-value',
                'route' => '/api/v1/summits/{id}/rsvp-templates/{template_id}/questions/{question_id}/values/{value_id}',
                'http_method' => 'PUT',
                'scopes' => [
                    sprintf(SummitScopes::WriteSummitData, $current_realm),
                    sprintf(SummitScopes::WriteRSVPTemplateData, $current_realm)
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                ]
            ],
            [
                'name' => 'delete-rsvp-template-question-value',
                'route' => '/api/v1/summits/{id}/rsvp-templates/{template_id}/questions/{question_id}/values/{value_id}',
                'http_method' => 'DELETE',
                'scopes' => [
                    sprintf(SummitScopes::WriteSummitData, $current_realm),
                    sprintf(SummitScopes::WriteRSVPTemplateData, $current_realm)
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                ]
            ],
            // rooms
            [
                'name' => 'get-venue-room',
                'route' => '/api/v1/summits/{id}/locations/venues/{venue_id}/rooms/{room_id}',
                'http_method' => 'GET',
                'scopes' => [
                    sprintf(SummitScopes::ReadSummitData, $current_realm),
                    sprintf(SummitScopes::ReadAllSummitData, $current_realm)
                ],
            ],
            [
                'name' => 'add-venue-room',
                'route' => '/api/v1/summits/{id}/locations/venues/{venue_id}/rooms',
                'http_method' => 'POST',
                'scopes' => [
                    sprintf(SummitScopes::WriteSummitData, $current_realm),
                    sprintf(SummitScopes::WriteLocationsData, $current_realm)
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                ]
            ],
            [
                'name' => 'add-venue-room-image',
                'route' => '/api/v1/summits/{id}/locations/venues/{venue_id}/rooms/{room_id}/image',
                'http_method' => 'POST',
                'scopes' => [
                    sprintf(SummitScopes::WriteSummitData, $current_realm),
                    sprintf(SummitScopes::WriteLocationsData, $current_realm)
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                ]
            ],
            [
                'name' => 'remove-venue-room-image',
                'route' => '/api/v1/summits/{id}/locations/venues/{venue_id}/rooms/{room_id}/image',
                'http_method' => 'DELETE',
                'scopes' => [
                    sprintf(SummitScopes::WriteSummitData, $current_realm),
                    sprintf(SummitScopes::WriteLocationsData, $current_realm)
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                ]
            ],
            [
                'name' => 'update-venue-room',
                'route' => '/api/v1/summits/{id}/locations/venues/{venue_id}/rooms/{room_id}',
                'http_method' => 'PUT',
                'scopes' => [
                    sprintf(SummitScopes::WriteSummitData, $current_realm),
                    sprintf(SummitScopes::WriteLocationsData, $current_realm)
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                ]
            ],
            [
                'name' => 'delete-venue-room',
                'route' => '/api/v1/summits/{id}/locations/venues/{venue_id}/rooms/{room_id}',
                'http_method' => 'DELETE',
                'scopes' => [
                    sprintf(SummitScopes::WriteSummitData, $current_realm),
                    sprintf(SummitScopes::WriteLocationsData, $current_realm)
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                ]
            ],
            // bookable rooms
            [
                'name' => 'get-bookable-venue-rooms',
                'route' => '/api/v1/summits/{id}/locations/bookable-rooms',
                'http_method' => 'GET',
                'scopes' => [
                    sprintf(SummitScopes::ReadBookableRoomsData, $current_realm),
                    sprintf(SummitScopes::ReadAllSummitData, $current_realm)
                ],
            ],
            [
                'name' => 'get-bookable-venue-room',
                'route' => '/api/v1/summits/{id}/locations/bookable-rooms/{room_id}',
                'http_method' => 'GET',
                'scopes' => [
                    sprintf(SummitScopes::ReadBookableRoomsData, $current_realm),
                    sprintf(SummitScopes::ReadAllSummitData, $current_realm)
                ],
            ],
            [
                'name' => 'get-bookable-venue-room-availability',
                'route' => '/api/v1/summits/{id}/locations/bookable-rooms/{room_id}/availability/{day}',
                'http_method' => 'GET',
                'scopes' => [
                    sprintf(SummitScopes::ReadBookableRoomsData, $current_realm),
                    sprintf(SummitScopes::ReadAllSummitData, $current_realm)
                ],
            ],
            [
                'name' => 'get-my-bookable-venue-room-reservations',
                'route' => '/api/v1/summits/{id}/locations/bookable-rooms/all/reservations/me',
                'http_method' => 'GET',
                'scopes' => [
                    sprintf(SummitScopes::ReadMyBookableRoomsReservationData, $current_realm),
                ],
            ],
            [
                'name' => 'get-bookable-venue-room-reservations-by-id',
                'route' => '/api/v1/summits/all/locations/bookable-rooms/all/reservations/{id}',
                'http_method' => 'GET',
                'scopes' => [
                    sprintf(SummitScopes::ReadBookableRoomsData, $current_realm),
                    sprintf(SummitScopes::ReadAllSummitData, $current_realm)
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                    IGroup::SummitRoomAdministrators,
                ]
            ],
            [
                'name' => 'cancel-my-bookable-venue-room-reservation',
                'route' => '/api/v1/summits/{id}/locations/bookable-rooms/all/reservations/{reservation_id}',
                'http_method' => 'DELETE',
                'scopes' => [
                    sprintf(SummitScopes::WriteMyBookableRoomsReservationData, $current_realm),
                ],
            ],
            [
                'name' => 'refund-bookable-venue-room-reservation',
                'route' => '/api/v1/summits/{id}/locations/bookable-rooms/{room_id}/reservations/{reservation_id}/refund',
                'http_method' => 'DELETE',
                'scopes' => [
                    sprintf(SummitScopes::WriteSummitData, $current_realm),
                    sprintf(SummitScopes::WriteBookableRoomsData, $current_realm),
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                ]
            ],
            [
                'name' => 'cancel-bookable-venue-room-reservation',
                'route' => '/api/v1/summits/{id}/locations/bookable-rooms/{room_id}/reservations/{reservation_id}/cancel',
                'http_method' => 'DELETE',
                'scopes' => [
                    sprintf(SummitScopes::WriteSummitData, $current_realm),
                    sprintf(SummitScopes::WriteBookableRoomsData, $current_realm),
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                ]
            ],
            [
                'name' => 'get-bookable-venue-room-reservations-by-summit',
                'route' => '/api/v1/summits/{id}/locations/bookable-rooms/all/reservations',
                'http_method' => 'GET',
                'scopes' => [
                    sprintf(SummitScopes::ReadAllSummitData, $current_realm),
                    sprintf(SummitScopes::ReadSummitData, $current_realm),
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                ]
            ],
            [
                'name' => 'get-bookable-venue-room-reservations-by-summit-csv',
                'route' => '/api/v1/summits/{id}/locations/bookable-rooms/all/reservations/csv',
                'http_method' => 'GET',
                'scopes' => [
                    sprintf(SummitScopes::ReadAllSummitData, $current_realm),
                    sprintf(SummitScopes::ReadSummitData, $current_realm),
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                ]
            ],
            [
                'name' => 'get-bookable-venue-room-reservation',
                'route' => '/api/v1/summits/{id}/locations/bookable-rooms/{room_id}/reservations/{reservation_id}',
                'http_method' => 'GET',
                'scopes' => [
                    sprintf(SummitScopes::ReadAllSummitData, $current_realm),
                    sprintf(SummitScopes::ReadSummitData, $current_realm),
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                ]
            ],
            [
                'name' => 'create-bookable-venue-room-reservation',
                'route' => '/api/v1/summits/{id}/locations/bookable-rooms/{room_id}/reservations',
                'http_method' => 'POST',
                'scopes' => [
                    sprintf(SummitScopes::WriteMyBookableRoomsReservationData, $current_realm),
                ],
            ],
            [
                'name' => 'create-offline-bookable-venue-room-reservation',
                'route' => '/api/v1/summits/{id}/locations/bookable-rooms/{room_id}/reservations/offline',
                'http_method' => 'POST',
                'scopes' => [
                    sprintf(SummitScopes::WriteSummitData, $current_realm),
                    sprintf(SummitScopes::WriteBookableRoomsData, $current_realm),
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                ]
            ],
            [
                'name' => 'update-bookable-venue-room-reservation',
                'route' => '/api/v1/summits/{id}/locations/bookable-rooms/{room_id}/reservations/{reservation_id}',
                'http_method' => 'PUT',
                'scopes' => [
                    sprintf(SummitScopes::WriteSummitData, $current_realm),
                    sprintf(SummitScopes::WriteBookableRoomsData, $current_realm),
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                ]
            ],
            [
                'name' => 'add-bookable-venue-room',
                'route' => '/api/v1/summits/{id}/locations/venues/{venue_id}/bookable-rooms',
                'http_method' => 'POST',
                'scopes' => [
                    sprintf(SummitScopes::WriteSummitData, $current_realm),
                    sprintf(SummitScopes::WriteBookableRoomsData, $current_realm),
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                ]
            ],
            [
                'name' => 'update-bookable-venue-room',
                'route' => '/api/v1/summits/{id}/locations/venues/{venue_id}/bookable-rooms/{room_id}',
                'http_method' => 'PUT',
                'scopes' => [
                    sprintf(SummitScopes::WriteSummitData, $current_realm),
                    sprintf(SummitScopes::WriteBookableRoomsData, $current_realm),
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                ]
            ],
            [
                'name' => 'delete-bookable-venue-room',
                'route' => '/api/v1/summits/{id}/locations/venues/{venue_id}/bookable-rooms/{room_id}',
                'http_method' => 'DELETE',
                'scopes' => [
                    sprintf(SummitScopes::WriteSummitData, $current_realm),
                    sprintf(SummitScopes::WriteBookableRoomsData, $current_realm),
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                ]
            ],
            // bookable room attributes
            [
                'name' => 'add-bookable-venue-room-attribute',
                'route' => '/api/v1/summits/{id}/locations/venues/{venue_id}/bookable-rooms/{room_id}/attributes/{attribute_id}',
                'http_method' => 'PUT',
                'scopes' => [
                    sprintf(SummitScopes::WriteSummitData, $current_realm),
                    sprintf(SummitScopes::WriteBookableRoomsData, $current_realm),
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                ]
            ],
            [
                'name' => 'remove-bookable-venue-room-attribute',
                'route' => '/api/v1/summits/{id}/locations/venues/{venue_id}/bookable-rooms/{room_id}/attributes/{attribute_id}',
                'http_method' => 'DELETE',
                'scopes' => [
                    sprintf(SummitScopes::WriteSummitData, $current_realm),
                    sprintf(SummitScopes::WriteBookableRoomsData, $current_realm),
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                ]
            ],
            // floor rooms
            [
                'name' => 'get-venue-floor-room',
                'route' => '/api/v1/summits/{id}/locations/venues/{venue_id}/floors/{floor_id}/rooms/{room_id}',
                'http_method' => 'GET',
                'scopes' => [
                    sprintf(SummitScopes::ReadSummitData, $current_realm),
                    sprintf(SummitScopes::ReadAllSummitData, $current_realm)
                ],
            ],
            [
                'name' => 'add-venue-floor-room',
                'route' => '/api/v1/summits/{id}/locations/venues/{venue_id}/floors/{floor_id}/rooms',
                'http_method' => 'POST',
                'scopes' => [
                    sprintf(SummitScopes::WriteSummitData, $current_realm),
                    sprintf(SummitScopes::WriteLocationsData, $current_realm)
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                ]
            ],
            [
                'name' => 'update-venue-floor-room',
                'route' => '/api/v1/summits/{id}/locations/venues/{venue_id}/floors/{floor_id}/rooms/{room_id}',
                'http_method' => 'PUT',
                'scopes' => [
                    sprintf(SummitScopes::WriteSummitData, $current_realm),
                    sprintf(SummitScopes::WriteLocationsData, $current_realm)
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                ]
            ],
            [
                'name' => 'add-venue-floor-image',
                'route' => '/api/v1/summits/{id}/locations/venues/{venue_id}/floors/{floor_id}/image',
                'http_method' => 'POST',
                'scopes' => [
                    sprintf(SummitScopes::WriteSummitData, $current_realm),
                    sprintf(SummitScopes::WriteLocationsData, $current_realm)
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                ]
            ],
            [
                'name' => 'remove-venue-floor-image',
                'route' => '/api/v1/summits/{id}/locations/venues/{venue_id}/floors/{floor_id}/image',
                'http_method' => 'DELETE',
                'scopes' => [
                    sprintf(SummitScopes::WriteSummitData, $current_realm),
                    sprintf(SummitScopes::WriteLocationsData, $current_realm)
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                ]
            ],
            [
                'name' => 'get-venue-floor-bookable-room',
                'route' => '/api/v1/summits/{id}/locations/venues/{venue_id}/floors/{floor_id}/bookable-rooms/{room_id}',
                'http_method' => 'GET',
                'scopes' => [
                    sprintf(SummitScopes::ReadBookableRoomsData, $current_realm),
                    sprintf(SummitScopes::ReadAllSummitData, $current_realm)
                ],
            ],
            [
                'name' => 'add-venue-floor-bookable-room',
                'route' => '/api/v1/summits/{id}/locations/venues/{venue_id}/floors/{floor_id}/bookable-rooms',
                'http_method' => 'POST',
                'scopes' => [
                    sprintf(SummitScopes::WriteBookableRoomsData, $current_realm),
                    sprintf(SummitScopes::WriteLocationsData, $current_realm),
                    sprintf(SummitScopes::WriteSummitData, $current_realm)
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                ]
            ],
            [
                'name' => 'update-venue-floor-bookable-room',
                'route' => '/api/v1/summits/{id}/locations/venues/{venue_id}/floors/{floor_id}/bookable-rooms/{room_id}',
                'http_method' => 'PUT',
                'scopes' => [
                    sprintf(SummitScopes::WriteBookableRoomsData, $current_realm),
                    sprintf(SummitScopes::WriteLocationsData, $current_realm),
                    sprintf(SummitScopes::WriteSummitData, $current_realm)
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                ]
            ],
            // external locations
            [
                'name' => 'get-external-locations',
                'route' => '/api/v1/summits/{id}/locations/external-locations',
                'http_method' => 'GET',
                'scopes' => [
                    sprintf(SummitScopes::ReadSummitData, $current_realm),
                    sprintf(SummitScopes::ReadAllSummitData, $current_realm)
                ],
            ],
            [
                'name' => 'add-external-location',
                'route' => '/api/v1/summits/{id}/locations/external-locations',
                'http_method' => 'POST',
                'scopes' => [
                    sprintf(SummitScopes::WriteSummitData, $current_realm),
                    sprintf(SummitScopes::WriteLocationsData, $current_realm)
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                ]
            ],
            [
                'name' => 'update-external-location',
                'route' => '/api/v1/summits/{id}/locations/external-locations/{external_location_id}',
                'http_method' => 'PUT',
                'scopes' => [
                    sprintf(SummitScopes::WriteSummitData, $current_realm),
                    sprintf(SummitScopes::WriteLocationsData, $current_realm)
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                ]
            ],
            [
                'name' => 'get-hotels',
                'route' => '/api/v1/summits/{id}/locations/hotels',
                'http_method' => 'GET',
                'scopes' => [
                    sprintf(SummitScopes::ReadSummitData, $current_realm),
                    sprintf(SummitScopes::ReadAllSummitData, $current_realm)
                ],
            ],
            [
                'name' => 'add-hotel',
                'route' => '/api/v1/summits/{id}/locations/hotels',
                'http_method' => 'POST',
                'scopes' => [
                    sprintf(SummitScopes::WriteSummitData, $current_realm),
                    sprintf(SummitScopes::WriteLocationsData, $current_realm)
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                ]
            ],
            [
                'name' => 'update-hotel',
                'route' => '/api/v1/summits/{id}/locations/hotels/{hotel_id}',
                'http_method' => 'PUT',
                'scopes' => [
                    sprintf(SummitScopes::WriteSummitData, $current_realm),
                    sprintf(SummitScopes::WriteLocationsData, $current_realm)
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                ]
            ],
            [
                'name' => 'get-airports',
                'route' => '/api/v1/summits/{id}/locations/airports',
                'http_method' => 'GET',
                'scopes' => [
                    sprintf(SummitScopes::ReadSummitData, $current_realm),
                    sprintf(SummitScopes::ReadAllSummitData, $current_realm)
                ],
            ],
            [
                'name' => 'add-airport',
                'route' => '/api/v1/summits/{id}/locations/airports',
                'http_method' => 'POST',
                'scopes' => [
                    sprintf(SummitScopes::WriteSummitData, $current_realm),
                    sprintf(SummitScopes::WriteLocationsData, $current_realm)
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                ]
            ],
            [
                'name' => 'update-airport',
                'route' => '/api/v1/summits/{id}/locations/airports/{airport_id}',
                'http_method' => 'PUT',
                'scopes' => [
                    sprintf(SummitScopes::WriteSummitData, $current_realm),
                    sprintf(SummitScopes::WriteLocationsData, $current_realm)
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                ]
            ],
            [
                'name' => 'get-location',
                'route' => '/api/v1/summits/{id}/locations/{location_id}',
                'http_method' => 'GET',
                'scopes' => [
                    sprintf(SummitScopes::ReadSummitData, $current_realm),
                    sprintf(SummitScopes::ReadAllSummitData, $current_realm)
                ],
            ],
            [
                'name' => 'get-location-events',
                'route' => '/api/v1/summits/{id}/locations/{location_id}/events',
                'http_method' => 'GET',
                'scopes' => [
                    sprintf(SummitScopes::ReadSummitData, $current_realm),
                    sprintf(SummitScopes::ReadAllSummitData, $current_realm)
                ],
            ],
            [
                'name' => 'get-location-published-events',
                'route' => '/api/v1/summits/{id}/locations/{location_id}/events/published',
                'http_method' => 'GET',
                'scopes' => [
                    sprintf(SummitScopes::ReadSummitData, $current_realm),
                    sprintf(SummitScopes::ReadAllSummitData, $current_realm)
                ],
            ],
            // event types
            [
                'name' => 'get-event-types',
                'route' => '/api/v1/summits/{id}/event-types',
                'http_method' => 'GET',
                'scopes' => [
                    sprintf(SummitScopes::ReadSummitData, $current_realm),
                    sprintf(SummitScopes::ReadAllSummitData, $current_realm)
                ],
            ],
            [
                'name' => 'get-event-types-csv',
                'route' => '/api/v1/summits/{id}/event-types/csv',
                'http_method' => 'GET',
                'scopes' => [
                    sprintf(SummitScopes::ReadSummitData, $current_realm),
                    sprintf(SummitScopes::ReadAllSummitData, $current_realm)
                ],
            ],
            [
                'name' => 'get-event-type-by-id',
                'route' => '/api/v1/summits/{id}/event-types/{event_type_id}',
                'http_method' => 'GET',
                'scopes' => [
                    sprintf(SummitScopes::ReadSummitData, $current_realm),
                    sprintf(SummitScopes::ReadAllSummitData, $current_realm)
                ],
            ],
            [
                'name' => 'add-event-type',
                'route' => '/api/v1/summits/{id}/event-types',
                'http_method' => 'POST',
                'scopes' => [
                    sprintf(SummitScopes::WriteEventTypeData, $current_realm),
                    sprintf(SummitScopes::WriteSummitData, $current_realm)
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                ]
            ],
            [
                'name' => 'seed-default-event-types',
                'route' => '/api/v1/summits/{id}/event-types/seed-defaults',
                'http_method' => 'POST',
                'scopes' => [
                    sprintf(SummitScopes::WriteEventTypeData, $current_realm),
                    sprintf(SummitScopes::WriteSummitData, $current_realm)
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                ]
            ],
            [
                'name' => 'update-event-type',
                'route' => '/api/v1/summits/{id}/event-types/{event_type_id}',
                'http_method' => 'PUT',
                'scopes' => [
                    sprintf(SummitScopes::WriteEventTypeData, $current_realm),
                    sprintf(SummitScopes::WriteSummitData, $current_realm)
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                ]
            ],
            [
                'name' => 'delete-event-type',
                'route' => '/api/v1/summits/{id}/event-types/{event_type_id}',
                'http_method' => 'DELETE',
                'scopes' => [
                    sprintf(SummitScopes::WriteEventTypeData, $current_realm),
                    sprintf(SummitScopes::WriteSummitData, $current_realm)
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                ]
            ],

            [
                'name' => 'add-document-2-event-type',
                'route' => '/api/v1/summits/{id}/event-types/{event_type_id}/summit-documents/{document_id}',
                'http_method' => 'PUT',
                'scopes' => [
                    sprintf(SummitScopes::WriteEventTypeData, $current_realm),
                    sprintf(SummitScopes::WriteSummitData, $current_realm)
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                ]
            ],
            [
                'name' => 'remove-document-from-event-type',
                'route' => '/api/v1/summits/{id}/event-types/{event_type_id}/summit-documents/{document_id}',
                'http_method' => 'DELETE',
                'scopes' => [
                    sprintf(SummitScopes::WriteEventTypeData, $current_realm),
                    sprintf(SummitScopes::WriteSummitData, $current_realm)
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                ]
            ],
            // tracks-chairs
            [
                'name' => 'get-tracks-chairs',
                'route' => '/api/v1/summits/{id}/track-chairs',
                'http_method' => 'GET',
                'scopes' => [
                    sprintf(SummitScopes::ReadSummitData, $current_realm),
                    sprintf(SummitScopes::ReadAllSummitData, $current_realm)
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                    IGroup::TrackChairsAdmins,
                    IGroup::TrackChairs
                ]
            ],
            [
                'name' => 'get-tracks-chairs-csv',
                'route' => '/api/v1/summits/{id}/track-chairs/csv',
                'http_method' => 'GET',
                'scopes' => [
                    sprintf(SummitScopes::ReadSummitData, $current_realm),
                    sprintf(SummitScopes::ReadAllSummitData, $current_realm)
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                    IGroup::TrackChairsAdmins,
                ]
            ],
            [
                'name' => 'add-tracks-chairs',
                'route' => '/api/v1/summits/{id}/track-chairs',
                'http_method' => 'POST',
                'scopes' => [
                    sprintf(SummitScopes::WriteSummitData, $current_realm),
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                    IGroup::TrackChairsAdmins,
                ]
            ],
            [
                'name' => 'get-tracks-chair',
                'route' => '/api/v1/summits/{id}/track-chairs/{track_chair_id}',
                'http_method' => 'GET',
                'scopes' => [
                    sprintf(SummitScopes::ReadSummitData, $current_realm),
                    sprintf(SummitScopes::ReadAllSummitData, $current_realm)
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                    IGroup::TrackChairsAdmins,
                ]
            ],
            [
                'name' => 'delete-tracks-chair',
                'route' => '/api/v1/summits/{id}/track-chairs/{track_chair_id}',
                'http_method' => 'DELETE',
                'scopes' => [
                    sprintf(SummitScopes::WriteSummitData, $current_realm),
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                    IGroup::TrackChairsAdmins,
                ]
            ],
            [
                'name' => 'update-tracks-chair',
                'route' => '/api/v1/summits/{id}/track-chairs/{track_chair_id}',
                'http_method' => 'PUT',
                'scopes' => [
                    sprintf(SummitScopes::WriteSummitData, $current_realm),
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                    IGroup::TrackChairsAdmins,
                ]
            ],
            [
                'name' => 'add-track-2-track-chair',
                'route' => '/api/v1/summits/{id}/track-chairs/{track_chair_id}/categories/{track_id}',
                'http_method' => 'POST',
                'scopes' => [
                    sprintf(SummitScopes::WriteSummitData, $current_realm),
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                    IGroup::TrackChairsAdmins,
                ]
            ],
            [
                'name' => 'remove-track-from-track-chair',
                'route' => '/api/v1/summits/{id}/track-chairs/{track_chair_id}/categories/{track_id}',
                'http_method' => 'DELETE',
                'scopes' => [
                    sprintf(SummitScopes::WriteSummitData, $current_realm),
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                    IGroup::TrackChairsAdmins,
                ]
            ],
            //tracks
            [
                'name' => 'get-tracks',
                'route' => '/api/v1/summits/{id}/tracks',
                'http_method' => 'GET',
                'scopes' => [
                    sprintf(SummitScopes::ReadSummitData, $current_realm),
                    sprintf(SummitScopes::ReadAllSummitData, $current_realm)
                ],
            ],
            [
                'name' => 'get-tracks-csv',
                'route' => '/api/v1/summits/{id}/tracks/csv',
                'http_method' => 'GET',
                'scopes' => [
                    sprintf(SummitScopes::ReadSummitData, $current_realm),
                    sprintf(SummitScopes::ReadAllSummitData, $current_realm)
                ],
            ],
            [
                'name' => 'get-track-by-id',
                'route' => '/api/v1/summits/{id}/tracks/{track_id}',
                'http_method' => 'GET',
                'scopes' => [
                    sprintf(SummitScopes::ReadSummitData, $current_realm),
                    sprintf(SummitScopes::ReadAllSummitData, $current_realm)
                ],
            ],
            [
                'name' => 'get-track-extra-questions',
                'route' => '/api/v1/summits/{id}/tracks/{track_id}/extra-questions',
                'http_method' => 'GET',
                'scopes' => [
                    sprintf(SummitScopes::ReadSummitData, $current_realm),
                    sprintf(SummitScopes::ReadAllSummitData, $current_realm)
                ],
            ],
            [
                'name' => 'add-track-icon',
                'route' => '/api/v1/summits/{id}/tracks/{track_id}/icon',
                'http_method' => 'POST',
                'scopes' => [
                    sprintf(SummitScopes::WriteTracksData, $current_realm),
                    sprintf(SummitScopes::WriteSummitData, $current_realm)
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                ]
            ],
            [
                'name' => 'remove-track-icon',
                'route' => '/api/v1/summits/{id}/tracks/{track_id}/icon',
                'http_method' => 'DELETE',
                'scopes' => [
                    sprintf(SummitScopes::WriteTracksData, $current_realm),
                    sprintf(SummitScopes::WriteSummitData, $current_realm)
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                ]
            ],
            [
                'name' => 'add-track-extra-questions',
                'route' => '/api/v1/summits/{id}/tracks/{track_id}/extra-questions/{question_id}',
                'http_method' => 'PUT',
                'scopes' => [
                    sprintf(SummitScopes::WriteTracksData, $current_realm),
                    sprintf(SummitScopes::WriteSummitData, $current_realm)
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                ]
            ],
            [
                'name' => 'remove-track-extra-questions',
                'route' => '/api/v1/summits/{id}/tracks/{track_id}/extra-questions/{question_id}',
                'http_method' => 'DELETE',
                'scopes' => [
                    sprintf(SummitScopes::WriteTracksData, $current_realm),
                    sprintf(SummitScopes::WriteSummitData, $current_realm)
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                ]
            ],
            [
                'name' => 'get-track-allowed-tags',
                'route' => '/api/v1/summits/{id}/tracks/{track_id}/allowed-tags',
                'http_method' => 'GET',
                'scopes' => [
                    sprintf(SummitScopes::ReadSummitData, $current_realm),
                    sprintf(SummitScopes::ReadAllSummitData, $current_realm)
                ],
            ],
            [
                'name' => 'copy-tracks-to-summit',
                'route' => '/api/v1/summits/{id}/tracks/copy/{to_summit_id}',
                'http_method' => 'POST',
                'scopes' => [
                    sprintf(SummitScopes::WriteTracksData, $current_realm),
                    sprintf(SummitScopes::WriteSummitData, $current_realm)
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                ]
            ],
            [
                'name' => 'add-track',
                'route' => '/api/v1/summits/{id}/tracks',
                'http_method' => 'POST',
                'scopes' => [
                    sprintf(SummitScopes::WriteTracksData, $current_realm),
                    sprintf(SummitScopes::WriteSummitData, $current_realm)
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                ]
            ],
            [
                'name' => 'update-track',
                'route' => '/api/v1/summits/{id}/tracks/{track_id}',
                'http_method' => 'PUT',
                'scopes' => [
                    sprintf(SummitScopes::WriteTracksData, $current_realm),
                    sprintf(SummitScopes::WriteSummitData, $current_realm)
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                ]
            ],
            [
                'name' => 'delete-track',
                'route' => '/api/v1/summits/{id}/tracks/{track_id}',
                'http_method' => 'DELETE',
                'scopes' => [
                    sprintf(SummitScopes::WriteTracksData, $current_realm),
                    sprintf(SummitScopes::WriteSummitData, $current_realm)
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                ]
            ],
            // track proposed schedule allowed locations
            [
                'name' => 'get-allowed-locations-by-track',
                'route' => '/api/v1/summits/{id}/tracks/{track_id}/proposed-schedule-allowed-locations',
                'http_method' => 'GET',
                'scopes' => [
                    sprintf(SummitScopes::ReadSummitData, $current_realm),
                    sprintf(SummitScopes::ReadAllSummitData, $current_realm)
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                    IGroup::TrackChairs,
                    IGroup::TrackChairsAdmins,
                ]
            ],
            [
                'name' => 'add-allowed-location-to-track',
                'route' => '/api/v1/summits/{id}/tracks/{track_id}/proposed-schedule-allowed-locations',
                'http_method' => 'POST',
                'scopes' => [
                    sprintf(SummitScopes::WriteTracksData, $current_realm),
                    sprintf(SummitScopes::WriteSummitData, $current_realm)
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                    IGroup::TrackChairsAdmins,
                ]
            ],
            [
                'name' => 'get-allowed-location-from-track',
                'route' => '/api/v1/summits/{id}/tracks/{track_id}/proposed-schedule-allowed-locations/{location_id}',
                'http_method' => 'GET',
                'scopes' => [
                    sprintf(SummitScopes::ReadSummitData, $current_realm),
                    sprintf(SummitScopes::ReadAllSummitData, $current_realm)
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                    IGroup::TrackChairs,
                    IGroup::TrackChairsAdmins,
                ]
            ],
            [
                'name' => 'remove-all-allowed-location-from-track',
                'route' => '/api/v1/summits/{id}/tracks/{track_id}/proposed-schedule-allowed-locations/all',
                'http_method' => 'DELETE',
                'scopes' => [
                    sprintf(SummitScopes::WriteTracksData, $current_realm),
                    sprintf(SummitScopes::WriteSummitData, $current_realm)
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                    IGroup::TrackChairsAdmins,
                ]
            ],

            [
                'name' => 'remove-allowed-location-from-track',
                'route' => '/api/v1/summits/{id}/tracks/{track_id}/proposed-schedule-allowed-locations/{location_id}',
                'http_method' => 'DELETE',
                'scopes' => [
                    sprintf(SummitScopes::WriteTracksData, $current_realm),
                    sprintf(SummitScopes::WriteSummitData, $current_realm)
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                ]
            ],
            [
                'name' => 'add-time-frame-2-allowed-location',
                'route' => '/api/v1/summits/{id}/tracks/{track_id}/proposed-schedule-allowed-locations/{location_id}/allowed-time-frames',
                'http_method' => 'POST',
                'scopes' => [
                    sprintf(SummitScopes::WriteTracksData, $current_realm),
                    sprintf(SummitScopes::WriteSummitData, $current_realm)
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                    IGroup::TrackChairsAdmins,
                ]
            ],
            [
                'name' => 'get-all-time-frame-from-allowed-location',
                'route' => '/api/v1/summits/{id}/tracks/{track_id}/proposed-schedule-allowed-locations/{location_id}/allowed-time-frames',
                'http_method' => 'GET',
                'scopes' => [
                    sprintf(SummitScopes::ReadSummitData, $current_realm),
                    sprintf(SummitScopes::ReadAllSummitData, $current_realm)
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                    IGroup::TrackChairs,
                    IGroup::TrackChairsAdmins,
                ]
            ],
            [
                'name' => 'get-time-frame-from-allowed-location',
                'route' => '/api/v1/summits/{id}/tracks/{track_id}/proposed-schedule-allowed-locations/{location_id}/allowed-time-frames/{time_frame_id}',
                'http_method' => 'GET',
                'scopes' => [
                    sprintf(SummitScopes::ReadSummitData, $current_realm),
                    sprintf(SummitScopes::ReadAllSummitData, $current_realm)
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                    IGroup::TrackChairs,
                    IGroup::TrackChairsAdmins,
                ]
            ],
            [
                'name' => 'delete-all--time-frame-from-allowed-location',
                'route' => '/api/v1/summits/{id}/tracks/{track_id}/proposed-schedule-allowed-locations/{location_id}/allowed-time-frames/all',
                'http_method' => 'DELETE',
                'scopes' => [
                    sprintf(SummitScopes::WriteTracksData, $current_realm),
                    sprintf(SummitScopes::WriteSummitData, $current_realm)
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                    IGroup::TrackChairsAdmins,
                ]
            ],
            [
                'name' => 'delete-time-frame-from-allowed-location',
                'route' => '/api/v1/summits/{id}/tracks/{track_id}/proposed-schedule-allowed-locations/{location_id}/allowed-time-frames/{time_frame_id}',
                'http_method' => 'DELETE',
                'scopes' => [
                    sprintf(SummitScopes::WriteTracksData, $current_realm),
                    sprintf(SummitScopes::WriteSummitData, $current_realm)
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                    IGroup::TrackChairsAdmins,
                ]
            ],
            [
                'name' => 'update-time-frame-from-allowed-location',
                'route' => '/api/v1/summits/{id}/tracks/{track_id}/proposed-schedule-allowed-locations/{location_id}/allowed-time-frames/{time_frame_id}',
                'http_method' => 'PUT',
                'scopes' => [
                    sprintf(SummitScopes::WriteTracksData, $current_realm),
                    sprintf(SummitScopes::WriteSummitData, $current_realm)
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                    IGroup::TrackChairs,
                    IGroup::TrackChairsAdmins,
                ]
            ],
            [
                'name' => 'add-sub-track',
                'route' => '/api/v1/summits/{id}/tracks/{track_id}/sub-tracks/{child_track_id}',
                'http_method' => 'PUT',
                'scopes' => [
                    sprintf(SummitScopes::WriteTracksData, $current_realm),
                    sprintf(SummitScopes::WriteSummitData, $current_realm)
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                    IGroup::TrackChairs,
                    IGroup::TrackChairsAdmins,
                ]
            ],
            [
                'name' => 'remove-sub-track',
                'route' => '/api/v1/summits/{id}/tracks/{track_id}/sub-tracks/{child_track_id}',
                'http_method' => 'DELETE',
                'scopes' => [
                    sprintf(SummitScopes::WriteTracksData, $current_realm),
                    sprintf(SummitScopes::WriteSummitData, $current_realm)
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                    IGroup::TrackChairs,
                    IGroup::TrackChairsAdmins,
                ]
            ],
            // ticket types
            [
                'name' => 'get-ticket-types',
                'route' => '/api/v1/summits/{id}/ticket-types',
                'http_method' => 'GET',
                'scopes' => [
                    sprintf(SummitScopes::ReadSummitData, $current_realm),
                    sprintf(SummitScopes::ReadAllSummitData, $current_realm)
                ],
            ],
            [
                'name' => 'get-ticket-types-csv',
                'route' => '/api/v1/summits/{id}/ticket-types/csv',
                'http_method' => 'GET',
                'scopes' => [
                    sprintf(SummitScopes::ReadSummitData, $current_realm),
                    sprintf(SummitScopes::ReadAllSummitData, $current_realm)
                ],
            ],
            [
                'name' => 'get-ticket-types-v2',
                'route' => '/api/v2/summits/{id}/ticket-types',
                'http_method' => 'GET',
                'scopes' => [
                    sprintf(SummitScopes::ReadSummitData, $current_realm),
                    sprintf(SummitScopes::ReadAllSummitData, $current_realm)
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                    IGroup::SummitRegistrationAdmins,
                ]
            ],
            [
                'name' => 'get-allowed_ticket-types',
                'route' => '/api/v1/summits/{id}/ticket-types/allowed',
                'http_method' => 'GET',
                'scopes' => [
                    sprintf(MemberScopes::ReadMyMemberData, $current_realm)
                ],
            ],
            [
                'name' => 'add-ticket-type',
                'route' => '/api/v1/summits/{id}/ticket-types',
                'http_method' => 'POST',
                'scopes' => [
                    sprintf(SummitScopes::WriteTicketTypeData, $current_realm),
                    sprintf(SummitScopes::WriteSummitData, $current_realm)
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                    IGroup::SummitRegistrationAdmins,
                ]
            ],
            [
                'name' => 'seed-default-ticket-types',
                'route' => '/api/v1/summits/{id}/ticket-types/seed-defaults',
                'http_method' => 'POST',
                'scopes' => [
                    sprintf(SummitScopes::WriteTicketTypeData, $current_realm),
                    sprintf(SummitScopes::WriteSummitData, $current_realm)
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                    IGroup::SummitRegistrationAdmins,
                ]
            ],
            [
                'name' => 'update-ticket-type',
                'route' => '/api/v1/summits/{id}/ticket-types/{ticket_type_id}',
                'http_method' => 'PUT',
                'scopes' => [
                    sprintf(SummitScopes::WriteTicketTypeData, $current_realm),
                    sprintf(SummitScopes::WriteSummitData, $current_realm)
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                    IGroup::SummitRegistrationAdmins,
                ]
            ],
            [
                'name' => 'delete-ticket-type',
                'route' => '/api/v1/summits/{id}/ticket-types/{ticket_type_id}',
                'http_method' => 'DELETE',
                'scopes' => [
                    sprintf(SummitScopes::WriteTicketTypeData, $current_realm),
                    sprintf(SummitScopes::WriteSummitData, $current_realm)
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                    IGroup::SummitRegistrationAdmins,
                ]
            ],
            [
                'name' => 'get-ticket-type',
                'route' => '/api/v1/summits/{id}/ticket-types/{ticket_type_id}',
                'http_method' => 'GET',
                'scopes' => [
                    sprintf(SummitScopes::ReadSummitData, $current_realm),
                    sprintf(SummitScopes::ReadAllSummitData, $current_realm)
                ],
            ],
            [
                'name' => 'update-ticket-types-currency-symbol',
                'route' => '/api/v1/summits/{id}/ticket-types/all/currency/{currency_symbol}',
                'http_method' => 'PUT',
                'scopes' => [
                    sprintf(SummitScopes::WriteTicketTypeData, $current_realm),
                    sprintf(SummitScopes::WriteSummitData, $current_realm)
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                    IGroup::SummitRegistrationAdmins,
                ]
            ],
            // track groups
            [
                'name' => 'get-track-groups',
                'route' => '/api/v1/summits/{id}/track-groups',
                'http_method' => 'GET',
                'scopes' => [
                    sprintf(SummitScopes::ReadSummitData, $current_realm),
                    sprintf(SummitScopes::ReadAllSummitData, $current_realm)
                ],
            ],
            [
                'name' => 'get-track-groups-csv',
                'route' => '/api/v1/summits/{id}/track-groups/csv',
                'http_method' => 'GET',
                'scopes' => [
                    sprintf(SummitScopes::ReadSummitData, $current_realm),
                    sprintf(SummitScopes::ReadAllSummitData, $current_realm)
                ],
            ],
            [
                'name' => 'get-track-groups-metadata',
                'route' => '/api/v1/summits/{id}/track-groups/metadata',
                'http_method' => 'GET',
                'scopes' => [
                    sprintf(SummitScopes::ReadSummitData, $current_realm),
                    sprintf(SummitScopes::ReadAllSummitData, $current_realm)
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                ]
            ],
            [
                'name' => 'get-track-group',
                'route' => '/api/v1/summits/{id}/track-groups/{track_group_id}',
                'http_method' => 'GET',
                'scopes' => [
                    sprintf(SummitScopes::ReadSummitData, $current_realm),
                    sprintf(SummitScopes::ReadAllSummitData, $current_realm)
                ],
            ],
            [
                'name' => 'add-track-group',
                'route' => '/api/v1/summits/{id}/track-groups',
                'http_method' => 'POST',
                'scopes' => [
                    sprintf(SummitScopes::WriteTrackGroupsData, $current_realm),
                    sprintf(SummitScopes::WriteSummitData, $current_realm)
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                ]
            ],
            [
                'name' => 'update-track-group',
                'route' => '/api/v1/summits/{id}/track-groups/{track_group_id}',
                'http_method' => 'PUT',
                'scopes' => [
                    sprintf(SummitScopes::WriteTrackGroupsData, $current_realm),
                    sprintf(SummitScopes::WriteSummitData, $current_realm)
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                ]
            ],
            [
                'name' => 'delete-track-group',
                'route' => '/api/v1/summits/{id}/track-groups/{track_group_id}',
                'http_method' => 'DELETE',
                'scopes' => [
                    sprintf(SummitScopes::WriteTrackGroupsData, $current_realm),
                    sprintf(SummitScopes::WriteSummitData, $current_realm)
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                ]
            ],
            [
                'name' => 'associate-track-2-track-group',
                'route' => '/api/v1/summits/{id}/track-groups/{track_group_id}/tracks/{track_id}',
                'http_method' => 'PUT',
                'scopes' => [
                    sprintf(SummitScopes::WriteTrackGroupsData, $current_realm),
                    sprintf(SummitScopes::WriteSummitData, $current_realm)
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                ]
            ],
            [
                'name' => 'disassociate-track-2-track-group',
                'route' => '/api/v1/summits/{id}/track-groups/{track_group_id}/tracks/{track_id}',
                'http_method' => 'DELETE',
                'scopes' => [
                    sprintf(SummitScopes::WriteTrackGroupsData, $current_realm),
                    sprintf(SummitScopes::WriteSummitData, $current_realm)
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                ]
            ],
            [
                'name' => 'associate-group-2-track-group',
                'route' => '/api/v1/summits/{id}/track-groups/{track_group_id}/allowed-groups/{group_id}',
                'http_method' => 'PUT',
                'scopes' => [
                    sprintf(SummitScopes::WriteTrackGroupsData, $current_realm),
                    sprintf(SummitScopes::WriteSummitData, $current_realm)
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                ]
            ],
            [
                'name' => 'disassociate-group-2-track-group',
                'route' => '/api/v1/summits/{id}/track-groups/{track_group_id}/allowed-groups/{group_id}',
                'http_method' => 'DELETE',
                'scopes' => [
                    sprintf(SummitScopes::WriteTrackGroupsData, $current_realm),
                    sprintf(SummitScopes::WriteSummitData, $current_realm)
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                ]
            ],
            //external orders
            array(
                'name' => 'get-external-order',
                'route' => '/api/v1/summits/{id}/external-orders/{external_order_id}',
                'http_method' => 'GET',
                'scopes' => [sprintf('%s/summits/read-external-orders', $current_realm)],
            ),
            array(
                'name' => 'confirm-external-order',
                'route' => '/api/v1/summits/{id}/external-orders/{external_order_id}/external-attendees/{external_attendee_id}/confirm',
                'http_method' => 'POST',
                'scopes' => [sprintf('%s/summits/confirm-external-orders', $current_realm)],
            ),
            [
                'name' => 'import-assets-from-mux',
                'route' => '/api/v1/summits/{id}/presentations/all/import/mux',
                'http_method' => 'POST',
                'scopes' => [
                    sprintf(SummitScopes::WriteSummitData, $current_realm),
                    sprintf(SummitScopes::WriteEventData, $current_realm),
                    sprintf(SummitScopes::WritePresentationData, $current_realm)
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                ]
            ],
            // presentations
            [
                'name' => 'get-presentations',
                'route' => '/api/v1/summits/{id}/presentations',
                'http_method' => 'GET',
                'scopes' => [
                    sprintf(SummitScopes::ReadSummitData, $current_realm),
                    sprintf(SummitScopes::ReadAllSummitData, $current_realm)
                ],
            ],
            // attendees votes
            [
                'name' => 'cast-attendee-vote',
                'route' => '/api/v1/summits/{id}/presentations/{presentation_id}/attendee-votes',
                'http_method' => 'POST',
                'scopes' => [
                    sprintf(SummitScopes::Allow2PresentationAttendeeVote, $current_realm),
                ],
            ],
            [
                'name' => 'uncast-attendee-vote',
                'route' => '/api/v1/summits/{id}/presentations/{presentation_id}/attendee-votes',
                'http_method' => 'DELETE',
                'scopes' => [
                    sprintf(SummitScopes::Allow2PresentationAttendeeVote, $current_realm),
                ],
            ],
            [
                'name' => 'get-attendees-votes',
                'route' => '/api/v1/summits/{id}/presentations/{id}/attendee-votes',
                'http_method' => 'GET',
                'scopes' => [
                    sprintf(SummitScopes::ReadSummitData, $current_realm),
                    sprintf(SummitScopes::ReadAllSummitData, $current_realm)
                ],
            ],
            // comments
            [
                'name' => 'get-presentation-comments',
                'route' => '/api/v1/summits/{id}/presentations/{presentation_id}/comments',
                'http_method' => 'GET',
                'scopes' => [
                    sprintf(SummitScopes::ReadSummitData, $current_realm),
                    sprintf(SummitScopes::ReadAllSummitData, $current_realm)
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                    IGroup::TrackChairsAdmins,
                    IGroup::TrackChairs,
                ]
            ],
            [
                'name' => 'get-presentation-comment',
                'route' => '/api/v1/summits/{id}/presentations/{presentation_id}/comments/{comment_id}',
                'http_method' => 'GET',
                'scopes' => [
                    sprintf(SummitScopes::ReadSummitData, $current_realm),
                    sprintf(SummitScopes::ReadAllSummitData, $current_realm)
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                    IGroup::TrackChairsAdmins,
                    IGroup::TrackChairs,
                ]
            ],
            [
                'name' => 'add-presentation-comment',
                'route' => '/api/v1/summits/{id}/presentations/{presentation_id}/comments',
                'http_method' => 'POST',
                'scopes' => [
                    sprintf(SummitScopes::WriteSummitData, $current_realm),
                    sprintf(SummitScopes::WriteEventData, $current_realm),
                    sprintf(SummitScopes::WritePresentationData, $current_realm)
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                    IGroup::TrackChairsAdmins,
                    IGroup::TrackChairs,
                ]
            ],
            [
                'name' => 'update-presentation-comment',
                'route' => '/api/v1/summits/{id}/presentations/{presentation_id}/comments/{comment_id}',
                'http_method' => 'PUT',
                'scopes' => [
                    sprintf(SummitScopes::WriteSummitData, $current_realm),
                    sprintf(SummitScopes::WriteEventData, $current_realm),
                    sprintf(SummitScopes::WritePresentationData, $current_realm)
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                    IGroup::TrackChairsAdmins,
                    IGroup::TrackChairs,
                ]
            ],
            [
                'name' => 'delete-presentation-comment',
                'route' => '/api/v1/summits/{id}/presentations/{presentation_id}/comments/{comment_id}',
                'http_method' => 'DELETE',
                'scopes' => [
                    sprintf(SummitScopes::WriteSummitData, $current_realm),
                    sprintf(SummitScopes::WriteEventData, $current_realm),
                    sprintf(SummitScopes::WritePresentationData, $current_realm)
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                    IGroup::TrackChairsAdmins,
                    IGroup::TrackChairs,
                ]
            ],
            // voteable presentations
            [
                'name' => 'get-voteable-presentations',
                'route' => '/api/v1/summits/{id}/presentations/voteable',
                'http_method' => 'GET',
                'scopes' => [
                    sprintf(SummitScopes::ReadSummitData, $current_realm),
                    sprintf(SummitScopes::ReadAllSummitData, $current_realm)
                ],
            ],
            // voteable presentations v2
            [
                'name' => 'get-voteable-presentations-v2',
                'route' => '/api/v2/summits/{id}/presentations/voteable',
                'http_method' => 'GET',
                'scopes' => [
                    sprintf(SummitScopes::ReadSummitData, $current_realm),
                    sprintf(SummitScopes::ReadAllSummitData, $current_realm)
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                ]
            ],
            // voteable presentations v2
            [
                'name' => 'get-voteable-presentations-v2-csv',
                'route' => '/api/v2/summits/{id}/presentations/voteable/csv',
                'http_method' => 'GET',
                'scopes' => [
                    sprintf(SummitScopes::ReadSummitData, $current_realm),
                    sprintf(SummitScopes::ReadAllSummitData, $current_realm)
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                ]
            ],
            [
                'name' => 'get-voteable-presentation-by-id',
                'route' => '/api/v1/summits/{id}/presentations/voteable/{presentation_id}',
                'http_method' => 'GET',
                'scopes' => [
                    sprintf(SummitScopes::ReadSummitData, $current_realm),
                    sprintf(SummitScopes::ReadAllSummitData, $current_realm)
                ],
            ],
            // presentation submissions
            [
                'name' => 'submit-presentation',
                'route' => '/api/v1/summits/{id}/presentations',
                'http_method' => 'POST',
                'scopes' => [
                    sprintf(SummitScopes::WriteSummitData, $current_realm),
                    sprintf(SummitScopes::WriteEventData, $current_realm),
                    sprintf(SummitScopes::WritePresentationData, $current_realm)
                ],
            ],
            [
                'name' => 'get-submit-presentation',
                'route' => '/api/v1/summits/{id}/presentations/{presentation_id}',
                'http_method' => 'GET',
                'scopes' => [
                    sprintf(SummitScopes::ReadSummitData, $current_realm),
                    sprintf(SummitScopes::ReadAllSummitData, $current_realm)
                ]
            ],
            [
                'name' => 'update-submit-presentation',
                'route' => '/api/v1/summits/{id}/presentations/{presentation_id}',
                'http_method' => 'PUT',
                'scopes' => [
                    sprintf(SummitScopes::WriteSummitData, $current_realm),
                    sprintf(SummitScopes::WriteEventData, $current_realm),
                    sprintf(SummitScopes::WritePresentationData, $current_realm)
                ],
            ],
            [
                'name' => 'complete-submit-presentation',
                'route' => '/api/v1/summits/{id}/presentations/{presentation_id}/completed',
                'http_method' => 'PUT',
                'scopes' => [
                    sprintf(SummitScopes::WriteSummitData, $current_realm),
                    sprintf(SummitScopes::WriteEventData, $current_realm),
                    sprintf(SummitScopes::WritePresentationData, $current_realm)
                ],
            ],
            [
                'name' => 'delete-submit-presentation',
                'route' => '/api/v1/summits/{id}/presentations/{presentation_id}',
                'http_method' => 'DELETE',
                'scopes' => [
                    sprintf(SummitScopes::WriteSummitData, $current_realm),
                    sprintf(SummitScopes::WriteEventData, $current_realm),
                    sprintf(SummitScopes::WritePresentationData, $current_realm)
                ],
            ],
            //extra-questions
            [
                'name' => 'get-presentation-extra-questions',
                'route' => '/api/v1/summits/{id}/presentations/{presentation_id}/extra-questions',
                'http_method' => 'GET',
                'scopes' => [
                    sprintf(SummitScopes::ReadSummitData, $current_realm),
                    sprintf(SummitScopes::ReadAllSummitData, $current_realm)
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                ]
            ],
            //videos
            [
                'name' => 'get-presentation-videos',
                'route' => '/api/v1/summits/{id}/presentations/{presentation_id}/videos',
                'http_method' => 'GET',
                'scopes' => [
                    sprintf(SummitScopes::ReadSummitData, $current_realm),
                    sprintf(SummitScopes::ReadAllSummitData, $current_realm)
                ],
            ],
            [
                'name' => 'get-presentation-video',
                'route' => '/api/v1/summits/{id}/presentations/{presentation_id}/video/{video_id}',
                'http_method' => 'GET',
                'scopes' => [
                    sprintf(SummitScopes::ReadSummitData, $current_realm),
                    sprintf(SummitScopes::ReadAllSummitData, $current_realm)
                ],
            ],
            [
                'name' => 'create-presentation-video',
                'route' => '/api/v1/summits/{id}/presentations/{presentation_id}/videos',
                'http_method' => 'POST',
                'scopes' => [
                    sprintf(SummitScopes::WriteVideoData, $current_realm),
                    sprintf(SummitScopes::WritePresentationMaterialsData, $current_realm),
                    sprintf(SummitScopes::WritePresentationVideosData, $current_realm)
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                ]
            ],
            [
                'name' => 'update-presentation-video',
                'route' => '/api/v1/summits/{id}/presentations/{presentation_id}/videos/{video_id}',
                'http_method' => 'PUT',
                'scopes' => [
                    sprintf(SummitScopes::WriteVideoData, $current_realm),
                    sprintf(SummitScopes::WritePresentationMaterialsData, $current_realm),
                    sprintf(SummitScopes::WritePresentationVideosData, $current_realm)
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                ]
            ],
            [
                'name' => 'delete-presentation-video',
                'route' => '/api/v1/summits/{id}/presentations/{presentation_id}/videos/{video_id}',
                'http_method' => 'DELETE',
                'scopes' => [
                    sprintf(SummitScopes::WriteVideoData, $current_realm),
                    sprintf(SummitScopes::WritePresentationMaterialsData, $current_realm),
                    sprintf(SummitScopes::WritePresentationVideosData, $current_realm)
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                ]
            ],
            // links
            [
                'name' => 'get-presentation-links',
                'route' => '/api/v1/summits/{id}/presentations/{presentation_id}/links',
                'http_method' => 'GET',
                'scopes' => [
                    sprintf(SummitScopes::ReadSummitData, $current_realm),
                    sprintf(SummitScopes::ReadAllSummitData, $current_realm)
                ],
            ],
            [
                'name' => 'get-presentation-link',
                'route' => '/api/v1/summits/{id}/presentations/{presentation_id}/links/{link_id}',
                'http_method' => 'GET',
                'scopes' => [
                    sprintf(SummitScopes::ReadSummitData, $current_realm),
                    sprintf(SummitScopes::ReadAllSummitData, $current_realm)
                ],
            ],
            [
                'name' => 'create-presentation-link',
                'route' => '/api/v1/summits/{id}/presentations/{presentation_id}/links',
                'http_method' => 'POST',
                'scopes' => [
                    sprintf(SummitScopes::WritePresentationMaterialsData, $current_realm),
                    sprintf(SummitScopes::WritePresentationLinksData, $current_realm)
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                ]
            ],
            [
                'name' => 'update-presentation-link',
                'route' => '/api/v1/summits/{id}/presentations/{presentation_id}/links/{link_id}',
                'http_method' => 'PUT',
                'scopes' => [
                    sprintf(SummitScopes::WritePresentationMaterialsData, $current_realm),
                    sprintf(SummitScopes::WritePresentationLinksData, $current_realm)
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                ]
            ],
            [
                'name' => 'delete-presentation-link',
                'route' => '/api/v1/summits/{id}/presentations/{presentation_id}/links/{link_id}',
                'http_method' => 'DELETE',
                'scopes' => [
                    sprintf(SummitScopes::WritePresentationMaterialsData, $current_realm),
                    sprintf(SummitScopes::WritePresentationLinksData, $current_realm)
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                ]
            ],
            // slides
            [
                'name' => 'get-presentation-slides',
                'route' => '/api/v1/summits/{id}/presentations/{presentation_id}/slides',
                'http_method' => 'GET',
                'scopes' => [
                    sprintf(SummitScopes::ReadSummitData, $current_realm),
                    sprintf(SummitScopes::ReadAllSummitData, $current_realm)
                ],
            ],
            [
                'name' => 'get-presentation-slide',
                'route' => '/api/v1/summits/{id}/presentations/{presentation_id}/slides/{slide_id}',
                'http_method' => 'GET',
                'scopes' => [
                    sprintf(SummitScopes::ReadSummitData, $current_realm),
                    sprintf(SummitScopes::ReadAllSummitData, $current_realm)
                ],
            ],
            [
                'name' => 'create-presentation-slide',
                'route' => '/api/v1/summits/{id}/presentations/{presentation_id}/slides',
                'http_method' => 'POST',
                'scopes' => [
                    sprintf(SummitScopes::WritePresentationMaterialsData, $current_realm),
                    sprintf(SummitScopes::WritePresentationSlidesData, $current_realm)
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                ]
            ],
            [
                'name' => 'update-presentation-slide',
                'route' => '/api/v1/summits/{id}/presentations/{presentation_id}/slides/{slide_id}',
                'http_method' => 'PUT',
                'scopes' => [
                    sprintf(SummitScopes::WritePresentationMaterialsData, $current_realm),
                    sprintf(SummitScopes::WritePresentationSlidesData, $current_realm)
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                ]
            ],
            [
                'name' => 'delete-presentation-slide',
                'route' => '/api/v1/summits/{id}/presentations/{presentation_id}/slides/{slide_id}',
                'http_method' => 'DELETE',
                'scopes' => [
                    sprintf(SummitScopes::WritePresentationMaterialsData, $current_realm),
                    sprintf(SummitScopes::WritePresentationSlidesData, $current_realm)
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                ]
            ],
            // media uploads
            [
                'name' => 'get-presentation-media-uploads',
                'route' => '/api/v1/summits/{id}/presentations/{presentation_id}/media-uploads',
                'http_method' => 'GET',
                'scopes' => [
                    sprintf(SummitScopes::ReadSummitData, $current_realm),
                    sprintf(SummitScopes::ReadAllSummitData, $current_realm)
                ],
            ],
            [
                'name' => 'get-presentation-media-upload',
                'route' => '/api/v1/summits/{id}/presentations/{presentation_id}/media-uploads/{media_upload_id}',
                'http_method' => 'GET',
                'scopes' => [
                    sprintf(SummitScopes::ReadSummitData, $current_realm),
                    sprintf(SummitScopes::ReadAllSummitData, $current_realm)
                ],
            ],
            [
                'name' => 'create-presentation-media-uploads',
                'route' => '/api/v1/summits/{id}/presentations/{presentation_id}/media-uploads',
                'http_method' => 'POST',
                'scopes' => [
                    sprintf(SummitScopes::WritePresentationMaterialsData, $current_realm),
                    sprintf(SummitScopes::WritePresentationSlidesData, $current_realm)
                ]
            ],
            [
                'name' => 'update-presentation-media-uploads',
                'route' => '/api/v1/summits/{id}/presentations/{presentation_id}/media-uploads/{media_upload_id}',
                'http_method' => 'PUT',
                'scopes' => [
                    sprintf(SummitScopes::WritePresentationMaterialsData, $current_realm),
                    sprintf(SummitScopes::WritePresentationSlidesData, $current_realm)
                ],
            ],
            [
                'name' => 'delete-presentation-media-uploads',
                'route' => '/api/v1/summits/{id}/presentations/{presentation_id}/media-uploads/{media_upload_id}',
                'http_method' => 'DELETE',
                'scopes' => [
                    sprintf(SummitScopes::WritePresentationMaterialsData, $current_realm),
                    sprintf(SummitScopes::WritePresentationSlidesData, $current_realm)
                ],
            ],
            // presentation speakers
            [
                'name' => 'add-presentation-speaker',
                'route' => '/api/v1/summits/{id}/presentations/{presentation_id}/speakers/{speaker_id}',
                'http_method' => 'POST',
                'scopes' => [
                    sprintf(SummitScopes::WritePresentationData, $current_realm),
                    sprintf(SummitScopes::WriteSpeakersData, $current_realm),
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                ]
            ],
            [
                'name' => 'update-presentation-speaker',
                'route' => '/api/v1/summits/{id}/presentations/{presentation_id}/speakers/{speaker_id}',
                'http_method' => 'PUT',
                'scopes' => [
                    sprintf(SummitScopes::WritePresentationData, $current_realm),
                    sprintf(SummitScopes::WriteSpeakersData, $current_realm),
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                ]
            ],
            [
                'name' => 'delete-presentation-speaker',
                'route' => '/api/v1/summits/{id}/presentations/{presentation_id}/speakers/{speaker_id}',
                'http_method' => 'DELETE',
                'scopes' => [
                    sprintf(SummitScopes::WritePresentationData, $current_realm),
                    sprintf(SummitScopes::WriteSpeakersData, $current_realm),
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                ]
            ],
            //members
            [
                'name' => 'create-schedule-shareable-link',
                'route' => '/api/v1/summits/{id}/members/{member_id}/schedule/shareable-link',
                'http_method' => 'POST',
                'scopes' => [
                    sprintf(SummitScopes::WriteSummitData, $current_realm),
                    sprintf(SummitScopes::AddMyScheduleShareable, $current_realm)
                ],
            ],
            [
                'name' => 'delete-schedule-shareable-link',
                'route' => '/api/v1/summits/{id}/members/{member_id}/schedule/shareable-link',
                'http_method' => 'DELETE',
                'scopes' => [
                    sprintf(SummitScopes::WriteSummitData, $current_realm),
                    sprintf(SummitScopes::DeleteMyScheduleShareable, $current_realm)
                ],
            ],
            [
                'name' => 'get-own-member',
                'route' => '/api/v1/summits/{id}/members/{member_id}',
                'http_method' => 'GET',
                'scopes' => [
                    sprintf(SummitScopes::MeRead, $current_realm),
                    sprintf(MemberScopes::ReadMyMemberData, $current_realm),
                ],
            ],
            [
                'name' => 'get-own-member-favorites',
                'route' => '/api/v1/summits/{id}/members/{member_id}/favorites',
                'http_method' => 'GET',
                'scopes' => [
                    sprintf(SummitScopes::MeRead, $current_realm),
                    sprintf(MemberScopes::ReadMyMemberData, $current_realm)
                ],
            ],
            [
                'name' => 'add-2-own-member-favorites',
                'route' => '/api/v1/summits/{id}/members/{member_id}/favorites/{event_id}',
                'http_method' => 'POST',
                'scopes' => [
                    sprintf(SummitScopes::AddMyFavorites, $current_realm),
                    sprintf(MemberScopes::ReadMyMemberData, $current_realm)
                ],
            ],
            [
                'name' => 'add-rsvp-member',
                'route' => '/api/v1/summits/{id}/members/{member_id}/schedule/{event_id}/rsvp',
                'http_method' => 'POST',
                'scopes' => [
                    sprintf(SummitScopes::WriteSummitData, $current_realm),
                    sprintf(SummitScopes::AddMyRSVP, $current_realm)
                ],
            ],
            [
                'name' => 'update-rsvp-member',
                'route' => '/api/v1/summits/{id}/members/{member_id}/schedule/{event_id}/rsvp',
                'http_method' => 'PUT',
                'scopes' => [
                    sprintf(SummitScopes::WriteSummitData, $current_realm),
                    sprintf(SummitScopes::AddMyRSVP, $current_realm)
                ],
            ],
            [
                'name' => 'delete-rsvp-member',
                'route' => '/api/v1/summits/{id}/members/{member_id}/schedule/{event_id}/rsvp',
                'http_method' => 'DELETE',
                'scopes' => [
                    sprintf(SummitScopes::WriteSummitData, $current_realm),
                    sprintf(SummitScopes::DeleteMyRSVP, $current_realm)
                ],
            ],
            [
                'name' => 'remove-from-own-member-favorites',
                'route' => '/api/v1/summits/{id}/members/{member_id}/favorites/{event_id}',
                'http_method' => 'DELETE',
                'scopes' => [sprintf(SummitScopes::DeleteMyFavorites, $current_realm)],
            ],
            [
                'name' => 'get-own-member-schedule',
                'route' => '/api/v1/summits/{id}/members/{member_id}/schedule',
                'http_method' => 'GET',
                'scopes' => [
                    sprintf(SummitScopes::MeRead, $current_realm),
                    sprintf(MemberScopes::ReadMyMemberData, $current_realm)
                ]
            ],
            [
                'name' => 'add-2-own-member-schedule',
                'route' => '/api/v1/summits/{id}/members/{member_id}/schedule/{event_id}',
                'http_method' => 'POST',
                'scopes' => [
                    sprintf(SummitScopes::WriteSummitData, $current_realm),
                    sprintf(SummitScopes::AddMySchedule, $current_realm),
                ],
            ],
            [
                'name' => 'remove-from-own-member-schedule',
                'route' => '/api/v1/summits/{id}/members/{member_id}/schedule/{event_id}',
                'http_method' => 'DELETE',
                'scopes' => [
                    sprintf(SummitScopes::WriteSummitData, $current_realm),
                    sprintf(SummitScopes::DeleteMySchedule, $current_realm),
                ],
            ],
            // enter/leave
            [
                'name' => 'enter-member-to-event',
                'route' => '/api/v1/summits/{id}/members/{member_id}/schedule/{event_id}/enter',
                'http_method' => 'PUT',
                'scopes' => [
                    sprintf(SummitScopes::EnterEvent, $current_realm)
                ],
            ],
            [
                'name' => 'leave-member-from-event',
                'route' => '/api/v1/summits/{id}/members/{member_id}/schedule/{event_id}/leave',
                'http_method' => 'POST',
                'scopes' => [
                    sprintf(SummitScopes::LeaveEvent, $current_realm)
                ],
            ],
            //
            [
                'name' => 'get-member-from-summit',
                'route' => '/api/v1/summits/{id}/members',
                'http_method' => 'GET',
                'scopes' => [sprintf(SummitScopes::ReadAllSummitData, $current_realm)],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                ]
            ],
            [
                'name' => 'get-member-from-summit-csv',
                'route' => '/api/v1/summits/{id}/members/csv',
                'http_method' => 'GET',
                'scopes' => [sprintf(SummitScopes::ReadAllSummitData, $current_realm)],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                ]
            ],
            // notifications
            [
                'name' => 'get-notifications',
                'route' => '/api/v1/summits/{id}/notifications',
                'http_method' => 'GET',
                'scopes' => [
                    sprintf(SummitScopes::ReadAllSummitData, $current_realm),
                    sprintf(SummitScopes::ReadNotifications, $current_realm)
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                ]
            ],
            [
                'name' => 'get-sent-notifications',
                'route' => '/api/v1/summits/{id}/notifications/sent',
                'http_method' => 'GET',
                'scopes' => [
                    sprintf(SummitScopes::ReadSummitData, $current_realm),
                    sprintf(SummitScopes::ReadNotifications, $current_realm)
                ],
            ],
            [
                'name' => 'get-notifications-csv',
                'route' => '/api/v1/summits/{id}/notifications/csv',
                'http_method' => 'GET',
                'scopes' => [
                    sprintf(SummitScopes::ReadAllSummitData, $current_realm),
                    sprintf(SummitScopes::ReadNotifications, $current_realm)
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                ]
            ],
            [
                'name' => 'get-notification-by-id',
                'route' => '/api/v1/summits/{id}/notifications/{notification_id}',
                'http_method' => 'GET',
                'scopes' => [
                    sprintf(SummitScopes::ReadAllSummitData, $current_realm),
                    sprintf(SummitScopes::ReadNotifications, $current_realm)
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                ]
            ],
            [
                'name' => 'add-notifications',
                'route' => '/api/v1/summits/{id}/notifications',
                'http_method' => 'POST',
                'scopes' => [
                    sprintf(SummitScopes::WriteSummitData, $current_realm),
                    sprintf(SummitScopes::WriteNotifications, $current_realm)
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                ]
            ],
            [
                'name' => 'approve-notification',
                'route' => '/api/v1/summits/{id}/notifications/{notification_id}/approve',
                'http_method' => 'PUT',
                'scopes' => [
                    sprintf(SummitScopes::WriteSummitData, $current_realm),
                    sprintf(SummitScopes::WriteNotifications, $current_realm)
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                ]
            ],
            [
                'name' => 'unapprove-notification',
                'route' => '/api/v1/summits/{id}/notifications/{notification_id}/approve',
                'http_method' => 'DELETE',
                'scopes' => [
                    sprintf(SummitScopes::WriteSummitData, $current_realm),
                    sprintf(SummitScopes::WriteNotifications, $current_realm)
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                ]
            ],
            [
                'name' => 'delete-notification',
                'route' => '/api/v1/summits/{id}/notifications/{notification_id}',
                'http_method' => 'DELETE',
                'scopes' => [
                    sprintf(SummitScopes::WriteSummitData, $current_realm),
                    sprintf(SummitScopes::WriteNotifications, $current_realm)
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                ]
            ],
            // promo codes
            [
                'name' => 'get-promo-codes',
                'route' => '/api/v1/summits/{id}/promo-codes',
                'http_method' => 'GET',
                'scopes' => [
                    sprintf(SummitScopes::ReadAllSummitData, $current_realm)
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                    IGroup::SummitRegistrationAdmins,
                ]
            ],
            [
                'name' => 'get-promo-codes-csv',
                'route' => '/api/v1/summits/{id}/promo-codes/csv',
                'http_method' => 'GET',
                'scopes' => [
                    sprintf(SummitScopes::ReadAllSummitData, $current_realm)
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                    IGroup::SummitRegistrationAdmins,
                ]
            ],
            [
                'name' => 'get-sponsor-promo-codes',
                'route' => '/api/v1/summits/{id}/sponsor-promo-codes',
                'http_method' => 'GET',
                'scopes' => [
                    sprintf(SummitScopes::ReadAllSummitData, $current_realm)
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                    IGroup::SummitRegistrationAdmins,
                ]
            ],
            [
                'name' => 'get-sponsor-promo-codes-csv',
                'route' => '/api/v1/summits/{id}/sponsor-promo-codes/csv',
                'http_method' => 'GET',
                'scopes' => [
                    sprintf(SummitScopes::ReadAllSummitData, $current_realm)
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                    IGroup::SummitRegistrationAdmins,
                ]
            ],
            [
                'name' => 'ingest-sponsor-promo-codes-csv',
                'route' => '/api/v1/summits/{id}/sponsor-promo-codes/csv',
                'http_method' => 'POST',
                'scopes' => [
                    sprintf(SummitScopes::WritePromoCodeData, $current_realm),
                    sprintf(SummitScopes::WriteSummitData, $current_realm)
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                    IGroup::SummitRegistrationAdmins,
                ]
            ],
            [
                'name' => 'ingest-promo-codes-csv',
                'route' => '/api/v1/summits/{id}/promo-codes/csv',
                'http_method' => 'POST',
                'scopes' => [
                    sprintf(SummitScopes::WritePromoCodeData, $current_realm),
                    sprintf(SummitScopes::WriteSummitData, $current_realm)
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                    IGroup::SummitRegistrationAdmins,
                ]
            ],
            [
                'name' => 'get-promo-code',
                'route' => '/api/v1/summits/{id}/promo-codes/{promo_code_id}',
                'http_method' => 'GET',
                'scopes' => [
                    sprintf(SummitScopes::ReadAllSummitData, $current_realm)
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                    IGroup::SummitRegistrationAdmins,
                ]
            ],
            [
                'name' => 'add-promo-code',
                'route' => '/api/v1/summits/{id}/promo-codes',
                'http_method' => 'POST',
                'scopes' => [
                    sprintf(SummitScopes::WritePromoCodeData, $current_realm),
                    sprintf(SummitScopes::WriteSummitData, $current_realm)
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                    IGroup::SummitRegistrationAdmins,
                ]
            ],
            [
                'name' => 'delete-promo-code',
                'route' => '/api/v1/summits/{id}/promo-codes/{promo_code_id}',
                'http_method' => 'DELETE',
                'scopes' => [
                    sprintf(SummitScopes::WritePromoCodeData, $current_realm),
                    sprintf(SummitScopes::WriteSummitData, $current_realm)
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                    IGroup::SummitRegistrationAdmins,
                ]
            ],
            [
                'name' => 'update-promo-code',
                'route' => '/api/v1/summits/{id}/promo-codes/{promo_code_id}',
                'http_method' => 'PUT',
                'scopes' => [
                    sprintf(SummitScopes::WritePromoCodeData, $current_realm),
                    sprintf(SummitScopes::WriteSummitData, $current_realm)
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                    IGroup::SummitRegistrationAdmins,
                ]
            ],
            [
                'name' => 'send-promo-code-mail',
                'route' => '/api/v1/summits/{id}/promo-codes/{promo_code_id}/mail',
                'http_method' => 'POST',
                'scopes' => [
                    sprintf(SummitScopes::WritePromoCodeData, $current_realm),
                    sprintf(SummitScopes::WriteSummitData, $current_realm)
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                    IGroup::SummitRegistrationAdmins,
                ]
            ],
            [
                'name' => 'send-sponsors-promo-codes-mail',
                'route' => '/api/v1/summits/{id}/sponsors/all/promo-codes/all/send',
                'http_method' => 'PUT',
                'scopes' => [
                    sprintf(SummitScopes::WritePromoCodeData, $current_realm),
                    sprintf(SummitScopes::WriteSummitData, $current_realm)
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                ]
            ],
            [
                'name' => 'get-promo-codes-metadata',
                'route' => '/api/v1/summits/{id}/promo-codes/metadata',
                'http_method' => 'GET',
                'scopes' => [
                    sprintf(SummitScopes::ReadAllSummitData, $current_realm)
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                    IGroup::SummitRegistrationAdmins,
                ]
            ],
            [
                'name' => 'add-promo-code-badge-feature',
                'route' => '/api/v1/summits/{id}/promo-codes/{promo_code_id}/badge-features/{badge_feature_id}',
                'http_method' => 'PUT',
                'scopes' => [
                    sprintf(SummitScopes::WritePromoCodeData, $current_realm),
                    sprintf(SummitScopes::WriteSummitData, $current_realm)
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                    IGroup::SummitRegistrationAdmins,
                ]
            ],
            [
                'name' => 'remove-promo-code-badge-feature',
                'route' => '/api/v1/summits/{id}/promo-codes/{promo_code_id}/badge-features/{badge_feature_id}',
                'http_method' => 'DELETE',
                'scopes' => [
                    sprintf(SummitScopes::WritePromoCodeData, $current_realm),
                    sprintf(SummitScopes::WriteSummitData, $current_realm)
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                    IGroup::SummitRegistrationAdmins,
                ]
            ],
            [
                'name' => 'add-promo-code-ticket-type',
                'route' => '/api/v1/summits/{id}/promo-codes/{promo_code_id}/ticket-types/{ticket_type_id}',
                'http_method' => 'PUT',
                'scopes' => [
                    sprintf(SummitScopes::WritePromoCodeData, $current_realm),
                    sprintf(SummitScopes::WriteSummitData, $current_realm)
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                    IGroup::SummitRegistrationAdmins,
                ]
            ],
            [
                'name' => 'remove-promo-code-ticket-type',
                'route' => '/api/v1/summits/{id}/promo-codes/{promo_code_id}/ticket-types/{ticket_type_id}',
                'http_method' => 'DELETE',
                'scopes' => [
                    sprintf(SummitScopes::WritePromoCodeData, $current_realm),
                    sprintf(SummitScopes::WriteSummitData, $current_realm)
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                    IGroup::SummitRegistrationAdmins,
                ]
            ],
            [
                'name' => 'pre-validate-promo-code',
                'route' => '/api/v1/summits/{id}/promo-codes/{promo_code_val}/apply',
                'http_method' => 'GET',
                'scopes' => [
                    sprintf(SummitScopes::ReadSummitData, $current_realm),
                    sprintf(SummitScopes::ReadAllSummitData, $current_realm)
                ]
            ],
            // speakers promo codes
            [
                'name' => 'get-promo-code-speakers',
                'route' => '/api/v1/summits/{id}/speakers-promo-codes/{promo_code_id}/speakers',
                'http_method' => 'GET',
                'scopes' => [
                    sprintf(SummitScopes::ReadAllSummitData, $current_realm)
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                ]
            ],
            [
                'name' => 'add-promo-code-speaker',
                'route' => '/api/v1/summits/{id}/speakers-promo-codes/{promo_code_id}/speakers/{speaker_id}',
                'http_method' => 'POST',
                'scopes' => [
                    sprintf(SummitScopes::WritePromoCodeData, $current_realm),
                    sprintf(SummitScopes::WriteSummitData, $current_realm)
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                ]
            ],
            [
                'name' => 'remove-promo-code-speaker',
                'route' => '/api/v1/summits/{id}/speakers-promo-codes/{promo_code_id}/speakers/{speaker_id}',
                'http_method' => 'DELETE',
                'scopes' => [
                    sprintf(SummitScopes::WritePromoCodeData, $current_realm),
                    sprintf(SummitScopes::WriteSummitData, $current_realm)
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                ]
            ],
            // speakers discount codes
            [
                'name' => 'get-discount-code-speakers',
                'route' => '/api/v1/summits/{id}/speakers-discount-codes/{discount_code_id}/speakers',
                'http_method' => 'GET',
                'scopes' => [
                    sprintf(SummitScopes::ReadAllSummitData, $current_realm)
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                ]
            ],
            [
                'name' => 'add-discount-code-speaker',
                'route' => '/api/v1/summits/{id}/speakers-discount-codes/{discount_code_id}/speakers/{speaker_id}',
                'http_method' => 'POST',
                'scopes' => [
                    sprintf(SummitScopes::WritePromoCodeData, $current_realm),
                    sprintf(SummitScopes::WriteSummitData, $current_realm)
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                ]
            ],
            [
                'name' => 'remove-discount-code-speaker',
                'route' => '/api/v1/summits/{id}/speakers-discount-codes/{discount_code_id}/speakers/{speaker_id}',
                'http_method' => 'DELETE',
                'scopes' => [
                    sprintf(SummitScopes::WritePromoCodeData, $current_realm),
                    sprintf(SummitScopes::WriteSummitData, $current_realm)
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                ]
            ],
            // summit speakers assistances
            [
                'name' => 'get-speaker-assistances-by-summit',
                'route' => '/api/v1/summits/{id}/speakers-assistances',
                'http_method' => 'GET',
                'scopes' => [
                    sprintf(SummitScopes::ReadAllSummitData, $current_realm)
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                ]
            ],
            [
                'name' => 'get-speaker-assistances-by-summit-csv',
                'route' => '/api/v1/summits/{id}/speakers-assistances/csv',
                'http_method' => 'GET',
                'scopes' => [
                    sprintf(SummitScopes::ReadAllSummitData, $current_realm)
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                ]
            ],
            [
                'name' => 'add-speaker-assistance',
                'route' => '/api/v1/summits/{id}/speakers-assistances',
                'http_method' => 'POST',
                'scopes' => [
                    sprintf(SummitScopes::WriteSummitSpeakerAssistanceData, $current_realm),
                    sprintf(SummitScopes::WriteSummitData, $current_realm)
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                ]
            ],
            [
                'name' => 'delete-speaker-assistance',
                'route' => '/api/v1/summits/{id}/speakers-assistances/{assistance_id}',
                'http_method' => 'DELETE',
                'scopes' => [
                    sprintf(SummitScopes::WriteSummitSpeakerAssistanceData, $current_realm),
                    sprintf(SummitScopes::WriteSummitData, $current_realm)
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                ]
            ],
            [
                'name' => 'get-speaker-assistance',
                'route' => '/api/v1/summits/{id}/speakers-assistances/{assistance_id}',
                'http_method' => 'GET',
                'scopes' => [
                    sprintf(SummitScopes::ReadAllSummitData, $current_realm)
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                ]
            ],
            [
                'name' => 'update-speaker-assistance',
                'route' => '/api/v1/summits/{id}/speakers-assistances/{assistance_id}',
                'http_method' => 'PUT',
                'scopes' => [
                    sprintf(SummitScopes::WriteSummitSpeakerAssistanceData, $current_realm),
                    sprintf(SummitScopes::WriteSummitData, $current_realm)
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                ]
            ],
            [
                'name' => 'send-speaker-assistance-mail',
                'route' => '/api/v1/summits/{id}/speakers-assistances/{assistance_id}/mail',
                'http_method' => 'POST',
                'scopes' => [
                    sprintf(SummitScopes::WriteSummitSpeakerAssistanceData, $current_realm),
                    sprintf(SummitScopes::WriteSummitData, $current_realm)
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                ]
            ],
            // selection plans
            [
                'name' => 'get-current-selection-plan-by-status',
                'route' => '/api/v1/summits/{id}/selection-plans/current/{status}',
                'http_method' => 'GET',
                'scopes' => [
                    sprintf(SummitScopes::ReadAllSummitData, $current_realm),
                    sprintf(SummitScopes::ReadSummitData, $current_realm)
                ],
            ],
            [
                'name' => 'get-all-current-selection-plans-by-status',
                'route' => '/api/v1/summits/{id}/selection-plans',
                'http_method' => 'GET',
                'scopes' => [
                    sprintf(SummitScopes::ReadAllSummitData, $current_realm),
                    sprintf(SummitScopes::ReadSummitData, $current_realm)
                ],
            ],
            [
                'name' => 'get-all-my-selection-plans',
                'route' => '/api/v1/summits/{id}/selection-plans/me',
                'http_method' => 'GET',
                'scopes' => [
                    sprintf(SummitScopes::ReadAllSummitData, $current_realm),
                    sprintf(SummitScopes::ReadSummitData, $current_realm)
                ],
            ],
            [
                'name' => 'get-selection-plan-by-id',
                'route' => '/api/v1/summits/{id}/selection-plans/{selection_plan_id}',
                'http_method' => 'GET',
                'scopes' => [
                    sprintf(SummitScopes::ReadAllSummitData, $current_realm),
                    sprintf(SummitScopes::ReadSummitData, $current_realm)
                ],
            ],
            [
                'name' => 'update-selection-plan',
                'route' => '/api/v1/summits/{id}/selection-plans/{selection_plan_id}',
                'http_method' => 'PUT',
                'scopes' => [
                    sprintf(SummitScopes::WriteSummitData, $current_realm)
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                ]
            ],
            [
                'name' => 'delete-selection-plan',
                'route' => '/api/v1/summits/{id}/selection-plans/{selection_plan_id}',
                'http_method' => 'DELETE',
                'scopes' => [
                    sprintf(SummitScopes::WriteSummitData, $current_realm)
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                ]
            ],
            [
                'name' => 'add-selection-plan',
                'route' => '/api/v1/summits/{id}/selection-plans',
                'http_method' => 'POST',
                'scopes' => [
                    sprintf(SummitScopes::WriteSummitData, $current_realm)
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                ]
            ],
            [
                'name' => 'add-track-group-2-selection-plan',
                'route' => '/api/v1/summits/{id}/selection-plans/{selection_plan_id}/track-groups/{track_group_id}',
                'http_method' => 'PUT',
                'scopes' => [
                    sprintf(SummitScopes::WriteSummitData, $current_realm)
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                ]
            ],
            [
                'name' => 'delete-track-group-2-selection-plan',
                'route' => '/api/v1/summits/{id}/selection-plans/{selection_plan_id}/track-groups/{track_group_id}',
                'http_method' => 'DELETE',
                'scopes' => [
                    sprintf(SummitScopes::WriteSummitData, $current_realm)
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                ]
            ],
            // selection plans extra questions
            // by summit
            [
                'name' => 'get-selection-plan-extra-questions',
                'route' => '/api/v1/summits/{id}/selection-plan-extra-questions',
                'http_method' => 'GET',
                'scopes' => [
                    sprintf(SummitScopes::ReadAllSummitData, $current_realm),
                    sprintf(SummitScopes::ReadSummitData, $current_realm)
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                ]
            ],
            [
                'name' => 'get-selection-plan-extra-questions-metadata',
                'route' => '/api/v1/summits/{id}/selection-plan-extra-questions/metadata',
                'http_method' => 'GET',
                'scopes' => [
                    sprintf(SummitScopes::ReadAllSummitData, $current_realm),
                    sprintf(SummitScopes::ReadSummitData, $current_realm)
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                ]
            ],
            [
                'name' => 'get-selection-plan-extra-question',
                'route' => '/api/v1/summits/{id}/selection-plan-extra-questions/{question_id}',
                'http_method' => 'GET',
                'scopes' => [
                    sprintf(SummitScopes::ReadAllSummitData, $current_realm),
                    sprintf(SummitScopes::ReadSummitData, $current_realm)
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                ]
            ],
            [
                'name' => 'add-selection-plan-extra-question',
                'route' => '/api/v1/summits/{id}/selection-plan-extra-questions',
                'http_method' => 'POST',
                'scopes' => [
                    sprintf(SummitScopes::WriteSummitData, $current_realm)
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                ]
            ],
            [
                'name' => 'update-selection-plan-extra-question',
                'route' => '/api/v1/summits/{id}/selection-plan-extra-questions/{question_id}',
                'http_method' => 'PUT',
                'scopes' => [
                    sprintf(SummitScopes::WriteSummitData, $current_realm)
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                ]
            ],
            [
                'name' => 'delete-selection-plan-extra-questions',
                'route' => '/api/v1/summits/{id}/selection-plan-extra-questions/{question_id}',
                'http_method' => 'DELETE',
                'scopes' => [
                    sprintf(SummitScopes::WriteSummitData, $current_realm)
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                ],
            ],
            // values
            [
                'name' => 'add-selection-plan-extra-question_value',
                'route' => '/api/v1/summits/{id}/selection-plan-extra-questions/{question_id}/values',
                'http_method' => 'POST',
                'scopes' => [
                    sprintf(SummitScopes::WriteSummitData, $current_realm)
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                ]
            ],
            [
                'name' => 'update-selection-plan-extra-question-value',
                'route' => '/api/v1/summits/{id}/selection-plan-extra-questions/{question_id}/values/{value_id}',
                'http_method' => 'PUT',
                'scopes' => [
                    sprintf(SummitScopes::WriteSummitData, $current_realm)
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                ]
            ],
            [
                'name' => 'delete-selection-plan-extra-question_value',
                'route' => '/api/v1/summits/{id}/selection-plan-extra-questions/{question_id}/values/{value_id}',
                'http_method' => 'DELETE',
                'scopes' => [
                    sprintf(SummitScopes::WriteSummitData, $current_realm)
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                ]
            ],
            // by selection plan
            [
                'name' => 'get-selection-plan-extra-questions-by-selection-plan',
                'route' => '/api/v1/summits/{id}/selection-plans/{selection_plan_id}/extra-questions',
                'http_method' => 'GET',
                'scopes' => [
                    sprintf(SummitScopes::ReadAllSummitData, $current_realm),
                    sprintf(SummitScopes::ReadSummitData, $current_realm)
                ],
            ],
            [
                'name' => 'get-selection-plan-extra-questions-metadata-by-selection-plan',
                'route' => '/api/v1/summits/{id}/selection-plans/{selection_plan_id}/extra-questions/metadata',
                'http_method' => 'GET',
                'scopes' => [
                    sprintf(SummitScopes::ReadAllSummitData, $current_realm),
                    sprintf(SummitScopes::ReadSummitData, $current_realm)
                ],
            ],

            [
                'name' => 'add-selection-plan-extra-question-and-assign-to-plan',
                'route' => '/api/v1/summits/{id}/selection-plans/{selection_plan_id}/extra-questions',
                'http_method' => 'POST',
                'scopes' => [
                    sprintf(SummitScopes::WriteSummitData, $current_realm)
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                ]
            ],
            [
                'name' => 'assign-extra-question-2-plan',
                'route' => '/api/v1/summits/{id}/selection-plans/{selection_plan_id}/extra-questions/{question_id}',
                'http_method' => 'POST',
                'scopes' => [
                    sprintf(SummitScopes::WriteSummitData, $current_realm)
                ],
            ],
            [
                'name' => 'get-selection-plan-extra-question-by-selection-plan',
                'route' => '/api/v1/summits/{id}/selection-plans/{selection_plan_id}/extra-questions/{question_id}',
                'http_method' => 'GET',
                'scopes' => [
                    sprintf(SummitScopes::ReadAllSummitData, $current_realm),
                    sprintf(SummitScopes::ReadSummitData, $current_realm)
                ],
            ],
            [
                'name' => 'update-selection-plan-extra-question-by-selection-plan',
                'route' => '/api/v1/summits/{id}/selection-plans/{selection_plan_id}/extra-questions/{question_id}',
                'http_method' => 'PUT',
                'scopes' => [
                    sprintf(SummitScopes::WriteSummitData, $current_realm)
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                ]
            ],
            [
                'name' => 'remove-selection-plan-extra-question',
                'route' => '/api/v1/summits/{id}/selection-plans/{selection_plan_id}/extra-questions/{question_id}',
                'http_method' => 'DELETE',
                'scopes' => [
                    sprintf(SummitScopes::WriteSummitData, $current_realm)
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                ]
            ],
            [
                'name' => 'add-selection-plan-extra-question-value-by-selection-plan',
                'route' => '/api/v1/summits/{id}/selection-plans/{selection_plan_id}/extra-questions/{question_id}/values',
                'http_method' => 'POST',
                'scopes' => [
                    sprintf(SummitScopes::WriteSummitData, $current_realm)
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                ]
            ],
            [
                'name' => 'update-selection-plan-extra-question-value-by-selection-plan',
                'route' => '/api/v1/summits/{id}/selection-plans/{selection_plan_id}/extra-questions/{question_id}/values/{value_id}',
                'http_method' => 'PUT',
                'scopes' => [
                    sprintf(SummitScopes::WriteSummitData, $current_realm)
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                ]
            ],
            [
                'name' => 'delete-selection-plan-extra-question-value-by-selection-plan',
                'route' => '/api/v1/summits/{id}/selection-plans/{selection_plan_id}/extra-questions/{question_id}/values/{value_id}',
                'http_method' => 'DELETE',
                'scopes' => [
                    sprintf(SummitScopes::WriteSummitData, $current_realm)
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                ]
            ],
            // selection plan event types
            [
                'name' => 'attach-selection-plan-event-type',
                'route' => '/api/v1/summits/{id}/selection-plans/{selection_plan_id}/event-types/{event_type_id}',
                'http_method' => 'PUT',
                'scopes' => [
                    sprintf(SummitScopes::WriteSummitData, $current_realm)
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                ]
            ],
            [
                'name' => 'detach-selection-plan-event-type',
                'route' => '/api/v1/summits/{id}/selection-plans/{selection_plan_id}/event-types/{event_type_id}',
                'http_method' => 'DELETE',
                'scopes' => [
                    sprintf(SummitScopes::WriteSummitData, $current_realm)
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                ]
            ],
            // allowed-members
            [
                'name' => 'get-selection-plan-allowed-members',
                'route' => '/api/v1/summits/{id}/selection-plans/{selection_plan_id}/allowed-members',
                'http_method' => 'GET',
                'scopes' => [
                    sprintf(SummitScopes::ReadAllSummitData, $current_realm),
                    sprintf(SummitScopes::ReadSummitData, $current_realm)
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                ]
            ],
            [
                'name' => 'attach-selection-plan-allowed-member',
                'route' => '/api/v1/summits/{id}/selection-plans/{selection_plan_id}/allowed-members',
                'http_method' => 'POST',
                'scopes' => [
                    sprintf(SummitScopes::WriteSummitData, $current_realm)
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                ]
            ],
            [
                'name' => 'detach-selection-plan-allowed-member',
                'route' => '/api/v1/summits/{id}/selection-plans/{selection_plan_id}/allowed-members/{allowed_member_id}',
                'http_method' => 'DELETE',
                'scopes' => [
                    sprintf(SummitScopes::WriteSummitData, $current_realm)
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                ]
            ],
            [
                'name' => 'import-selection-plan-allowed-members',
                'route' => '/api/v1/summits/{id}/selection-plans/{selection_plan_id}/allowed-members/csv',
                'http_method' => 'POST',
                'scopes' => [
                    sprintf(SummitScopes::WriteSummitData, $current_realm)
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                ]
            ],
            // selection plan presentations
            [
                'name' => 'get-selection-plan-presentations',
                'route' => '/api/v1/summits/{id}/selection-plans/{selection_plan_id}/presentations',
                'http_method' => 'GET',
                'scopes' => [
                    sprintf(SummitScopes::ReadSummitData, $current_realm)
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::TrackChairs,
                    IGroup::TrackChairsAdmins,
                ]
            ],
            [
                'name' => 'get-selection-plan-presentations-csv',
                'route' => '/api/v1/summits/{id}/selection-plans/{selection_plan_id}/presentations/csv',
                'http_method' => 'GET',
                'scopes' => [
                    sprintf(SummitScopes::ReadSummitData, $current_realm)
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::TrackChairs,
                    IGroup::TrackChairsAdmins,
                ]
            ],
            [
                'name' => 'get-selection-plan-presentation',
                'route' => '/api/v1/summits/{id}/selection-plans/{selection_plan_id}/presentations/{presentation_id}',
                'http_method' => 'GET',
                'scopes' => [
                    sprintf(SummitScopes::ReadSummitData, $current_realm)
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::TrackChairs,
                    IGroup::TrackChairsAdmins,
                ]
            ],
            [
                'name' => 'presentation-action-complete',
                'route' => '/api/v1/summits/{id}/selection-plans/{selection_plan_id}/presentations/{presentation_id}/actions/{action_type_id}/complete',
                'http_method' => 'PUT',
                'scopes' => [
                    sprintf(SummitScopes::WriteSummitData, $current_realm),
                    sprintf(SummitScopes::WriteEventData, $current_realm)
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::TrackChairs,
                    IGroup::TrackChairsAdmins,
                ]
            ],
            [
                'name' => 'presentation-action-uncomplete',
                'route' => '/api/v1/summits/{id}/selection-plans/{selection_plan_id}/presentations/{presentation_id}/actions/{action_type_id}/incomplete',
                'http_method' => 'DELETE',
                'scopes' => [
                    sprintf(SummitScopes::WriteSummitData, $current_realm)
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::TrackChairs,
                    IGroup::TrackChairsAdmins,
                ]
            ],
            [
                'name' => 'add-comment-2-presentation',
                'route' => '/api/v1/summits/{id}/selection-plans/{selection_plan_id}/presentations/{presentation_id}/comments',
                'http_method' => 'POST',
                'scopes' => [
                    sprintf(SummitScopes::WriteSummitData, $current_realm)
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::TrackChairs,
                    IGroup::TrackChairsAdmins,
                ]
            ],
            [
                'name' => 'mark-presentation-viewed',
                'route' => '/api/v1/summits/{id}/selection-plans/{selection_plan_id}/presentations/{presentation_id}/view',
                'http_method' => 'PUT',
                'scopes' => [
                    sprintf(SummitScopes::WriteSummitData, $current_realm)
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::TrackChairs,
                    IGroup::TrackChairsAdmins,
                ]
            ],
            [
                'name' => 'get-presentation-category-change-requests',
                'route' => '/api/v1/summits/{id}/selection-plans/{selection_plan_id}/presentations/all/category-change-requests',
                'http_method' => 'GET',
                'scopes' => [
                    sprintf(SummitScopes::ReadAllSummitData, $current_realm),
                    sprintf(SummitScopes::ReadSummitData, $current_realm)
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::TrackChairs,
                    IGroup::TrackChairsAdmins,
                ]
            ],
            [
                'name' => 'create-presentation-category-change-request',
                'route' => '/api/v1/summits/{id}/selection-plans/{selection_plan_id}/presentations/{presentation_id}/category-change-requests',
                'http_method' => 'POST',
                'scopes' => [
                    sprintf(SummitScopes::WriteSummitData, $current_realm)
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::TrackChairs,
                    IGroup::TrackChairsAdmins,
                ]
            ],
            [
                'name' => 'resolve-presentation-category-change-request',
                'route' => '/api/v1/summits/{id}/selection-plans/{selection_plan_id}/presentations/{presentation_id}/category-change-requests/{category_change_request_id}',
                'http_method' => 'PUT',
                'scopes' => [
                    sprintf(SummitScopes::WriteSummitData, $current_realm)
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::TrackChairs,
                    IGroup::TrackChairsAdmins,
                ]
            ],
            // selection plan allowed presentation action types
            [
                'name' => 'get-allowed-presentation-action-types',
                'route' => '/api/v1/summits/{id}/selection-plans/{selection_plan_id}/allowed-presentation-action-types',
                'http_method' => 'GET',
                'scopes' => [
                    sprintf(SummitScopes::ReadAllSummitData, $current_realm),
                    sprintf(SummitScopes::ReadSummitData, $current_realm)
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                    IGroup::TrackChairs,
                    IGroup::TrackChairsAdmins,
                ]
            ],
            [
                'name' => 'get-allowed-presentation-action-type',
                'route' => '/api/v1/summits/{id}/selection-plans/{selection_plan_id}/allowed-presentation-action-types/{type_id}',
                'http_method' => 'GET',
                'scopes' => [
                    sprintf(SummitScopes::ReadSummitData, $current_realm)
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                    IGroup::TrackChairs,
                    IGroup::TrackChairsAdmins,
                ]
            ],
            [
                'name' => 'add-allowed-presentation-action-type',
                'route' => '/api/v1/summits/{id}/selection-plans/{selection_plan_id}/allowed-presentation-action-types/{type_id}',
                'http_method' => 'POST',
                'scopes' => [
                    sprintf(SummitScopes::WriteSummitData, $current_realm)
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                    IGroup::TrackChairs,
                    IGroup::TrackChairsAdmins,
                ]
            ],
            [
                'name' => 'update-allowed-presentation-action-type',
                'route' => '/api/v1/summits/{id}/selection-plans/{selection_plan_id}/allowed-presentation-action-types/{type_id}',
                'http_method' => 'PUT',
                'scopes' => [
                    sprintf(SummitScopes::WriteSummitData, $current_realm)
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                    IGroup::TrackChairs,
                    IGroup::TrackChairsAdmins,
                ]
            ],
            [
                'name' => 'remove-allowed-presentation-action-type',
                'route' => '/api/v1/summits/{id}/selection-plans/{selection_plan_id}/allowed-presentation-action-types/{type_id}',
                'http_method' => 'DELETE',
                'scopes' => [
                    sprintf(SummitScopes::WriteSummitData, $current_realm)
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                    IGroup::TrackChairs,
                    IGroup::TrackChairsAdmins,
                ]
            ],
            // track chair rating
            [
                'name' => 'add-track-chair-score',
                'route' => '/api/v1/summits/{id}/selection-plans/{selection_plan_id}/presentations/{presentation_id}/track-chair-scores/{score_type_id}',
                'http_method' => 'POST',
                'scopes' => [
                    sprintf(SummitScopes::WriteSummitData, $current_realm),
                    sprintf(SummitScopes::WriteEventData, $current_realm)
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::TrackChairs,
                    IGroup::TrackChairsAdmins,
                ]
            ],
            [
                'name' => 'delete-track-chair-score',
                'route' => '/api/v1/summits/{id}/selection-plans/{selection_plan_id}/presentations/{presentation_id}/track-chair-scores/{score_type_id}',
                'http_method' => 'DELETE',
                'scopes' => [
                    sprintf(SummitScopes::WriteSummitData, $current_realm)
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::TrackChairs,
                    IGroup::TrackChairsAdmins,
                ]
            ],
            // track tag groups
            [
                'name' => 'get-track-tag-groups',
                'route' => '/api/v1/summits/{id}/track-tag-groups',
                'http_method' => 'GET',
                'scopes' => [
                    sprintf(SummitScopes::ReadAllSummitData, $current_realm)
                ],
            ],
            [
                'name' => 'get-track-tag-groups-allowed-tags',
                'route' => '/api/v1/summits/{id}/track-tag-groups/all/allowed-tags',
                'http_method' => 'GET',
                'scopes' => [
                    sprintf(SummitScopes::WriteSummitData, $current_realm)
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                ]
            ],
            [
                'name' => 'seed-track-tag-groups-allowed-tags',
                'route' => '/api/v1/summits/{id}/track-tag-groups/all/allowed-tags/{tag_id}/seed-on-tracks',
                'http_method' => 'POST',
                'scopes' => [
                    sprintf(SummitScopes::WriteSummitData, $current_realm),
                    sprintf(SummitScopes::WriteTracksData, $current_realm),
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                ]
            ],
            [
                'name' => 'get-track-tag-group',
                'route' => '/api/v1/summits/{id}/track-tag-groups/{track_tag_group_id}',
                'http_method' => 'GET',
                'scopes' => [
                    sprintf(SummitScopes::ReadAllSummitData, $current_realm)
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                ]
            ],
            [
                'name' => 'seed-default-track-tag-groups',
                'route' => '/api/v1/summits/{id}/track-tag-groups/seed-defaults',
                'http_method' => 'POST',
                'scopes' => [
                    sprintf(SummitScopes::WriteSummitData, $current_realm),
                    sprintf(SummitScopes::WriteTrackTagGroupsData, $current_realm)
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                ]
            ],
            [
                'name' => 'add-track-tag-group',
                'route' => '/api/v1/summits/{id}/track-tag-groups',
                'http_method' => 'POST',
                'scopes' => [
                    sprintf(SummitScopes::WriteSummitData, $current_realm),
                    sprintf(SummitScopes::WriteTrackTagGroupsData, $current_realm)
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                ]
            ],
            [
                'name' => 'update-track-tag-group',
                'route' => '/api/v1/summits/{id}/track-tag-groups/{track_tag_group_id}',
                'http_method' => 'PUT',
                'scopes' => [
                    sprintf(SummitScopes::WriteSummitData, $current_realm),
                    sprintf(SummitScopes::WriteTrackTagGroupsData, $current_realm)
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                ]
            ],
            [
                'name' => 'delete-track-tag-group',
                'route' => '/api/v1/summits/{id}/track-tag-groups/{track_tag_group_id}',
                'http_method' => 'DELETE',
                'scopes' => [
                    sprintf(SummitScopes::WriteSummitData, $current_realm),
                    sprintf(SummitScopes::WriteTrackTagGroupsData, $current_realm)
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                ]
            ],
            [
                'name' => 'copy-track-tag-group-allowed-tags-to-track',
                'route' => '/api/v1/summits/{id}/track-tag-groups/{track_tag_group_id}/allowed-tags/all/copy/tracks/{track_id}',
                'http_method' => 'POST',
                'scopes' => [
                    sprintf(SummitScopes::WriteSummitData, $current_realm),
                    sprintf(SummitScopes::WriteTracksData, $current_realm)
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                ]
            ],

            // email-flows-events
            [
                'name' => 'get-all-email-flows-events',
                'route' => '/api/v1/summits/{id}/email-flows-events',
                'http_method' => 'GET',
                'scopes' => [
                    sprintf(SummitScopes::ReadAllSummitData, $current_realm),
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                ]
            ],
            [
                'name' => 'get-email-flows-event-by-id',
                'route' => '/api/v1/summits/{id}/email-flows-events/{event_id}',
                'http_method' => 'GET',
                'scopes' => [
                    sprintf(SummitScopes::ReadAllSummitData, $current_realm),
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                ]
            ],
            [
                'name' => 'update-email-flows-event',
                'route' => '/api/v1/summits/{id}/email-flows-events/{event_id}',
                'http_method' => 'PUT',
                'scopes' => [
                    sprintf(SummitScopes::WriteSummitData, $current_realm),
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                ]
            ],
            // summit documents
            [
                'name' => 'add-summit-document',
                'route' => '/api/v1/summits/{id}/summit-documents',
                'http_method' => 'POST',
                'scopes' => [
                    sprintf(SummitScopes::WriteSummitData, $current_realm)
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                ]
            ],
            [
                'name' => 'get-summit-documents',
                'route' => '/api/v1/summits/{id}/summit-documents',
                'http_method' => 'GET',
                'scopes' => [
                    sprintf(SummitScopes::ReadAllSummitData, $current_realm)
                ],
            ],
            [
                'name' => 'get-summit-document',
                'route' => '/api/v1/summits/{id}/summit-documents/{document_id}',
                'http_method' => 'GET',
                'scopes' => [
                    sprintf(SummitScopes::ReadAllSummitData, $current_realm)
                ],
            ],
            [
                'name' => 'update-summit-document',
                'route' => '/api/v1/summits/{id}/summit-documents/{document_id}',
                'http_method' => 'PUT',
                'scopes' => [
                    sprintf(SummitScopes::WriteSummitData, $current_realm)
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                ]
            ],
            [
                'name' => 'delete-summit-document',
                'route' => '/api/v1/summits/{id}/summit-documents/{document_id}',
                'http_method' => 'DELETE',
                'scopes' => [
                    sprintf(SummitScopes::WriteSummitData, $current_realm)
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                ]
            ],
            [
                'name' => 'add-event-type-2-summit-document',
                'route' => '/api/v1/summits/{id}/summit-documents/{document_id}/event-types/{event_type_id}',
                'http_method' => 'PUT',
                'scopes' => [
                    sprintf(SummitScopes::WriteSummitData, $current_realm)
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                ]
            ],
            [
                'name' => 'remove-event-type-from-summit-document',
                'route' => '/api/v1/summits/{id}/summit-documents/{document_id}/event-types/{event_type_id}',
                'http_method' => 'DELETE',
                'scopes' => [
                    sprintf(SummitScopes::WriteSummitData, $current_realm)
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                ]
            ],
            [
                'name' => 'add-file-2-summit-document',
                'route' => '/api/v1/summits/{id}/summit-documents/{document_id}/file',
                'http_method' => 'POST',
                'scopes' => [
                    sprintf(SummitScopes::WriteSummitData, $current_realm)
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                ]
            ],
            [
                'name' => 'remove-file-from-summit-document',
                'route' => '/api/v1/summits/{id}/summit-documents/{document_id}/file',
                'http_method' => 'DELETE',
                'scopes' => [
                    sprintf(SummitScopes::WriteSummitData, $current_realm)
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                ]
            ],
            // media-upload-types
            [
                'name' => 'get-all-media-upload-types',
                'route' => '/api/v1/summits/{id}/media-upload-types',
                'http_method' => 'GET',
                'scopes' => [
                    sprintf(SummitScopes::ReadAllSummitData, $current_realm)
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                ]
            ],
            [
                'name' => 'add-media-upload-types',
                'route' => '/api/v1/summits/{id}/media-upload-types',
                'http_method' => 'POST',
                'scopes' => [
                    sprintf(SummitScopes::WriteSummitData, $current_realm)
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                ]
            ],
            [
                'name' => 'get-media-upload-type',
                'route' => '/api/v1/summits/{id}/media-upload-types/{media_upload_type_id}',
                'http_method' => 'GET',
                'scopes' => [
                    sprintf(SummitScopes::ReadAllSummitData, $current_realm)
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                ]
            ],
            [
                'name' => 'update-media-upload-types',
                'route' => '/api/v1/summits/{id}/media-upload-types/{media_upload_type_id}',
                'http_method' => 'PUT',
                'scopes' => [
                    sprintf(SummitScopes::WriteSummitData, $current_realm)
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                ]
            ],
            [
                'name' => 'delete-media-upload-types',
                'route' => '/api/v1/summits/{id}/media-upload-types/{media_upload_type_id}',
                'http_method' => 'DELETE',
                'scopes' => [
                    sprintf(SummitScopes::WriteSummitData, $current_realm)
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                ]
            ],
            [
                'name' => 'add-event-type-2-media-upload-type',
                'route' => '/api/v1/summits/{id}/media-upload-types/{media_upload_type_id}/presentation-types/{event_type_id}',
                'http_method' => 'PUT',
                'scopes' => [
                    sprintf(SummitScopes::WriteSummitData, $current_realm)
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                ]
            ],
            [
                'name' => 'remove-event-type-media-upload-type',
                'route' => '/api/v1/summits/{id}/media-upload-types/{media_upload_type_id}/presentation-types/{event_type_id}',
                'http_method' => 'DELETE',
                'scopes' => [
                    sprintf(SummitScopes::WriteSummitData, $current_realm)
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                ]
            ],
            [
                'name' => 'clone-media-upload-types',
                'route' => '/api/v1/summits/{id}/media-upload-types/all/clone/{to_summit_id}',
                'http_method' => 'POST',
                'scopes' => [
                    sprintf(SummitScopes::WriteSummitData, $current_realm)
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                ]
            ],
            [
                'name' => 'add-individual-selection-list',
                'route' => '/api/v1/summits/{id}/selection-plans/{selection_plan_id}/tracks/{track_id}/selection-lists/individual/owner/me',
                'http_method' => 'POST',
                'scopes' => [
                    sprintf(SummitScopes::WriteSummitData, $current_realm)
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::TrackChairs,
                    IGroup::TrackChairsAdmins,
                ]
            ],
            [
                'name' => 'get-individual-selection-list',
                'route' => '/api/v1/summits/{id}/selection-plans/{selection_plan_id}/tracks/{track_id}/selection-lists/individual/owner/{owner_id}',
                'http_method' => 'GET',
                'scopes' => [
                    sprintf(SummitScopes::ReadSummitData, $current_realm)
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::TrackChairs,
                    IGroup::TrackChairsAdmins,
                ]
            ],
            [
                'name' => 'add-presentation-to-individual-selection-list',
                'route' => '/api/v1/summits/{id}/selection-plans/{selection_plan_id}/tracks/{track_id}/selection-lists/individual/presentation-selections/{collection}/presentations/{presentation_id}',
                'http_method' => 'POST',
                'scopes' => [
                    sprintf(SummitScopes::WriteSummitData, $current_realm)
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::TrackChairs,
                    IGroup::TrackChairsAdmins,
                ]
            ],
            [
                'name' => 'remove-presentation-from-individual-selection-list',
                'route' => '/api/v1/summits/{id}/selection-plans/{selection_plan_id}/tracks/{track_id}/selection-lists/individual/presentation-selections/{collection}/presentations/{presentation_id}',
                'http_method' => 'DELETE',
                'scopes' => [
                    sprintf(SummitScopes::WriteSummitData, $current_realm)
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::TrackChairs,
                    IGroup::TrackChairsAdmins,
                ]
            ],
            [
                'name' => 'get-team-selection-list',
                'route' => '/api/v1/summits/{id}/selection-plans/{selection_plan_id}/tracks/{track_id}/selection-lists/team',
                'http_method' => 'GET',
                'scopes' => [
                    sprintf(SummitScopes::ReadSummitData, $current_realm)
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::TrackChairs,
                    IGroup::TrackChairsAdmins,
                ]
            ],
            [
                'name' => 'add-team-selection-list',
                'route' => '/api/v1/summits/{id}/selection-plans/{selection_plan_id}/tracks/{track_id}/selection-lists/team',
                'http_method' => 'POST',
                'scopes' => [
                    sprintf(SummitScopes::WriteSummitData, $current_realm)
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::TrackChairs,
                    IGroup::TrackChairsAdmins,
                ]
            ],
            [
                'name' => 'reorder-selection-list',
                'route' => '/api/v1/summits/{id}/selection-plans/{selection_plan_id}/tracks/{track_id}/selection-lists/{list_id}/reorder',
                'http_method' => 'PUT',
                'scopes' => [
                    sprintf(SummitScopes::WriteSummitData, $current_realm)
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::TrackChairs,
                    IGroup::TrackChairsAdmins,
                ]
            ],
            //track chair rating types & score types
            [
                'name' => 'get-track-chair-rating-types',
                'route' => '/api/v1/summits/{id}/selection-plans/{selection_plan_id}/track-chair-rating-types',
                'http_method' => 'GET',
                'scopes' => [
                    sprintf(SummitScopes::ReadSummitData, $current_realm)
                ]
            ],
            [
                'name' => 'add-track-chair-rating-type',
                'route' => '/api/v1/summits/{id}/selection-plans/{selection_plan_id}/track-chair-rating-types',
                'http_method' => 'POST',
                'scopes' => [
                    sprintf(SummitScopes::WriteSummitData, $current_realm)
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::TrackChairs,
                    IGroup::TrackChairsAdmins,
                ]
            ],
            [
                'name' => 'get-track-chair-rating-type',
                'route' => '/api/v1/summits/{id}/selection-plans/{selection_plan_id}/track-chair-rating-types/{type_id}',
                'http_method' => 'GET',
                'scopes' => [
                    sprintf(SummitScopes::ReadSummitData, $current_realm)
                ]
            ],
            [
                'name' => 'update-track-chair-rating-type',
                'route' => '/api/v1/summits/{id}/selection-plans/{selection_plan_id}/track-chair-rating-types/{type_id}',
                'http_method' => 'PUT',
                'scopes' => [
                    sprintf(SummitScopes::WriteSummitData, $current_realm)
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::TrackChairs,
                    IGroup::TrackChairsAdmins,
                ]
            ],
            [
                'name' => 'remove-track-chair-rating-type',
                'route' => '/api/v1/summits/{id}/selection-plans/{selection_plan_id}/track-chair-rating-types/{type_id}',
                'http_method' => 'DELETE',
                'scopes' => [
                    sprintf(SummitScopes::WriteSummitData, $current_realm)
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::TrackChairs,
                    IGroup::TrackChairsAdmins,
                ]
            ],
            [
                'name' => 'get-track-chair-scope-types',
                'route' => '/api/v1/summits/{id}/selection-plans/{selection_plan_id}/track-chair-rating-types/{type_id}/score-types',
                'http_method' => 'GET',
                'scopes' => [
                    sprintf(SummitScopes::ReadSummitData, $current_realm)
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::TrackChairs,
                    IGroup::TrackChairsAdmins,
                ]
            ],
            [
                'name' => 'add-track-chair-scope-type',
                'route' => '/api/v1/summits/{id}/selection-plans/{selection_plan_id}/track-chair-rating-types/{type_id}/score-types',
                'http_method' => 'POST',
                'scopes' => [
                    sprintf(SummitScopes::WriteSummitData, $current_realm)
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::TrackChairs,
                    IGroup::TrackChairsAdmins,
                ]
            ],
            [
                'name' => 'get-track-chair-scope-type',
                'route' => '/api/v1/summits/{id}/selection-plans/{selection_plan_id}/track-chair-rating-types/{type_id}/score-types/{score_type_id}',
                'http_method' => 'GET',
                'scopes' => [
                    sprintf(SummitScopes::ReadSummitData, $current_realm)
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::TrackChairs,
                    IGroup::TrackChairsAdmins,
                ]
            ],
            [
                'name' => 'update-track-chair-scope-type',
                'route' => '/api/v1/summits/{id}/selection-plans/{selection_plan_id}/track-chair-rating-types/{type_id}/score-types/{score_type_id}',
                'http_method' => 'PUT',
                'scopes' => [
                    sprintf(SummitScopes::WriteSummitData, $current_realm)
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::TrackChairs,
                    IGroup::TrackChairsAdmins,
                ]
            ],
            [
                'name' => 'remove-track-chair-scope-type',
                'route' => '/api/v1/summits/{id}/selection-plans/{selection_plan_id}/track-chair-rating-types/{type_id}/score-types/{score_type_id}',
                'http_method' => 'DELETE',
                'scopes' => [
                    sprintf(SummitScopes::WriteSummitData, $current_realm)
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::TrackChairs,
                    IGroup::TrackChairsAdmins,
                ]
            ],

            // proposed schedule
            [
                'name' => 'get-proposed-schedule',
                'route' => '/api/v1/summits/{id}/proposed-schedules/{source}/presentations',
                'http_method' => 'GET',
                'scopes' => [
                    sprintf(SummitScopes::ReadAllSummitData, $current_realm),
                    sprintf(SummitScopes::ReadSummitData, $current_realm)
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                    IGroup::TrackChairs,
                    IGroup::TrackChairsAdmins,
                ]
            ],
            [
                'name' => 'propose-proposed-schedule-event',
                'route' => '/api/v1/summits/{id}/proposed-schedules/{source}/presentations/{presentation_id}/propose',
                'http_method' => 'PUT',
                'scopes' => [
                    sprintf(SummitScopes::WriteSummitData, $current_realm)
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                    IGroup::TrackChairs,
                    IGroup::TrackChairsAdmins,
                ]
            ],
            [
                'name' => 'unpropose-proposed-schedule-event',
                'route' => '/api/v1/summits/{id}/proposed-schedules/{source}/presentations/{presentation_id}/propose',
                'http_method' => 'DELETE',
                'scopes' => [
                    sprintf(SummitScopes::WriteSummitData, $current_realm)
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                    IGroup::TrackChairs,
                    IGroup::TrackChairsAdmins,
                ]
            ],
            [
                'name' => 'publish-all-proposed-schedule-events',
                'route' => '/api/v1/summits/{id}/proposed-schedules/{source}/presentations/all/publish',
                'http_method' => 'PUT',
                'scopes' => [
                    sprintf(SummitScopes::WriteSummitData, $current_realm)
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::SummitAdministrators,
                    IGroup::Administrators,
                    IGroup::TrackChairs,
                    IGroup::TrackChairsAdmins,
                ]
            ],
            [
                'name' => 'add-proposed-schedule-lock',
                'route' => '/api/v1/summits/{id}/proposed-schedules/{source}/tracks/{track_id}/lock',
                'http_method' => 'POST',
                'scopes' => [
                    sprintf(SummitScopes::WriteSummitData, $current_realm)
                ],
                'authz_groups' => [
                    IGroup::TrackChairs,
                    IGroup::TrackChairsAdmins,
                ]
            ],
            [
                'name' => 'remove-proposed-schedule-lock',
                'route' => '/api/v1/summits/{id}/proposed-schedules/{source}/tracks/{track_id}/lock',
                'http_method' => 'DELETE',
                'scopes' => [
                    sprintf(SummitScopes::WriteSummitData, $current_realm)
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators
                ]
            ],
            [
                'name' => 'get-proposed-schedule-locks',
                'route' => '/api/v1/summits/{id}/proposed-schedules/{source}/locks',
                'http_method' => 'GET',
                'scopes' => [
                    sprintf(SummitScopes::ReadSummitData, $current_realm)
                ]
            ],
            [
                'name' => 'generate-qr-enc-key',
                'route' => '/api/v1/summits/{id}/qr-codes/all/enc-key',
                'http_method' => 'PUT',
                'scopes' => [
                    sprintf(SummitScopes::WriteSummitData, $current_realm)
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::SummitAdministrators,
                    IGroup::Administrators
                ]
            ],
            [
                'name' => 'get-registration-feed-metadata',
                'route' => '/api/v1/summits/{id}/registration-feed-metadata',
                'http_method' => 'GET',
                'scopes' => [
                    sprintf(SummitScopes::ReadSummitData, $current_realm),
                    sprintf(SummitScopes::ReadAllSummitData, $current_realm),
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::SummitAdministrators,
                    IGroup::SummitRegistrationAdmins,
                    IGroup::Administrators,
                    IGroup::Sponsors,
                ]
            ],
            [
                'name' => 'add-registration-feed-metadata',
                'route' => '/api/v1/summits/{id}/registration-feed-metadata',
                'http_method' => 'POST',
                'scopes' => [
                    sprintf(SummitScopes::WriteSummitData, $current_realm),
                    sprintf(SummitScopes::WriteRegistrationData, $current_realm)
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::SummitAdministrators,
                    IGroup::Administrators,
                ],
            ],
            [
                'name' => 'delete-registration-feed-metadata',
                'route' => '/api/v1/summits/{id}/registration-feed-metadata/{metadata_id}',
                'http_method' => 'DELETE',
                'scopes' => [
                    sprintf(SummitScopes::WriteSummitData, $current_realm),
                    sprintf(SummitScopes::WriteRegistrationData, $current_realm)
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::SummitAdministrators,
                    IGroup::Administrators
                ],
            ],
            [
                'name' => 'update-registration-feed-metadata',
                'route' => '/api/v1/summits/{id}/registration-feed-metadata/{metadata_id}',
                'http_method' => 'PUT',
                'scopes' => [
                    sprintf(SummitScopes::WriteSummitData, $current_realm),
                    sprintf(SummitScopes::WriteRegistrationData, $current_realm)
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::SummitAdministrators,
                    IGroup::Administrators
                ],
            ],
            [
                'name' => 'get-registration-feed-metadata-by-id',
                'route' => '/api/v1/summits/{id}/registration-feed-metadata/{metadata_id}',
                'http_method' => 'GET',
                'scopes' => [
                    sprintf(SummitScopes::ReadSummitData, $current_realm),
                    sprintf(SummitScopes::ReadAllSummitData, $current_realm),
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::SummitAdministrators,
                    IGroup::Administrators
                ],
            ],
            // lead report settings
            [
                'name' => 'get-summit-report-settings-metadata',
                'route' => '/api/v1/summits/{id}/lead-report-settings/metadata',
                'http_method' => 'GET',
                'scopes' => [
                    sprintf(SummitScopes::ReadSummitData, $current_realm),
                    sprintf(SummitScopes::ReadAllSummitData, $current_realm)
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                    IGroup::Sponsors,
                    IGroup::SponsorExternalUsers,
                ]
            ],
            [
                'name' => 'get-summit-report-settings',
                'route' => '/api/v1/summits/{id}/lead-report-settings',
                'http_method' => 'GET',
                'scopes' => [
                    sprintf(SummitScopes::ReadSummitData, $current_realm),
                    sprintf(SummitScopes::ReadAllSummitData, $current_realm)
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                    IGroup::Sponsors,
                    IGroup::SponsorExternalUsers,
                ]
            ],
            [
                'name' => 'add-summit-report-settings',
                'route' => '/api/v1/summits/{id}/lead-report-settings',
                'http_method' => 'POST',
                'scopes' => [
                    sprintf(SummitScopes::WriteSummitData, $current_realm),
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                ]
            ],
            [
                'name' => 'update-summit-report-settings',
                'route' => '/api/v1/summits/{id}/lead-report-settings',
                'http_method' => 'PUT',
                'scopes' => [
                    sprintf(SummitScopes::WriteSummitData, $current_realm),
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                ]
            ],
            //session overflow streaming
            [
                'name' => 'update-overflow-streaming',
                'route' => '/api/v1/summits/{id}/events/{event_id}/overflow',
                'http_method' => 'PUT',
                'scopes' => [sprintf(SummitScopes::WriteEventData, $current_realm)],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                ]
            ],
            [
                'name' => 'delete-overflow-streaming',
                'route' => '/api/v1/summits/{id}/events/{event_id}/overflow',
                'http_method' => 'DELETE',
                'scopes' => [sprintf(SummitScopes::WriteEventData, $current_realm)],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::SummitAdministrators,
                ]
            ],
            [
                'name' => 'validate-badge',
                'route' => '/api/v1/summits/{id}/badge/{badge}/validate',
                'http_method' => 'GET',
                'scopes' => [
                    sprintf(SummitScopes::ReadBadgeScanValidate, $current_realm),
                ],
                'authz_groups' => [
                    IGroup::SponsorExternalUsers,
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                ]
            ],
        ]);

    }

    private function seedAuditLogEndpoints()
    {
        $current_realm = Config::get('app.scope_base_realm');

        $this->seedApiEndpoints('audit-logs', [
            [
                'name' => 'get-summit-audit-log',
                'route' => '/api/v1/audit-logs',
                'http_method' => 'GET',
                'scopes' => [
                    sprintf(SummitScopes::ReadAuditLogs, $current_realm),
                ],
                'authz_groups' => [
                    IGroup::SuperAdmins,
                    IGroup::Administrators,
                    IGroup::TrackChairsAdmins,
                    IGroup::SummitAdministrators
                ]
            ]
        ]);
    }

    /**
     * @param string $api_name
     * @param array $endpoints_info
     */
    private function seedApiEndpoints($api_name, array $endpoints_info)
    {

        $api = EntityManager::getRepository(\App\Models\ResourceServer\Api::class)->findOneBy(['name' => $api_name]);
        if (is_null($api)) return;

        foreach ($endpoints_info as $endpoint_info) {

            $endpoint = new ApiEndpoint();
            $endpoint->setName($endpoint_info['name']);
            $endpoint->setRoute($endpoint_info['route']);
            $endpoint->setHttpMethod($endpoint_info['http_method']);
            $endpoint->setActive(true);
            $endpoint->setAllowCors(true);
            $endpoint->setAllowCredentials(true);
            $endpoint->setApi($api);

            if (isset($endpoint_info['rate_limit']))
                $endpoint->setRateLimit(intval($endpoint_info['rate_limit']));

            if (isset($endpoint_info['rate_limit_decay']))
                $endpoint->setRateLimitDecay(intval($endpoint_info['rate_limit_decay']));

            foreach ($endpoint_info['scopes'] as $scope_name) {
                $scope = EntityManager::getRepository(\App\Models\ResourceServer\ApiScope::class)->findOneBy(['name' => $scope_name]);
                if (is_null($scope)) continue;
                $endpoint->addScope($scope);
            }

            if (isset($endpoint_info['authz_groups'])) {
                foreach ($endpoint_info['authz_groups'] as $authz_group_slug) {
                    $endpoint->addAuthGroup($authz_group_slug);
                }
            }

            EntityManager::persist($endpoint);
        }

        EntityManager::flush();
    }

    private function seedMemberEndpoints()
    {
        $current_realm = Config::get('app.scope_base_realm');

        $this->seedApiEndpoints('members', [
                // members
                [
                    'name' => 'get-members',
                    'route' => '/api/v1/members',
                    'http_method' => 'GET',
                    'scopes' => [sprintf(MemberScopes::ReadMemberData, $current_realm)],
                ],
                [
                    'name' => 'get-my-member',
                    'route' => '/api/v1/members/me',
                    'http_method' => 'GET',
                    'scopes' => [sprintf(MemberScopes::ReadMyMemberData, $current_realm)],
                ],
                [
                    'name' => 'update-my-member',
                    'route' => '/api/v1/members/me',
                    'http_method' => 'PUT',
                    'scopes' => [sprintf(MemberScopes::WriteMyMemberData, $current_realm)],
                ],
                // my membership
                [
                    'name' => 'sign-foundation-membership',
                    'route' => '/api/v1/members/me/membership/foundation',
                    'http_method' => 'PUT',
                    'scopes' => [sprintf(MemberScopes::WriteMyMemberData, $current_realm)],
                ],
                [
                    'name' => 'sign-individual-membership',
                    'route' => '/api/v1/members/me/membership/individual',
                    'http_method' => 'PUT',
                    'scopes' => [sprintf(MemberScopes::WriteMyMemberData, $current_realm)],
                ],
                [
                    'name' => 'sign-community-membership',
                    'route' => '/api/v1/members/me/membership/community',
                    'http_method' => 'PUT',
                    'scopes' => [sprintf(MemberScopes::WriteMyMemberData, $current_realm)],
                ],
                [
                    'name' => 'resign-membership',
                    'route' => '/api/v1/members/me/membership/resign',
                    'http_method' => 'DELETE',
                    'scopes' => [sprintf(MemberScopes::WriteMyMemberData, $current_realm)],
                ],
                // my member affiliations
                [
                    'name' => 'get-my-member-affiliations',
                    'route' => '/api/v1/members/me/affiliations',
                    'http_method' => 'GET',
                    'scopes' => [sprintf(MemberScopes::ReadMyMemberData, $current_realm)],
                ],
                [
                    'name' => 'add-my-member-affiliation',
                    'route' => '/api/v1/members/me/affiliations',
                    'http_method' => 'POST',
                    'scopes' => [
                        sprintf(MemberScopes::WriteMyMemberData, $current_realm)
                    ],
                ],
                [
                    'name' => 'update-my-member-affiliation',
                    'route' => '/api/v1/members/me/affiliations/{affiliation_id}',
                    'http_method' => 'PUT',
                    'scopes' => [
                        sprintf(MemberScopes::WriteMyMemberData, $current_realm)
                    ],
                ],
                [
                    'name' => 'delete-my-member-affiliation',
                    'route' => '/api/v1/members/me/affiliations/{affiliation_id}',
                    'http_method' => 'DELETE',
                    'scopes' => [
                        sprintf(MemberScopes::WriteMyMemberData, $current_realm)
                    ],
                ],
                // member affiliations
                [
                    'name' => 'get-member-affiliations',
                    'route' => '/api/v1/members/{member_id}/affiliations',
                    'http_method' => 'GET',
                    'scopes' => [sprintf('%s/members/read', $current_realm)],
                    'authz_groups' => [
                        IGroup::SuperAdmins,
                        IGroup::Administrators,
                        IGroup::SummitAdministrators,
                    ]
                ],
                [
                    'name' => 'add-member-affiliation',
                    'route' => '/api/v1/members/{member_id}/affiliations',
                    'http_method' => 'POST',
                    'scopes' => [
                        sprintf(MemberScopes::WriteMemberData, $current_realm)
                    ],
                    'authz_groups' => [
                        IGroup::SuperAdmins,
                        IGroup::Administrators,
                        IGroup::SummitAdministrators,
                    ]
                ],
                [
                    'name' => 'update-member-affiliation',
                    'route' => '/api/v1/members/{member_id}/affiliations/{affiliation_id}',
                    'http_method' => 'PUT',
                    'scopes' => [
                        sprintf(MemberScopes::WriteMemberData, $current_realm)
                    ],
                    'authz_groups' => [
                        IGroup::SuperAdmins,
                        IGroup::Administrators,
                        IGroup::SummitAdministrators,
                    ]
                ],
                [
                    'name' => 'delete-member-affiliation',
                    'route' => '/api/v1/members/{member_id}/affiliations/{affiliation_id}',
                    'http_method' => 'DELETE',
                    'scopes' => [
                        sprintf(MemberScopes::WriteMemberData, $current_realm)
                    ],
                    'authz_groups' => [
                        IGroup::SuperAdmins,
                        IGroup::Administrators,
                        IGroup::SummitAdministrators,
                    ]
                ],
                [
                    'name' => 'delete-member-rsvp',
                    'route' => '/api/v1/members/{member_id}/rsvp/{rsvp_id}',
                    'http_method' => 'DELETE',
                    'scopes' => [
                        sprintf(MemberScopes::WriteMemberData, $current_realm)
                    ],
                    'authz_groups' => [
                        IGroup::SuperAdmins,
                        IGroup::Administrators,
                        IGroup::SummitAdministrators,
                    ]
                ],
            ]
        );
    }

    private function seedTagsEndpoints()
    {
        $current_realm = Config::get('app.scope_base_realm');

        $this->seedApiEndpoints('tags', [
                // tags
                [
                    'name' => 'get-tags',
                    'route' => '/api/v1/tags',
                    'http_method' => 'GET',
                    'scopes' => [
                        sprintf(SummitScopes::ReadAllSummitData, $current_realm),
                        sprintf(SummitScopes::ReadSummitData, $current_realm),
                        sprintf(SummitScopes::ReadTagsData, $current_realm)
                    ],
                ],
                [
                    'name' => 'get-tag',
                    'route' => '/api/v1/tags/{id}',
                    'http_method' => 'GET',
                    'scopes' => [
                        sprintf(SummitScopes::ReadAllSummitData, $current_realm),
                        sprintf(SummitScopes::ReadSummitData, $current_realm),
                        sprintf(SummitScopes::ReadTagsData, $current_realm)
                    ],
                ],
                [
                    'name' => 'add-tag',
                    'route' => '/api/v1/tags',
                    'http_method' => 'POST',
                    'scopes' => [
                        sprintf(SummitScopes::WriteSummitData, $current_realm),
                        sprintf(SummitScopes::WriteTagsData, $current_realm)
                    ],
                    'authz_groups' => [
                        IGroup::SuperAdmins,
                        IGroup::Administrators,
                        IGroup::SummitAdministrators,
                    ]
                ],
                [
                    'name' => 'update-tag',
                    'route' => '/api/v1/tags/{id}',
                    'http_method' => 'PUT',
                    'scopes' => [
                        sprintf(SummitScopes::WriteSummitData, $current_realm),
                        sprintf(SummitScopes::WriteTagsData, $current_realm)
                    ],
                    'authz_groups' => [
                        IGroup::SuperAdmins,
                        IGroup::Administrators,
                        IGroup::SummitAdministrators,
                    ]
                ],
                [
                    'name' => 'delete-tag',
                    'route' => '/api/v1/tags/{id}',
                    'http_method' => 'DELETE',
                    'scopes' => [
                        sprintf(SummitScopes::WriteSummitData, $current_realm),
                        sprintf(SummitScopes::WriteTagsData, $current_realm)
                    ],
                    'authz_groups' => [
                        IGroup::SuperAdmins,
                        IGroup::Administrators,
                        IGroup::SummitAdministrators,
                    ]
                ]
            ]
        );
    }

    private function seedCompaniesEndpoints()
    {
        $current_realm = Config::get('app.scope_base_realm');
        $this->seedApiEndpoints('companies', [
                [
                    'name' => 'get-companies',
                    'route' => '/api/v1/companies',
                    'http_method' => 'GET',
                    'scopes' => [
                        sprintf(SummitScopes::ReadAllSummitData, $current_realm),
                        sprintf(SummitScopes::ReadSummitData, $current_realm),
                        sprintf(CompanyScopes::Read, $current_realm)
                    ],
                ],
                [
                    'name' => 'add-company',
                    'route' => '/api/v1/companies',
                    'http_method' => 'POST',
                    'scopes' => [
                        sprintf(CompanyScopes::Write, $current_realm)
                    ],
                    'authz_groups' => [
                        IGroup::SuperAdmins,
                        IGroup::Administrators,
                    ]
                ],
                [
                    'name' => 'update-company',
                    'route' => '/api/v1/companies/{id}',
                    'http_method' => 'PUT',
                    'scopes' => [
                        sprintf(CompanyScopes::Write, $current_realm)
                    ],
                    'authz_groups' => [
                        IGroup::SuperAdmins,
                        IGroup::Administrators,
                    ]
                ],
                [
                    'name' => 'delete-company',
                    'route' => '/api/v1/companies/{id}',
                    'http_method' => 'DELETE',
                    'scopes' => [
                        sprintf(CompanyScopes::Write, $current_realm)
                    ],
                    'authz_groups' => [
                        IGroup::SuperAdmins,
                        IGroup::Administrators,
                    ]
                ],
                [
                    'name' => 'get-company',
                    'route' => '/api/v1/companies/{id}',
                    'http_method' => 'GET',
                    'scopes' => [
                        sprintf(CompanyScopes::Read, $current_realm)
                    ]
                ],
                [
                    'name' => 'add-company-logo',
                    'route' => '/api/v1/companies/{id}/logo',
                    'http_method' => 'POST',
                    'scopes' => [
                        sprintf(CompanyScopes::Write, $current_realm)
                    ],
                    'authz_groups' => [
                        IGroup::SuperAdmins,
                        IGroup::Administrators,
                    ]
                ],
                [
                    'name' => 'delete-company-logo',
                    'route' => '/api/v1/companies/{id}/logo',
                    'http_method' => 'DELETE',
                    'scopes' => [
                        sprintf(CompanyScopes::Write, $current_realm)
                    ],
                    'authz_groups' => [
                        IGroup::SuperAdmins,
                        IGroup::Administrators,
                    ]
                ],
                [
                    'name' => 'add-company-big-logo',
                    'route' => '/api/v1/companies/{id}/logo/big',
                    'http_method' => 'POST',
                    'scopes' => [
                        sprintf(CompanyScopes::Write, $current_realm)
                    ],
                    'authz_groups' => [
                        IGroup::SuperAdmins,
                        IGroup::Administrators,
                    ]
                ],
                [
                    'name' => 'delete-company-big-logo',
                    'route' => '/api/v1/companies/{id}/logo/big',
                    'http_method' => 'DELETE',
                    'scopes' => [
                        sprintf(CompanyScopes::Write, $current_realm)
                    ],
                    'authz_groups' => [
                        IGroup::SuperAdmins,
                        IGroup::Administrators,
                    ]
                ],

            ]
        );
    }

    private function seedSponsoredProjectsEndpoints()
    {
        $current_realm = Config::get('app.scope_base_realm');
        $this->seedApiEndpoints('sponsored-projects', [
                [
                    'name' => 'get-sponsored-projects',
                    'route' => '/api/v1/sponsored-projects',
                    'http_method' => 'GET',
                    'scopes' => [
                        sprintf(SponsoredProjectScope::Read, $current_realm)
                    ],
                ],
                [
                    'name' => 'add-sponsored-projects',
                    'route' => '/api/v1/sponsored-projects',
                    'http_method' => 'POST',
                    'scopes' => [
                        sprintf(SponsoredProjectScope::Write, $current_realm)
                    ],
                    'authz_groups' => [
                        IGroup::SuperAdmins,
                        IGroup::Administrators,
                    ]
                ],
                [
                    'name' => 'update-sponsored-projects',
                    'route' => '/api/v1/sponsored-projects/{id}',
                    'http_method' => 'PUT',
                    'scopes' => [
                        sprintf(SponsoredProjectScope::Write, $current_realm)
                    ],
                    'authz_groups' => [
                        IGroup::SuperAdmins,
                        IGroup::Administrators,
                    ]
                ],
                [
                    'name' => 'delete-sponsored-projects',
                    'route' => '/api/v1/sponsored-projects/{id}',
                    'http_method' => 'DELETE',
                    'scopes' => [
                        sprintf(SponsoredProjectScope::Write, $current_realm)
                    ],
                    'authz_groups' => [
                        IGroup::SuperAdmins,
                        IGroup::Administrators,
                    ]
                ],
                [
                    'name' => 'get-sponsored-project',
                    'route' => '/api/v1/sponsored-projects/{id}',
                    'http_method' => 'GET',
                    'scopes' => [
                        sprintf(SponsoredProjectScope::Read, $current_realm)
                    ]
                ],
                [
                    'name' => 'add-sponsored-project-logo',
                    'route' => '/api/v1/sponsored-projects/{id}/logo',
                    'http_method' => 'POST',
                    'scopes' => [
                        sprintf(SponsoredProjectScope::Write, $current_realm)
                    ],
                    'authz_groups' => [
                        IGroup::SuperAdmins,
                        IGroup::Administrators,
                    ]
                ],
                [
                    'name' => 'delete-sponsored-project-logo',
                    'route' => '/api/v1/sponsored-projects/{id}/logo',
                    'http_method' => 'DELETE',
                    'scopes' => [
                        sprintf(SponsoredProjectScope::Write, $current_realm)
                    ],
                    'authz_groups' => [
                        IGroup::SuperAdmins,
                        IGroup::Administrators,
                    ]
                ],
                // sponsorship types
                [
                    'name' => 'get-sponsored-project-sponsorship-types',
                    'route' => '/api/v1/sponsored-projects/{id}/sponsorship-types',
                    'http_method' => 'GET',
                    'scopes' => [
                        sprintf(SponsoredProjectScope::Read, $current_realm)
                    ],
                ],
                [
                    'name' => 'add-sponsored-project-sponsorship-types',
                    'route' => '/api/v1/sponsored-projects/{id}/sponsorship-types',
                    'http_method' => 'POST',
                    'scopes' => [
                        sprintf(SponsoredProjectScope::Write, $current_realm)
                    ],
                    'authz_groups' => [
                        IGroup::SuperAdmins,
                        IGroup::Administrators,
                    ]
                ],
                [
                    'name' => 'update-sponsored-project-sponsorship-types',
                    'route' => '/api/v1/sponsored-projects/{id}/sponsorship-types/{sponsorship_type_id}',
                    'http_method' => 'PUT',
                    'scopes' => [
                        sprintf(SponsoredProjectScope::Write, $current_realm)
                    ],
                    'authz_groups' => [
                        IGroup::SuperAdmins,
                        IGroup::Administrators,
                    ]
                ],

                [
                    'name' => 'delete-sponsored-project-sponsorship-types',
                    'route' => '/api/v1/sponsored-projects/{id}/sponsorship-types/{sponsorship_type_id}',
                    'http_method' => 'DELETE',
                    'scopes' => [
                        sprintf(SponsoredProjectScope::Write, $current_realm)
                    ],
                    'authz_groups' => [
                        IGroup::SuperAdmins,
                        IGroup::Administrators,
                    ]
                ],

                [
                    'name' => 'get-sponsored-project-sponsorship-type',
                    'route' => '/api/v1/sponsored-projects/{id}/sponsorship-types/{sponsorship_type_id}',
                    'http_method' => 'GET',
                    'scopes' => [
                        sprintf(SponsoredProjectScope::Read, $current_realm)
                    ],
                ],
                [
                    'name' => 'get-sponsored-project-supporting-companies',
                    'route' => '/api/v1/sponsored-projects/{id}/sponsorship-types/{sponsorship_type_id}/supporting-companies',
                    'http_method' => 'GET',
                    'scopes' => [
                        sprintf(SponsoredProjectScope::Read, $current_realm)
                    ],
                ],
                [
                    'name' => 'add-sponsored-project-supporting-companies',
                    'route' => '/api/v1/sponsored-projects/{id}/sponsorship-types/{sponsorship_type_id}/supporting-companies',
                    'http_method' => 'POST',
                    'scopes' => [
                        sprintf(SponsoredProjectScope::Write, $current_realm)
                    ],
                    'authz_groups' => [
                        IGroup::SuperAdmins,
                        IGroup::Administrators,
                    ]
                ],
                [
                    'name' => 'update-sponsored-project-supporting-companies',
                    'route' => '/api/v1/sponsored-projects/{id}/sponsorship-types/{sponsorship_type_id}/supporting-companies/{company_id}',
                    'http_method' => 'PUT',
                    'scopes' => [
                        sprintf(SponsoredProjectScope::Write, $current_realm)
                    ],
                    'authz_groups' => [
                        IGroup::SuperAdmins,
                        IGroup::Administrators,
                    ]
                ],
                [
                    'name' => 'delete-sponsored-project-supporting-companies',
                    'route' => '/api/v1/sponsored-projects/{id}/sponsorship-types/{sponsorship_type_id}/supporting-companies/{company_id}',
                    'http_method' => 'DELETE',
                    'scopes' => [
                        sprintf(SponsoredProjectScope::Write, $current_realm)
                    ],
                    'authz_groups' => [
                        IGroup::SuperAdmins,
                        IGroup::Administrators,
                    ]
                ],
                [
                    'name' => 'get-sponsored-project-supporting-company',
                    'route' => '/api/v1/sponsored-projects/{id}/sponsorship-types/{sponsorship_type_id}/supporting-companies/{company_id}',
                    'http_method' => 'GET',
                    'scopes' => [
                        sprintf(SponsoredProjectScope::Read, $current_realm)
                    ],
                    'authz_groups' => [
                        IGroup::SuperAdmins,
                        IGroup::Administrators,
                    ]
                ],
                [
                    'name' => 'get-sponsored-subprojects',
                    'route' => '/api/v1/sponsored-projects/{id}/subprojects',
                    'http_method' => 'GET',
                    'scopes' => [
                        sprintf(SponsoredProjectScope::Read, $current_realm)
                    ],
                    'authz_groups' => [
                        IGroup::SuperAdmins,
                        IGroup::Administrators,
                    ]
                ]
            ]
        );
    }

    private function seedGroupsEndpoints()
    {
        $current_realm = Config::get('app.scope_base_realm');

        $this->seedApiEndpoints('groups', [
                // members
                [
                    'name' => 'get-groups',
                    'route' => '/api/v1/groups',
                    'http_method' => 'GET',
                    'scopes' => [
                        sprintf(SummitScopes::ReadAllSummitData, $current_realm),
                        sprintf(SummitScopes::ReadSummitData, $current_realm),
                        sprintf('%s/groups/read', $current_realm)
                    ],
                ]
            ]
        );
    }

    private function seedOrganizationsEndpoints()
    {
        $current_realm = Config::get('app.scope_base_realm');

        $this->seedApiEndpoints('organizations', [
                // organizations
                [
                    'name' => 'get-organizations',
                    'route' => '/api/v1/organizations',
                    'http_method' => 'GET',
                    'scopes' => [
                        sprintf(OrganizationScopes::ReadOrganizationData, $current_realm)
                    ],
                ]
            ]
        );

        $this->seedApiEndpoints('organizations', [
                // organizations
                [
                    'name' => 'add-organizations',
                    'route' => '/api/v1/organizations',
                    'http_method' => 'POST',
                    'scopes' => [
                        sprintf(OrganizationScopes::WriteOrganizationData, $current_realm)
                    ],
                ]
            ]
        );
    }

    public function seedTrackQuestionTemplateEndpoints()
    {
        $current_realm = Config::get('app.scope_base_realm');

        $this->seedApiEndpoints('track-question-templates', [
                // track question templates
                [
                    'name' => 'get-track-question-templates',
                    'route' => '/api/v1/track-question-templates',
                    'http_method' => 'GET',
                    'scopes' => [
                        sprintf(SummitScopes::ReadAllSummitData, $current_realm),
                    ],
                    'authz_groups' => [
                        IGroup::SuperAdmins,
                        IGroup::Administrators,
                        IGroup::SummitAdministrators,
                    ]
                ],
                [
                    'name' => 'add-track-question-templates',
                    'route' => '/api/v1/track-question-templates',
                    'http_method' => 'POST',
                    'scopes' => [
                        sprintf(SummitScopes::WriteSummitData, $current_realm),
                        sprintf(SummitScopes::WriteTrackQuestionTemplateData, $current_realm),
                    ],
                    'authz_groups' => [
                        IGroup::SuperAdmins,
                        IGroup::Administrators,
                        IGroup::SummitAdministrators,
                    ]
                ],
                [
                    'name' => 'update-track-question-templates',
                    'route' => '/api/v1/track-question-templates/{track_question_template_id}',
                    'http_method' => 'PUT',
                    'scopes' => [
                        sprintf(SummitScopes::WriteSummitData, $current_realm),
                        sprintf(SummitScopes::WriteTrackQuestionTemplateData, $current_realm),
                    ],
                    'authz_groups' => [
                        IGroup::SuperAdmins,
                        IGroup::Administrators,
                        IGroup::SummitAdministrators,
                    ]
                ],
                [
                    'name' => 'delete-track-question-templates',
                    'route' => '/api/v1/track-question-templates/{track_question_template_id}',
                    'http_method' => 'DELETE',
                    'scopes' => [
                        sprintf(SummitScopes::WriteSummitData, $current_realm),
                        sprintf(SummitScopes::WriteTrackQuestionTemplateData, $current_realm),
                    ],
                    'authz_groups' => [
                        IGroup::SuperAdmins,
                        IGroup::Administrators,
                        IGroup::SummitAdministrators,
                    ]
                ],
                [
                    'name' => 'get-track-question-template',
                    'route' => '/api/v1/track-question-templates/{track_question_template_id}',
                    'http_method' => 'GET',
                    'scopes' => [
                        sprintf(SummitScopes::ReadAllSummitData, $current_realm)
                    ],
                    'authz_groups' => [
                        IGroup::SuperAdmins,
                        IGroup::Administrators,
                        IGroup::SummitAdministrators,
                    ]
                ],
                [
                    'name' => 'get-track-question-templates-metadata',
                    'route' => '/api/v1/track-question-templates/metadata',
                    'http_method' => 'GET',
                    'scopes' => [
                        sprintf(SummitScopes::ReadAllSummitData, $current_realm)
                    ],
                    'authz_groups' => [
                        IGroup::SuperAdmins,
                        IGroup::Administrators,
                        IGroup::SummitAdministrators,
                    ]
                ],
                [
                    'name' => 'add-track-question-template-value',
                    'route' => '/api/v1/track-question-templates/{track_question_template_id}/values',
                    'http_method' => 'POST',
                    'scopes' => [
                        sprintf(SummitScopes::WriteSummitData, $current_realm),
                        sprintf(SummitScopes::WriteTrackQuestionTemplateData, $current_realm),
                    ],
                    'authz_groups' => [
                        IGroup::SuperAdmins,
                        IGroup::Administrators,
                        IGroup::SummitAdministrators,
                    ]
                ],
                [
                    'name' => 'update-track-question-template-value',
                    'route' => '/api/v1/track-question-templates/{track_question_template_id}/values/{track_question_template_value_id}',
                    'http_method' => 'PUT',
                    'scopes' => [
                        sprintf(SummitScopes::WriteSummitData, $current_realm),
                        sprintf(SummitScopes::WriteTrackQuestionTemplateData, $current_realm),
                    ],
                    'authz_groups' => [
                        IGroup::SuperAdmins,
                        IGroup::Administrators,
                        IGroup::SummitAdministrators,
                    ]
                ],
                [
                    'name' => 'delete-track-question-template-value',
                    'route' => '/api/v1/track-question-templates/{track_question_template_id}/values/{track_question_template_value_id}',
                    'http_method' => 'DELETE',
                    'scopes' => [
                        sprintf(SummitScopes::WriteSummitData, $current_realm),
                        sprintf(SummitScopes::WriteTrackQuestionTemplateData, $current_realm),
                    ],
                    'authz_groups' => [
                        IGroup::SuperAdmins,
                        IGroup::Administrators,
                        IGroup::SummitAdministrators,
                    ]
                ],
                [
                    'name' => 'get-track-question-template-value',
                    'route' => '/api/v1/track-question-templates/{track_question_template_id}/values/{track_question_template_value_id}',
                    'http_method' => 'GET',
                    'scopes' => [
                        sprintf(SummitScopes::ReadAllSummitData, $current_realm)
                    ],
                    'authz_groups' => [
                        IGroup::SuperAdmins,
                        IGroup::Administrators,
                        IGroup::SummitAdministrators,
                    ]
                ],
                // badge scans
                [
                    'name' => 'add-badge-scan',
                    'route' => '/api/v1/summits/{id}/badge-scans',
                    'http_method' => 'POST',
                    'scopes' => [
                        sprintf(SummitScopes::WriteBadgeScan, $current_realm)
                    ],
                    'authz_groups' => [
                        IGroup::SuperAdmins,
                        IGroup::Administrators,
                        IGroup::SummitAdministrators,
                        IGroup::Sponsors,
                        IGroup::SponsorExternalUsers,
                    ]
                ],
                [
                    'name' => 'update-badge-scan',
                    'route' => '/api/v1/summits/{id}/badge-scans/{scan_id}',
                    'http_method' => 'PUT',
                    'scopes' => [
                        sprintf(SummitScopes::WriteSummitData, $current_realm),
                        sprintf(SummitScopes::WriteBadgeScan, $current_realm),
                    ],
                    'authz_groups' => [
                        IGroup::SuperAdmins,
                        IGroup::Administrators,
                        IGroup::SummitAdministrators,
                        IGroup::Sponsors,
                        IGroup::SponsorExternalUsers,
                    ]
                ],
                [
                    'name' => 'get-badge-scan',
                    'route' => '/api/v1/summits/{id}/badge-scans/{scan_id}',
                    'http_method' => 'GET',
                    'scopes' => [
                        sprintf(SummitScopes::ReadAllSummitData, $current_realm),
                        sprintf(SummitScopes::ReadBadgeScan, $current_realm),
                        sprintf(SummitScopes::ReadMyBadgeScan, $current_realm)
                    ],
                    'authz_groups' => [
                        IGroup::SuperAdmins,
                        IGroup::Administrators,
                        IGroup::SummitAdministrators,
                        IGroup::Sponsors,
                        IGroup::SponsorExternalUsers,
                    ]
                ],
                [
                    'name' => 'get-my-badge-scans',
                    'route' => '/api/v1/summits/{id}/badge-scans/me',
                    'http_method' => 'GET',
                    'scopes' => [
                        sprintf(SummitScopes::ReadMyBadgeScan, $current_realm)
                    ],
                ],
                [
                    'name' => 'get-badge-scans',
                    'route' => '/api/v1/summits/{id}/badge-scans',
                    'http_method' => 'GET',
                    'scopes' => [
                        sprintf(SummitScopes::ReadAllSummitData, $current_realm),
                        sprintf(SummitScopes::ReadBadgeScan, $current_realm)
                    ],
                    'authz_groups' => [
                        IGroup::SuperAdmins,
                        IGroup::Administrators,
                        IGroup::SummitAdministrators,
                        IGroup::Sponsors,
                        IGroup::SponsorExternalUsers,
                    ]
                ],
                [
                    'name' => 'get-badge-scans-csv',
                    'route' => '/api/v1/summits/{id}/badge-scans/csv',
                    'http_method' => 'GET',
                    'scopes' => [
                        sprintf(SummitScopes::ReadAllSummitData, $current_realm),
                        sprintf(SummitScopes::ReadBadgeScan, $current_realm)
                    ],
                    'authz_groups' => [
                        IGroup::SuperAdmins,
                        IGroup::Administrators,
                        IGroup::SummitAdministrators,
                        IGroup::Sponsors,
                        IGroup::SponsorExternalUsers,
                    ]
                ],
                [
                    'name' => 'badge-scan-checkin',
                    'route' => '/api/v1/summits/{id}/badge-scans/checkin',
                    'http_method' => 'PUT',
                    'scopes' => [
                        sprintf(SummitScopes::WriteSummitData, $current_realm),
                        sprintf(SummitScopes::WriteBadgeScan, $current_realm)
                    ],
                ],

                // featured speakers

                [
                    'name' => 'get-featured-speakers',
                    'route' => '/api/v1/summits/{id}/featured-speakers',
                    'http_method' => 'GET',
                    'scopes' => [
                        sprintf(SummitScopes::ReadAllSummitData, $current_realm),
                        sprintf(SummitScopes::ReadSummitData, $current_realm),
                    ],
                    'authz_groups' => [
                        IGroup::SuperAdmins,
                        IGroup::Administrators,
                        IGroup::SummitAdministrators,
                    ]
                ],
                [
                    'name' => 'add-featured-speaker',
                    'route' => '/api/v1/summits/{id}/featured-speakers/{speaker_id}',
                    'http_method' => 'POST',
                    'scopes' => [
                        sprintf(SummitScopes::WriteSummitData, $current_realm)
                    ],
                    'authz_groups' => [
                        IGroup::SuperAdmins,
                        IGroup::Administrators,
                        IGroup::SummitAdministrators,
                    ]
                ],
                [
                    'name' => 'update-featured-speaker',
                    'route' => '/api/v1/summits/{id}/featured-speakers/{speaker_id}',
                    'http_method' => 'PUT',
                    'scopes' => [
                        sprintf(SummitScopes::WriteSummitData, $current_realm)
                    ],
                    'authz_groups' => [
                        IGroup::SuperAdmins,
                        IGroup::Administrators,
                        IGroup::SummitAdministrators,
                    ]
                ],
                [
                    'name' => 'delete-featured-speaker',
                    'route' => '/api/v1/summits/{id}/featured-speakers/{speaker_id}',
                    'http_method' => 'DELETE',
                    'scopes' => [
                        sprintf(SummitScopes::WriteSummitData, $current_realm)
                    ],
                    'authz_groups' => [
                        IGroup::SuperAdmins,
                        IGroup::Administrators,
                        IGroup::SummitAdministrators,
                    ]
                ],
                [
                    'name' => 'get-presentation-action-types',
                    'route' => '/api/v1/summits/{id}/presentation-action-types',
                    'http_method' => 'GET',
                    'scopes' => [
                        sprintf(SummitScopes::ReadAllSummitData, $current_realm),
                        sprintf(SummitScopes::ReadSummitData, $current_realm)
                    ],
                    'authz_groups' => [
                        IGroup::SuperAdmins,
                        IGroup::Administrators,
                        IGroup::SummitAdministrators,
                        IGroup::TrackChairsAdmins,
                        IGroup::TrackChairs,
                    ]
                ],
                [
                    'name' => 'get-presentation-action-types-csv',
                    'route' => '/api/v1/summits/{id}/presentation-action-types/csv',
                    'http_method' => 'GET',
                    'scopes' => [
                        sprintf(SummitScopes::ReadAllSummitData, $current_realm),
                        sprintf(SummitScopes::ReadSummitData, $current_realm)
                    ],
                    'authz_groups' => [
                        IGroup::SuperAdmins,
                        IGroup::Administrators,
                        IGroup::SummitAdministrators,
                        IGroup::TrackChairsAdmins,
                    ]
                ],
                [
                    'name' => 'add-presentation-action-types',
                    'route' => '/api/v1/summits/{id}/presentation-action-types',
                    'http_method' => 'POST',
                    'scopes' => [
                        sprintf(SummitScopes::WriteSummitData, $current_realm),
                    ],
                    'authz_groups' => [
                        IGroup::SuperAdmins,
                        IGroup::Administrators,
                        IGroup::SummitAdministrators,
                        IGroup::TrackChairsAdmins,
                    ]
                ],
                [
                    'name' => 'get-presentation-action-type-by-id',
                    'route' => '/api/v1/summits/{id}/presentation-action-types/{action_id}',
                    'http_method' => 'GET',
                    'scopes' => [
                        sprintf(SummitScopes::ReadAllSummitData, $current_realm),
                        sprintf(SummitScopes::ReadSummitData, $current_realm)
                    ],
                    'authz_groups' => [
                        IGroup::SuperAdmins,
                        IGroup::Administrators,
                        IGroup::SummitAdministrators,
                        IGroup::TrackChairsAdmins,
                    ]
                ],
                [
                    'name' => 'delete-presentation-action-type',
                    'route' => '/api/v1/summits/{id}/presentation-action-types/{action_id}',
                    'http_method' => 'DELETE',
                    'scopes' => [
                        sprintf(SummitScopes::WriteSummitData, $current_realm),
                    ],
                    'authz_groups' => [
                        IGroup::SuperAdmins,
                        IGroup::Administrators,
                        IGroup::SummitAdministrators,
                        IGroup::TrackChairsAdmins,
                    ]
                ],
                [
                    'name' => 'update-presentation-action-type',
                    'route' => '/api/v1/summits/{id}/presentation-action-types/{action_id}',
                    'http_method' => 'PUT',
                    'scopes' => [
                        sprintf(SummitScopes::WriteSummitData, $current_realm),
                    ],
                    'authz_groups' => [
                        IGroup::SuperAdmins,
                        IGroup::Administrators,
                        IGroup::SummitAdministrators,
                        IGroup::TrackChairsAdmins,
                    ]
                ],
                // registration companies
                [
                    'name' => 'get-registration-companies',
                    'route' => '/api/v1/summits/{id}/registration-companies',
                    'http_method' => 'GET',
                    'scopes' => [
                        sprintf(SummitScopes::ReadAllSummitData, $current_realm),
                        sprintf(SummitScopes::ReadSummitData, $current_realm)
                    ],
                ],
                [
                    'name' => 'import-registration-company',
                    'route' => '/api/v1/summits/{id}/registration-companies/csv',
                    'http_method' => 'POST',
                    'scopes' => [
                        sprintf(SummitScopes::WriteSummitData, $current_realm),
                    ],
                    'authz_groups' => [
                        IGroup::SuperAdmins,
                        IGroup::Administrators,
                        IGroup::SummitAdministrators,
                        IGroup::TrackChairsAdmins,
                    ]
                ],
                [
                    'name' => 'add-registration-company',
                    'route' => '/api/v1/summits/{id}/registration-companies/{company_id}',
                    'http_method' => 'PUT',
                    'scopes' => [
                        sprintf(SummitScopes::WriteSummitData, $current_realm),
                    ],
                    'authz_groups' => [
                        IGroup::SuperAdmins,
                        IGroup::Administrators,
                        IGroup::SummitAdministrators,
                        IGroup::TrackChairsAdmins,
                    ]
                ],
                [
                    'name' => 'delete-registration-company',
                    'route' => '/api/v1/summits/{id}/registration-companies/{company_id}',
                    'http_method' => 'DELETE',
                    'scopes' => [
                        sprintf(SummitScopes::WriteSummitData, $current_realm),
                    ],
                    'authz_groups' => [
                        IGroup::SuperAdmins,
                        IGroup::Administrators,
                        IGroup::SummitAdministrators,
                        IGroup::TrackChairsAdmins,
                    ]
                ]
            ]
        );
    }

    public function seedSummitAdministratorGroupsEndpoints()
    {
        $current_realm = Config::get('app.scope_base_realm');

        $this->seedApiEndpoints('summit-administrator-groups', [
                [
                    'name' => 'get-summit-administrator-groups',
                    'route' => '/api/v1/summit-administrator-groups',
                    'http_method' => 'GET',
                    'scopes' => [
                        sprintf(SummitScopes::ReadSummitAdminGroups, $current_realm),
                    ],
                    'authz_groups' => [
                        IGroup::SuperAdmins,
                        IGroup::Administrators,
                    ]
                ],
                [
                    'name' => 'get-summit-administrator-group',
                    'route' => '/api/v1/summit-administrator-groups/{group_id}',
                    'http_method' => 'GET',
                    'scopes' => [
                        sprintf(SummitScopes::ReadSummitAdminGroups, $current_realm),
                    ],
                    'authz_groups' => [
                        IGroup::SuperAdmins,
                        IGroup::Administrators,
                    ]
                ],
                [
                    'name' => 'add-summit-administrator-group',
                    'route' => '/api/v1/summit-administrator-groups',
                    'http_method' => 'POST',
                    'scopes' => [
                        sprintf(SummitScopes::WriteSummitAdminGroups, $current_realm),
                    ],
                    'authz_groups' => [
                        IGroup::SuperAdmins,
                        IGroup::Administrators,
                    ]
                ],
                [
                    'name' => 'update-summit-administrator-group',
                    'route' => '/api/v1/summit-administrator-groups/{group_id}',
                    'http_method' => 'PUT',
                    'scopes' => [
                        sprintf(SummitScopes::WriteSummitAdminGroups, $current_realm),
                    ],
                    'authz_groups' => [
                        IGroup::SuperAdmins,
                        IGroup::Administrators,
                    ]
                ],
                [
                    'name' => 'delete-summit-administrator-group',
                    'route' => '/api/v1/summit-administrator-groups/{group_id}',
                    'http_method' => 'DELETE',
                    'scopes' => [
                        sprintf(SummitScopes::WriteSummitAdminGroups, $current_realm),
                    ],
                    'authz_groups' => [
                        IGroup::SuperAdmins,
                        IGroup::Administrators,
                    ]
                ],
                [
                    'name' => 'add-member-to-summit-administrator-group',
                    'route' => '/api/v1/summit-administrator-groups/{group_id}/members/{member_id}',
                    'http_method' => 'PUT',
                    'scopes' => [
                        sprintf(SummitScopes::WriteSummitAdminGroups, $current_realm),
                    ],
                    'authz_groups' => [
                        IGroup::SuperAdmins,
                        IGroup::Administrators,
                    ]
                ],
                [
                    'name' => 'remove-member-from-summit-administrator-group',
                    'route' => '/api/v1/summit-administrator-groups/{group_id}/members/{member_id}',
                    'http_method' => 'DELETE',
                    'scopes' => [
                        sprintf(SummitScopes::WriteSummitAdminGroups, $current_realm),
                    ],
                    'authz_groups' => [
                        IGroup::SuperAdmins,
                        IGroup::Administrators,
                    ]
                ],
                [
                    'name' => 'add-summit-to-summit-administrator-group',
                    'route' => '/api/v1/summit-administrator-groups/{group_id}/summits/{summit_id}',
                    'http_method' => 'PUT',
                    'scopes' => [
                        sprintf(SummitScopes::WriteSummitAdminGroups, $current_realm),
                    ],
                    'authz_groups' => [
                        IGroup::SuperAdmins,
                        IGroup::Administrators,
                    ]
                ],
                [
                    'name' => 'remove-summit-from-summit-administrator-group',
                    'route' => '/api/v1/summit-administrator-groups/{group_id}/summits/{summit_id}',
                    'http_method' => 'DELETE',
                    'scopes' => [
                        sprintf(SummitScopes::WriteSummitAdminGroups, $current_realm),
                    ],
                    'authz_groups' => [
                        IGroup::SuperAdmins,
                        IGroup::Administrators,
                    ]
                ],

            ]
        );
    }

    public function seedSummitMediaFileTypeEndpoints()
    {
        $current_realm = Config::get('app.scope_base_realm');

        $this->seedApiEndpoints('summit-media-file-types', [
                [
                    'name' => 'get-summit-media-file-types',
                    'route' => '/api/v1/summit-media-file-types',
                    'http_method' => 'GET',
                    'scopes' => [sprintf(SummitScopes::ReadSummitMediaFileTypes, $current_realm)],
                    'authz_groups' => [
                        IGroup::SuperAdmins,
                        IGroup::Administrators,
                        IGroup::SummitAdministrators,
                    ]
                ],
                [
                    'name' => 'get-summit-media-file-type-by-id',
                    'route' => '/api/v1/summit-media-file-types/{id}',
                    'http_method' => 'GET',
                    'scopes' => [sprintf(SummitScopes::ReadSummitMediaFileTypes, $current_realm)],
                    'authz_groups' => [
                        IGroup::SuperAdmins,
                        IGroup::Administrators,
                        IGroup::SummitAdministrators,
                    ]
                ],
                [
                    'name' => 'add-summit-media-file-type',
                    'route' => '/api/v1/summit-media-file-types',
                    'http_method' => 'POST',
                    'scopes' => [sprintf(SummitScopes::WriteSummitMediaFileTypes, $current_realm)],
                    'authz_groups' => [
                        IGroup::SuperAdmins,
                        IGroup::Administrators,
                    ]
                ],
                [
                    'name' => 'update-summit-media-file-type',
                    'route' => '/api/v1/summit-media-file-types/{id}',
                    'http_method' => 'PUT',
                    'scopes' => [sprintf(SummitScopes::WriteSummitMediaFileTypes, $current_realm)],
                    'authz_groups' => [
                        IGroup::SuperAdmins,
                        IGroup::Administrators,
                    ]
                ],
                [
                    'name' => 'delete-summit-media-file-type',
                    'route' => '/api/v1/summit-media-file-types/{id}',
                    'http_method' => 'DELETE',
                    'scopes' => [sprintf(SummitScopes::WriteSummitMediaFileTypes, $current_realm)],
                    'authz_groups' => [
                        IGroup::SuperAdmins,
                        IGroup::Administrators,
                    ]
                ],
                [
                    'name' => 'metric-enter',
                    'route' => '/api/v1/summits/{id}/metrics/enter',
                    'http_method' => 'PUT',
                    'scopes' => [
                        sprintf(SummitScopes::EnterEvent, $current_realm),
                        sprintf(SummitScopes::WriteMetrics, $current_realm)
                    ],
                ],
                [
                    'name' => 'metric-leave',
                    'route' => '/api/v1/summits/{id}/metrics/leave',
                    'http_method' => 'POST',
                    'scopes' => [
                        sprintf(SummitScopes::LeaveEvent, $current_realm),
                        sprintf(SummitScopes::WriteMetrics, $current_realm)
                    ],
                ],
                [
                    'name' => 'metric-onsite-enter-check',
                    'route' => '/api/v1/summits/{id}/metrics/onsite/enter',
                    'http_method' => 'GET',
                    'scopes' => [
                        sprintf(SummitScopes::ReadAllSummitData, $current_realm),
                        sprintf(SummitScopes::ReadSummitData, $current_realm),
                        sprintf(SummitScopes::ReadMetrics, $current_realm),
                    ],
                    'authz_groups' => [
                        IGroup::SummitAccessControl,
                    ]
                ],
                [
                    'name' => 'metric-onsite-enter',
                    'route' => '/api/v1/summits/{id}/metrics/onsite/enter',
                    'http_method' => 'PUT',
                    'scopes' => [
                        sprintf(SummitScopes::WriteMetrics, $current_realm)
                    ],
                    'authz_groups' => [
                        IGroup::SummitAccessControl,
                    ]
                ],
                [
                    'name' => 'metric-onsite-leave',
                    'route' => '/api/v1/summits/{id}/metrics/onsite/leave',
                    'http_method' => 'POST',
                    'scopes' => [
                        sprintf(SummitScopes::WriteMetrics, $current_realm)
                    ],
                    'authz_groups' => [
                        IGroup::SummitAccessControl,
                    ]
                ],
                [
                    'name' => 'get-summit-signs',
                    'route' => '/api/v1/summits/{id}/signs',
                    'http_method' => 'GET',
                    'scopes' => [
                        sprintf(SummitScopes::ReadAllSummitData, $current_realm)
                    ]
                ],
                [
                    'name' => 'add-summit-sign',
                    'route' => '/api/v1/summits/{id}/signs',
                    'http_method' => 'POST',
                    'scopes' => [
                        sprintf(SummitScopes::WriteSummitData, $current_realm)
                    ],
                    'authz_groups' => [
                        IGroup::SuperAdmins,
                        IGroup::Administrators,
                        IGroup::SummitAdministrators,
                    ]
                ],
                [
                    'name' => 'update-summit-sign',
                    'route' => '/api/v1/summits/{id}/signs/{sign_id}',
                    'http_method' => 'PUT',
                    'scopes' => [
                        sprintf(SummitScopes::WriteSummitData, $current_realm)
                    ],
                    'authz_groups' => [
                        IGroup::SuperAdmins,
                        IGroup::Administrators,
                        IGroup::SummitAdministrators,
                    ]
                ],
                [
                    'name' => 'get-summit-sign',
                    'route' => '/api/v1/summits/{id}/signs/{sign_id}',
                    'http_method' => 'GET',
                    'scopes' => [
                        sprintf(SummitScopes::ReadAllSummitData, $current_realm)
                    ],
                ],
            ]
        );
    }

    public function seedElectionsEndpoints()
    {

        $this->seedApiEndpoints('elections', [
                [
                    'name' => 'get-all-elections',
                    'route' => '/api/v1/elections',
                    'http_method' => 'GET',
                    'scopes' => [
                        ElectionScopes::ReadAllElections
                    ],
                ],
                [
                    'name' => 'get-election-by-id',
                    'route' => '/api/v1/elections/{election_id}',
                    'http_method' => 'GET',
                    'scopes' => [
                        ElectionScopes::ReadAllElections
                    ],
                ],
                [
                    'name' => 'get-election-by-id-candidates',
                    'route' => '/api/v1/elections/{election_id}/candidates',
                    'http_method' => 'GET',
                    'scopes' => [
                        ElectionScopes::ReadAllElections
                    ],
                ],
                [
                    'name' => 'get-election-by-id-gold-candidates',
                    'route' => '/api/v1/elections/{election_id}/candidates/gold',
                    'http_method' => 'GET',
                    'scopes' => [
                        ElectionScopes::ReadAllElections
                    ],
                ],
                [
                    'name' => 'update-my-candidate-profile',
                    'route' => '/api/v1/elections/current/candidates/me',
                    'http_method' => 'PUT',
                    'scopes' => [
                        ElectionScopes::WriteMyCandidateProfile
                    ],
                ],
                [
                    'name' => 'nominate-candidate',
                    'route' => '/api/v1/elections/current/candidates/{candidate_id}',
                    'http_method' => 'POST',
                    'scopes' => [
                        ElectionScopes::NominatesCandidates
                    ],
                ],
            ]
        );
    }
}