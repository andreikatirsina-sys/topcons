<?php
/**
 * env.php — TopCons.md
 * Incarca variabilele din fisierul .env (fara nicio librarie externa).
 * NU necesita composer/internet - e o functie simpla, autonoma.
 */

function loadEnv($path) {
    if (!file_exists($path)) {
        throw new RuntimeException("Fisierul .env nu exista la: {$path}. Copiaza .env.example ca .env si completeaza-l.");
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) {
            continue;
        }
        if (!str_contains($line, '=')) {
            continue;
        }
        [$key, $value] = explode('=', $line, 2);
        $key = trim($key);
        $value = trim($value);
        // Elimina ghilimelele daca cineva le-a pus in jurul valorii
        $value = trim($value, "\"'");

        if (!array_key_exists($key, $_ENV)) {
            $_ENV[$key] = $value;
            putenv("{$key}={$value}");
        }
    }
}
