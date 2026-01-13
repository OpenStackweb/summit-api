<?php

namespace App\Http\Controllers;

use OpenApi\Attributes as OA;

/**
 * Response schemas for OAuth2SummitLocationsApiController endpoints
 */

// ============================================================================
// Summit Location Paginated Responses
// ============================================================================

#[OA\Schema(
    schema: 'SummitAbstractLocationPaginatedResponse',
    description: 'Paginated response containing summit abstract locations',
    allOf: [
        new OA\Schema(ref: '#/components/schemas/PaginateDataSchemaResponse'),
        new OA\Schema(
            properties: [
                new OA\Property(
                    property: 'data',
                    type: 'array',
                    items: new OA\Items(ref: '#/components/schemas/SummitAbstractLocation'),
                    description: 'Array of summit abstract location items'
                )
            ]
        )
    ]
)]
class SummitAbstractLocationPaginatedResponseSchema {}

#[OA\Schema(
    schema: 'SummitVenuePaginatedResponse',
    description: 'Paginated response containing summit venues',
    allOf: [
        new OA\Schema(ref: '#/components/schemas/PaginateDataSchemaResponse'),
        new OA\Schema(
            properties: [
                new OA\Property(
                    property: 'data',
                    type: 'array',
                    items: new OA\Items(ref: '#/components/schemas/SummitVenue'),
                    description: 'Array of summit venue items'
                )
            ]
        )
    ]
)]
class SummitVenuePaginatedResponseSchema {}

#[OA\Schema(
    schema: 'SummitVenueRoomPaginatedResponse',
    description: 'Paginated response containing summit venue rooms',
    allOf: [
        new OA\Schema(ref: '#/components/schemas/PaginateDataSchemaResponse'),
        new OA\Schema(
            properties: [
                new OA\Property(
                    property: 'data',
                    type: 'array',
                    items: new OA\Items(ref: '#/components/schemas/SummitVenueRoom'),
                    description: 'Array of summit venue room items'
                )
            ]
        )
    ]
)]
class SummitVenueRoomPaginatedResponseSchema {}

#[OA\Schema(
    schema: 'SummitVenueFloorPaginatedResponse',
    description: 'Paginated response containing summit venue floors',
    allOf: [
        new OA\Schema(ref: '#/components/schemas/PaginateDataSchemaResponse'),
        new OA\Schema(
            properties: [
                new OA\Property(
                    property: 'data',
                    type: 'array',
                    items: new OA\Items(ref: '#/components/schemas/SummitVenueFloor'),
                    description: 'Array of summit venue floor items'
                )
            ]
        )
    ]
)]
class SummitVenueFloorPaginatedResponseSchema {}

#[OA\Schema(
    schema: 'SummitHotelPaginatedResponse',
    description: 'Paginated response containing summit hotels',
    allOf: [
        new OA\Schema(ref: '#/components/schemas/PaginateDataSchemaResponse'),
        new OA\Schema(
            properties: [
                new OA\Property(
                    property: 'data',
                    type: 'array',
                    items: new OA\Items(ref: '#/components/schemas/SummitHotel'),
                    description: 'Array of summit hotel items'
                )
            ]
        )
    ]
)]
class SummitHotelPaginatedResponseSchema {}

#[OA\Schema(
    schema: 'SummitAirportPaginatedResponse',
    description: 'Paginated response containing summit airports',
    allOf: [
        new OA\Schema(ref: '#/components/schemas/PaginateDataSchemaResponse'),
        new OA\Schema(
            properties: [
                new OA\Property(
                    property: 'data',
                    type: 'array',
                    items: new OA\Items(ref: '#/components/schemas/SummitAirport'),
                    description: 'Array of summit airport items'
                )
            ]
        )
    ]
)]
class SummitAirportPaginatedResponseSchema {}

#[OA\Schema(
    schema: 'SummitExternalLocationPaginatedResponse',
    description: 'Paginated response containing summit external locations',
    allOf: [
        new OA\Schema(ref: '#/components/schemas/PaginateDataSchemaResponse'),
        new OA\Schema(
            properties: [
                new OA\Property(
                    property: 'data',
                    type: 'array',
                    items: new OA\Items(ref: '#/components/schemas/SummitExternalLocation'),
                    description: 'Array of summit external location items'
                )
            ]
        )
    ]
)]
class SummitExternalLocationPaginatedResponseSchema {}

