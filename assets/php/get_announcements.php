<?php
require_once 'common_utilities.php';

initializeSession();
header('Content-Type: application/json');

$pdo = initializeDatabase();

if (!isset($_SESSION['user_id'])) {
    sendJsonResponse(['success' => false, 'message' => 'Unauthorized access'], 401);
}

try {
    $user_role = $_SESSION['role'];
    
    // Base query for announcements
    $announcements_query = "
        SELECT COUNT(*) as count
        FROM announcements a 
        WHERE a.is_active = TRUE 
        AND (a.target_audience = 'all' OR a.target_audience = ?)
        AND a.created_at > DATE_SUB(NOW(), INTERVAL 30 DAY)
    ";
    
    $stmt = $pdo->prepare($announcements_query);
    $stmt->execute([$user_role]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Get recent announcements for change detection
    $recent_query = "
        SELECT a.announcement_id, a.title, a.created_at
        FROM announcements a 
        WHERE a.is_active = TRUE 
        AND (a.target_audience = 'all' OR a.target_audience = ?)
        ORDER BY a.created_at DESC 
        LIMIT 10
    ";
    
    $stmt = $pdo->prepare($recent_query);
    $stmt->execute([$user_role]);
    $recent_announcements = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    sendJsonResponse([
        'success' => true,
        'count' => (int)$result['count'],
        'announcements' => $recent_announcements
    ]);
    
} catch (Exception $e) {
    sendJsonResponse(['success' => false, 'message' => 'Error fetching announcements'], 500);
}
?>