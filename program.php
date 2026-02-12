<?php
require_once 'assets/php/common_utilities.php';
initializeSession();
$pdo = get_db_connection();
$pdo->exec("SET time_zone = '+08:00'");
validateUserSession('program_chair');
$user_id = $_SESSION['user_id'];
$program_chair_name = $_SESSION['full_name'];
$stmt = $pdo->prepare("SELECT full_name FROM users WHERE role = 'campus_director' AND is_active = TRUE ORDER BY user_id LIMIT 1");
$stmt->execute();
$campus_director_name = $stmt->fetchColumn() ?: 'Campus Director';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    switch ($_POST['action']) {
        case 'assign_course_load':
            try {
                $faculty_id = $_POST['faculty_id'];
                $course_code = $_POST['course_code'];
                $class_id = $_POST['class_id'];
                $days = $_POST['days'];
                $time_start = $_POST['time_start'];
                $time_end = $_POST['time_end'];
                $room = $_POST['room'] ?? null;
                $is_edit_mode = $_POST['is_edit_mode'] === 'true';
                if ($is_edit_mode) {
                    $original_course = $_POST['original_course_code'];
                    $original_time = $_POST['original_time_start'];
                    $original_days = $_POST['original_days'];
                    $delete_stmt = $pdo->prepare("DELETE FROM schedules WHERE faculty_id = ? AND course_code = ? AND time_start = ? AND days = ? AND is_active = TRUE");
                    $delete_stmt->execute([$faculty_id, $original_course, $original_time, $original_days]);
                }
                $insert_stmt = $pdo->prepare("INSERT INTO schedules (faculty_id, course_code, class_id, days, time_start, time_end, room, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, TRUE)");
                $insert_stmt->execute([$faculty_id, $course_code, $class_id, $days, $time_start, $time_end, $room]);
                echo json_encode(['success' => true, 'message' => $is_edit_mode ? 'Course updated successfully' : 'Course assigned successfully']);
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
            }
            exit;
        case 'delete_schedule':
            try {
                $faculty_id = $_POST['faculty_id'];
                $course_code = $_POST['course_code'];
                $time_start = $_POST['time_start'];
                $days = $_POST['days'];
                $stmt = $pdo->prepare("DELETE FROM schedules WHERE faculty_id = ? AND course_code = ? AND time_start = ? AND days = ? AND is_active = TRUE");
                $stmt->execute([$faculty_id, $course_code, $time_start, $days]);
                echo json_encode(['success' => true, 'message' => 'Schedule deleted successfully']);
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
            }
            exit;
        case 'get_courses_and_classes':
            try {
                $stmt = $pdo->prepare("SELECT program FROM faculty WHERE user_id = ? AND is_active = TRUE");
                $stmt->execute([$user_id]);
                $program = $stmt->fetchColumn();
                $courses_query = "SELECT c.course_code, c.course_description FROM courses c LEFT JOIN programs p ON c.program_id = p.program_id WHERE c.is_active = TRUE AND p.program_name = ? ORDER BY c.course_code";
                $stmt = $pdo->prepare($courses_query);
                $stmt->execute([$program]);
                $courses = $stmt->fetchAll(PDO::FETCH_ASSOC);
                echo json_encode(['success' => true, 'courses' => $courses]);
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
            }
            exit;
        case 'get_classes_for_course':
            try {
                $course_code = $_POST['course_code'];
                $stmt = $pdo->prepare("SELECT DISTINCT c.class_id, c.class_code, c.class_name, c.year_level FROM classes c JOIN curriculum curr ON c.year_level = curr.year_level AND c.semester = curr.semester WHERE curr.course_code = ? AND c.is_active = TRUE AND curr.is_active = TRUE ORDER BY c.class_code");
                $stmt->execute([$course_code]);
                $classes = $stmt->fetchAll(PDO::FETCH_ASSOC);
                echo json_encode(['success' => true, 'classes' => $classes]);
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
            }
            exit;
        case 'get_room_options':
            $rooms = ['Room 101', 'Room 102', 'Room 103', 'Room 201', 'Room 202', 'Room 203', 'Computer Lab 1', 'Computer Lab 2', 'TBA'];
            echo json_encode(['success' => true, 'rooms' => $rooms]);
            exit;
    }
}
$chair_info = getProgramChairInfo($pdo, $user_id);
$program = $chair_info ? $chair_info['program'] : 'Unknown Program';
$classes_data = getProgramClasses($pdo, $user_id);
$class_ids = array_column($classes_data, 'class_id');
$faculty_data = [];
$class_schedules = [];
$courses_data = [];
$faculty_schedules = [];
if (!empty($class_ids)) {
    $faculty_data = getAllFacultyProgram($pdo);
    foreach ($faculty_data as $faculty) {
        $faculty_schedules[$faculty['faculty_id']] = getFacultySchedulesProgram($pdo, $faculty['faculty_id']);
    }
    $stmt = $pdo->prepare("SELECT program FROM faculty WHERE user_id = ? AND is_active = TRUE");
    $stmt->execute([$user_id]);
    $program = $stmt->fetchColumn();
    $courses_query = "
        SELECT DISTINCT c.course_id, c.course_code, c.course_description, c.units,
               COUNT(s.schedule_id) as times_scheduled
        FROM courses c
        LEFT JOIN programs p ON c.program_id = p.program_id
        LEFT JOIN schedules s ON c.course_code = s.course_code AND s.is_active = TRUE
        WHERE c.is_active = TRUE AND p.program_name = ?
        GROUP BY c.course_id
        ORDER BY c.course_code";
    $stmt = $pdo->prepare($courses_query);
    $stmt->execute([$program]);
    $courses_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($classes_data as $class) {
        $class_schedules[$class['class_id']] = getClassSchedules($pdo, $class['class_id']);
    }
}
require_once 'assets/php/announcement_functions.php';
$announcements = fetchAnnouncements($pdo, $_SESSION['role'], 10);
function checkDayOverlap($days1, $days2) {
    $days1 = strtoupper(trim($days1));
    $days2 = strtoupper(trim($days2));
    $parseDays = function($dayString) {
        $dayString = str_replace(' ', '', $dayString);
        $days = [];
        $i = 0;
        while ($i < strlen($dayString)) {
            if ($i < strlen($dayString) - 1 && substr($dayString, $i, 2) === 'TH') {
                $days[] = 'TH';
                $i += 2;
            } else {
                $days[] = $dayString[$i];
                $i++;
            }
        }
        return $days;
    };
    $arr1 = $parseDays($days1);
    $arr2 = $parseDays($days2);
    return count(array_intersect($arr1, $arr2)) > 0;
}
function checkTimeOverlap($start1, $end1, $start2, $end2) {
    $start1_time = strtotime($start1);
    $end1_time = strtotime($end1);
    $start2_time = strtotime($start2);
    $end2_time = strtotime($end2);
    return !($end1_time <= $start2_time || $start1_time >= $end2_time);
}
function getProgramChairInfo($pdo, $user_id) {
    $chair_program_query = "SELECT program FROM faculty WHERE user_id = ? AND is_active = TRUE";
    $stmt = $pdo->prepare($chair_program_query);
    $stmt->execute([$user_id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}
function getProgramClasses($pdo, $user_id) {
    $classes_query = "
        SELECT c.class_id, c.class_code, c.class_name, c.year_level, c.semester, c.academic_year, c.total_students,
               u.full_name as class_account_name,
               COUNT(DISTINCT curr.course_code) as total_subjects,
               COUNT(DISTINCT s.faculty_id) as assigned_faculty
        FROM classes c
        JOIN users u ON c.user_id = u.user_id
        LEFT JOIN curriculum curr ON c.year_level = curr.year_level
                                  AND c.semester = curr.semester
                                  AND curr.is_active = TRUE
        LEFT JOIN schedules s ON c.class_id = s.class_id AND s.is_active = TRUE
        WHERE c.program_chair_id = ? AND c.is_active = TRUE
        GROUP BY c.class_id
        ORDER BY c.year_level, c.class_name";
    $stmt = $pdo->prepare($classes_query);
    $stmt->execute([$user_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
function getAllFacultyProgram($pdo) {
    $faculty_query = "
        SELECT
            f.faculty_id,
            u.full_name,
            f.employee_id,
            f.program,
            u2.full_name as dean_name,
            f.office_hours,
            f.contact_email,
            f.contact_phone,
            f.current_location,
            f.last_location_update,
            CASE
                WHEN f.is_active = 1 THEN 'Available'
                ELSE 'Offline'
            END as status,
            COALESCE(
                (SELECT CASE
                    WHEN lh.time_set > DATE_SUB(NOW(), INTERVAL 30 MINUTE) THEN CONCAT(TIMESTAMPDIFF(MINUTE, lh.time_set, NOW()), ' minutes ago')
                    WHEN lh.time_set > DATE_SUB(NOW(), INTERVAL 24 HOUR) THEN CONCAT(TIMESTAMPDIFF(HOUR, lh.time_set, NOW()), ' hours ago')
                    WHEN lh.time_set > DATE_SUB(NOW(), INTERVAL 7 DAY) THEN CONCAT(TIMESTAMPDIFF(DAY, lh.time_set, NOW()), ' days ago')
                    ELSE 'Over a week ago'
                END
                FROM location_history lh
                WHERE lh.faculty_id = f.faculty_id
                ORDER BY lh.time_set DESC
                LIMIT 1),
                'No location history'
            ) as last_updated
        FROM faculty f
        JOIN users u ON f.user_id = u.user_id
        LEFT JOIN programs p ON f.program = p.program_name
        LEFT JOIN users u2 ON p.dean_id = u2.user_id
        ORDER BY u.full_name";
    $stmt = $pdo->prepare($faculty_query);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
function getFacultySchedulesProgram($pdo, $faculty_id) {
    $schedule_query = "
        SELECT s.course_code, c.course_description, c.units, s.days, s.time_start, s.time_end, s.room,
               cl.class_name, cl.class_code, cl.total_students
        FROM schedules s
        JOIN courses c ON s.course_code = c.course_code
        JOIN classes cl ON s.class_id = cl.class_id
        WHERE s.faculty_id = ? AND s.is_active = TRUE
        ORDER BY s.time_start";
    $stmt = $pdo->prepare($schedule_query);
    $stmt->execute([$faculty_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
function getProgramCourses($pdo, $class_ids) {
    $in_clause = buildInClause($class_ids);
    $course_query = "
        SELECT DISTINCT c.course_id, c.course_code, c.course_description, c.units
        FROM courses c
        LEFT JOIN schedules s ON c.course_code = s.course_code AND s.class_id IN ({$in_clause['placeholders']})
        WHERE c.is_active = TRUE
        ORDER BY c.course_code";
    $stmt = $pdo->prepare($course_query);
    $stmt->execute($in_clause['values']);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
function getClassSchedules($pdo, $class_id) {
    $schedule_query = "
        SELECT s.course_code, c.course_description, s.days, s.time_start, s.time_end, s.room,
               u.full_name as faculty_name
        FROM schedules s
        JOIN courses c ON s.course_code = c.course_code
        JOIN faculty f ON s.faculty_id = f.faculty_id
        JOIN users u ON f.user_id = u.user_id
        WHERE s.class_id = ? AND s.is_active = TRUE
        ORDER BY s.time_start";
    $stmt = $pdo->prepare($schedule_query);
    $stmt->execute([$class_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
if (isset($_POST['action']) && $_POST['action'] === 'get_courses_and_classes') {
    try {
        $courses_query = "SELECT course_code, course_description, units FROM courses WHERE is_active = TRUE ORDER BY course_code";
        $courses_stmt = $pdo->prepare($courses_query);
        $courses_stmt->execute();
        $courses = $courses_stmt->fetchAll(PDO::FETCH_ASSOC);
        $classes_query = "SELECT class_id, class_code, class_name, year_level FROM classes WHERE program_chair_id = ? AND is_active = TRUE ORDER BY year_level, class_name";
        $classes_stmt = $pdo->prepare($classes_query);
        $classes_stmt->execute([$user_id]);
        $classes = $classes_stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode([
            'success' => true,
            'courses' => $courses,
            'classes' => $classes
        ]);
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Failed to fetch data: ' . $e->getMessage()
        ]);
    }
    exit;
}
if (isset($_POST['action']) && $_POST['action'] === 'assign_course_load') {
    try {
        $faculty_id = $_POST['faculty_id'];
        $course_code = $_POST['course_code'];
        $class_id = $_POST['class_id'];
        $days = $_POST['days'];
        $time_start = $_POST['time_start'];
        $time_end = $_POST['time_end'];
        $room = $_POST['room'] ?? null;
        $is_edit = isset($_POST['is_edit_mode']) && $_POST['is_edit_mode'] === 'true';
        $original_course = $is_edit ? ($_POST['original_course_code'] ?? '') : '';
        $original_time_start = $is_edit ? ($_POST['original_time_start'] ?? '') : '';
        $original_days = $is_edit ? ($_POST['original_days'] ?? '') : '';
        if (strtotime($time_end) <= strtotime($time_start)) {
            echo json_encode(['success' => false, 'message' => 'End time must be after start time']);
            exit;
        }
        $curriculum_check = "SELECT COUNT(*) as valid FROM curriculum cur
                           JOIN classes cl ON cur.year_level = cl.year_level AND cur.semester = cl.semester
                           WHERE cur.course_code = ? AND cl.class_id = ? AND cur.is_active = TRUE";
        $curriculum_stmt = $pdo->prepare($curriculum_check);
        $curriculum_stmt->execute([$course_code, $class_id]);
        $is_course_valid = $curriculum_stmt->fetch(PDO::FETCH_ASSOC)['valid'] > 0;
        if (!$is_course_valid) {
            echo json_encode(['success' => false, 'message' => 'Course not assigned to this class curriculum']);
            exit;
        }
        $faculty_conflict_query = "SELECT course_code, time_start, time_end, days FROM schedules
                                 WHERE faculty_id = ? AND is_active = TRUE";
        if ($is_edit) {
            $faculty_conflict_query .= " AND NOT (course_code = ? AND time_start = ? AND days = ?)";
            $faculty_stmt = $pdo->prepare($faculty_conflict_query);
            $faculty_stmt->execute([$faculty_id, $original_course, $original_time_start, $original_days]);
        } else {
            $faculty_stmt = $pdo->prepare($faculty_conflict_query);
            $faculty_stmt->execute([$faculty_id]);
        }
        $faculty_schedules = $faculty_stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($faculty_schedules as $schedule) {
            if (checkDayOverlap($days, $schedule['days'])) {
                if (checkTimeOverlap($time_start, $time_end, $schedule['time_start'], $schedule['time_end'])) {
                    echo json_encode(['success' => false, 'message' => "Faculty has conflict with {$schedule['course_code']} at {$schedule['time_start']}-{$schedule['time_end']} on {$schedule['days']}"]);
                    exit;
                }
            }
        }
        $room_conflict_query = "SELECT course_code, time_start, time_end, days FROM schedules
                              WHERE room = ? AND is_active = TRUE";
        if ($is_edit) {
            $room_conflict_query .= " AND NOT (course_code = ? AND time_start = ? AND days = ?)";
            $room_stmt = $pdo->prepare($room_conflict_query);
            $room_stmt->execute([$room, $original_course, $original_time_start, $original_days]);
        } else {
            $room_stmt = $pdo->prepare($room_conflict_query);
            $room_stmt->execute([$room]);
        }
        $room_schedules = $room_stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($room_schedules as $schedule) {
            if (checkDayOverlap($days, $schedule['days'])) {
                if (checkTimeOverlap($time_start, $time_end, $schedule['time_start'], $schedule['time_end'])) {
                    echo json_encode(['success' => false, 'message' => "Room conflict with {$schedule['course_code']} at {$schedule['time_start']}-{$schedule['time_end']} on {$schedule['days']}"]);
                    exit;
                }
            }
        }
        if ($is_edit) {
            $update_query = "UPDATE schedules
                            SET faculty_id = ?, course_code = ?, class_id = ?, days = ?,
                                time_start = ?, time_end = ?, room = ?
                            WHERE course_code = ? AND time_start = ? AND days = ? AND is_active = TRUE";
            $stmt = $pdo->prepare($update_query);
            if ($stmt->execute([$faculty_id, $course_code, $class_id, $days, $time_start, $time_end, $room,
                               $original_course, $original_time_start, $original_days])) {
                echo json_encode(['success' => true, 'message' => 'Course assignment updated successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to update course assignment']);
            }
        } else {
            $current_year = date('Y');
            $academic_year = $current_year . '-' . substr($current_year + 1, -2);
            $insert_query = "INSERT INTO schedules (faculty_id, course_code, class_id, days, time_start, time_end, room, semester, academic_year, is_active)
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, TRUE)";
            $stmt = $pdo->prepare($insert_query);
            if ($stmt->execute([$faculty_id, $course_code, $class_id, $days, $time_start, $time_end, $room, '1st', $academic_year])) {
                echo json_encode(['success' => true, 'message' => 'Course assigned successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to assign course']);
            }
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
    exit;
}
if (isset($_POST['action']) && $_POST['action'] === 'get_curriculum_assignment_data') {
    try {
        $course_code = $_POST['course_code'];
        $curriculum_query = "
            SELECT curriculum_id, year_level, semester
            FROM curriculum
            WHERE course_code = ? AND is_active = TRUE
            ORDER BY year_level, semester";
        $stmt = $pdo->prepare($curriculum_query);
        $stmt->execute([$course_code]);
        $existingAssignments = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode([
            'success' => true,
            'existingAssignments' => $existingAssignments
        ]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
    exit;
}
if (isset($_POST['action']) && $_POST['action'] === 'get_curriculum_assignment_data_with_classes') {
    try {
        $course_code = $_POST['course_code'];
        $curriculum_query = "
            SELECT cur.curriculum_id, cur.year_level, cur.semester,
                   GROUP_CONCAT(DISTINCT c.class_code SEPARATOR ', ') as class_names
            FROM curriculum cur
            LEFT JOIN classes c ON c.year_level = cur.year_level
                                AND c.semester = cur.semester
                                AND c.is_active = TRUE
            WHERE cur.course_code = ? AND cur.is_active = TRUE
            GROUP BY cur.curriculum_id, cur.year_level, cur.semester
            ORDER BY cur.year_level, cur.semester";
        $stmt = $pdo->prepare($curriculum_query);
        $stmt->execute([$course_code]);
        $existingAssignments = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode([
            'success' => true,
            'existingAssignments' => $existingAssignments
        ]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
    exit;
}
if (isset($_POST['action']) && $_POST['action'] === 'assign_course_to_curriculum') {
    try {
        $course_code = $_POST['course_code'];
        $year_level = $_POST['year_level'];
        $semester = $_POST['semester'];
        $check_query = "SELECT COUNT(*) as count FROM curriculum
                       WHERE course_code = ? AND year_level = ? AND semester = ? AND is_active = TRUE";
        $check_stmt = $pdo->prepare($check_query);
        $check_stmt->execute([$course_code, $year_level, $semester]);
        $exists = $check_stmt->fetch(PDO::FETCH_ASSOC)['count'] > 0;
        if ($exists) {
            echo json_encode(['success' => false, 'message' => 'This course is already assigned to that year level and semester']);
            exit;
        }
        $insert_query = "INSERT INTO curriculum (course_code, year_level, semester, program_chair_id, is_active)
                        VALUES (?, ?, ?, ?, TRUE)";
        $stmt = $pdo->prepare($insert_query);
        if ($stmt->execute([$course_code, $year_level, $semester, $user_id])) {
            echo json_encode(['success' => true, 'message' => 'Course assigned to curriculum successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to assign course to curriculum']);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
    exit;
}
if (isset($_POST['action']) && $_POST['action'] === 'remove_curriculum_assignment') {
    try {
        $curriculum_id = $_POST['curriculum_id'];
        $delete_query = "DELETE FROM curriculum WHERE curriculum_id = ?";
        $stmt = $pdo->prepare($delete_query);
        if ($stmt->execute([$curriculum_id])) {
            echo json_encode(['success' => true, 'message' => 'Course removed from curriculum successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to remove course from curriculum']);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
    exit;
}
if (isset($_POST['action']) && $_POST['action'] === 'get_courses_and_classes') {
    try {
        $courses = getProgramCourses($pdo, $class_ids);
        $classes = getProgramClasses($pdo, $user_id);
        echo json_encode([
            'success' => true,
            'courses' => $courses,
            'classes' => $classes
        ]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
    exit;
}
if (isset($_POST['action']) && $_POST['action'] === 'get_classes_for_course') {
    try {
        $course_code = $_POST['course_code'];
        $classes_query = "
            SELECT DISTINCT c.class_id, c.class_code, c.class_name, c.year_level, c.semester
            FROM classes c
            JOIN curriculum cur ON c.year_level = cur.year_level
                                AND c.semester = cur.semester
            WHERE cur.course_code = ? AND cur.is_active = TRUE AND c.is_active = TRUE
            AND c.program_chair_id = ?
            ORDER BY c.year_level, c.class_name";
        $stmt = $pdo->prepare($classes_query);
        $stmt->execute([$course_code, $user_id]);
        $classes = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode([
            'success' => true,
            'classes' => $classes
        ]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
    exit;
}
if (isset($_POST['action']) && $_POST['action'] === 'get_faculty_schedules') {
    try {
        $faculty_data = getAllFacultyProgram($pdo);
        $faculty_schedules = [];
        foreach ($faculty_data as $faculty) {
            $faculty_schedules[$faculty['faculty_id']] = getFacultySchedulesProgram($pdo, $faculty['faculty_id']);
        }
        echo json_encode([
            'success' => true,
            'faculty_schedules' => $faculty_schedules
        ]);
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Error fetching faculty schedules: ' . $e->getMessage()
        ]);
    }
    exit;
}
if (isset($_POST['action']) && $_POST['action'] === 'validate_schedule') {
    try {
        $faculty_id = $_POST['faculty_id'];
        $course_code = $_POST['course_code'];
        $class_id = $_POST['class_id'];
        $time_start = $_POST['time_start'];
        $time_end = $_POST['time_end'];
        $days = $_POST['days'];
        $is_edit = isset($_POST['is_edit']) && $_POST['is_edit'] === 'true';
        $original_course = $is_edit ? $_POST['original_course'] : '';
        $original_time_start = $is_edit ? $_POST['original_time_start'] : '';
        $original_days = $is_edit ? $_POST['original_days'] : '';
        $conflicts = [];
        $faculty_check = "SELECT s.course_code, s.time_start, s.time_end, s.days, s.room
                         FROM schedules s
                         JOIN courses c ON s.course_code = c.course_code
                         WHERE s.faculty_id = ? AND s.is_active = TRUE";
        if ($is_edit) {
            $faculty_check .= " AND NOT (s.course_code = ? AND s.time_start = ? AND s.days = ?)";
        }
        $stmt = $pdo->prepare($faculty_check);
        if ($is_edit) {
            $stmt->execute([$faculty_id, $original_course, $original_time_start, $original_days]);
        } else {
            $stmt->execute([$faculty_id]);
        }
        $faculty_schedules = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($faculty_schedules as $schedule) {
            $schedule_days = strtoupper($schedule['days']);
            $new_days = strtoupper($days);
            $has_day_overlap = false;
            for ($i = 0; $i < strlen($new_days); $i++) {
                if (strpos($schedule_days, $new_days[$i]) !== false) {
                    $has_day_overlap = true;
                    break;
                }
            }
            if ($has_day_overlap) {
                $schedule_start = strtotime($schedule['time_start']);
                $schedule_end = strtotime($schedule['time_end']);
                $new_start = strtotime($time_start);
                $new_end = strtotime($time_end);
                if (($new_start < $schedule_end) && ($new_end > $schedule_start)) {
                    $conflicts[] = "Faculty conflict: Already teaching {$schedule['course_code']} at " .
                                 date('g:i A', $schedule_start) . "-" . date('g:i A', $schedule_end) .
                                 " on " . $schedule['days'];
                }
            }
        }
        $class_check = "SELECT s.course_code, s.time_start, s.time_end, s.days, u.full_name as faculty_name
                       FROM schedules s
                       JOIN faculty f ON s.faculty_id = f.faculty_id
                       JOIN users u ON f.user_id = u.user_id
                       WHERE s.class_id = ? AND s.is_active = TRUE";
        if ($is_edit) {
            $class_check .= " AND NOT (s.course_code = ? AND s.time_start = ? AND s.days = ?)";
        }
        $stmt = $pdo->prepare($class_check);
        if ($is_edit) {
            $stmt->execute([$class_id, $original_course, $original_time_start, $original_days]);
        } else {
            $stmt->execute([$class_id]);
        }
        $class_schedules = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($class_schedules as $schedule) {
            $schedule_days = strtoupper($schedule['days']);
            $new_days = strtoupper($days);
            $has_day_overlap = false;
            for ($i = 0; $i < strlen($new_days); $i++) {
                if (strpos($schedule_days, $new_days[$i]) !== false) {
                    $has_day_overlap = true;
                    break;
                }
            }
            if ($has_day_overlap) {
                $schedule_start = strtotime($schedule['time_start']);
                $schedule_end = strtotime($schedule['time_end']);
                $new_start = strtotime($time_start);
                $new_end = strtotime($time_end);
                if (($new_start < $schedule_end) && ($new_end > $schedule_start)) {
                    $conflicts[] = "Class conflict: {$schedule['course_code']} already scheduled with {$schedule['faculty_name']} at " .
                                 date('g:i A', $schedule_start) . "-" . date('g:i A', $schedule_end) .
                                 " on " . $schedule['days'];
                }
            }
        }
        if (isset($_POST['room']) && !empty($_POST['room'])) {
            $room = $_POST['room'];
            $room_check = "SELECT s.course_code, s.time_start, s.time_end, s.days, u.full_name as faculty_name
                          FROM schedules s
                          JOIN faculty f ON s.faculty_id = f.faculty_id
                          JOIN users u ON f.user_id = u.user_id
                          WHERE s.room = ? AND s.is_active = TRUE";
            if ($is_edit) {
                $room_check .= " AND NOT (s.course_code = ? AND s.time_start = ? AND s.days = ?)";
            }
            $stmt = $pdo->prepare($room_check);
            if ($is_edit) {
                $stmt->execute([$room, $original_course, $original_time_start, $original_days]);
            } else {
                $stmt->execute([$room]);
            }
            $room_schedules = $stmt->fetchAll(PDO::FETCH_ASSOC);
            foreach ($room_schedules as $schedule) {
                $schedule_days = strtoupper($schedule['days']);
                $new_days = strtoupper($days);
                $has_day_overlap = false;
                for ($i = 0; $i < strlen($new_days); $i++) {
                    if (strpos($schedule_days, $new_days[$i]) !== false) {
                        $has_day_overlap = true;
                        break;
                    }
                }
                if ($has_day_overlap) {
                    $schedule_start = strtotime($schedule['time_start']);
                    $schedule_end = strtotime($schedule['time_end']);
                    $new_start = strtotime($time_start);
                    $new_end = strtotime($time_end);
                    if (($new_start < $schedule_end) && ($new_end > $schedule_start)) {
                        $conflicts[] = "Room conflict: {$room} already booked for {$schedule['course_code']} with {$schedule['faculty_name']} at " .
                                     date('g:i A', $schedule_start) . "-" . date('g:i A', $schedule_end) .
                                     " on " . $schedule['days'];
                    }
                }
            }
        }
        echo json_encode([
            'success' => count($conflicts) === 0,
            'conflicts' => $conflicts,
            'has_conflicts' => count($conflicts) > 0,
            'message' => count($conflicts) > 0 ? implode('; ', $conflicts) : 'No conflicts detected'
        ]);
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Validation error: ' . $e->getMessage()
        ]);
    }
    exit;
}
if (isset($_POST['action']) && $_POST['action'] === 'get_course_curriculum') {
    try {
        $course_code = $_POST['course_code'];
        $curriculum_query = "SELECT DISTINCT year_level, semester
                           FROM curriculum
                           WHERE course_code = ? AND is_active = TRUE
                           ORDER BY year_level, semester";
        $stmt = $pdo->prepare($curriculum_query);
        $stmt->execute([$course_code]);
        $curriculum = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode([
            'success' => true,
            'curriculum' => $curriculum
        ]);
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Error fetching course curriculum: ' . $e->getMessage()
        ]);
    }
    exit;
}
if (isset($_POST['action']) && $_POST['action'] === 'get_room_options') {
    try {
        $room_query = "SELECT DISTINCT room
                      FROM schedules
                      WHERE room IS NOT NULL AND room != '' AND is_active = TRUE
                      ORDER BY room";
        $stmt = $pdo->prepare($room_query);
        $stmt->execute();
        $existing_rooms = $stmt->fetchAll(PDO::FETCH_COLUMN);
        $predefined_rooms = [
            'Room 101', 'Room 102', 'Room 103', 'Room 104', 'Room 105',
            'Room 201', 'Room 202', 'Room 203', 'Room 204', 'Room 205',
            'Room 301', 'Room 302', 'Room 303', 'Room 304', 'Room 305',
            'Computer Lab 1', 'Computer Lab 2', 'Physics Lab', 'Chemistry Lab',
            'Library', 'Auditorium', 'Conference Room', 'TBA'
        ];
        $all_rooms = array_unique(array_merge($existing_rooms, $predefined_rooms));
        sort($all_rooms);
        echo json_encode([
            'success' => true,
            'rooms' => $all_rooms
        ]);
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Error fetching room options: ' . $e->getMessage()
        ]);
    }
    exit;
}
if (isset($_POST['action']) && $_POST['action'] === 'get_validated_options') {
    try {
        $selected_course = $_POST['selected_course'] ?? null;
        $response = [
            'success' => true,
            'courses' => [],
            'classes' => [],
            'rooms' => []
        ];
        $courses_query = "SELECT DISTINCT c.course_code, c.course_description, c.units
                         FROM courses c
                         WHERE c.is_active = TRUE
                         ORDER BY c.course_code";
        $courses_stmt = $pdo->prepare($courses_query);
        $courses_stmt->execute();
        $response['courses'] = $courses_stmt->fetchAll(PDO::FETCH_ASSOC);
        if ($selected_course) {
            $classes_query = "SELECT DISTINCT cl.class_id, cl.class_code, cl.class_name, cl.year_level
                             FROM classes cl
                             JOIN curriculum cur ON cl.year_level = cur.year_level
                                                AND cl.semester = cur.semester
                             WHERE cur.course_code = ? AND cur.is_active = TRUE
                             AND cl.is_active = TRUE AND cl.program_chair_id = ?
                             ORDER BY cl.year_level, cl.class_name";
            $classes_stmt = $pdo->prepare($classes_query);
            $classes_stmt->execute([$selected_course, $user_id]);
        } else {
            $classes_query = "SELECT class_id, class_code, class_name, year_level
                             FROM classes
                             WHERE program_chair_id = ? AND is_active = TRUE
                             ORDER BY year_level, class_name";
            $classes_stmt = $pdo->prepare($classes_query);
            $classes_stmt->execute([$user_id]);
        }
        $response['classes'] = $classes_stmt->fetchAll(PDO::FETCH_ASSOC);
        $room_query = "SELECT DISTINCT room
                      FROM schedules
                      WHERE room IS NOT NULL AND room != '' AND is_active = TRUE
                      ORDER BY room";
        $stmt = $pdo->prepare($room_query);
        $stmt->execute();
        $existing_rooms = $stmt->fetchAll(PDO::FETCH_COLUMN);
        $predefined_rooms = [
            'Room 101', 'Room 102', 'Room 103', 'Room 104', 'Room 105',
            'Room 201', 'Room 202', 'Room 203', 'Room 204', 'Room 205',
            'Room 301', 'Room 302', 'Room 303', 'Room 304', 'Room 305',
            'Computer Lab 1', 'Computer Lab 2', 'Physics Lab', 'Chemistry Lab',
            'Library', 'Auditorium', 'Conference Room', 'TBA'
        ];
        $all_rooms = array_unique(array_merge($existing_rooms, $predefined_rooms));
        sort($all_rooms);
        $response['rooms'] = $all_rooms;
        echo json_encode($response);
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Error fetching validated options: ' . $e->getMessage()
        ]);
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FaculTrack - Program Chair Dashboard</title>
    <link rel="stylesheet" href="assets/css/theme.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/scheduling.css">
    <style>
        .course-card,
        .class-card {
            position: relative;
            overflow: hidden;
        }
        .course-details-overlay,
        .class-details-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(248, 249, 250, 0.98);
            border-radius: 12px;
            z-index: 10;
            opacity: 0;
            transform: translateY(-100%);
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            overflow-y: auto;
        }
        .course-details-overlay.overlay-visible,
        .class-details-overlay.overlay-visible {
            opacity: 1;
            transform: translateY(0);
        }
        .overlay-header {
            padding: 12px 16px 8px 16px;
            border-bottom: 1px solid rgba(224, 224, 224, 0.3);
            background: rgba(46, 125, 50, 0.05);
        }
        .overlay-header h4 {
            margin: 0;
            color: var(--primary-green);
            font-size: 0.9rem;
            font-weight: 600;
        }
        .overlay-body {
            padding: 0;
        }
        .assignments-preview {
            padding: 12px;
        }
        .assignment-item {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            padding: 12px;
            margin-bottom: 8px;
            background: white;
            border: 1px solid rgba(224, 224, 224, 0.5);
            border-radius: 8px;
            transition: all 0.2s ease;
        }
        .assignment-item:hover {
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            border-color: var(--primary-green-light);
        }
        .assignment-item:last-child {
            margin-bottom: 0;
        }
        .assignment-info {
            flex: 1;
            font-size: 0.85rem;
            line-height: 1.4;
        }
        .remove-assignment-btn {
            background: rgba(220, 53, 69, 0.1);
            color: #dc3545;
            border: 1px solid rgba(220, 53, 69, 0.3);
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.75rem;
            cursor: pointer;
            transition: all 0.2s ease;
            margin-left: 12px;
        }
        .remove-assignment-btn:hover {
            background: #dc3545;
            color: white;
            border-color: #dc3545;
        }
        .schedule-preview {
            max-height: 250px;
            overflow-y: auto;
        }
        .schedule-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 12px;
            border-bottom: 1px solid rgba(224, 224, 224, 0.3);
            background: white;
            margin-bottom: 4px;
            border-radius: 6px;
            transition: background-color 0.2s ease;
        }
        .schedule-item:hover {
            background: rgba(46, 125, 50, 0.05);
        }
        .schedule-item:last-child {
            border-bottom: none;
            margin-bottom: 0;
        }
        .schedule-course-info {
            flex: 1;
        }
        .schedule-course {
            font-weight: 600;
            color: var(--primary-green);
            font-size: 0.85rem;
            margin-bottom: 2px;
        }
        .schedule-time {
            text-align: right;
            font-size: 0.8rem;
            color: #666;
            line-height: 1.3;
        }
        .course-details-toggle,
        .class-details-toggle {
            width: 100%;
            background: rgba(46, 125, 50, 0.1);
            color: var(--primary-green);
            border: 1px solid rgba(46, 125, 50, 0.2);
            padding: 10px 16px;
            border-radius: 0 0 12px 12px;
            cursor: pointer;
            font-size: 0.85rem;
            font-weight: 500;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            margin-top: auto;
        }
        .course-details-toggle:hover,
        .class-details-toggle:hover {
            background: rgba(46, 125, 50, 0.15);
            border-color: rgba(46, 125, 50, 0.3);
            transform: translateY(-1px);
        }
        .course-details-toggle .arrow,
        .class-details-toggle .arrow {
            font-size: 0.7rem;
            transition: transform 0.3s ease;
        }
        .loading-assignments {
            text-align: center;
            color: #666;
            font-style: italic;
            padding: 20px;
        }
        .no-data {
            text-align: center;
            color: #666;
            padding: 20px;
        }
        .no-data svg {
            color: #ccc;
        }
        .empty-state-container {
            grid-column: auto / -1;
            width: auto;
            text-align: center;
            padding: 20px;
            color: var(--text-secondary);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: 100%;
            min-height: 200px;
            background: rgba(255, 255, 255, 0.5);
            border-radius: 12px;
            border: 2px dashed rgba(0, 0, 0, 0.1);
        }
        .empty-state-icon {
            width: 48px;
            height: 48px;
            margin-bottom: 16px;
            opacity: 0.5;
            color: var(--primary-green);
        }
        .empty-state-title {
            font-size: 1.1rem;
            font-weight: 600;
            margin-bottom: 8px;
            color: var(--text-primary);
        }
        .empty-state-description {
            font-size: 0.9rem;
            color: var(--text-secondary);
            max-width: 400px;
            line-height: 1.5;
        }
    </style>
</head>
<body>
    <?php include 'assets/php/feather_icons.php'; ?>
    <div class="main-container">
        <div class="content-wrapper" id="contentWrapper">
            <?php
            $online_count = array_reduce($faculty_data, function($count, $faculty) {
                return $count + ($faculty['status'] === 'available' ? 1 : 0);
            }, 0);
            $total_schedules = array_reduce($classes_data, function($count, $class) {
                return $count + $class['total_subjects'];
            }, 0);
            $header_config = [
                'page_title' => 'FaculTrack - Program Chair',
                'page_subtitle' => 'Sultan Kudarat State University - Isulan Campus',
                'user_name' => $program_chair_name,
                'user_role' => 'Program Chair',
                'user_details' => $program,
                'announcements_count' => count($announcements),
                'announcements' => $announcements,
                'stats' => [
                    ['label' => 'Faculty', 'value' => count($faculty_data)],
                    ['label' => 'Classes', 'value' => count($classes_data)],
                    ['label' => 'Online', 'value' => $online_count],
                    ['label' => 'Subjects', 'value' => $total_schedules]
                ]
            ];
            include 'assets/php/page_header.php';
            ?>
            <div class="dashboard-tabs">
                <button class="tab-button active" onclick="switchTab('faculty')" data-tab="faculty">
                    <svg class="feather feather-sm"><use href="#users"></use></svg> Faculty Members
                </button>
                <button class="tab-button" onclick="switchTab('courses')" data-tab="courses">
                    <svg class="feather feather-sm"><use href="#book-open"></use></svg> Courses
                </button>
                <button class="tab-button" onclick="switchTab('classes')" data-tab="classes">
                    <svg class="feather feather-sm"><use href="#clipboard"></use></svg> Classes
                </button>
                <div class="search-bar">
                    <div class="search-container collapsed" id="searchContainer">
                        <input type="text" class="search-input" placeholder="Search..." id="searchInput">
                        <button class="search-toggle" onclick="toggleSearch()">
                            <svg class="feather"><use href="#search"></use></svg>
                        </button>
                    </div>
                </div>
            </div>
            <div class="tab-content active" id="faculty-content">
                <div class="faculty-grid" id="facultyGrid">
                    <div class="add-card add-card-first" data-modal="addFacultyModal">
                        <div class="add-card-icon">
                            <svg class="feather"><use href="#user-plus"></use></svg>
                        </div>
                        <div class="add-card-title">Add Faculty Member</div>
                        <div class="add-card-subtitle">Register a new faculty member</div>
                    </div>
                    <script>
                    window.facultyData = <?php echo json_encode($faculty_data); ?>;
                    window.campusDirectorName = <?php echo json_encode($campus_director_name); ?>;
                    const facultyNames = {};
                    const facultySchedules = {};
                    const facultyDeanNames = {};
                    const phpFacultyData = <?php echo json_encode($faculty_data); ?> || [];
                    const phpFacultySchedules = <?php echo json_encode($faculty_schedules); ?> || {};
                    phpFacultyData.forEach(faculty => {
                        facultyNames[faculty.faculty_id] = faculty.full_name;
                        if (faculty.dean_name) {
                            facultyDeanNames[faculty.faculty_id] = faculty.dean_name;
                        }
                    });
                    Object.keys(phpFacultySchedules).forEach(facultyId => {
                        facultySchedules[facultyId] = phpFacultySchedules[facultyId];
                    });
                    window.facultyDeanNames = facultyDeanNames;
                    function getEmptyStateHTML(title, description) {
                        return `
                            <div class="empty-state-container">
                                <svg class="feather empty-state-icon"><use href="#inbox"></use></svg>
                                <div class="empty-state-title">${title}</div>
                                <div class="empty-state-description">${description}</div>
                            </div>
                        `;
                    }
                    document.addEventListener('DOMContentLoaded', function() {
                        const facultyGrid = document.querySelector('.faculty-grid');
                        const facultyData = window.facultyData || [];
                        const dynamicCards = facultyGrid.querySelectorAll('.faculty-card');
                        dynamicCards.forEach(card => card.remove());
                        const existingEmpty = facultyGrid.querySelector('.empty-state-container');
                        if (existingEmpty) existingEmpty.remove();
                        if (facultyData.length === 0) {
                            facultyGrid.insertAdjacentHTML('beforeend', getEmptyStateHTML(
                                'No faculty members found',
                                'No faculty members are currently assigned to the <?php echo htmlspecialchars($program); ?> program'
                            ));
                        } else {
                            facultyData.forEach(faculty => {
                                const cardHTML = window.livePollingManager ?
                                    window.livePollingManager.createFacultyCard(faculty) :
                                    createFacultyCardFallback(faculty);
                                facultyGrid.insertAdjacentHTML('beforeend', cardHTML);
                            });
                        }
                    });
                    function createFacultyCardFallback(faculty) {
                        const status = faculty.status || 'Offline';
                        const statusClass = status.toLowerCase() === 'available' ? 'available' : 'offline';
                        const nameParts = (faculty.full_name || '').split(' ');
                        const initials = nameParts.map(part => part.charAt(0)).join('').substring(0, 2);
                        return `
                            <div class="faculty-card" data-faculty-id="${faculty.faculty_id}" data-name="${escapeHtml(faculty.full_name)}">
                                <div class="faculty-avatar">${initials}</div>
                                <div class="faculty-name">${escapeHtml(faculty.full_name)}</div>
                                <div class="location-info">
                                    <div class="location-status">
                                        <span class="status-dot status-${statusClass}"></span>
                                        <span class="location-text">${status}</span>
                                    </div>
                                    <div style="margin-left: 14px; color: #333; font-weight: 500; font-size: 0.85rem;">
                                        ${escapeHtml(faculty.current_location || 'No Location')}
                                    </div>
                                    <div class="time-info">Last updated: ${faculty.last_updated || '0 minutes ago'}</div>
                                </div>
                                <div class="contact-info">
                                    <div class="office-hours">Office Hours:<br>${escapeHtml(faculty.office_hours || 'Not specified')}</div>
                                    <div class="faculty-actions">
                                        ${faculty.contact_email ? `<button class="action-btn primary" onclick="contactFaculty('${faculty.contact_email}')">Email</button>` : ''}
                                        ${faculty.contact_phone ? `<button class="action-btn" onclick="callFaculty('${faculty.contact_phone}')">Call</button>` : ''}
                                        <button class="action-btn" onclick="viewSchedule(${faculty.faculty_id})">Schedule</button>
                                        <button class="action-btn" onclick="viewCourseLoad(${faculty.faculty_id})">Course Load</button>
                                    </div>
                                </div>
                            </div>
                        `;
                    }
                    function escapeHtml(text) {
                        if (!text) return '';
                        const div = document.createElement('div');
                        div.textContent = text;
                        return div.innerHTML;
                    }
                    </script>
                </div>
            </div>
            <div class="tab-content" id="courses-content">
                <div class="courses-grid">
                    <div class="add-card add-card-course add-card-first" data-modal="addCourseModal">
                        <div class="add-card-icon add-card-course-icon">
                            <svg class="feather"><use href="#book-open"></use></svg>
                        </div>
                        <div class="add-card-title">Add Course</div>
                        <div class="add-card-subtitle">Create a new course entry</div>
                    </div>
                    <?php if (empty($courses_data)): ?>
                        <script>
                            document.write(getEmptyStateHTML(
                                'No courses found',
                                'No courses are currently scheduled under the <?php echo htmlspecialchars($program); ?> program'
                            ));
                        </script>
                    <?php else: ?>
                        <?php foreach ($courses_data as $course): ?>
                            <div class="course-card" data-course="<?php echo htmlspecialchars($course['course_code']); ?>" data-course-id="<?php echo $course['course_id']; ?>">
                                <div class="course-card-content">
                                    <div class="course-card-default-content">
                                        <div class="course-header">
                                            <div class="course-code"><?php echo htmlspecialchars($course['course_code']); ?></div>
                                            <div class="course-units"><?php echo htmlspecialchars($course['units']); ?> unit<?php echo $course['units'] > 1 ? 's' : ''; ?></div>
                                        </div>
                                        <div class="course-description">
                                            <?php echo htmlspecialchars($course['course_description']); ?>
                                        </div>
                                        <div class="course-actions">
                                            <button class="action-btn primary" onclick="assignCourseToYearLevel('<?php echo htmlspecialchars($course['course_code']); ?>')">
                                                Assign to Year Level
                                            </button>
                                            <button class="action-btn danger" onclick="deleteCourse('<?php echo htmlspecialchars($course['course_code']); ?>')">
                                                Delete
                                            </button>
                                        </div>
                                    </div>
                                    <div class="course-details-overlay">
                                        <div class="overlay-header">
                                            <h4>Current Assignments</h4>
                                        </div>
                                        <div class="overlay-body">
                                            <div class="assignments-preview" style="padding: 12px;">
                                                <div class="loading-assignments">Loading assignments...</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <button class="course-details-toggle" onclick="toggleCourseDetailsOverlay(this, '<?php echo htmlspecialchars($course['course_code']); ?>')">
                                    View Assignments
                                    <span class="arrow">▼</span>
                                </button>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
            <div class="tab-content" id="classes-content">
                <div class="classes-grid" id="classesGrid">
                    <div class="add-card add-card-first" data-modal="addClassModal">
                        <div class="add-card-icon">
                            <svg class="feather"><use href="#users"></use></svg>
                        </div>
                        <div class="add-card-title">Add Class</div>
                        <div class="add-card-subtitle">Assign a new class group</div>
                    </div>
                    <script>
                    window.classesData = <?php echo json_encode($classes_data); ?>;
                    window.classSchedules = <?php echo json_encode($class_schedules); ?>;
                    document.addEventListener('DOMContentLoaded', function() {
                        const classGrid = document.querySelector('.classes-grid');
                        const classesData = window.classesData || [];
                        const dynamicCards = classGrid.querySelectorAll('.class-card');
                        dynamicCards.forEach(card => card.remove());
                        const existingEmpty = classGrid.querySelector('.empty-state-container');
                        if (existingEmpty) existingEmpty.remove();
                        if (classesData.length === 0) {
                            classGrid.insertAdjacentHTML('beforeend', getEmptyStateHTML(
                                'No classes assigned',
                                'No classes are currently under your supervision'
                            ));
                        } else {
                            classesData.forEach(classItem => {
                                const cardHTML = createSophisticatedClassCard(classItem);
                                classGrid.insertAdjacentHTML('beforeend', cardHTML);
                            });
                        }
                    });
                    function createSophisticatedClassCard(classData) {
                        return `
                            <div class="class-card" data-name="${escapeHtml(classData.class_name)}" data-code="${escapeHtml(classData.class_code)}">
                                <div class="class-card-content">
                                    <div class="class-card-default-content">
                                        <div class="class-header">
                                            <div class="class-info">
                                                <div class="class-name">${escapeHtml(classData.class_name)}</div>
                                                <div class="class-code">${escapeHtml(classData.class_code)}</div>
                                                <div class="class-meta">
                                                    Year ${classData.year_level} • ${escapeHtml(classData.semester)} Semester
                                                </div>
                                            </div>
                                        </div>
                                        <div class="class-stats">
                                            <div class="class-stat">
                                                <div class="class-stat-number">${classData.total_subjects || 0}</div>
                                                <div class="class-stat-label">Subjects</div>
                                            </div>
                                            <div class="class-stat">
                                                <div class="class-stat-number">${classData.assigned_faculty || 0}</div>
                                                <div class="class-stat-label">Faculty</div>
                                            </div>
                                            <div class="class-stat">
                                                <div class="class-stat-number">${classData.total_students || 0}</div>
                                                <div class="class-stat-label">Students</div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="class-details-overlay">
                                        <div class="overlay-header">
                                            <h4>Current Class Schedule</h4>
                                        </div>
                                        <div class="overlay-body">
                                            <div class="schedule-preview">
                                                ${(() => {
                                                    const schedules = window.classSchedules?.[classData.class_id] || [];
                                                    if (schedules.length === 0) {
                                                        return `
                                                            <div class="no-data" style="padding: 20px; text-align: center;">
                                                                <div style="font-size: 2rem; margin-bottom: 10px;">
                                                                    <svg class="feather feather-xl"><use href="#calendar"></use></svg>
                                                                </div>
                                                                <p style="color: #666; margin: 0;">No schedules assigned yet</p>
                                                            </div>
                                                        `;
                                                    }
                                                    return schedules.map(schedule => `
                                                        <div class="schedule-item">
                                                            <div class="schedule-course-info">
                                                                <div class="schedule-course">${escapeHtml(schedule.course_code)}</div>
                                                                <div style="font-size: 0.75rem; color: #666;">${escapeHtml(schedule.course_description || '')}</div>
                                                                <div style="font-size: 0.75rem; color: #999; margin-top: 2px;">
                                                                    ${escapeHtml(schedule.faculty_name || 'No faculty assigned')}
                                                                </div>
                                                            </div>
                                                            <div class="schedule-time">
                                                                <div>${escapeHtml(schedule.days || '')}</div>
                                                                <div>${escapeHtml(schedule.time_start || '')} - ${escapeHtml(schedule.time_end || '')}</div>
                                                                <div style="font-size: 0.7rem; color: #999;">${escapeHtml(schedule.room || 'TBA')}</div>
                                                            </div>
                                                        </div>
                                                    `).join('');
                                                })()}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <button class="class-details-toggle" onclick="toggleClassDetailsOverlay(this)">
                                    View Schedule Details
                                    <span class="arrow">▼</span>
                                </button>
                            </div>
                        `;
                    }
                    function escapeHtml(text) {
                        if (!text) return '';
                        const div = document.createElement('div');
                        div.textContent = text;
                        return div.innerHTML;
                    }
                    </script>
                </div>
            </div>
        </div>
    </div>
    <?php include 'assets/php/shared_modals.php'; ?>
    <script src="assets/js/shared_modals.js"></script>
    <script src="assets/js/schedule_print.js"></script>
    <script>
        window.userRole = 'program_chair';
    </script>
    <script src="assets/js/live_polling.js"></script>
    <script src="assets/js/shared_functions.js"></script>
    <script src="assets/js/program.js"></script>
    <script>
        if (typeof window.toggleSearch !== 'function') {
            window.toggleSearch = function() {
                const container = document.getElementById('searchContainer');
                const searchInput = document.getElementById('searchInput');
                if (!container || !searchInput) {
                    return;
                }
                if (container.classList.contains('collapsed')) {
                    container.classList.remove('collapsed');
                    container.classList.add('expanded');
                    setTimeout(() => searchInput.focus(), 400);
                } else {
                    container.classList.remove('expanded');
                    container.classList.add('collapsed');
                    searchInput.blur();
                }
            };
        }
    </script>
    <script src="assets/js/polling_config.js"></script>
    <script src="assets/js/toast_manager.js"></script>
</body>
</html>