#[OA\Schema(
    schema: 'SummitLocationBannerPaginatedResponse',
    description: 'Paginated response containing summit location banners',
    allOf: [
        new OA\Schema(ref: '#/components/schemas/PaginateDataSchemaResponse'),
        new OA\Schema(
            properties: [
                new OA\Property(
                    property: 'data',
                    type: 'array',
                    items: new OA\Items(ref: '#/components/schemas/SummitLocationBanner'),
                    description: 'Array of summit location banner items'
                )
            ]
        )
    ]
)]
class SummitLocationBannerPaginatedResponseSchema {}

#[OA\Schema(
    schema: 'SummitLocationMapPaginatedResponse',
    description: 'Paginated response containing summit location maps',
    allOf: [
        new OA\Schema(ref: '#/components/schemas/PaginateDataSchemaResponse'),
        new OA\Schema(
            properties: [
                new OA\Property(
                    property: 'data',
                    type: 'array',
                    items: new OA\Items(ref: '#/components/schemas/SummitLocationMap'),
                    description: 'Array of summit location map items'
                )
            ]
        )
    ]
)]
class SummitLocationMapPaginatedResponseSchema {}

#[OA\Schema(
    schema: 'SummitLocationImagePaginatedResponse',
    description: 'Paginated response containing summit location images',
    allOf: [
        new OA\Schema(ref: '#/components/schemas/PaginateDataSchemaResponse'),
        new OA\Schema(
            properties: [
                new OA\Property(
                    property: 'data',
                    type: 'array',
                    items: new OA\Items(ref: '#/components/schemas/SummitLocationImage'),
                    description: 'Array of summit location image items'
                )
            ]
        )
    ]
)]
class SummitLocationImagePaginatedResponseSchema {}

#[OA\Schema(
    schema: 'SummitBookableVenueRoomPaginatedResponse',
    description: 'Paginated response containing summit bookable venue rooms',
    allOf: [
        new OA\Schema(ref: '#/components/schemas/PaginateDataSchemaResponse'),
        new OA\Schema(
            properties: [
                new OA\Property(
                    property: 'data',
                    type: 'array',
                    items: new OA\Items(ref: '#/components/schemas/SummitBookableVenueRoom'),
                    description: 'Array of summit bookable venue room items'
                )
            ]
        )
    ]
)]
class SummitBookableVenueRoomPaginatedResponseSchema {}

#[OA\Schema(
    schema: 'SummitRoomReservationPaginatedResponse',
    description: 'Paginated response containing summit room reservations',
    allOf: [
        new OA\Schema(ref: '#/components/schemas/PaginateDataSchemaResponse'),
        new OA\Schema(
            properties: [
                new OA\Property(
                    property: 'data',
                    type: 'array',
                    items: new OA\Items(ref: '#/components/schemas/SummitRoomReservation'),
                    description: 'Array of summit room reservation items'
                )
            ]
        )
    ]
)]
class SummitRoomReservationPaginatedResponseSchema {}
#[OA\Schema(
    schema: 'SummitEventPaginatedResponse',
    description: 'Paginated response containing summit events',
    allOf: [
        new OA\Schema(ref: '#/components/schemas/PaginateDataSchemaResponse'),
        new OA\Schema(
            properties: [
                new OA\Property(
                    property: 'data',
                    type: 'array',
                    items: new OA\Items(ref: '#/components/schemas/SummitEvent'),
                    description: 'Array of summit event items'
                )
            ]
        )
    ]
)]
class SummitEventPaginatedResponseSchema {}

#[OA\Schema(
    schema: 'SummitImage',
    description: 'Summit image entity',
    properties: [
        new OA\Property(property: 'id', type: 'integer', description: 'Image ID'),
        new OA\Property(property: 'created', type: 'integer', description: 'Created timestamp'),
        new OA\Property(property: 'last_edited', type: 'integer', description: 'Last edited timestamp'),
        new OA\Property(property: 'name', type: 'string', description: 'Image name', nullable: true),
        new OA\Property(property: 'description', type: 'string', description: 'Image description', nullable: true),
        new OA\Property(property: 'filename', type: 'string', description: 'Image filename'),
        new OA\Property(property: 'image_url', type: 'string', description: 'Image URL'),
        new OA\Property(property: 'order', type: 'integer', description: 'Display order'),
    ]
)]
class SummitImageSchema {}

