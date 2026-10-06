<?php
defined('PREVENT_DIRECT_ACCESS') OR exit('No direct script access allowed');
/**
 * ------------------------------------------------------------------
 * LavaLust - an opensource lightweight PHP MVC Framework
 * ------------------------------------------------------------------
 *
 * MIT License
 *
 * Copyright (c) 2020 Ronald M. Marasigan
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 *
 * @package LavaLust
 * @author Ronald M. Marasigan <ronald.marasigan@yahoo.com>
 * @since Version 1
 * @link https://github.com/ronmarasigan/LavaLust
 * @license https://opensource.org/licenses/MIT MIT License
 */

/*
| -------------------------------------------------------------------
| DATABASE CONNECTIVITY SETTINGS
| -------------------------------------------------------------------
| This file will contain the settings needed to access your database.
| -------------------------------------------------------------------
| EXPLANATION OF VARIABLES
| -------------------------------------------------------------------
|
|	['driver'] 		The driver of your database server.
|	['hostname'] 	The hostname of your database server.
|	['port'] 		The port used by your database server.
|	['username'] 	The username used to connect to the database
|	['password'] 	The password used to connect to the database
|	['database'] 	The name of the database you want to connect to
|	['charset']		The default character set
|   ['dbprefix']    You can add an optional prefix, which will be added
|				    to the table name when using the  Query Builder class
|   You can create new instance of the database by adding new element of
|   $database variable.
|   Example: $database['another_example'] = array('key' => 'value')
*/

/*
| -------------------------------------------------------------------
| TLS / SSL (required by Aiven MySQL)
| -------------------------------------------------------------------
| Aiven only accepts encrypted connections. Set DB_SSL=true to enable it.
|
|   DB_SSL=true            Turn TLS on (encryption only, same as Aiven's
|                          "ssl-mode=REQUIRED").
|   DB_SSL_CA=<path>       Optional. Path to Aiven's ca.pem. When given, the
|                          server certificate is verified too.
|   DB_SSL_CA_CERT=<pem>   Optional. Same as above but the PEM text itself
|                          (handy on Render, where you can't upload files).
|
| The numeric keys below are the PDO MySQL driver options. Plain numbers
| are used on purpose: the PDO::MYSQL_ATTR_* constants are deprecated in
| PHP 8.5 and would raise notices.
|   1002 = INIT_COMMAND, 1009 = SSL_CA, 1014 = SSL_VERIFY_SERVER_CERT
*/
$db_options = array(
    // Always talk to MySQL in UTC so TIMESTAMP columns are predictable.
    1002 => "SET time_zone = '+00:00'",
);

if (filter_var(getenv('DB_SSL'), FILTER_VALIDATE_BOOLEAN)) {
    $ca_file = getenv('DB_SSL_CA') ?: '';

    if ($ca_file === '' && ($ca_pem = getenv('DB_SSL_CA_CERT'))) {
        // Render env vars often store newlines as literal "\n"
        $ca_pem  = str_replace('\\n', "\n", $ca_pem);
        $ca_file = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'db-ca-' . md5($ca_pem) . '.pem';
        if (!is_file($ca_file)) {
            file_put_contents($ca_file, $ca_pem);
        }
    }

    if ($ca_file !== '' && is_file($ca_file)) {
        // Encrypted + server certificate verified
        $db_options[1009] = $ca_file;
        $db_options[1014] = true;
    } else {
        // Encrypted only (equivalent to ssl-mode=REQUIRED): no CA to verify against.
        foreach (['/etc/ssl/certs/ca-certificates.crt', '/etc/pki/tls/certs/ca-bundle.crt', '/etc/ssl/cert.pem'] as $bundle) {
            if (is_file($bundle)) {
                $db_options[1009] = $bundle;
                break;
            }
        }
        $db_options[1014] = false;
    }
}

$database['main'] = array(
    'driver'	=> getenv('DB_DRIVER') ?: '',
    'hostname'	=> getenv('DB_HOST') ?: '',
    'port'		=> getenv('DB_PORT') ?: '',
    'username'	=> getenv('DB_USER') ?: '',
    'password'	=> getenv('DB_PASSWORD') ?: '',
    'database'	=> getenv('DB_NAME') ?: '',
    'charset'	=> getenv('DB_CHARSET') ?: '',
    'dbprefix'	=> getenv('DB_PREFIX') ?: '',
    // Optional for SQLite
    'path'      => '',
    // Extra PDO driver options (TLS, time zone). See above.
    'options'   => $db_options
);

?>