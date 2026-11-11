<?php

$dumpExe = 'C:\wamp64\bin\mariadb\mariadb11.4.9\bin\mariadb-dump.exe';
if (!file_exists($dumpExe)) {
    die("Dump exe not found at: {$dumpExe}\n");
}

$cmd = "\"{$dumpExe}\" -P 3307 -u root --default-character-set=utf8mb4 kaldis_performance";
$output = shell_exec($cmd);

if (!$output || strlen($output) < 1000) {
    die("Failed to generate dump or output too small!\n");
}

// Remove all MariaDB-specific directives (/*M! ... */) that fail on MySQL/phpMyAdmin
$output = preg_replace('/^\/\*M!.*?\*\/;\s*/m', '', $output);
$output = preg_replace('/^\/\*M!.*?\*\/\s*/m', '', $output);

// Strip any carriage-return anomalies and save as clean UTF-8
$target = __DIR__ . '/../database/database_export_full.sql';
file_put_contents($target, $output);

echo "Successfully exported " . strlen($output) . " bytes in clean UTF-8 to " . realpath($target) . "\n";
