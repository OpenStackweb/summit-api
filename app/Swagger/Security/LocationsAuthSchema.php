<?php

namespace App\Swagger\schemas;

use App\Security\SummitScopes;
use OpenApi\Attributes as OA;

#[
  OA\SecurityScheme(
  type: 'oauth2',
  securityScheme: 'locations_oauth2',
  flows: [
    new OA\Flow(
      authorizationUrl: L5_SWAGGER_CONST_AUTH_URL,
      tokenUrl: L5_SWAGGER_CONST_TOKEN_URL,
      flow: 'authorizationCode',
      scopes: [
        SummitScopes::ReadAllSummitData => 'Read All Summit Data',
        SummitScopes::ReadSummitData => 'Read Summit Data',
        SummitScopes::WriteSummitData => 'Write Summit Data',
        SummitScopes::WriteLocationsData => 'Write Locations Data',
        SummitScopes::WriteLocationBannersData => 'Write Location Banners Data',
        SummitScopes::ReadBookableRoomsData => 'Read Bookable Rooms Data',
        SummitScopes::WriteBookableRoomsData => 'Write Bookable Rooms Data',
        SummitScopes::ReadMyBookableRoomsReservationData => 'Read My Bookable Rooms Reservation Data',
        SummitScopes::WriteMyBookableRoomsReservationData => 'Write My Bookable Rooms Reservation Data',
      ],
    ),
  ],
)
]
class LocationsAuthSchema
{
}