// ============================================================================
// Request Body Payloads for Location Management
// ============================================================================

#[OA\Schema(
    schema: 'AddLocationPayload',
    type: 'object',
    description: 'Payload for creating a new location',
    required: ['class_name', 'name', 'address_1', 'city', 'country'],
    properties: [
        new OA\Property(property: 'class_name', type: 'string', enum: ['SummitVenue', 'SummitExternalLocation', 'SummitHotel', 'SummitAirport'], description: 'Type of location'),
        new OA\Property(property: 'name', type: 'string', maxLength: 255, description: 'Location name'),
        new OA\Property(property: 'description', type: 'string', description: 'Location description'),
        new OA\Property(property: 'address_1', type: 'string', maxLength: 255, description: 'Address line 1'),
        new OA\Property(property: 'address_2', type: 'string', maxLength: 255, description: 'Address line 2'),
        new OA\Property(property: 'zip_code', type: 'string', maxLength: 16, description: 'Zip/Postal code'),
        new OA\Property(property: 'city', type: 'string', maxLength: 100, description: 'City name'),
        new OA\Property(property: 'state', type: 'string', maxLength: 100, description: 'State or Province'),
        new OA\Property(property: 'country', type: 'string', maxLength: 100, description: 'Country'),
        new OA\Property(property: 'lng', type: 'number', format: 'double', description: 'Longitude'),
        new OA\Property(property: 'lat', type: 'number', format: 'double', description: 'Latitude'),
        new OA\Property(property: 'sold_out', type: 'boolean', description: 'Whether the location is sold out'),
        new OA\Property(property: 'is_main', type: 'boolean', description: 'Whether this is the main location'),
        new OA\Property(property: 'order', type: 'integer', description: 'Display order'),
    ]
)]
class AddLocationPayloadSchema {}

#[OA\Schema(
    schema: 'UpdateLocationPayload',
    type: 'object',
    description: 'Payload for updating an existing location',
    properties: [
        new OA\Property(property: 'name', type: 'string', maxLength: 255, description: 'Location name'),
        new OA\Property(property: 'description', type: 'string', description: 'Location description'),
        new OA\Property(property: 'address_1', type: 'string', maxLength: 255, description: 'Address line 1'),
        new OA\Property(property: 'address_2', type: 'string', maxLength: 255, description: 'Address line 2'),
        new OA\Property(property: 'zip_code', type: 'string', maxLength: 16, description: 'Zip/Postal code'),
        new OA\Property(property: 'city', type: 'string', maxLength: 100, description: 'City name'),
        new OA\Property(property: 'state', type: 'string', maxLength: 100, description: 'State or Province'),
        new OA\Property(property: 'country', type: 'string', maxLength: 100, description: 'Country'),
        new OA\Property(property: 'lng', type: 'number', format: 'double', description: 'Longitude'),
        new OA\Property(property: 'lat', type: 'number', format: 'double', description: 'Latitude'),
        new OA\Property(property: 'sold_out', type: 'boolean', description: 'Whether the location is sold out'),
        new OA\Property(property: 'is_main', type: 'boolean', description: 'Whether this is the main location'),
        new OA\Property(property: 'order', type: 'integer', description: 'Display order'),
    ]
)]
class UpdateLocationPayloadSchema {}

#[OA\Schema(
    schema: 'AddVenuePayload',
    type: 'object',
    description: 'Payload for creating a new venue',
    required: ['name', 'address_1', 'city', 'country'],
    properties: [
        new OA\Property(property: 'name', type: 'string', maxLength: 255, description: 'Venue name'),
        new OA\Property(property: 'description', type: 'string', description: 'Venue description'),
        new OA\Property(property: 'address_1', type: 'string', maxLength: 255, description: 'Address line 1'),
        new OA\Property(property: 'address_2', type: 'string', maxLength: 255, description: 'Address line 2'),
        new OA\Property(property: 'zip_code', type: 'string', maxLength: 16, description: 'Zip/Postal code'),
        new OA\Property(property: 'city', type: 'string', maxLength: 100, description: 'City name'),
        new OA\Property(property: 'state', type: 'string', maxLength: 100, description: 'State or Province'),
        new OA\Property(property: 'country', type: 'string', maxLength: 100, description: 'Country'),
        new OA\Property(property: 'lng', type: 'number', format: 'double', description: 'Longitude'),
        new OA\Property(property: 'lat', type: 'number', format: 'double', description: 'Latitude'),
        new OA\Property(property: 'sold_out', type: 'boolean', description: 'Whether the venue is sold out'),
        new OA\Property(property: 'is_main', type: 'boolean', description: 'Whether this is the main venue'),
        new OA\Property(property: 'order', type: 'integer', description: 'Display order'),
    ]
)]
class AddVenuePayloadSchema {}

