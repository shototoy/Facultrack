<?php
if (!isset($_GET['ready']) && empty($_POST)) {
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FaculTrack - Loading</title>
    <link rel="icon" type="image/png" href="assets/images/favicon.png">
    <link rel="stylesheet" href="assets/css/theme.css">
    <style>
        body, html { margin: 0; padding: 0; height: 100%; overflow: hidden; font-family: 'Segoe UI', sans-serif; background: #075822; }
        .loader-container {
            position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            display: flex; flex-direction: column; justify-content: flex-end; align-items: center;
            padding-bottom: 10vh;
            box-sizing: border-box;
            z-index: 9999;
            background: #075822 url('assets/images/icon.png') no-repeat center center;
            background-size: contain;
        }
        .spinner {
            width: 50px; height: 50px;
            border: 4px solid rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            border-top: 4px solid #ffffff;
            animation: spin 1s linear infinite;
            margin-bottom: 1.5rem;
            z-index: 10000; 
            margin-top: 2rem;
        }
        .loading-text { 
            font-size: 1.2rem; 
            margin-bottom: 1rem; 
            color: #ffffff;
            font-weight: 500;
            letter-spacing: 0.5px;
            text-align: center;
            text-shadow: 0 1px 4px rgba(0,0,0,0.5); 
            z-index: 10000;
        }
        .progress-bar-bg {
            width: 240px; height: 6px; background: rgba(255, 255, 255, 0.2);
            border-radius: 3px; overflow: hidden;
            position: relative;
            max-width: 80%;
            z-index: 10000;
            box-shadow: 0 1px 3px rgba(0,0,0,0.3);
        }
        .progress-bar {
            position: absolute; top: 0; left: 0; height: 100%; width: 30%;
            background: #ffffff;
            border-radius: 3px;
            animation: indeterminate 2s infinite ease-in-out;
        }
        .status-message {
            margin-top: 15px; font-size: 0.85rem; color: rgba(255, 255, 255, 0.9);
            max-width: 300px; text-align: center; line-height: 1.4;
            padding: 0 20px;
            text-shadow: 0 1px 2px rgba(0,0,0,0.5);
            z-index: 10000;
        }
        @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
        @keyframes indeterminate {
            0% { left: -100%; width: 100%; }
            100% { left: 100%; width: 10%; }
        }
    </style>
</head>
<body>
    <div class="loader-container">
        <div class="spinner"></div>
        <div class="loading-text" id="loading-text">Connecting to Server...</div>
        <div class="progress-bar-bg">
            <div class="progress-bar"></div>
        </div>
        <div class="status-message">Please wait while we wake up the server. This allows us to save resources when not in use.</div>
    </div>
    <script>
        const texts = ["Connecting to Server...", "Waking up Database...", "Establishing Secure Connection...", "Almost Ready..."];
        let textIdx = 0;
        const textInterval = setInterval(() => {
            textIdx = (textIdx + 1) % texts.length;
            document.getElementById('loading-text').innerText = texts[textIdx];
        }, 2000);
        function checkServer() {
            fetch('assets/php/polling_api.php?action=test', { cache: "no-store" })
            .then(response => {
                if (response.ok) return response.json();
                throw new Error('Network response was not ok: ' + response.status);
            })
            .then(data => {
                if (data.success) {
                    clearInterval(textInterval);
                    document.getElementById('loading-text').innerText = "Connected!";
                    document.querySelector('.progress-bar').style.animation = 'none';
                    document.querySelector('.progress-bar').style.width = '100%';
                    setTimeout(() => {
                        window.location.href = 'index.php?ready=1';
                    }, 500);
                } else {
                    setTimeout(checkServer, 1000);
                }
            })
            .catch(error => {
                console.log('Polling...', error);
                setTimeout(checkServer, 1000); 
            });
        }
        checkServer();
    </script>
</body>
</html>
<?php
exit();
}
require_once 'assets/php/common_utilities.php';
initializeSession();
$pdo = get_db_connection();
$error_message = '';
$success_message = '';
if ($_POST) {
    $input_username = $_POST['username'] ?? '';
    $input_password = $_POST['password'] ?? '';
    if (empty($input_username) || empty($input_password)) {
        $error_message = 'Please enter both username and password.';
    } else {
        try {
            $stmt = $pdo->prepare("SELECT user_id, username, password, role, full_name, is_active FROM users WHERE username = ? AND is_active = 1");
            $stmt->execute([$input_username]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($user && $user['password'] === $input_password) {
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['full_name'] = $user['full_name'];
                $_SESSION['logged_in'] = true;
                if ($user['role'] == 'class') {
                    $class_stmt = $pdo->prepare("SELECT class_id, class_code, class_name FROM classes WHERE user_id = ? AND is_active = 1");
                    $class_stmt->execute([$user['user_id']]);
                    $class_info = $class_stmt->fetch(PDO::FETCH_ASSOC);
                    if ($class_info) {
                        $_SESSION['class_id'] = $class_info['class_id'];
                        $_SESSION['class_code'] = $class_info['class_code'];
                        $_SESSION['class_name'] = $class_info['class_name'];
                    }
                } elseif ($user['role'] == 'faculty') {
                    $faculty_stmt = $pdo->prepare("SELECT faculty_id, employee_id FROM faculty WHERE user_id = ?");
                    $faculty_stmt->execute([$user['user_id']]);
                    $faculty_info = $faculty_stmt->fetch(PDO::FETCH_ASSOC);
                    if ($faculty_info) {
                        $_SESSION['faculty_id'] = $faculty_info['faculty_id'];
                        $_SESSION['employee_id'] = $faculty_info['employee_id'];
                        $set_online_query = "UPDATE faculty SET is_active = 1, status = 'Available' WHERE user_id = ?";
                        $online_stmt = $pdo->prepare($set_online_query);
                        $online_stmt->execute([$user['user_id']]);
                    }
                } elseif ($user['role'] == 'program_chair') {
                    $faculty_stmt = $pdo->prepare("SELECT faculty_id, employee_id, program FROM faculty WHERE user_id = ?");
                    $faculty_stmt->execute([$user['user_id']]);
                    $faculty_info = $faculty_stmt->fetch(PDO::FETCH_ASSOC);
                    if ($faculty_info) {
                        $_SESSION['faculty_id'] = $faculty_info['faculty_id'];
                        $_SESSION['employee_id'] = $faculty_info['employee_id'];
                        $_SESSION['program'] = $faculty_info['program'];
                        $set_online_query = "UPDATE faculty SET is_active = 1, status = 'Available' WHERE user_id = ?";
                        $online_stmt = $pdo->prepare($set_online_query);
                        $online_stmt->execute([$user['user_id']]);
                    }
                } elseif ($user['role'] == 'campus_director') {
                    $director_faculty_stmt = $pdo->prepare("
                        INSERT INTO faculty (user_id, employee_id, program, current_location, is_active, last_location_update)
                        VALUES (?, CONCAT('DIR-', ?), 'Administration', 'Director Office', 1, NOW())
                        ON DUPLICATE KEY UPDATE is_active = 1, last_location_update = NOW()
                    ");
                    $director_faculty_stmt->execute([$user['user_id'], $user['user_id']]);
                    $get_faculty_id = $pdo->prepare("SELECT faculty_id FROM faculty WHERE user_id = ?");
                    $get_faculty_id->execute([$user['user_id']]);
                    $faculty_info = $get_faculty_id->fetch(PDO::FETCH_ASSOC);
                    if ($faculty_info) {
                        $_SESSION['faculty_id'] = $faculty_info['faculty_id'];
                        $_SESSION['employee_id'] = 'DIR-' . $user['user_id'];
                        $_SESSION['program'] = 'Administration';
                    }
                }
                switch ($user['role']) {
                    case 'class':
                        header('Location: home.php');
                        break;
                    case 'program_chair':
                        header('Location: program.php');
                        break;
                    case 'faculty':
                        header('Location: faculty.php');
                        break;
                    case 'campus_director':
                        header('Location: director.php');
                        break;
                    default:
                        $error_message = 'Invalid user role.';
                }
                if (!$error_message) {
                    exit();
                }
            } else {
                $error_message = 'Invalid username or password.';
            }
        } catch (PDOException $e) {
            $error_message = 'Database connection failed. Please try again later.';
            error_log("Database error: " . $e->getMessage());
        }
    }
}
if (isset($_SESSION['logged_in']) && $_SESSION['logged_in']) {
    if ($_SESSION['role'] == 'class') {
        header('Location: home.php');
        exit();
    } elseif ($_SESSION['role'] == 'program_chair') {
        header('Location: program.php');
        exit();
    }
}
$demo_accounts = [];
try {
    $stmt = $pdo->prepare("
        SELECT username, role, full_name FROM users WHERE role = 'campus_director' AND is_active = 1
        UNION ALL
        SELECT username, role, full_name FROM users WHERE role = 'program_chair' AND is_active = 1
        UNION ALL 
        SELECT username, role, full_name FROM users WHERE role = 'faculty' AND is_active = 1
        UNION ALL
        SELECT username, role, full_name FROM users WHERE role = 'class' AND is_active = 1
        ORDER BY 
            CASE role 
                WHEN 'campus_director' THEN 1
                WHEN 'program_chair' THEN 2
                WHEN 'faculty' THEN 3
                WHEN 'class' THEN 4
            END, full_name
    ");
    $stmt->execute();
    $demo_accounts = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Demo account fetch error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FaculTrack - Login</title>
    <link rel="icon" type="image/png" href="assets/images/favicon.png">
    <link rel="stylesheet" href="assets/css/theme.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        body {
            background-color: #075822 !important;
        }
        .login-container {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 20px;
        }
        .login-card {
            background: url('assets/images/favicon.png') no-repeat center center;
            background-size: contain;
            border-radius: 25px;
            padding: 30px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
            width: 100%;
            max-width: 400px;
            min-height: 350px;
            text-align: center;
            position: relative;
            z-index: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        .login-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
            border-radius: 15px;
            z-index: -1;
        }
        .form-input {
            background: rgba(255, 255, 255, 0.6) !important;
            border: 1px solid rgba(255, 255, 255, 0.4);
            color: #000;
            font-weight: 600;
            font-size: 1.2rem; 
            padding: 16px; 
        }
        .form-input:focus {
            background: rgba(255, 255, 255, 0.9) !important;
            box-shadow: 0 0 0 3px rgba(255, 255, 255, 0.3);
        }
        #password {
            margin-bottom: 3rem; 
        }
        .form-label {
            color: #fff;
            text-shadow: 0 1px 2px rgba(0,0,0,0.5);
            font-size: 1.3rem; 
            margin-bottom: 10px;
        }
        .login-btn {
            width: 100%;
            background: #075822;
            color: white;
            border: 1px solid rgba(255,255,255,0.2);
            padding: 12px 24px;
            border-radius: 8px;
            font-size: 1.5rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
        }
        .login-btn:hover {
            background: #054018;
            transform: translateY(-2px);
        }
        .demo-accounts {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid rgba(255,255,255,0.3);
            display: none;
        }
        .demo-title {
            color: #eee;
        }
        @media (max-width: 768px) {
            .login-card {
                padding: 30px; 
                min-height: auto;
                width: 90%;
            }
            .form-label {
                font-size: 1rem;
                margin-bottom: 5px;
            }
            .form-input {
                font-size: 1rem !important;
                padding: 12px !important;
                height: auto;
            }
            #password {
                margin-bottom: 0 !important;
            }
            .login-btn {
                font-size: 1rem !important;
                margin-top: 10px !important;
                padding: 12px;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <?php if ($error_message): ?>
                <div class="error-message">
                    <?php echo htmlspecialchars($error_message); ?>
                </div>
            <?php endif; ?>
            <?php if ($success_message): ?>
                <div class="success-message">
                    <?php echo htmlspecialchars($success_message); ?>
                </div>
            <?php endif; ?>
            <form method="POST" action="">
                <div class="form-group">
                    <label class="form-label" for="username">Username</label>
                    <input type="text" 
                           class="form-input" 
                           id="username" 
                           name="username" 
                           value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>" 
                           required>
                </div>
                <div class="form-group">
                    <label class="form-label" for="password">Password</label>
                    <input type="password" 
                           class="form-input" 
                           id="password" 
                           name="password" 
                           required>
                </div>
                <button type="submit" class="login-btn">Sign In</button>
            </form>
            <div class="demo-accounts">
                <div class="demo-title">Demo Accounts (Click to use):</div>
                <?php foreach ($demo_accounts as $account): ?>
                    <form method="POST" action="" style="display: inline;">
                        <input type="hidden" name="username" value="<?php echo $account['username']; ?>">
                        <input type="hidden" name="password" value="<?php 
                            if ($account['role'] === 'campus_director') echo 'admin123';
                            elseif ($account['role'] === 'program_chair') echo 'chair123';
                            elseif ($account['role'] === 'faculty') echo 'prof123';
                            else echo 'class123';
                        ?>">
                        <div class="demo-account" onclick="this.parentNode.submit()">
                            <strong><?php echo ucfirst(str_replace('_', ' ', $account['role'])); ?>:</strong> <?php echo $account['username']; ?> / 
                            <?php 
                                if ($account['role'] === 'campus_director') echo 'admin123';
                                elseif ($account['role'] === 'program_chair') echo 'chair123';
                                elseif ($account['role'] === 'faculty') echo 'prof123';
                                else echo 'class123';
                            ?>
                            <div class="available">Click to Sign In</div>
                        </div>
                    </form>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <script>
        function fillCredentials(username, password) {
            document.getElementById('username').value = username;
            document.getElementById('password').value = password;
        }
    </script>
</body>
</html>

