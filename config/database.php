<?php
// BN-Infrastructure - Database Configuration

define('DB_HOST', 'localhost');
define('DB_USER', 'tumamaoni_user');
define('DB_PASS', 'Mo2004@12');
define('DB_NAME', 'bn_infrastructure_db');

function getConnection() {
    static $conn = null;
    if ($conn === null) {
        try {
            $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
            if ($conn->connect_error) {
                throw new Exception("Connection failed: " . $conn->connect_error);
            }
            $conn->set_charset("utf8mb4");
        } catch (Exception $e) {
            die("Database connection error. Please check your config.");
        }
    }
    return $conn;
}

function query($sql, $params = []) {
    $conn = getConnection();
    if (!empty($params)) {
        $stmt = $conn->prepare($sql);
        if ($stmt === false) {
            die("Query error: " . $conn->error);
        }
        $types = '';
        foreach ($params as $param) {
            if (is_int($param)) $types .= 'i';
            elseif (is_double($param)) $types .= 'd';
            else $types .= 's';
        }
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
        $stmt->close();
        return $result;
    }
    return $conn->query($sql);
}

function fetchAll($sql, $params = []) {
    $result = query($sql, $params);
    if ($result) {
        return $result->fetch_all(MYSQLI_ASSOC);
    }
    return [];
}

function fetchOne($sql, $params = []) {
    $result = query($sql, $params);
    if ($result) {
        return $result->fetch_assoc();
    }
    return null;
}

function execute($sql, $params = []) {
    $conn = getConnection();
    if (!empty($params)) {
        $stmt = $conn->prepare($sql);
        if ($stmt === false) {
            die("Query error: " . $conn->error);
        }
        $types = '';
        foreach ($params as $param) {
            if (is_int($param)) $types .= 'i';
            elseif (is_double($param)) $types .= 'd';
            else $types .= 's';
        }
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $affected = $stmt->affected_rows;
        $insert_id = $stmt->insert_id;
        $stmt->close();
        return $insert_id ?: $affected;
    }
    $conn->query($sql);
    return $conn->affected_rows;
}
