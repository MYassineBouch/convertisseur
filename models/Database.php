<?php
class Database {
    private static $pdo = null;

    public static function get(): PDO {
        if (self::$pdo === null) {
            $config = require __DIR__ . '/../config.php';
            self::$pdo = new PDO(
                "mysql:host={$config['db_host']};dbname={$config['db_name']};charset=utf8",
                $config['db_user'],
                $config['db_pass'],
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            );
            self::init(self::$pdo);
        }
        return self::$pdo;
    }

    private static function init(PDO $pdo): void {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS conversion_logs (
                id CHAR(36) PRIMARY KEY,
                filename VARCHAR(255),
                source_format VARCHAR(20),
                destination_format VARCHAR(20),
                error TEXT,
                status ENUM('success','fail'),
                date_time DATETIME
            )
        ");
    }
}
