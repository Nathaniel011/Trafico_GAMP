<?php

return [
    'tiles_url' => env('MAP_TILES_URL', 'https://tile.openstreetmap.org/{z}/{x}/{y}.png'),
    'attribution' => env('MAP_ATTRIBUTION', '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'),
    'max_zoom' => (int) env('MAP_MAX_ZOOM', 19),
    'center' => [-19.5889181, -65.7539971],
    'accuracy_warning_meters' => (float) env('GPS_ACCURACY_WARNING_METERS', 25),
];
