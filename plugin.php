<?php
/*
Plugin Name: Path-Safe Slug Redirect
Plugin URI: https://github.com/highTower/append-qs
Description: Allows using first path segment as YOURLS slug and appends remaining path & query string to target URL.
Version: 1.4
Author: highTower
*/

// 1. Slug aus dem ersten Pfadsegment extrahieren
yourls_add_filter('request', 'ht_custom_slug_from_first_path');
function ht_custom_slug_from_first_path($request) {
    // Wenn ein Pfad vorhanden ist (nicht leer)
    if (!empty($_SERVER['REQUEST_URI'])) {
        $request_path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $parts = explode('/', trim($request_path, '/'));
        if (!empty($parts)) {
            return $parts[0]; // NUR das erste Element = Slug
        }
    }
    return $request; // Fallback
}

// 2. Subpfade + Query an Ziel-URL anhängen
yourls_add_filter('redirect_location', 'ht_append_path_and_query');
function ht_append_path_and_query($url) {
    $request_path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $parts = explode('/', trim($request_path, '/'));

    // Erstes Segment ist der Slug → entfernen
    array_shift($parts);

    // Rest zusammenbauen
    $subpath = implode('/', $parts);
    if (!empty($subpath)) {
        $subpath = '/' . $subpath;
    }

    // Query-String anhängen, falls vorhanden
    if (!empty($_SERVER['QUERY_STRING'])) {
        parse_str($_SERVER['QUERY_STRING'], $query);
        $query_string = http_build_query($query);
        $separator = (strpos($url, '?') === false) ? '?' : '&';
        $url .= $separator . $query_string;
    }

    // Ziel-URL parsen & Subpfad korrekt anfügen
    $parsed = parse_url($url);
    $base_path = rtrim($parsed['path'] ?? '', '/');

    $final_url = 
        ($parsed['scheme'] ?? 'http') . '://' .
        ($parsed['host'] ?? '') .
        (isset($parsed['port']) ? ':' . $parsed['port'] : '') .
        $base_path . $subpath .
        (isset($parsed['query']) ? '?' . $parsed['query'] : '') .
        (isset($parsed['fragment']) ? '#' . $parsed['fragment'] : '');

    return $final_url;
}
?>
