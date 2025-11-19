<?php
require_once 'assets/php/common_utilities.php';
initializeSession();
$pdo = initializeDatabase();
$php_timezone = date_default_timezone_get();
$mysql_timezone_map = [
    'Asia/Manila' => '+08:00',
    'Europe/Berlin' => '+01:00',
    'UTC' => '+00:00',
    'America/New_York' => '-05:00',
    'America/Los_Angeles' => '-08:00'
];
$mysql_timezone = $mysql_timezone_map[$php_timezone] ?? '+08:00'; 
$pdo->exec("SET time_zone = '$mysql_timezone'");
$current_time = date('H:i:s');
$current_date = date('Y-m-d');
$current_day = date('w');
function getFacultyInfo($pdo, $user_id) {
    $faculty_query = "SELECT f.*, u.full_name FROM faculty f JOIN users u ON f.user_id = u.user_id WHERE f.user_id = ? AND u.is_active = TRUE";
    $stmt = $pdo->prepare($faculty_query);
    $stmt->execute([$user_id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}
function getLocationHistory($pdo, $faculty_id, $limit = 10) {
    $limit = (int)$limit;
    $query = "SELECT location, time_set, time_changed 
              FROM location_history 
              WHERE faculty_id = ? 
              ORDER BY time_set DESC 
              LIMIT " . $limit;
    $stmt = $pdo->prepare($query);
    $stmt->execute([$faculty_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
function getSmartScheduleTabs($pdo, $faculty_id) {
    $schedules_query = "SELECT DISTINCT days FROM schedules WHERE faculty_id = ? AND is_active = TRUE ORDER BY days";
    $stmt = $pdo->prepare($schedules_query);
    $stmt->execute([$faculty_id]);
    $schedules = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if (empty($schedules)) {
        return [];
    }
    $all_days = [];
    foreach ($schedules as $schedule) {
        $days = $schedule['days'];
        switch($days) {
            case 'MW':
                $all_days[] = 'M';
                $all_days[] = 'W';
                break;
            case 'MF':
                $all_days[] = 'M';
                $all_days[] = 'F';
                break;
            case 'WF':
                $all_days[] = 'W';
                $all_days[] = 'F';
                break;
            case 'MWF':
                $all_days[] = 'M';
                $all_days[] = 'W';
                $all_days[] = 'F';
                break;
            case 'TTH':
                $all_days[] = 'T';
                $all_days[] = 'TH';
                break;
            case 'MTWTHF':
                $all_days[] = 'M';
                $all_days[] = 'T';
                $all_days[] = 'W';
                $all_days[] = 'TH';
                $all_days[] = 'F';
                break;
            case 'MTWTHFS':
                $all_days[] = 'M';
                $all_days[] = 'T';
                $all_days[] = 'W';
                $all_days[] = 'TH';
                $all_days[] = 'F';
                $all_days[] = 'S';
                break;
            default:
                $all_days[] = $days;
                break;
        }
    }
    $unique_days = array_unique($all_days);
    $day_order = ['M', 'T', 'W', 'TH', 'F', 'S'];
    $sorted_tabs = [];
    foreach ($day_order as $day) {
        if (in_array($day, $unique_days)) {
            $sorted_tabs[] = $day;
        }
    }
    return $sorted_tabs;
}
function getScheduleStatus($status) {
    switch ($status) {
        case 'ongoing': return ['text' => 'In Progress', 'class' => 'ongoing'];
        case 'upcoming': return ['text' => 'Upcoming', 'class' => 'upcoming'];
        case 'finished': return ['text' => 'Completed', 'class' => 'finished'];
        default: return ['text' => 'Unknown', 'class' => 'unknown'];
    }
}
validateUserSession('faculty');
$user_id = $_SESSION['user_id'];
$faculty_name = $_SESSION['full_name'];
$faculty_info = getFacultyInfo($pdo, $user_id);
if (!$faculty_info) {
    die("Faculty information not found");
}
try {
    $set_online_query = "UPDATE faculty SET is_active = 1, status = 'Available', last_location_update = NOW() WHERE user_id = ?";
    $stmt = $pdo->prepare($set_online_query);
    $stmt->execute([$user_id]);
} catch (Exception $e) {
    error_log("Failed to set faculty online status: " . $e->getMessage());
}
$today_schedule = [];
$location_history = getLocationHistory($pdo, $faculty_info['faculty_id'], 10);
$schedule_tabs = getSmartScheduleTabs($pdo, $faculty_info['faculty_id']);
require_once 'assets/php/announcement_functions.php';
$announcements = fetchAnnouncements($pdo, $_SESSION['role'], 10);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FaculTrack - Faculty Dashboard</title>
    <link rel="stylesheet" href="assets/css/theme.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .schedule-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 1px;
            background: linear-gradient(90deg, transparent, rgba(46, 125, 50, 0.3), transparent);
            animation: shimmer 4s infinite;
        }
        @keyframes shimmer {
            0%, 100% { opacity: 0; }
            50% { opacity: 1; }
        }
        .schedule-card {
            height: 100%;
            display: flex;
            flex-direction: column;
        }
        @media (min-width: 1025px) {
            .dashboard-grid {
                display: grid !important;
                grid-template-columns: 2fr 1fr !important;
                grid-template-rows: 1fr 1fr !important;
                gap: 0 !important;
                height: calc(100vh - var(--header-height, 160px) - 40px) !important;
                overflow: hidden !important;
            }
            .schedule-section {
                grid-column: 1 !important;
                grid-row: 1 / 3 !important;
                overflow-y: auto !important;
                overflow-x: hidden !important;
                border-right: 1px solid rgba(224, 224, 224, 0.3) !important;
                padding: 20px !important;
                box-sizing: border-box !important;
            }
            .location-section {
                grid-column: 2 !important;
                grid-row: 1 !important;
                border-bottom: 1px solid rgba(224, 224, 224, 0.3) !important;
                padding: 20px !important;
                overflow: hidden !important;
                background: white !important;
                min-height: 100% !important;
                box-sizing: border-box !important;
            }
            .actions-section {
                grid-column: 2 !important;
                grid-row: 2 !important;
                position: relative !important;
                bottom: auto !important;
                left: auto !important;
                right: auto !important;
                z-index: auto !important;
                background: white !important;
                padding: 0 !important;
                border: none !important;
                box-shadow: none !important;
                backdrop-filter: none !important;
                min-height: 100% !important;
                transition: none !important;
                overflow: hidden !important;
                box-sizing: border-box !important;
            }
        }
        @media (max-width: 1024px) and (min-width: 769px) {
            body {
                padding-bottom: 140px !important;
                overflow: hidden !important;
            }
            .dashboard-grid {
                display: grid !important;
                grid-template-columns: 1fr !important;
                grid-template-rows: auto 1fr !important;
                height: calc(100vh - var(--header-height, 160px) - 140px) !important;
                gap: 16px !important;
                overflow: hidden !important;
            }
            .location-section {
                grid-row: 1 !important;
                grid-column: 1 !important;
                order: 1 !important;
                padding: 16px !important;
                border-bottom: 1px solid rgba(224, 224, 224, 0.3) !important;
                min-height: auto !important;
                max-height: 140px !important;
                overflow: hidden !important;
            }
            .schedule-section {
                grid-row: 2 !important;
                grid-column: 1 !important;
                order: 2 !important;
                padding: 16px !important;
                overflow-y: auto !important;
                overflow-x: hidden !important;
                border: none !important;
            }
            .actions-section {
                position: fixed !important;
                bottom: 0 !important;
                left: 0 !important;
                right: 0 !important;
                z-index: 1000 !important;
                background: rgba(255, 255, 255, 0.98) !important;
                padding: 16px !important;
                border-top: 1px solid rgba(46, 125, 50, 0.2) !important;
                box-shadow: 0 -5px 30px rgba(0, 0, 0, 0.15) !important;
                backdrop-filter: blur(10px) !important;
                height: 140px !important;
                order: 3 !important;
            }
            .quick-actions h3 {
                display: none !important;
            }
            .action-card {
                padding: 12px !important;
                margin: 4px !important;
            }
            .action-icon {
                font-size: 1.8rem !important;
                margin-bottom: 4px !important;
            }
            .quick-actions {
                height: 100% !important;
                display: flex !important;
                flex-direction: column !important;
                justify-content: center !important;
                align-items: center !important;
                margin: 0 !important;
                padding: 0 !important;
            }
            .actions-grid {
                display: grid !important;
                grid-template-columns: 1fr 1fr 1fr !important;
                gap: 12px !important;
                width: 100% !important;
                max-width: none !important;
                flex: 0 !important;
            }
        }
        @media (max-width: 768px) {
            body .page-header {
                position: fixed !important;
                top: 0 !important;
                left: 0 !important;
                right: 0 !important;
                z-index: 1001 !important;
                background: var(--primary-green) !important;
                backdrop-filter: none !important;
                transform: translateY(0) !important;
                opacity: 1 !important;
                transition: transform 0.4s cubic-bezier(0.4, 0, 0.2, 1), opacity 0.4s ease !important;
            }
            .page-header.scroll-hidden {
                transform: translateY(-100%) !important;
                opacity: 0 !important;
            }
            body {
                overflow-y: auto !important;
                padding-bottom: 100px !important;
            }
            .dashboard-grid {
                display: grid !important;
                grid-template-columns: 1fr !important;
                grid-template-rows: auto 1fr !important;
                height: calc(100vh - 100px - 24px) !important;
                min-height: calc(100vh - 100px - 24px) !important;
                gap: 12px !important;
            }
            .location-section {
                grid-row: 1 !important;
                grid-column: 1 !important;
                order: 1 !important;
                padding: 12px !important;
                border-bottom: 1px solid rgba(224, 224, 224, 0.3) !important;
                min-height: auto !important;
                max-height: 120px !important;
            }
            .schedule-section {
                grid-row: 2 !important;
                grid-column: 1 !important;
                order: 2 !important;
                padding: 12px !important;
                padding-bottom: 100px !important;
                overflow-y: auto !important;
                border: none !important;
            }
            .schedule-section.scroll-mode-active {
                max-height: calc(100vh - 220px) !important;
                overflow-y: auto !important;
                padding-bottom: 20px !important;
            }
            .actions-section {
                position: fixed !important;
                bottom: 0 !important;
                left: 0 !important;
                right: 0 !important;
                z-index: 1000 !important;
                background: rgba(255, 255, 255, 0.98) !important;
                padding: 12px !important;
                border-top: 1px solid rgba(46, 125, 50, 0.2) !important;
                box-shadow: 0 -5px 30px rgba(0, 0, 0, 0.15) !important;
                backdrop-filter: blur(10px) !important;
                height: 100px !important;
                order: 3 !important;
                transform: translateY(100%) !important;
                opacity: 0 !important;
                transition: transform 0.4s cubic-bezier(0.4, 0, 0.2, 1), opacity 0.4s ease !important;
            }
            .actions-section.scroll-visible {
                transform: translateY(0) !important;
                opacity: 1 !important;
            }
            .quick-actions h3 {
                display: none !important;
            }
            .action-card {
                padding: 8px !important;
                margin: 2px !important;
            }
            .action-icon {
                font-size: 1.4rem !important;
                margin-bottom: 2px !important;
            }
            .action-title {
                font-size: 0.7rem !important;
            }
            .quick-actions {
                height: 100% !important;
                display: flex !important;
                flex-direction: column !important;
                justify-content: center !important;
                align-items: center !important;
                margin: 0 !important;
                padding: 0 !important;
            }
            .actions-grid {
                display: grid !important;
                grid-template-columns: 1fr 1fr 1fr !important;
                gap: 8px !important;
                width: 100% !important;
                max-width: none !important;
                flex: 0 !important;
            }
        }
        .schedule-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding: 15px;
            background: linear-gradient(90deg, transparent, rgba(46, 125, 50, 0.05), transparent);
            border-radius: 8px;
            margin: -10px -10px 20px -10px;
            border-bottom: 2px solid rgba(240, 240, 240, 0.8);
        }
        .schedule-header h3 {
            color: var(--text-green-primary);
            margin: 0;
            font-size: 1.4rem;
            text-shadow: 0 2px 4px rgba(255, 255, 255, 0.8);
            font-weight: 700;
        }
        .schedule-date {
            color: var(--text-secondary);
            font-size: 0.9rem;
            font-weight: 500;
            text-shadow: 0 1px 2px rgba(255, 255, 255, 0.8);
        }
        .schedule-list {
            display: flex;
            flex-direction: column;
            gap: 15px;
            flex: 1;
            overflow-y: auto;
            overflow-x: hidden;
        }
        .schedule-item {
            display: flex;
            align-items: center;
            padding: 15px;
            background: var(--bg-glass-semi);
            border-radius: 12px;
            border-left: 4px solid #e0e0e0;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: var(--shadow-medium), inset 0 1px 0 rgba(255, 255, 255, 0.9);
            position: relative;
            overflow: hidden;
            gap: 10px;
        }
        .schedule-item::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.6s;
        }
        .schedule-item:hover::before {
            left: 100%;
        }
        .schedule-item:hover {
            transform: translateY(-3px) translateX(2px);
            box-shadow: var(--shadow-heavy), inset 0 2px 0 rgba(255, 255, 255, 1);
        }
        .schedule-item.ongoing {
            background: var(--status-available-bg);
            border-left-color: var(--primary-green-light);
            box-shadow: 0 4px 20px rgba(76, 175, 80, 0.25), inset 0 1px 0 rgba(255, 255, 255, 0.9);
            border: 1px solid rgba(76, 175, 80, 0.2);
        }
        .schedule-item.upcoming {
            background: var(--status-busy-bg);
            border-left-color: var(--warning-dark);
            box-shadow: 0 4px 15px rgba(255, 152, 0, 0.2), inset 0 1px 0 rgba(255, 255, 255, 0.9);
            border: 1px solid rgba(255, 152, 0, 0.2);
        }
        .schedule-item.finished {
            background: linear-gradient(135deg, rgba(245, 245, 245, 0.9), rgba(238, 238, 238, 0.9));
            border-left-color: #9E9E9E;
            opacity: 0.8;
            box-shadow: 0 2px 8px rgba(158, 158, 158, 0.2),
                        inset 0 1px 0 rgba(255, 255, 255, 0.9);
        }
        .location-section .location-update-card {
            height: auto !important;
            display: block !important;
            background: transparent !important;
            position: relative !important;
            top: 0 !important;
        }
        .location-section .location-current {
            height: auto !important;
            display: block !important;
        }
        .location-section .location-display {
            height: auto !important;
            display: block !important;
        }
        .location-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            padding: 15px;
            background: linear-gradient(90deg, transparent, rgba(46, 125, 50, 0.05), transparent);
            border-radius: 8px;
            margin: -10px -10px 15px -10px;
        }
        .location-header h3 {
            color: var(--text-green-primary);
            margin: 0;
            font-size: 1.2rem;
            text-shadow: 0 2px 4px rgba(255, 255, 255, 0.8);
            font-weight: 700;
        }
        .location-status {
            display: flex;
            align-items: center;
            gap: 5px;
            font-size: 0.85rem;
            color: var(--primary-green-light);
            text-shadow: 0 1px 2px rgba(255, 255, 255, 0.8);
            padding: 4px 8px;
            background: rgba(76, 175, 80, 0.1);
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(76, 175, 80, 0.2);
        }
        .location-updated {
            font-size: 0.8rem;
            color: var(--text-secondary);
            text-shadow: 0 1px 1px rgba(255, 255, 255, 0.8);
        }
        .location-actions {
            display: flex;
            gap: 10px;
        }
        .location-actions button {
            flex: 1;
            padding: 10px 15px;
            border: none;
            border-radius: 8px;
            font-size: 0.85rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            background: linear-gradient(135deg, #2E7D32 0%, #4CAF50 100%);
            color: white;
            box-shadow: 0 4px 15px rgba(46, 125, 50, 0.3),
                        inset 0 1px 0 rgba(255, 255, 255, 0.2);
            text-shadow: 1px 1px 3px rgba(0, 0, 0, 0.2);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        .location-actions button:hover {
            background: linear-gradient(135deg, #1B5E20 0%, #2E7D32 100%);
            box-shadow: 0 6px 20px rgba(46, 125, 50, 0.4),
                        inset 0 2px 0 rgba(255, 255, 255, 0.3);
            transform: translateY(-2px);
        }
        .actions-section .quick-actions {
            height: auto !important;
            display: block !important;
            position: absolute !important;
            bottom: 20px !important;
            left: 20px !important;
            right: 20px !important;
        }
        .quick-actions h3 {
            color: #1B5E20;
            margin: 0 0 15px 0;
            font-size: 1.2rem;
            text-shadow: 0 2px 4px rgba(255, 255, 255, 0.8);
            font-weight: 700;
            padding: 15px;
            background: linear-gradient(90deg, transparent, rgba(46, 125, 50, 0.05), transparent);
            border-radius: 8px;
            margin: -10px -10px 15px -10px;
        }
        .actions-section .actions-grid {
            display: grid !important;
            grid-template-columns: 1fr 1fr 1fr !important;
            gap: 12px !important;
            height: 120px !important;
        }
        .action-card {
            border: 2px solid rgba(46, 125, 50, 0.1);
            border-radius: 12px;
            padding: 16px;
            text-align: center;
            cursor: pointer;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            background: linear-gradient(145deg, #ffffff, #f8f9fa);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08),
                        inset 0 1px 0 rgba(255, 255, 255, 0.9);
            position: relative;
            overflow: hidden;
        }
        .action-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(46, 125, 50, 0.1), transparent);
            transition: left 0.6s;
        }
        .action-card:hover::before {
            left: 100%;
        }
        .action-card:hover {
            background: linear-gradient(135deg, rgba(233, 236, 239, 0.9), rgba(248, 249, 250, 0.9));
            border-color: #2E7D32;
            transform: translateY(-4px) scale(1.02);
            box-shadow: 0 8px 25px rgba(46, 125, 50, 0.15),
                        inset 0 2px 0 rgba(255, 255, 255, 1);
        }
        .action-icon {
            font-size: 2rem;
            margin-bottom: 4px;
            color: #2E7D32;
            filter: drop-shadow(0 2px 4px rgba(46, 125, 50, 0.3));
            transition: all 0.3s ease;
        }
        .action-card:hover .action-icon {
            transform: scale(1.1);
            filter: drop-shadow(0 4px 8px rgba(46, 125, 50, 0.4));
        }
        .action-title {
            font-size: 1rem;
            font-weight: bold;
            margin-bottom: 3px;
            text-shadow: 0 1px 2px rgba(255, 255, 255, 0.8);
        }
        .btn-primary .action-title {
            color: white;
            text-shadow: 1px 1px 3px rgba(0, 0, 0, 0.3);
        }
        .action-subtitle {
            font-size: 0.75rem;
            color: #666;
            text-shadow: 0 1px 1px rgba(255, 255, 255, 0.8);
        }
        .schedule-time {
            min-width: 70px;
            text-align: center;
            margin-right: 15px;
        }
        .time-display {
            font-size: 1rem;
            font-weight: bold;
            color: #333;
            text-shadow: 0 1px 2px rgba(255, 255, 255, 0.8);
        }
        .time-duration {
            font-size: 0.75rem;
            color: #666;
            margin-top: 2px;
            text-shadow: 0 1px 1px rgba(255, 255, 255, 0.8);
        }
        .schedule-details {
            flex: 1;
            margin-right: 15px;
        }
        .schedule-info {
            gap: 12px;
            font-size: 0.8rem;
            color: #666;
            margin-top: 5px;
            text-shadow: 0 1px 1px rgba(255, 255, 255, 0.8);
        }
            .schedule-info span {
            display: block;
        }
        .schedule-status {
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            gap: 6px;
        }
        .no-schedule {
            text-align: center;
            padding: 40px 20px;
            color: #666;
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
        }
        .no-schedule-icon {
            font-size: 2.5rem;
            margin-bottom: 15px;
            opacity: 0.5;
            filter: drop-shadow(0 2px 4px rgba(0, 0, 0, 0.1));
        }
        .no-schedule-text {
            font-size: 1.1rem;
            margin-bottom: 5px;
            color: #333;
            text-shadow: 0 1px 2px rgba(255, 255, 255, 0.8);
        }
        .no-schedule-subtitle {
            font-size: 0.85rem;
            color: #666;
            text-shadow: 0 1px 1px rgba(255, 255, 255, 0.8);
        }
        .schedule-tabs {
            display: flex;
            gap: 5px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }
        .schedule-tab {
            padding: 8px 16px;
            border: 1px solid rgba(221, 221, 221, 0.5);
            background: linear-gradient(145deg, #f8f9fa, #e9ecef);
            border-radius: 8px;
            cursor: pointer;
            font-size: 0.85rem;
            font-weight: 500;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05),
                        inset 0 1px 0 rgba(255, 255, 255, 0.9);
            text-shadow: 0 1px 1px rgba(255, 255, 255, 0.8);
        }
        .schedule-tab.active {
            background: linear-gradient(135deg, #2E7D32 0%, #4CAF50 100%);
            color: white;
            border-color: #2E7D32;
            box-shadow: 0 4px 15px rgba(46, 125, 50, 0.3),
                        inset 0 1px 0 rgba(255, 255, 255, 0.2);
            text-shadow: 1px 1px 3px rgba(0, 0, 0, 0.3);
        }
        .schedule-tab:hover:not(.active) {
            background: linear-gradient(145deg, #e9ecef, #dee2e6);
            border-color: #aaa;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08),
                        inset 0 1px 0 rgba(255, 255, 255, 1);
            transform: translateY(-2px);
        }
        .location-history-list {
            max-height: 400px;
            overflow-y: auto;
            padding: 15px;
        }
        .history-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 15px;
            margin-bottom: 8px;
            background: linear-gradient(145deg, #f8f9fa, #e9ecef);
            border-radius: 8px;
            border-left: 3px solid #9E9E9E;
            transition: all 0.3s ease;
            position: relative;
        }
        .history-item:hover {
            background: linear-gradient(145deg, #e9ecef, #dee2e6);
            transform: translateX(3px);
        }
        .history-item.current-location {
            background: linear-gradient(135deg, rgba(232, 245, 232, 0.9), rgba(200, 230, 201, 0.9));
            border-left-color: #4CAF50;
            box-shadow: 0 4px 15px rgba(76, 175, 80, 0.2);
        }
        .history-location-name {
            font-size: 1rem;
            font-weight: 600;
            color: #333;
            flex: 1;
        }
        .history-timestamp {
            font-size: 0.85rem;
            color: #666;
            margin-left: 15px;
        }
        .current-badge {
            position: absolute;
            top: -8px;
            right: 10px;
            background: #4CAF50;
            color: white;
            font-size: 0.7rem;
            padding: 2px 8px;
            border-radius: 10px;
            font-weight: 600;
            box-shadow: 0 2px 8px rgba(76, 175, 80, 0.3);
        }
        .no-history {
            text-align: center;
            padding: 40px 20px;
            color: #666;
        }
        .no-history p {
            margin: 0;
            font-size: 0.95rem;
        }
        .loading-state {
            text-align: center;
            padding: 40px 20px;
            color: #666;
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            font-size: 1rem;
            animation: pulse 2s infinite;
        }
        @keyframes pulse {
            0%, 100% { opacity: 0.6; }
            50% { opacity: 1; }
        }
</style>
</head>
<body class="faculty-page">
    <?php include 'assets/php/feather_icons.php'; ?>
    <div class="main-container">
        <div class="sidebar-overlay" onclick="closeSidebar()"></div>
        <div class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <button class="close-btn" onclick="closeSidebar()">×</button>
                <div class="sidebar-title">Announcements</div>
                <div class="sidebar-subtitle">Latest Updates</div>
            </div>
            <div class="announcements-container" id="announcementsContainer">
                <?php foreach ($announcements as $announcement): ?>
                <div class="announcement-card">
                    <div class="announcement-header">
                        <div>
                            <div class="announcement-title"><?php echo htmlspecialchars($announcement['title']); ?></div>
                            <div class="announcement-category"><?php echo getAnnouncementCategory($announcement['target_audience']); ?></div>
                        </div>
                    </div>
                    <div class="announcement-content"><?php echo htmlspecialchars($announcement['content']); ?></div>
                    <div class="announcement-meta">
                        <span class="announcement-time"><?php echo $announcement['time_ago']; ?></span>
                        <span class="announcement-priority priority-<?php echo $announcement['priority']; ?>">
                            <?php echo strtoupper($announcement['priority']); ?>
                        </span>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <div class="content-wrapper" id="contentWrapper">
            <?php 
            $ongoing_classes = count(array_filter($today_schedule, function($schedule) {
                return $schedule['status'] === 'ongoing';
            }));
            $completed_classes = count(array_filter($today_schedule, function($schedule) {
                return $schedule['status'] === 'finished';
            }));
            $total_classes = count($today_schedule);
            $header_config = [
                'page_title' => 'FaculTrack - Faculty Portal',
                'page_subtitle' => 'Sultan Kudarat State University - Isulan Campus',
                'user_name' => $faculty_name,
                'user_role' => 'Faculty Member',
                'user_details' => $faculty_info['employee_id'] ? 'ID: ' . $faculty_info['employee_id'] : '',
                'announcements_count' => count($announcements),
                'announcements' => $announcements,
                'stats' => [
                    ['label' => 'Today', 'value' => $total_classes],
                    ['label' => 'Ongoing', 'value' => $ongoing_classes],
                    ['label' => 'Completed', 'value' => $completed_classes],
                    ['label' => 'Status', 'value' => 'Active']
                ]
            ];
            include 'assets/php/page_header.php';
            ?>
            <div class="dashboard-grid">
                <div class="schedule-section">
                    <div class="schedule-card">
                    <div class="schedule-header">
                        <h3>Schedule</h3>
                        <div class="schedule-date">
                            <?php 
                            $display_date = (isset($DEBUG_MODE) && $DEBUG_MODE && isset($DEBUG_DATE)) ? date('F j, Y - l', strtotime($DEBUG_DATE)) : date('F j, Y - l');
                            echo $display_date;
                            ?>
                        </div>
                    </div>
                        <div class="schedule-tabs">
                            <?php if (!empty($schedule_tabs)): ?>
                                <?php foreach ($schedule_tabs as $index => $tab): ?>
                                <button class="schedule-tab <?php echo $index === 0 ? 'active' : ''; ?>" 
                                        onclick="switchScheduleTab('<?php echo $tab; ?>', this)">
                                    <?php echo $tab; ?>
                                </button>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                        <div class="schedule-list" id="scheduleList">
                            <div class="loading-state">Loading schedule...</div>
                        </div>
                    </div>
                </div>
                <div class="location-section">
                    <div class="location-update-card">
                        <div class="location-current">
                            <div class="location-header">
                                <h3>Current Location</h3>
                                <div class="location-status">
                                    <span class="status-dot"></span>
                                    <span>Available</span>
                                </div>
                            </div>
                            <div class="location-display">
                                <div class="location-row">
                                    <div class="location-text" id="currentLocation">
                                        <?php echo htmlspecialchars($faculty_info['current_location'] ?: 'Location not set'); ?>
                                    </div>
                                    <div class="location-status location-status-mobile">
                                        <span class="status-dot"></span>
                                        <span>Available</span>
                                    </div>
                                </div>
                                <div class="location-updated">
                                    Last updated: <?php 
                                        if ($faculty_info['last_location_update']) {
                                            $time_diff = time() - strtotime($faculty_info['last_location_update']);
                                            if ($time_diff < 3600) {
                                                echo floor($time_diff / 60) . ' minutes ago';
                                            } elseif ($time_diff < 86400) {
                                                echo floor($time_diff / 3600) . ' hours ago';
                                            } else {
                                                echo floor($time_diff / 86400) . ' days ago';
                                            }
                                        } else {
                                            echo 'Never';
                                        }
                                    ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="actions-section">
                    <div class="quick-actions">
                        <h3>Quick Actions</h3>
                        <div class="actions-grid">
                            <button class="action-card btn-primary" onclick="openStatusModal()">
                                <div class="action-icon">
                                    <svg class="feather"><use href="#user-check"></use></svg>
                                </div>
                                <div class="action-title">Update Status</div>
                            </button>
                            <button class="action-card" onclick="openLocationModal()">
                                <div class="action-icon">
                                    <svg class="feather"><use href="#map-pin"></use></svg>
                                </div>
                                <div class="action-title">Update Location</div>
                            </button>
                            <button class="action-card" onclick="viewLocationHistory()">
                                <div class="action-icon">
                                    <svg class="feather"><use href="#clipboard"></use></svg>
                                </div>
                                <div class="action-title">Location History</div>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="modal-overlay" id="locationModal">
            <div class="modal">
                <div class="modal-header">
                    <h3 class="modal-title">Update Location</h3>
                    <button type="button" class="modal-close" onclick="closeLocationModal()">&times;</button>
                </div>
                <form id="locationForm" class="modal-form" onsubmit="event.preventDefault(); updateLocation();">
                    <div class="form-group">
                        <label class="form-label">Current Location *</label>
                        <select id="locationSelect" name="location" class="form-select" required>
                            <option value="">Select your location</option>
                            <option value="NR102">NR102</option>
                            <option value="NR103">NR103</option>
                            <option value="NR104">NR104</option>
                            <option value="NR105">NR105</option>
                            <option value="NR106">NR106</option>
                            <option value="NR107">NR107</option>
                            <option value="NR108">NR108</option>
                            <option value="NR109">NR109</option>
                            <option value="NR202">NR202</option>
                            <option value="NR203">NR203</option>
                            <option value="NR204">NR204</option>
                            <option value="NR205">NR205</option>
                            <option value="NR206">NR206</option>
                            <option value="NR207">NR207</option>
                            <option value="CL208">CL208</option>
                            <option value="CL209">CL209</option>
                            <option value="NR302">NR302</option>
                            <option value="NR303">NR303</option>
                            <option value="NR304">NR304</option>
                            <option value="NR305">NR305</option>
                            <option value="NR306">NR306</option>
                            <option value="NR307">NR307</option>
                            <option value="CL308">CL308</option>
                            <option value="CL309">CL309</option>
                            <option value="Gymnasium">Gymnasium</option>
                            <option value="Cafeteria">Cafeteria</option>
                            <option value="Library">Library</option>
                            <option value="Registrar">Registrar</option>
                            <option value="Outside Campus">Outside Campus</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Custom Location</label>
                        <input type="text" id="customLocation" name="custom_location" class="form-input" 
                            placeholder="Type custom location if not in list above">
                        <small class="form-help">Leave empty to use selected location above</small>
                    </div>
                    <div class="modal-actions">
                        <button type="button" class="btn-secondary" onclick="closeLocationModal()">Cancel</button>
                        <button type="submit" class="btn-primary">Update Location</button>
                    </div>
                </form>
            </div>
        </div>
        <div class="modal-overlay" id="locationHistoryModal">
            <div class="modal">
                <div class="modal-header">
                    <h3 class="modal-title">Location History</h3>
                    <button type="button" class="modal-close" onclick="closeLocationHistoryModal()">&times;</button>
                </div>
                <div class="location-history-list">
                </div>
                <div class="modal-actions">
                    <button type="button" class="btn-secondary" onclick="closeLocationHistoryModal()">Close</button>
                </div>
            </div>
        </div>
        <div class="modal-overlay" id="statusModal">
            <div class="modal">
                <div class="modal-header">
                    <h3 class="modal-title">Update Status</h3>
                    <button type="button" class="modal-close" onclick="closeStatusModal()">&times;</button>
                </div>
                <form id="statusForm" class="modal-form" onsubmit="event.preventDefault(); updateStatus();">
                    <div class="form-group">
                        <label class="form-label">Current Status *</label>
                        <select id="statusSelect" name="status" class="form-select" required>
                            <option value="">Select your status</option>
                            <option value="Available">Available</option>
                            <option value="In Meeting">In Meeting</option>
                            <option value="On Leave">On Leave</option>
                        </select>
                    </div>
                    <div class="modal-actions">
                        <button type="button" class="btn-secondary" onclick="closeStatusModal()">Cancel</button>
                        <button type="submit" class="btn-primary">Update Status</button>
                    </div>
                </form>
            </div>
        </div>
    <script>
        window.userRole = 'faculty';
        
        function openStatusModal() {
            document.getElementById('statusModal').classList.add('show');
            document.body.style.overflow = 'hidden';
        }

        function closeStatusModal() {
            document.getElementById('statusModal').classList.remove('show');
            document.body.style.overflow = 'auto';
            document.getElementById('statusForm').reset();
        }

        async function updateStatus() {
            const statusSelect = document.getElementById('statusSelect');
            const selectedStatus = statusSelect.value;

            if (!selectedStatus) {
                showNotification('Please select a status', 'error');
                return;
            }

            try {
                const response = await fetch('assets/php/polling_api.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `action=update_status&status=${encodeURIComponent(selectedStatus)}`
                });

                const result = await response.json();
                
                if (result.success) {
                    showNotification(`Status updated to: ${selectedStatus}`, 'success');
                    closeStatusModal();
                } else {
                    throw new Error(result.message || 'Failed to update status');
                }
            } catch (error) {
                console.error('Status update error:', error);
                showNotification('Failed to update status. Please try again.', 'error');
            }
        }
    </script>
    <script src="assets/js/polling_config.js"></script>
    <script src="assets/js/toast_manager.js"></script>
    <script src="assets/js/shared_functions.js"></script>
    <script src="assets/js/live_polling.js"></script>
    <script src="assets/js/faculty.js"></script>
</body>
</html>