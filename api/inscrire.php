<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/vendor/autoload.php';
require_once dirname(__DIR__) . '/config/db.php';

use Cloudinary\Cloudinary;

header('Content-Type: application/json; charset=utf-8');

function respond(int $status, array $payload): void
{
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}

// 1. Méthode autorisée
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(405, ['success' => false, 'error' => 'Méthode non autorisée.']);
}

// 2. Validation des champs texte
$nom    = trim($_POST['nom'] ?? '');
$prenom = trim($_POST['prenom'] ?? '');
$ville  = trim($_POST['ville'] ?? '');

if ($nom === '' || $prenom === '' || $ville === '') {
    respond(422, ['success' => false, 'error' => 'Tous les champs sont obligatoires.']);
}

foreach ([$nom, $prenom, $ville] as $value) {
    if (mb_strlen($value) > 100) {
        respond(422, ['success' => false, 'error' => 'Un champ dépasse 100 caractères.']);
    }
}

// 3. Validation du fichier image
if (!isset($_FILES['photo']) || $_FILES['photo']['error'] !== UPLOAD_ERR_OK) {
    respond(422, ['success' => false, 'error' => 'Veuillez sélectionner une photo valide.']);
}

$tmpPath = $_FILES['photo']['tmp_name'];
$maxSize = 5 * 1024 * 1024; // 5 Mo

if ($_FILES['photo']['size'] > $maxSize) {
    respond(422, ['success' => false, 'error' => 'La photo ne doit pas dépasser 5 Mo.']);
}

$imageInfo = @getimagesize($tmpPath);
$allowed   = [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_WEBP];

if ($imageInfo === false || !in_array($imageInfo[2], $allowed, true)) {
    respond(422, ['success' => false, 'error' => 'Format accepté : JPG, PNG ou WebP.']);
}

try {
    // 4. Envoi vers Cloudinary
    $cloudinaryUrl = getenv('CLOUDINARY_URL');
    if (!$cloudinaryUrl) {
        throw new RuntimeException('CLOUDINARY_URL manquante.');
    }

    $cloudinary = new Cloudinary($cloudinaryUrl);
    $upload = $cloudinary->uploadApi()->upload($tmpPath, [
        'folder'          => 'stagiaires',
        'resource_type'   => 'image',
        'transformation'  => ['width' => 600, 'height' => 600, 'crop' => 'fill', 'gravity' => 'auto'],
    ]);

    $photoUrl = $upload['secure_url'];

    // 5. Enregistrement dans MySQL
    $pdo  = getDatabase();
    $stmt = $pdo->prepare(
        'INSERT INTO stagiaires (nom, prenom, ville, photo) VALUES (:nom, :prenom, :ville, :photo)'
    );
    $stmt->execute([
        ':nom'    => $nom,
        ':prenom' => $prenom,
        ':ville'  => $ville,
        ':photo'  => $photoUrl,
    ]);

    // 6. Réponse JSON
    respond(201, [
        'success' => true,
        'message' => 'Stagiaire inscrit avec succès.',
        'photo'   => $photoUrl,
    ]);
} catch (Throwable $e) {
    error_log('[inscrire.php] ' . $e->getMessage());
    respond(500, ['success' => false, 'error' => 'Erreur serveur. Veuillez réessayer.']);
}