<?php
/**
 * Dynamic PWA Manifest
 * Generates manifest.json with correct paths based on BASE_URL
 */

require_once __DIR__ . '/config/config.php';

// Get base path from BASE_URL
$basePath = parse_url(BASE_URL, PHP_URL_PATH);
if (!$basePath || $basePath === '/') {
    $basePath = '';
} else {
    // Remove trailing slash
    $basePath = rtrim($basePath, '/');
}

// Set content type
header('Content-Type: application/manifest+json');

// Generate manifest
$manifest = [
    'name' => 'Evently',
    'short_name' => 'Evently',
    'description' => 'Event and order management system',
    'start_url' => $basePath . '/index.php',
    'scope' => $basePath . '/',
    'display' => 'standalone',
    'background_color' => '#ffffff',
    'theme_color' => '#4CAF50',
    'orientation' => 'portrait',
    'icons' => [
        // iOS requires specific sizes for best results
        [
            'src' => $basePath . '/assets/images/bookify logo (5).png',
            'sizes' => '180x180',
            'type' => 'image/png',
            'purpose' => 'any'
        ],
        [
            'src' => $basePath . '/assets/images/bookify logo (5).png',
            'sizes' => '192x192',
            'type' => 'image/png',
            'purpose' => 'any'
        ],
        [
            'src' => $basePath . '/assets/images/bookify logo (5).png',
            'sizes' => '512x512',
            'type' => 'image/png',
            'purpose' => 'any'
        ],
        [
            'src' => $basePath . '/assets/images/bookify logo (5).png',
            'sizes' => '1024x1024',
            'type' => 'image/png',
            'purpose' => 'any'
        ]
    ],
    'categories' => ['business', 'productivity']
];

// Output JSON
echo json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

