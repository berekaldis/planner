<?php

namespace App\Database\Connectors;

use Illuminate\Database\Connectors\MySqlConnector as BaseMySqlConnector;
use Illuminate\Support\Facades\Log;
use Throwable;

class ResilientMySqlConnector extends BaseMySqlConnector
{
    /**
     * Establish a database connection with auto-healing fallback.
     * Prevents SQLSTATE[HY000] [2002] "No such file or directory" when host is 'localhost'
     * on Linux/Unix systems by falling back to TCP (127.0.0.1), alternate ports, or valid socket paths.
     */
    public function connect(array $config)
    {
        // 1. First attempt with default config
        try {
            return parent::connect($config);
        } catch (Throwable $e) {
            $msg = $e->getMessage();
            $isConnectionError = str_contains($msg, '2002')
                || str_contains($msg, 'No such file or directory')
                || str_contains($msg, 'Connection refused')
                || str_contains($msg, 'Operation timed out');

            if (!$isConnectionError) {
                throw $e;
            }

            // Strategy 1: Map 'localhost' to '127.0.0.1' over TCP
            // On Linux, PDO MySQL treats 'localhost' as Unix socket and ignores DB_PORT.
            // Connecting via 127.0.0.1 forces TCP/IP to the specified port (e.g. 3307).
            $host = strtolower(trim((string)($config['host'] ?? '')));
            if ($host === 'localhost' || empty($config['unix_socket'])) {
                $tcpConfig = $config;
                $tcpConfig['host'] = '127.0.0.1';
                unset($tcpConfig['unix_socket']);
                try {
                    return parent::connect($tcpConfig);
                } catch (Throwable $e1) {}
            }

            // Strategy 2: If port was non-standard (e.g. 3307) and failed, try standard port 3306 on 127.0.0.1
            $port = (int)($config['port'] ?? 3306);
            if ($port !== 3306) {
                $portConfig = $config;
                $portConfig['host'] = '127.0.0.1';
                $portConfig['port'] = 3306;
                unset($portConfig['unix_socket']);
                try {
                    return parent::connect($portConfig);
                } catch (Throwable $e2) {}
            }

            // Strategy 3: Check for existing Unix sockets on disk
            $commonSockets = [
                '/var/lib/mysql/mysql.sock',
                '/tmp/mysql.sock',
                '/var/run/mysqld/mysqld.sock',
                '/var/run/mysql/mysql.sock',
                '/opt/lampp/var/mysql/mysql.sock',
            ];
            foreach ($commonSockets as $sock) {
                if (file_exists($sock)) {
                    $sockConfig = $config;
                    $sockConfig['unix_socket'] = $sock;
                    unset($sockConfig['host'], $sockConfig['port']);
                    try {
                        return parent::connect($sockConfig);
                    } catch (Throwable $e3) {}
                }
            }

            // If all resilient fallbacks fail, rethrow original exception
            throw $e;
        }
    }
}
