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
if (!function_exists('renderAnnouncementCard')) {
    function renderAnnouncementCard($announcement) {
        $priority_class = $announcement['priority'];
        $priority_icon = getAnnouncementPriorityIcon($announcement['priority']);
        $category = getAnnouncementCategory($announcement['target_audience']);
        $created_at = $announcement['time_ago'] ?? date('M j, Y', strtotime($announcement['created_at']));
        $full_date = date('F d, Y', strtotime($announcement['created_at']));
        $announcementJson = htmlspecialchars(json_encode($announcement), ENT_QUOTES, 'UTF-8');
        $emailButton = '';
        if (isset($_SESSION['role']) && $_SESSION['role'] === 'campus_director') {
            $emailButton = "
                <button onclick='event.stopPropagation(); emailAnnouncement({$announcementJson})' title='Email Announcement' style='background: none; border: none; cursor: pointer; margin-left: 4px; padding: 4px; color: inherit; vertical-align: middle; opacity: 0.7; transition: opacity 0.2s;' onmouseover='this.style.opacity=1' onmouseout='this.style.opacity=0.7'>
                    <svg class='feather' style='width: 14px; height: 14px;'><use href='#mail'></use></svg>
                </button>";
        }
        return "
        <div class='announcement-card priority-{$priority_class}' id='announcement-{$announcement['announcement_id']}'>
            <div class='announcement-header'>
                <div class='announcement-priority'>
                    <span class='priority-icon'>{$priority_icon}</span>
                    <span class='priority-text'>" . ucfirst($announcement['priority']) . "</span>
                </div>
                <div class='announcement-date' data-full-date='{$full_date}'>
                    {$created_at}
                    <button onclick='event.stopPropagation(); printAnnouncement({$announcementJson})' title='Print Announcement' style='background: none; border: none; cursor: pointer; margin-left: 8px; padding: 4px; color: inherit; vertical-align: middle; opacity: 0.7; transition: opacity 0.2s;' onmouseover='this.style.opacity=1' onmouseout='this.style.opacity=0.7'>
                        <svg class='feather' style='width: 14px; height: 14px;'><use href='#printer'></use></svg>
                    </button>
                    {$emailButton}
                </div>
            </div>
            <h4 class='announcement-title'>" . htmlspecialchars($announcement['title']) . "</h4>
            <p class='announcement-content'>" . htmlspecialchars($announcement['content']) . "</p>
            <div class='announcement-footer'>
                <span class='announcement-author'>By: " . htmlspecialchars($announcement['created_by_name'] ?? 'System') . "</span>
                <span class='announcement-audience'>{$category}</span>
            </div>
        </div>";
    }
}
?>