#[OA\Schema(
    schema: 'UpdateVenuePayload',
    type: 'object',
    description: 'Payload for updating an existing venue',
    properties: [
        new OA\Property(property: 'name', type: 'string', maxLength: 255, description: 'Venue name'),
        new OA\Property(property: 'description', type: 'string', description: 'Venue description'),
        new OA\Property(property: 'address_1', type: 'string', maxLength: 255, description: 'Address line 1'),
        new OA\Property(property: 'address_2', type: 'string', maxLength: 255, description: 'Address line 2'),
        new OA\Property(property: 'zip_code', type: 'string', maxLength: 16, description: 'Zip/Postal code'),
        new OA\Property(property: 'city', type: 'string', maxLength: 100, description: 'City name'),
        new OA\Property(property: 'state', type: 'string', maxLength: 100, description: 'State or Province'),
        new OA\Property(property: 'country', type: 'string', maxLength: 100, description: 'Country'),
        new OA\Property(property: 'lng', type: 'number', format: 'double', description: 'Longitude'),
        new OA\Property(property: 'lat', type: 'number', format: 'double', description: 'Latitude'),
        new OA\Property(property: 'sold_out', type: 'boolean', description: 'Whether the venue is sold out'),
        new OA\Property(property: 'is_main', type: 'boolean', description: 'Whether this is the main venue'),
        new OA\Property(property: 'order', type: 'integer', description: 'Display order'),
    ]
)]
class UpdateVenuePayloadSchema {}

#[OA\Schema(
    schema: 'AddExternalLocationPayload',
    type: 'object',
    description: 'Payload for creating a new external location',
    required: ['name', 'address_1', 'city', 'country'],
    properties: [
        new OA\Property(property: 'name', type: 'string', maxLength: 255, description: 'Location name'),
        new OA\Property(property: 'description', type: 'string', description: 'Location description'),
        new OA\Property(property: 'address_1', type: 'string', maxLength: 255, description: 'Address line 1'),
        new OA\Property(property: 'address_2', type: 'string', maxLength: 255, description: 'Address line 2'),
        new OA\Property(property: 'zip_code', type: 'string', maxLength: 16, description: 'Zip/Postal code'),
        new OA\Property(property: 'city', type: 'string', maxLength: 100, description: 'City name'),
        new OA\Property(property: 'state', type: 'string', maxLength: 100, description: 'State or Province'),
        new OA\Property(property: 'country', type: 'string', maxLength: 100, description: 'Country'),
        new OA\Property(property: 'lng', type: 'number', format: 'double', description: 'Longitude'),
        new OA\Property(property: 'lat', type: 'number', format: 'double', description: 'Latitude'),
        new OA\Property(property: 'sold_out', type: 'boolean', description: 'Whether the location is sold out'),
        new OA\Property(property: 'is_main', type: 'boolean', description: 'Whether this is the main location'),
        new OA\Property(property: 'order', type: 'integer', description: 'Display order'),
    ]
)]
class AddExternalLocationPayloadSchema {}

