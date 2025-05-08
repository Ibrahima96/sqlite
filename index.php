<?php
// Connexion à PostgreSQL sur Render
$databaseUrl = getenv('DATABASE_URL');
if ($databaseUrl) {
    $dbParams = parse_url($databaseUrl);
    $pdo = new PDO(
        sprintf(
            "pgsql:host=%s;port=%s;dbname=%s;user=%s;password=%s",
            $dbParams['host'],
            $dbParams['port'],
            ltrim($dbParams['path'], '/'),
            $dbParams['user'],
            $dbParams['pass']
        )
    );
} else {
    // Connexion locale (développement)
    $pdo = new PDO('sqlite:./database/identifier.sqlite');
}
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);