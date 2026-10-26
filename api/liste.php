<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/config/db.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

try {
    $pdo  = getDatabase();
    $stmt = $pdo->query(
        'SELECT id, nom, prenom, ville, photo, date_inscription
         FROM stagiaires
         ORDER BY id DESC'
    );

    echo json_encode([
        'success' => true,
        'data'    => $stmt->fetchAll(),
    ], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    error_log('[liste.php] ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error'   => 'Impossible de charger la liste.',
    ], JSON_UNESCAPED_UNICODE);
}