#[OA\Schema(
    schema: 'UpdateExternalLocationPayload',
    type: 'object',
    description: 'Payload for updating an existing external location',
    properties: [
        new OA\Property(property: 'name', type: 'string', maxLength: 255, description: 'Location name'),
        new OA\Property(property: 'description', type: 'string', description: 'Location description'),
        new OA\Property(property: 'address_1', type: 'string', maxLength: 255, description: 'Address line 1'),
        new OA\Property(property: 'address_2', type: 'string', maxLength: 255, description: 'Address line 2'),
        new OA\Property(property: 'zip_code', type: 'string', maxLength: 16, description: 'Zip/Postal code'),
        new OA\Property(property: 'city', type: 'string', maxLength: 100, description: 'City name'),
        new OA\Property(property: 'state', type: 'string', maxLength: 100, description: 'State or Province'),
        new OA\Property(property: 'country', type: 'string', maxLength: 100, description: 'Country'),
        new OA\Property(property: 'lng', type: 'number', format: 'double', description: 'Longitude'),
        new OA\Property(property: 'lat', type: 'number', format: 'double', description: 'Latitude'),
        new OA\Property(property: 'sold_out', type: 'boolean', description: 'Whether the location is sold out'),
        new OA\Property(property: 'is_main', type: 'boolean', description: 'Whether this is the main location'),
        new OA\Property(property: 'order', type: 'integer', description: 'Display order'),
    ]
)]
class UpdateExternalLocationPayloadSchema {}

#[OA\Schema(
    schema: 'AddHotelPayload',
    type: 'object',
    description: 'Payload for creating a new hotel',
    required: ['name', 'address_1', 'city', 'country'],
    properties: [
        new OA\Property(property: 'name', type: 'string', maxLength: 255, description: 'Hotel name'),
        new OA\Property(property: 'description', type: 'string', description: 'Hotel description'),
        new OA\Property(property: 'address_1', type: 'string', maxLength: 255, description: 'Address line 1'),
        new OA\Property(property: 'address_2', type: 'string', maxLength: 255, description: 'Address line 2'),
        new OA\Property(property: 'zip_code', type: 'string', maxLength: 16, description: 'Zip/Postal code'),
        new OA\Property(property: 'city', type: 'string', maxLength: 100, description: 'City name'),
        new OA\Property(property: 'state', type: 'string', maxLength: 100, description: 'State or Province'),
        new OA\Property(property: 'country', type: 'string', maxLength: 100, description: 'Country'),
        new OA\Property(property: 'lng', type: 'number', format: 'double', description: 'Longitude'),
        new OA\Property(property: 'lat', type: 'number', format: 'double', description: 'Latitude'),
        new OA\Property(property: 'sold_out', type: 'boolean', description: 'Whether the hotel is sold out'),
        new OA\Property(property: 'is_main', type: 'boolean', description: 'Whether this is the main hotel'),
        new OA\Property(property: 'order', type: 'integer', description: 'Display order'),
    ]
)]
class AddHotelPayloadSchema {}

#[OA\Schema(
    schema: 'UpdateHotelPayload',
    type: 'object',
    description: 'Payload for updating an existing hotel',
    properties: [
        new OA\Property(property: 'name', type: 'string', maxLength: 255, description: 'Hotel name'),
        new OA\Property(property: 'description', type: 'string', description: 'Hotel description'),
        new OA\Property(property: 'address_1', type: 'string', maxLength: 255, description: 'Address line 1'),
        new OA\Property(property: 'address_2', type: 'string', maxLength: 255, description: 'Address line 2'),
        new OA\Property(property: 'zip_code', type: 'string', maxLength: 16, description: 'Zip/Postal code'),
        new OA\Property(property: 'city', type: 'string', maxLength: 100, description: 'City name'),
        new OA\Property(property: 'state', type: 'string', maxLength: 100, description: 'State or Province'),
        new OA\Property(property: 'country', type: 'string', maxLength: 100, description: 'Country'),
        new OA\Property(property: 'lng', type: 'number', format: 'double', description: 'Longitude'),
        new OA\Property(property: 'lat', type: 'number', format: 'double', description: 'Latitude'),
        new OA\Property(property: 'sold_out', type: 'boolean', description: 'Whether the hotel is sold out'),
        new OA\Property(property: 'is_main', type: 'boolean', description: 'Whether this is the main hotel'),
        new OA\Property(property: 'order', type: 'integer', description: 'Display order'),
    ]
)]
class UpdateHotelPayloadSchema {}

