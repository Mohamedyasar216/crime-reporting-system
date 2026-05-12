<?php
/**
 * Nominatim Proxy Script
 * Solves 403 Forbidden and CORS issues by providing a valid User-Agent 
 * and requesting from the server-side.
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$q = isset($_GET['q']) ? $_GET['q'] : '';
$format = isset($_GET['format']) ? $_GET['format'] : 'json';
$polygon_geojson = isset($_GET['polygon_geojson']) ? $_GET['polygon_geojson'] : '0';
$limit = isset($_GET['limit']) ? $_GET['limit'] : '1';
$countrycodes = isset($_GET['countrycodes']) ? $_GET['countrycodes'] : '';
$lat = isset($_GET['lat']) ? $_GET['lat'] : '';
$lon = isset($_GET['lon']) ? $_GET['lon'] : '';

// Determine if it's a search or reverse geocode
if ($lat !== '' && $lon !== '') {
    $endpoint = "reverse";
    $params = [
        'format' => $format,
        'lat' => $lat,
        'lon' => $lon
    ];
} else {
    $endpoint = "search";
    $params = [
        'q' => $q,
        'format' => $format,
        'polygon_geojson' => $polygon_geojson,
        'limit' => $limit,
        'countrycodes' => $countrycodes
    ];
}

$url = "https://nominatim.openstreetmap.org/" . $endpoint . "?" . http_build_query($params);

$options = [
    'http' => [
        'method' => "GET",
        'header' => [
            "User-Agent: SafeCity-App/1.0 (https://localhost:8000; support@safecity.gov)",
            "Accept-Language: en-US,en;q=0.5"
        ]
    ]
];

$context = stream_context_create($options);
$result = @file_get_contents($url, false, $context);

if ($result === false) {
    http_response_code(502);
    echo json_encode(['error' => 'Unable to connect to Geocoding Service']);
} else {
    echo $result;
}
