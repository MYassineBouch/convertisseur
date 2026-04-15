<?php
class ConversionLog {
    public static function insert(string $filename, string $src, string $dst, string $status, string $error = ''): void {
        $pdo = Database::get();
        $stmt = $pdo->prepare("INSERT INTO conversion_logs VALUES (?,?,?,?,?,?,?)");
        $stmt->execute([
            self::uuid(),
            $filename,
            $src,
            $dst,
            $error,
            $status,
            date('Y-m-d H:i:s')
        ]);
    }

    public static function getAll(): array {
        return Database::get()->query("SELECT * FROM conversion_logs ORDER BY date_time DESC")->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function deleteAll(): void {
        Database::get()->exec("DELETE FROM conversion_logs");
    }

    private static function uuid(): string {
        return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
    }
}
