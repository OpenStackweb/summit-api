<?php

namespace App\Swagger\Models;

use OpenApi\Attributes as OA;

/**
 * Schema for SummitGeoLocatedLocation model
 * Extends SummitAbstractLocation with geographic properties
 */
#[OA\Schema(
    schema: 'SummitGeoLocatedLocation',
    type: 'object',
    description: 'Geo-located summit location with address and coordinates',
    allOf: [
        new OA\Schema(ref: '#/components/schemas/SummitAbstractLocation'),
        new OA\Schema(
            type: 'object',
            properties: [
                new OA\Property(property: 'address_1', type: 'string', description: 'Primary address line'),
                new OA\Property(property: 'address_2', type: 'string', description: 'Secondary address line'),
                new OA\Property(property: 'zip_code', type: 'string', description: 'ZIP/Postal code'),
                new OA\Property(property: 'city', type: 'string', description: 'City name'),
                new OA\Property(property: 'state', type: 'string', description: 'State/Province'),
                new OA\Property(property: 'country', type: 'string', description: 'Country'),
                new OA\Property(property: 'lng', type: 'number', format: 'float', description: 'Longitude coordinate'),
                new OA\Property(property: 'lat', type: 'number', format: 'float', description: 'Latitude coordinate'),
                new OA\Property(property: 'website_url', type: 'string', format: 'uri', description: 'Website URL'),
                new OA\Property(property: 'display_on_site', type: 'boolean', description: 'Whether to display on public site'),
                new OA\Property(property: 'details_page', type: 'boolean', description: 'Whether to show details page'),
                new OA\Property(property: 'location_message', type: 'string', description: 'Custom message for this location'),
                new OA\Property(
                    property: 'maps',
                    type: 'array',
                    items: new OA\Items(type: 'integer'),
                    description: 'Array of map IDs'
                ),
                new OA\Property(
                    property: 'images',
                    type: 'array',
                    items: new OA\Items(type: 'integer'),
                    description: 'Array of image IDs'
                ),
            ]
        )
    ]
)]
class SummitGeoLocatedLocationSchema {}
