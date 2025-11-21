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
    'name' => 'Order Book',
    'short_name' => 'OrderBook',
    'description' => 'Order management system for tracking and managing orders',
    'start_url' => $basePath . '/index.php',
    'scope' => $basePath . '/',
    'display' => 'standalone',
    'background_color' => '#ffffff',
    'theme_color' => '#4CAF50',
    'orientation' => 'portrait',
    'icons' => [
        [
            'src' => $basePath . '/assets/images/icon-72.png',
            'sizes' => '72x72',
            'type' => 'image/png',
            'purpose' => 'any'
        ],
        [
            'src' => $basePath . '/assets/images/icon-96.png',
            'sizes' => '96x96',
            'type' => 'image/png',
            'purpose' => 'any'
        ],
        [
            'src' => $basePath . '/assets/images/icon-128.png',
            'sizes' => '128x128',
            'type' => 'image/png',
            'purpose' => 'any'
        ],
        [
            'src' => $basePath . '/assets/images/icon-144.png',
            'sizes' => '144x144',
            'type' => 'image/png',
            'purpose' => 'any'
        ],
        [
            'src' => $basePath . '/assets/images/icon-152.png',
            'sizes' => '152x152',
            'type' => 'image/png',
            'purpose' => 'any'
        ],
        [
            'src' => $basePath . '/assets/images/icon-192.png',
            'sizes' => '192x192',
            'type' => 'image/png',
            'purpose' => 'any maskable'
        ],
        [
            'src' => $basePath . '/assets/images/icon-384.png',
            'sizes' => '384x384',
            'type' => 'image/png',
            'purpose' => 'any'
        ],
        [
            'src' => $basePath . '/assets/images/icon-512.png',
            'sizes' => '512x512',
            'type' => 'image/png',
            'purpose' => 'any maskable'
        ]
    ],
    'categories' => ['business', 'productivity']
];

// Output JSON
echo json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

