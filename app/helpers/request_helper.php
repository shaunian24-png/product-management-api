<?php
defined('PREVENT_DIRECT_ACCESS') OR exit('No direct script access allowed');

/**
 * Request helpers for the JSON API controllers.
 *
 * Why not just use $this->api->body()?
 * Api::body() runs htmlspecialchars() over every string, which would store
 * "Tom & Jerry" as "Tom &amp; Jerry" and silently alter passwords that
 * contain characters such as & < > " '. All database access goes through
 * prepared statements and React escapes output, so the raw JSON body is
 * safe to use after validation.
 */

if ( ! function_exists('json_input'))
{
    /**
     * Read the JSON request body.
     *
     * @return array|null  Decoded object, [] for an empty body, null if invalid JSON
     */
    function json_input()
    {
        $raw = file_get_contents('php://input');

        if ($raw === false || trim($raw) === '') {
            return [];
        }

        $data = json_decode($raw, true);

        return (json_last_error() === JSON_ERROR_NONE && is_array($data)) ? $data : null;
    }
}

if ( ! function_exists('client_ip'))
{
    /**
     * Best-effort client IP (Render and most hosts sit behind a proxy).
     * Only used as a rate-limit key, never for authorization.
     */
    function client_ip()
    {
        $forwarded = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? '';

        if ($forwarded !== '') {
            $first = trim(explode(',', $forwarded)[0]);
            if (filter_var($first, FILTER_VALIDATE_IP)) {
                return $first;
            }
        }

        return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    }
}
