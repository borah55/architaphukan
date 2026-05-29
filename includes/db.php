<?php
/**
 * PDO database connection (singleton).
 */
if (!defined('SITE_ROOT')) { http_response_code(500); exit; }

function db(): PDO {
    static $pdo = null;
    if ($pdo !== null) {
        return $pdo;
    }
    $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
    $opts = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];
    try {
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $opts);
    } catch (PDOException $e) {
        if (defined('DEBUG') && DEBUG) {
            die('DB connection failed: ' . htmlspecialchars($e->getMessage()));
        }
        die('Database connection error. Please try again later.');
    }
    return $pdo;
}

function db_query(string $sql, array $params = []): PDOStatement {
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    return $stmt;
}

function db_one(string $sql, array $params = []) {
    return db_query($sql, $params)->fetch();
}

function db_all(string $sql, array $params = []): array {
    return db_query($sql, $params)->fetchAll();
}

function db_value(string $sql, array $params = []) {
    $row = db_query($sql, $params)->fetch(PDO::FETCH_NUM);
    return $row ? $row[0] : null;
}

function db_insert(string $table, array $data): int {
    $cols = array_keys($data);
    $placeholders = array_map(fn($c) => ':' . $c, $cols);
    $sql = 'INSERT INTO `' . $table . '` (`' . implode('`,`', $cols) . '`) VALUES (' . implode(',', $placeholders) . ')';
    db_query($sql, $data);
    return (int) db()->lastInsertId();
}

function db_update(string $table, array $data, string $where, array $whereParams = []): int {
    $set = [];
    foreach (array_keys($data) as $c) {
        $set[] = '`' . $c . '` = :' . $c;
    }
    $sql = 'UPDATE `' . $table . '` SET ' . implode(', ', $set) . ' WHERE ' . $where;
    return db_query($sql, array_merge($data, $whereParams))->rowCount();
}