#[OA\Schema(
    schema: 'AddAirportPayload',
    type: 'object',
    description: 'Payload for creating a new airport',
    required: ['name', 'address_1', 'city', 'country'],
    properties: [
        new OA\Property(property: 'name', type: 'string', maxLength: 255, description: 'Airport name'),
        new OA\Property(property: 'description', type: 'string', description: 'Airport description'),
        new OA\Property(property: 'address_1', type: 'string', maxLength: 255, description: 'Address line 1'),
        new OA\Property(property: 'address_2', type: 'string', maxLength: 255, description: 'Address line 2'),
        new OA\Property(property: 'zip_code', type: 'string', maxLength: 16, description: 'Zip/Postal code'),
        new OA\Property(property: 'city', type: 'string', maxLength: 100, description: 'City name'),
        new OA\Property(property: 'state', type: 'string', maxLength: 100, description: 'State or Province'),
        new OA\Property(property: 'country', type: 'string', maxLength: 100, description: 'Country'),
        new OA\Property(property: 'lng', type: 'number', format: 'double', description: 'Longitude'),
        new OA\Property(property: 'lat', type: 'number', format: 'double', description: 'Latitude'),
        new OA\Property(property: 'sold_out', type: 'boolean', description: 'Whether the airport is sold out'),
        new OA\Property(property: 'is_main', type: 'boolean', description: 'Whether this is the main airport'),
        new OA\Property(property: 'order', type: 'integer', description: 'Display order'),
    ]
)]
class AddAirportPayloadSchema {}

#[OA\Schema(
    schema: 'UpdateAirportPayload',
    type: 'object',
    description: 'Payload for updating an existing airport',
    properties: [
        new OA\Property(property: 'name', type: 'string', maxLength: 255, description: 'Airport name'),
        new OA\Property(property: 'description', type: 'string', description: 'Airport description'),
        new OA\Property(property: 'address_1', type: 'string', maxLength: 255, description: 'Address line 1'),
        new OA\Property(property: 'address_2', type: 'string', maxLength: 255, description: 'Address line 2'),
        new OA\Property(property: 'zip_code', type: 'string', maxLength: 16, description: 'Zip/Postal code'),
        new OA\Property(property: 'city', type: 'string', maxLength: 100, description: 'City name'),
        new OA\Property(property: 'state', type: 'string', maxLength: 100, description: 'State or Province'),
        new OA\Property(property: 'country', type: 'string', maxLength: 100, description: 'Country'),
        new OA\Property(property: 'lng', type: 'number', format: 'double', description: 'Longitude'),
        new OA\Property(property: 'lat', type: 'number', format: 'double', description: 'Latitude'),
        new OA\Property(property: 'sold_out', type: 'boolean', description: 'Whether the airport is sold out'),
        new OA\Property(property: 'is_main', type: 'boolean', description: 'Whether this is the main airport'),
        new OA\Property(property: 'order', type: 'integer', description: 'Display order'),
    ]
)]
class UpdateAirportPayloadSchema {}

#[OA\Schema(
    schema: 'AddVenueFloorPayload',
    type: 'object',
    description: 'Payload for creating a new venue floor',
    required: ['name', 'number'],
    properties: [
        new OA\Property(property: 'name', type: 'string', maxLength: 50, description: 'Floor name'),
        new OA\Property(property: 'number', type: 'integer', description: 'Floor number'),
        new OA\Property(property: 'description', type: 'string', description: 'Floor description'),
    ]
)]
class AddVenueFloorPayloadSchema {}

#[OA\Schema(
    schema: 'UpdateVenueFloorPayload',
    type: 'object',
    description: 'Payload for updating an existing venue floor',
    properties: [
        new OA\Property(property: 'name', type: 'string', maxLength: 50, description: 'Floor name'),
        new OA\Property(property: 'number', type: 'integer', description: 'Floor number'),
        new OA\Property(property: 'description', type: 'string', description: 'Floor description'),
    ]
)]
class UpdateVenueFloorPayloadSchema {}

#[OA\Schema(
    schema: 'AddVenueRoomPayload',
    type: 'object',
    description: 'Payload for creating a new venue room',
    required: ['name', 'capacity'],
    properties: [
        new OA\Property(property: 'name', type: 'string', maxLength: 255, description: 'Room name'),
        new OA\Property(property: 'capacity', type: 'integer', minimum: 1, description: 'Room capacity'),
        new OA\Property(property: 'description', type: 'string', description: 'Room description'),
        new OA\Property(property: 'order', type: 'integer', description: 'Display order'),
    ]
)]
class AddVenueRoomPayloadSchema {}

