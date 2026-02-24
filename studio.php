<?php
function adminer_object() {
    class AdminerSoftware extends Adminer {
        function credentials() {
            $host = getenv('DB_HOST') ?: getenv('MYSQLHOST') ?: 'localhost';
            $user = getenv('DB_USER') ?: getenv('MYSQLUSER') ?: 'root';
            $pass = getenv('DB_PASSWORD') ?: getenv('MYSQLPASSWORD') ?: '';
            return array($host, $user, $pass);
        }
        function database() {
            return getenv('DB_NAME') ?: getenv('MYSQLDATABASE') ?: 'facultrack_db';
        }
        function login($login, $password) {
            return true;
        }
    }
    return new AdminerSoftware;
}

if (!isset($_GET['mysql'])) {
    $host = getenv('DB_HOST') ?: getenv('MYSQLHOST') ?: 'localhost';
    $user = getenv('DB_USER') ?: getenv('MYSQLUSER') ?: 'root';
    $_GET['mysql'] = $host;
    $_GET['username'] = $user;
}

include "./adminer_core.php";
