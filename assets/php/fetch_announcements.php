<?php
function fetchAnnouncements($pdo, $userRole, $limit = 10) {
    $targetAudiences = ['all'];
    
    switch ($userRole) {
        case 'faculty':
            $targetAudiences[] = 'faculty';
            break;
        case 'class':
            $targetAudiences[] = 'classes';
            break;
        case 'program_chair':
            $targetAudiences[] = 'program_chairs';
            $targetAudiences[] = 'faculty';
            break;
    }

    $placeholders = implode(',', array_fill(0, count($targetAudiences), '?'));
    
    $announcements_query = "
        SELECT a.*, u.full_name as created_by_name,
               DATE_FORMAT(a.created_at, '%M %d, %Y at %h:%i %p') as formatted_date,
               CASE 
                   WHEN a.created_at > DATE_SUB(NOW(), INTERVAL 2 HOUR) THEN CONCAT(TIMESTAMPDIFF(MINUTE, a.created_at, NOW()), ' minutes ago')
                   WHEN a.created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR) THEN CONCAT(TIMESTAMPDIFF(HOUR, a.created_at, NOW()), ' hours ago')
                   WHEN a.created_at > DATE_SUB(NOW(), INTERVAL 7 DAY) THEN CONCAT(TIMESTAMPDIFF(DAY, a.created_at, NOW()), ' days ago')
                   ELSE '1 week ago'
               END as time_ago
        FROM announcements a 
        JOIN users u ON a.created_by = u.user_id 
        WHERE a.is_active = TRUE 
        AND a.target_audience IN ($placeholders)
        ORDER BY a.created_at DESC 
        LIMIT " . intval($limit);
    
    $stmt = $pdo->prepare($announcements_query);
    $stmt->execute($targetAudiences);
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
function getAnnouncementCategory($target_audience) {
    switch ($target_audience) {
        case 'faculty': 
            return 'Faculty';
        case 'classes': 
            return 'Classes';
        case 'program_chairs': 
            return 'Admin';
        default: 
            return 'General';
    }
}
function getAnnouncementPriorityIcon($priority) {
    switch ($priority) {
        case 'high':
            return '🔴';
        case 'medium':
            return '🟡';
        case 'low':
            return '🟢';
        default:
            return '🔵';
    }
}
function renderAnnouncementCard($announcement) {
    $priorityIcon = getAnnouncementPriorityIcon($announcement['priority']);
    $category = getAnnouncementCategory($announcement['target_audience']);
    
    return "
        <div class=\"announcement-card\">
            <div class=\"announcement-header\">
                <div>
                    <div class=\"announcement-title\">" . htmlspecialchars($announcement['title']) . "</div>
                    <div class=\"announcement-category\">$category</div>
                </div>
                <div class=\"announcement-priority-icon\">$priorityIcon</div>
            </div>
            <div class=\"announcement-content\">" . htmlspecialchars($announcement['content']) . "</div>
            <div class=\"announcement-meta\">
                <span class=\"announcement-time\">" . $announcement['time_ago'] . "</span>
                <span class=\"announcement-priority priority-" . $announcement['priority'] . "\">
                    " . strtoupper($announcement['priority']) . "
                </span>
            </div>
            <div class=\"announcement-footer\">
                <small>By: " . htmlspecialchars($announcement['created_by_name']) . "</small>
            </div>
        </div>
    ";
}
?>