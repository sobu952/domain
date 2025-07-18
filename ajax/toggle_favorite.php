<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

require_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

$input = json_decode(file_get_contents('php://input'), true);
$domainId = $input['domain_id'] ?? null;

if (!$domainId) {
    echo json_encode(['success' => false, 'error' => 'Missing domain ID']);
    exit;
}

try {
    // Sprawdź czy domena jest już w ulubionych
    $stmt = $db->prepare("SELECT id FROM favorite_domains WHERE user_id = ? AND domain_id = ?");
    $stmt->execute([$_SESSION['user_id'], $domainId]);
    $favorite = $stmt->fetch();
    
    if ($favorite) {
        // Usuń z ulubionych
        $stmt = $db->prepare("DELETE FROM favorite_domains WHERE user_id = ? AND domain_id = ?");
        $stmt->execute([$_SESSION['user_id'], $domainId]);
        $isFavorite = false;
    } else {
        // Dodaj do ulubionych
        $stmt = $db->prepare("INSERT INTO favorite_domains (user_id, domain_id) VALUES (?, ?)");
        $stmt->execute([$_SESSION['user_id'], $domainId]);
        $isFavorite = true;
    }
    
    echo json_encode([
        'success' => true,
        'is_favorite' => $isFavorite
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>