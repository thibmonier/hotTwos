<?php

/**
 * Returns the importmap for this application.
 *
 * - "path" is a path inside the asset mapper system. Use the
 *     "debug:asset-map" command to see the full list of paths.
 *
 * - "entrypoint" (JavaScript only) set to true for any module that will
 *     be used as an "entrypoint" (and passed to the importmap() Twig function).
 *
 * The "importmap:require" command can be used to add new entries to this file.
 *
 * @return array<string, array{    // Import name as key, description of the imported file as value
 *     path: string,               // Logical, relative or absolute path to the file
 *     type?: 'js'|'css'|'json',   // Type of the file, defaults to 'js'
 *     entrypoint?: bool,          // Whether the file is an entrypoint, for 'js' only
 * }|array{
 *     version: string,            // Version of the remote package
 *     package_specifier?: string, // Remote "package-name/path" specifier, defaults to the import name
 *     type?: 'js'|'css'|'json',
 *     entrypoint?: bool,
 * }>
 */
return [
    'app' => ['path' => './assets/app.js', 'entrypoint' => true],
    '@hotwired/stimulus' => ['version' => '3.2.2'],
    '@symfony/stimulus-bundle' => ['path' => './vendor/symfony/stimulus-bundle/assets/dist/loader.js'],
    '@hotwired/turbo' => ['version' => '8.0.23'],
    'fullcalendar' => ['path' => './vendor/tailsfadmin/tailsfadmin-bundle/assets/vendor-src/fullcalendar/fullcalendar-esm.js'],
    'flatpickr' => ['version' => '4.6.13'],
    'flatpickr/dist/flatpickr.min.css' => ['version' => '4.6.13', 'type' => 'css'],
    'dropzone' => ['version' => '6.2.0'],
    'dropzone/dist/dropzone.css' => ['version' => '6.2.0', 'type' => 'css'],
    'apexcharts' => ['version' => '7.1.0'],
    'apexcharts/core' => ['version' => '7.1.0'],
    'fullcalendar/index.global.min.js' => ['version' => '6.1.21'],
    'jsvectormap' => ['version' => '1.7.0'],
    'jsvectormap/dist/maps/world.js' => ['version' => '1.7.0'],
    'jsvectormap/dist/jsvectormap.min.css' => ['version' => '1.7.0', 'type' => 'css'],
];
