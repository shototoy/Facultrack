<?php
require_once 'common_utilities.php';
initializeSession();
header('Content-Type: application/json');

try {
    $pdo = initializeDatabase();
} catch (Exception $e) {
    sendJsonResponse(['success' => false, 'message' => 'Database connection failed: ' . $e->getMessage()], 500);
}

try {
    validateUserSession(['program_chair', 'campus_director']);
} catch (Exception $e) {
    sendJsonResponse(['success' => false, 'message' => 'Unauthorized access'], 403);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

$action = $_POST['action'] ?? '';
$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['role'];

if (strpos($action, 'add_') === 0) {
    $response = handleAdd($pdo, $_POST, $user_id, $user_role);
} elseif (strpos($action, 'delete_') === 0) {
    $response = handleDelete($pdo, $_POST, $user_id, $user_role);
} else {
    $response = ['success' => false, 'message' => 'Unknown action: ' . $action];
}

if (ob_get_length()) ob_clean();
echo json_encode($response);
exit();

function handleAdd($pdo, $data, $user_id, $user_role) {
    $action = $data['action'] ?? null;

    $config = [
        'add_faculty' => [
            'required' => ['full_name', 'username', 'password'],
            'unique' => ['users.username'],
            'user' => ['role' => function($d) {
                return isset($d['is_program_chair']) && $d['is_program_chair'] == '1' ? 'program_chair' : 'faculty';
            }],
            'generate_id' => ['table' => 'faculty', 'column' => 'employee_id', 'prefix' => function($d) {
                return isset($d['is_program_chair']) && $d['is_program_chair'] == '1' ? 'CHAIR-' : 'EMP-';
            }],
            'table' => 'faculty',
            'fields' => ['program', 'office_hours', 'contact_email', 'contact_phone']
        ],
        'add_course' => [
            'required' => ['course_code', 'course_description', 'units'],
            'unique' => ['courses.course_code'],
            'table' => 'courses',
            'fields' => ['course_code', 'course_description', 'units']
        ],
        'add_class' => [
            'required' => ['class_name', 'class_code', 'year_level', 'semester', 'academic_year', 'username', 'password'],
            'unique' => ['users.username', 'classes.class_code'],
            'user' => ['role' => 'class'],
            'table' => 'classes',
            'fields' => ['class_code', 'class_name', 'year_level', 'semester', 'academic_year', 'program_chair_id']
        ],
        'add_announcement' => [
            'required' => ['title', 'content', 'priority', 'target_audience'],
            'role_required' => 'campus_director',
            'table' => 'announcements',
            'fields' => ['title', 'content', 'priority', 'target_audience']
        ]
    ];

    if (!isset($config[$action])) {
        return ['success' => false, 'message' => 'Invalid action'];
    }

    $c = $config[$action];

    if (isset($c['role_required']) && $user_role !== $c['role_required']) {
        return ['success' => false, 'message' => 'Unauthorized'];
    }

    foreach ($c['required'] as $field) {
        if (empty($data[$field])) {
            return ['success' => false, 'message' => "Missing required field: $field"];
        }
    }

    foreach ($c['unique'] ?? [] as $u) {
        [$table, $column] = explode('.', $u);
        $stmt = $pdo->prepare("SELECT 1 FROM $table WHERE $column = ?");
        $stmt->execute([$data[$column]]);
        if ($stmt->fetch()) {
            return ['success' => false, 'message' => ucfirst($column) . ' already exists'];
        }
    }

    $pdo->beginTransaction();

    try {
        $new_user_id = null;
        if (isset($c['user'])) {
            $role = is_callable($c['user']['role']) ? $c['user']['role']($data) : $c['user']['role'];
            $stmt = $pdo->prepare("INSERT INTO users (username, password, full_name, role) VALUES (?, ?, ?, ?)");
            $stmt->execute([
                $data['username'],
                password_hash($data['password'], PASSWORD_DEFAULT),
                $data['full_name'] . ($role === 'class' ? ' Class Account' : ''),
                $role
            ]);
            $new_user_id = $pdo->lastInsertId();
        }

        $insert_data = [];

        if ($action === 'add_faculty') {
            $role = is_callable($c['user']['role']) ? $c['user']['role']($data) : 'faculty';
            
            if ($role === 'program_chair') {
                if ($user_role === 'campus_director') {
                    $insert_data['program'] = $data['program'] ?? null;
                    if (!$insert_data['program']) {
                        throw new Exception('Program is required for Program Chair');
                    }
                } else {
                    $stmt = $pdo->prepare("SELECT program FROM faculty WHERE user_id = ? AND is_active = TRUE");
                    $stmt->execute([$user_id]);
                    $insert_data['program'] = $stmt->fetchColumn();
                }
            }

            $prefix = $c['generate_id']['prefix']($data);
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM {$c['generate_id']['table']} WHERE {$c['generate_id']['column']} LIKE ?");
            $stmt->execute([$prefix . '%']);
            $count = $stmt->fetchColumn();
            $insert_data['employee_id'] = $prefix . str_pad($count + 1, 4, '0', STR_PAD_LEFT);
        }

        if ($action === 'add_class') {
            $insert_data['program_chair_id'] = $user_role === 'program_chair' ? $user_id : ($data['program_chair_id'] ?? null);
        }

        if ($action === 'add_announcement') {
            $insert_data['created_by'] = $user_id;
        }

        foreach ($c['fields'] as $field) {
            $insert_data[$field] = $data[$field] ?? null;
        }

        if ($new_user_id) {
            $insert_data['user_id'] = $new_user_id;
        }

        $columns = array_keys($insert_data);
        $placeholders = array_fill(0, count($columns), '?');
        $stmt = $pdo->prepare("INSERT INTO {$c['table']} (" . implode(',', $columns) . ") VALUES (" . implode(',', $placeholders) . ")");
        $stmt->execute(array_values($insert_data));

        $entity_id = $pdo->lastInsertId();
        $pdo->commit();

        return fetchAddedRecord($pdo, $action, $entity_id);
        
    } catch (Exception $e) {
        $pdo->rollBack();
        return ['success' => false, 'message' => $e->getMessage()];
    }
}

function handleDelete($pdo, $data, $user_id, $user_role) {
    $action = $data['action'] ?? null;
    
    $id_mappings = [
        'delete_faculty' => 'faculty_id',
        'delete_course' => 'course_id', 
        'delete_class' => 'class_id',
        'delete_announcement' => 'announcement_id'
    ];
    
    $id_field = $id_mappings[$action] ?? 'id';
    $id = $data[$id_field] ?? $data['id'] ?? null;

    $config = [
        'delete_faculty' => [
            'table' => 'faculty',
            'key' => 'faculty_id',
            'soft_delete' => true,
            'also_deactivate_user' => true
        ],
        'delete_course' => [
            'table' => 'courses',
            'key' => 'course_id',
            'soft_delete' => true
        ],
        'delete_class' => [
            'table' => 'classes',
            'key' => 'class_id',
            'soft_delete' => true,
            'also_deactivate_user' => true
        ],
        'delete_announcement' => [
            'table' => 'announcements',
            'key' => 'announcement_id',
            'soft_delete' => false
        ]
    ];

    if (!isset($config[$action]) || !$id) {
        return ['success' => false, 'message' => 'Invalid delete request. Action: ' . $action . ', ID: ' . $id];
    }

    $c = $config[$action];
    $table = $c['table'];
    $key = $c['key'];

    $pdo->beginTransaction();

    try {
        $stmt = $pdo->prepare("SELECT 1 FROM $table WHERE $key = ?");
        $stmt->execute([$id]);
        if (!$stmt->fetch()) {
            throw new Exception('Record not found');
        }

        if ($c['soft_delete']) {
            $stmt = $pdo->prepare("UPDATE $table SET is_active = FALSE WHERE $key = ?");
            $stmt->execute([$id]);
        } else {
            $stmt = $pdo->prepare("DELETE FROM $table WHERE $key = ?");
            $stmt->execute([$id]);
        }

        if (!empty($c['also_deactivate_user'])) {
            $stmt = $pdo->prepare("SELECT user_id FROM $table WHERE $key = ?");
            $stmt->execute([$id]);
            $linked_user_id = $stmt->fetchColumn();

            if ($linked_user_id) {
                $stmt = $pdo->prepare("UPDATE users SET is_active = FALSE WHERE user_id = ?");
                $stmt->execute([$linked_user_id]);
            }
        }

        $pdo->commit();
        return ['success' => true, 'message' => ucfirst(str_replace('delete_', '', $action)) . ' deleted successfully'];
        
    } catch (Exception $e) {
        $pdo->rollBack();
        return ['success' => false, 'message' => 'Delete failed: ' . $e->getMessage()];
    }
}

function fetchAddedRecord($pdo, $action, $id) {
    switch ($action) {
        case 'add_faculty':
            $stmt = $pdo->prepare("
                SELECT f.faculty_id, u.full_name, f.employee_id, f.program, f.office_hours,
                       f.contact_email, f.contact_phone, f.current_location, f.last_location_update,
                       CASE 
                           WHEN f.last_location_update > DATE_SUB(NOW(), INTERVAL 30 MINUTE) THEN 'Available'
                           WHEN f.last_location_update > DATE_SUB(NOW(), INTERVAL 2 HOUR) THEN 'Busy'
                           ELSE 'Offline'
                       END as status
                FROM faculty f
                JOIN users u ON f.user_id = u.user_id
                WHERE f.faculty_id = ? AND f.is_active = TRUE
            ");
            break;

        case 'add_course':
            $stmt = $pdo->prepare("
                SELECT c.course_id, c.course_code, c.course_description, c.units,
                       COUNT(s.schedule_id) as times_scheduled
                FROM courses c
                LEFT JOIN schedules s ON c.course_code = s.course_code AND s.is_active = TRUE
                WHERE c.course_id = ?
                GROUP BY c.course_id
            ");
            break;

        case 'add_class':
            $stmt = $pdo->prepare("
                SELECT c.class_id, c.class_code, c.class_name, c.year_level, c.semester, c.academic_year,
                       u.full_name as program_chair_name,
                       COUNT(s.schedule_id) as total_subjects
                FROM classes c
                LEFT JOIN faculty f ON c.program_chair_id = f.user_id
                LEFT JOIN users u ON f.user_id = u.user_id
                LEFT JOIN schedules s ON c.class_id = s.class_id AND s.is_active = TRUE
                WHERE c.class_id = ? AND c.is_active = TRUE
                GROUP BY c.class_id
            ");
            break;

        case 'add_announcement':
            $stmt = $pdo->prepare("
                SELECT a.announcement_id, a.title, a.content, a.priority, a.target_audience,
                       a.created_at, u.full_name as created_by_name
                FROM announcements a
                JOIN users u ON a.created_by = u.user_id
                WHERE a.announcement_id = ?
            ");
            break;

        default:
            return ['success' => false, 'message' => 'Unknown action for fetching record'];
    }

    $stmt->execute([$id]);
    $record = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$record) {
        return ['success' => false, 'message' => 'Failed to fetch added record'];
    }
    
    return ['success' => true, 'data' => $record, 'message' => ucfirst(str_replace('add_', '', $action)) . ' added successfully'];
}