#[OA\Schema(
    schema: 'UpdateVenueRoomPayload',
    type: 'object',
    description: 'Payload for updating an existing venue room',
    properties: [
        new OA\Property(property: 'name', type: 'string', maxLength: 255, description: 'Room name'),
        new OA\Property(property: 'capacity', type: 'integer', minimum: 1, description: 'Room capacity'),
        new OA\Property(property: 'description', type: 'string', description: 'Room description'),
        new OA\Property(property: 'order', type: 'integer', description: 'Display order'),
        new OA\Property(property: 'floor_id', type: 'integer', description: 'Floor ID'),
    ]
)]
class UpdateVenueRoomPayloadSchema {}

#[OA\Schema(
    schema: 'AddLocationBannerPayload',
    type: 'object',
    description: 'Payload for creating a new location banner',
    required: ['class_name', 'title', 'type'],
    properties: [
        new OA\Property(property: 'class_name', type: 'string', enum: ['IImageHolder', 'AudioHolder'], description: 'Banner class type'),
        new OA\Property(property: 'title', type: 'string', maxLength: 255, description: 'Banner title'),
        new OA\Property(property: 'content', type: 'string', description: 'Banner content/HTML'),
        new OA\Property(property: 'type', type: 'string', enum: ['Internal', 'External'], description: 'Banner type'),
        new OA\Property(property: 'enabled', type: 'boolean', description: 'Whether the banner is enabled'),
        new OA\Property(property: 'start_date', type: 'integer', description: 'Start date as Unix timestamp'),
        new OA\Property(property: 'end_date', type: 'integer', description: 'End date as Unix timestamp'),
    ]
)]
class AddLocationBannerPayloadSchema {}

#[OA\Schema(
    schema: 'UpdateLocationBannerPayload',
    type: 'object',
    description: 'Payload for updating an existing location banner',
    properties: [
        new OA\Property(property: 'title', type: 'string', maxLength: 255, description: 'Banner title'),
        new OA\Property(property: 'content', type: 'string', description: 'Banner content/HTML'),
        new OA\Property(property: 'type', type: 'string', enum: ['Internal', 'External'], description: 'Banner type'),
        new OA\Property(property: 'enabled', type: 'boolean', description: 'Whether the banner is enabled'),
        new OA\Property(property: 'start_date', type: 'integer', description: 'Start date as Unix timestamp'),
        new OA\Property(property: 'end_date', type: 'integer', description: 'End date as Unix timestamp'),
    ]
)]
class UpdateLocationBannerPayloadSchema {}

#[OA\Schema(
    schema: 'AddLocationImagePayload',
    type: 'object',
    description: 'Payload for uploading a location image (multipart form data)',
    required: ['file'],
    properties: [
        new OA\Property(property: 'file', type: 'string', format: 'binary', description: 'Image file'),
        new OA\Property(property: 'description', type: 'string', description: 'Image description'),
    ]
)]
class AddLocationImagePayloadSchema {}

#[OA\Schema(
    schema: 'UpdateLocationImagePayload',
    type: 'object',
    description: 'Payload for updating a location image (JSON)',
    properties: [
        new OA\Property(property: 'description', type: 'string', description: 'Image description'),
    ]
)]
class UpdateLocationImagePayloadSchema {}

#[OA\Schema(
    schema: 'AddVenueFloorRoomPayload',
    type: 'object',
    description: 'Payload for creating a new room on a venue floor',
    required: ['name', 'capacity'],
    properties: [
        new OA\Property(property: 'name', type: 'string', maxLength: 255, description: 'Room name'),
        new OA\Property(property: 'capacity', type: 'integer', minimum: 1, description: 'Room capacity'),
        new OA\Property(property: 'description', type: 'string', description: 'Room description'),
        new OA\Property(property: 'order', type: 'integer', description: 'Display order'),
    ]
)]
class AddVenueFloorRoomPayloadSchema {}

#[OA\Schema(
    schema: 'UpdateVenueFloorRoomPayload',
    type: 'object',
    description: 'Payload for updating an existing room on a venue floor',
    properties: [
        new OA\Property(property: 'name', type: 'string', maxLength: 255, description: 'Room name'),
        new OA\Property(property: 'capacity', type: 'integer', minimum: 1, description: 'Room capacity'),
        new OA\Property(property: 'description', type: 'string', description: 'Room description'),
        new OA\Property(property: 'order', type: 'integer', description: 'Display order'),
    ]
)]
class UpdateVenueFloorRoomPayloadSchema {}
