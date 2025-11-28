<?php

// Prevent direct access
if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', dirname(__DIR__, 2));
}

/**
 * Sanitize output for HTML
 */
function h($string)
{
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

/**
 * Get base URL of the application
 */
function base_url($path = '')
{
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
    $host = $_SERVER['HTTP_HOST'];
    $script_dir = dirname($_SERVER['SCRIPT_NAME']);

    $base = $protocol . "://" . $host . $script_dir;

    // Clean up slashes
    $base = rtrim($base, '/');
    $path = ltrim($path, '/');

    return $base . '/' . $path;
}

/**
 * Redirect to a specific page
 */
function redirect($path)
{
    header("Location: " . base_url($path));
    exit;
}

/**
 * Format currency (THB)
 */
function format_currency($amount)
{
    return number_format($amount, 2) . ' ฿';
}

/**
 * Format date (Thai)
 */
function format_date_thai($date_string)
{
    if (!$date_string) return '-';
    $date = new DateTime($date_string);
    return $date->format('d/m/Y H:i');
}
