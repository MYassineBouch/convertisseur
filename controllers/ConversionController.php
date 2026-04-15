<?php
class ConversionController {

    private array $allowed = [
        'image' => ['png','jpg','jpeg','webp','gif','bmp','tiff','ico'],
        'data'  => ['csv','json','xml','yaml','yml','toml'],
        'doc'   => ['txt','html','htm','md'],
    ];

    private array $targets = [
        'image' => ['png','jpg','webp','bmp','ico'],
        'data'  => ['csv','json','xml','yaml','html'],
        'doc'   => ['txt','html','md'],
    ];

    public function home(): void {
        $allowed = $this->getAllowed();
        $targets = $this->getTargets();
        require '../views/home.php';
    }

    public function history(): void {
        $logs = ConversionLog::getAll();
        require '../views/history.php';
    }

    public function deleteHistory(): void {
        ConversionLog::deleteAll();
        header('Location: index.php?action=history');
        exit;
    }

    public function exportHistory(): void {
        $logs = ConversionLog::getAll();
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="history.csv"');
        $f = fopen('php://output', 'w');
        fputcsv($f, ['id','filename','source','destination','status','error','date']);
        foreach ($logs as $row) {
            fputcsv($f, $row);
        }
        fclose($f);
        exit;
    }

    public function convert(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_FILES['file'])) {
            header('Location: index.php');
            exit;
        }

        $file    = $_FILES['file'];
        $origName = basename($file['name']);
        $tmpPath  = $file['tmp_name'];

        $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
        $dst = strtolower($_POST['target'] ?? '');

        $category = $this->getCategory($ext);

        if (!$category) {
            $this->error("Format source non supporté : .$ext");
            return;
        }

        if (!in_array($dst, $this->targets[$category])) {
            $this->error("Format cible invalide : .$dst");
            return;
        }

        $mime = mime_content_type($tmpPath);
        if (!$this->validateMime($mime, $category)) {
            $this->error("Type MIME incohérent avec l'extension ($mime)");
            return;
        }

        $outName = pathinfo($origName, PATHINFO_FILENAME) . '_converted.' . $dst;
        $outPath = __DIR__ . '/../public/converted/' . $outName;
        if (!is_dir(dirname($outPath))) mkdir(dirname($outPath), 0777, true);

        try {
            $this->runConversion($tmpPath, $outPath, $category, $ext, $dst);
            ConversionLog::insert($origName, $ext, $dst, 'success');
            $_SESSION['last_file'] = $outName;
            header('Location: index.php?action=download&file=' . urlencode($outName));
        } catch (Exception $e) {
            ConversionLog::insert($origName, $ext, $dst, 'fail', $e->getMessage());
            $this->error($e->getMessage());
        }
        exit;
    }

    public function download(): void {
        $file = basename($_GET['file'] ?? '');
        $path = __DIR__ . '/../public/converted/' . $file;
        if (!$file || !file_exists($path)) {
            $this->error("Fichier introuvable.");
            return;
        }
        require '../views/download.php';
    }

    private function runConversion(string $src, string $dst, string $cat, string $srcExt, string $dstExt): void {
        match($cat) {
            'image' => $this->convertImage($src, $dst, $dstExt, $srcExt),
            'data'  => $this->convertData($src, $dst, $srcExt, $dstExt),
            'doc'   => $this->convertDoc($src, $dst, $srcExt, $dstExt),
        };
    }

    private function convertImage(string $src, string $dst, string $dstExt, string $srcExt = ''): void {
        if (!file_exists($src)) throw new Exception("Fichier temporaire introuvable : $src");
        if (!is_readable($src)) throw new Exception("Fichier temporaire non lisible : $src");

        $img = match(true) {
            in_array($srcExt, ['jpg','jpeg']) => imagecreatefromjpeg($src),
            $srcExt === 'png'  => imagecreatefrompng($src),
            $srcExt === 'gif'  => imagecreatefromgif($src),
            $srcExt === 'bmp'  => imagecreatefrombmp($src),
            $srcExt === 'webp' => imagecreatefromwebp($src),
            default => throw new Exception("Format image source non supporté par GD.")
        };

        if (!$img) throw new Exception("GD n'a pas pu lire l'image (srcExt=$srcExt, src=$src).");

        $dstDir = dirname($dst);
        if (!is_writable($dstDir)) throw new Exception("Dossier de destination non accessible : $dstDir");

        $ok = match($dstExt) {
            'png'  => imagepng($img, $dst),
            'jpg'  => imagejpeg($img, $dst, 90),
            'webp' => imagewebp($img, $dst),
            'bmp'  => imagebmp($img, $dst),
            'gif'  => imagegif($img, $dst),
            'ico'  => $this->saveIco($img, $dst),
            default => throw new Exception("Format image cible non supporté.")
        };

        imagedestroy($img);
        if (!$ok) throw new Exception("Échec écriture fichier converti : $dst");
    }

    private function saveIco($img, string $dst): bool {
        $w = imagesx($img);
        $h = imagesy($img);
        $bmp = '';
        for ($y = $h - 1; $y >= 0; $y--) {
            for ($x = 0; $x < $w; $x++) {
                $c = imagecolorat($img, $x, $y);
                $bmp .= pack('V', $c);
            }
        }
        $header = pack('vvv', 0, 1, 1);
        $entry  = pack('CCCCvvVV', $w, $h, 0, 0, 1, 32, strlen($bmp) + 40, 22);
        $dib    = pack('VVVvvVVVVVV', 40, $w, $h * 2, 1, 32, 0, strlen($bmp), 0, 0, 0, 0);
        return file_put_contents($dst, $header . $entry . $dib . $bmp) !== false;
    }

    private function convertData(string $src, string $dst, string $srcExt, string $dstExt): void {
        $content = file_get_contents($src);
        $data = $this->parseData($content, $srcExt);
        $out  = $this->serializeData($data, $dstExt);
        if (file_put_contents($dst, $out) === false) throw new Exception("Impossible d'écrire le fichier.");
    }

    private function parseData(string $content, string $ext): array {
        return match($ext) {
            'json' => json_decode($content, true) ?? throw new Exception("JSON invalide."),
            'csv'  => $this->parseCsv($content),
            'xml'  => $this->parseXml($content),
            'yaml','yml' => $this->parseYaml($content),
            'toml' => $this->parseToml($content),
            default => throw new Exception("Format données non supporté.")
        };
    }

    private function serializeData(array $data, string $ext): string {
        return match($ext) {
            'json' => json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
            'csv'  => $this->toCsv($data),
            'xml'  => $this->toXml($data),
            'yaml' => $this->toYaml($data),
            'html' => $this->toHtmlTable($data),
            default => throw new Exception("Format cible données non supporté.")
        };
    }

    private function parseCsv(string $content): array {
        $lines = explode("\n", trim($content));
        $headers = str_getcsv(array_shift($lines));
        $result = [];
        foreach ($lines as $line) {
            if (empty(trim($line))) continue;
            $result[] = array_combine($headers, str_getcsv($line));
        }
        return $result;
    }

    private function parseXml(string $content): array {
        $xml = simplexml_load_string($content);
        if (!$xml) throw new Exception("XML invalide.");
        return json_decode(json_encode($xml), true);
    }

    private function parseYaml(string $content): array {
        if (!function_exists('yaml_parse')) {
            throw new Exception("Extension YAML PHP non installée. Installez php-yaml.");
        }
        return yaml_parse($content) ?? throw new Exception("YAML invalide.");
    }

    private function parseToml(string $content): array {
        $result = [];
        $current = &$result;
        foreach (explode("\n", $content) as $line) {
            $line = trim($line);
            if (!$line || $line[0] === '#') continue;
            if (preg_match('/^\[(.+)\]$/', $line, $m)) {
                $result[$m[1]] = [];
                $current = &$result[$m[1]];
            } elseif (preg_match('/^(\w+)\s*=\s*(.+)$/', $line, $m)) {
                $current[$m[1]] = trim($m[2], '"\'');
            }
        }
        return $result;
    }

    private function toCsv(array $data): string {
        if (empty($data)) return '';
        $f = fopen('php://temp', 'r+');
        if (isset($data[0]) && is_array($data[0])) {
            fputcsv($f, array_keys($data[0]));
            foreach ($data as $row) fputcsv($f, $row);
        } else {
            fputcsv($f, array_keys($data));
            fputcsv($f, array_values($data));
        }
        rewind($f);
        $out = stream_get_contents($f);
        fclose($f);
        return $out;
    }

    private function toXml(array $data, string $root = 'root', string $item = 'item'): string {
        $xml = new SimpleXMLElement("<$root/>");
        $this->arrayToXml($data, $xml, $item);
        $dom = dom_import_simplexml($xml)->ownerDocument;
        $dom->formatOutput = true;
        return $dom->saveXML();
    }

    private function arrayToXml(array $data, SimpleXMLElement $xml, string $item): void {
        foreach ($data as $key => $value) {
            $tag = is_int($key) ? $item : preg_replace('/[^a-zA-Z0-9_]/', '_', $key);
            if (is_array($value)) {
                $child = $xml->addChild($tag);
                $this->arrayToXml($value, $child, $item);
            } else {
                $xml->addChild($tag, htmlspecialchars((string)$value));
            }
        }
    }

    private function toYaml(array $data, int $indent = 0): string {
        $out = '';
        foreach ($data as $k => $v) {
            $pad = str_repeat('  ', $indent);
            if (is_array($v)) {
                $out .= "$pad$k:\n" . $this->toYaml($v, $indent + 1);
            } else {
                $out .= "$pad$k: $v\n";
            }
        }
        return $out;
    }

    private function toHtmlTable(array $data): string {
        if (empty($data)) return '<p>Aucune donnée</p>';
        if (!isset($data[0])) $data = [$data];
        $html = '<table border="1" cellpadding="6" style="border-collapse:collapse">';
        $html .= '<tr>' . implode('', array_map(fn($h) => "<th>$h</th>", array_keys($data[0]))) . '</tr>';
        foreach ($data as $row) {
            $html .= '<tr>' . implode('', array_map(fn($v) => "<td>" . htmlspecialchars((string)$v) . "</td>", $row)) . '</tr>';
        }
        return $html . '</table>';
    }

    private function convertDoc(string $src, string $dst, string $srcExt, string $dstExt): void {
        $content = file_get_contents($src);
        $out = match(true) {
            $srcExt === 'txt'  && $dstExt === 'html' => "<pre>" . htmlspecialchars($content) . "</pre>",
            $srcExt === 'txt'  && $dstExt === 'md'   => $content,
            $srcExt === 'html' && $dstExt === 'txt'  => strip_tags($content),
            $srcExt === 'html' && $dstExt === 'md'   => strip_tags($content),
            $srcExt === 'md'   && $dstExt === 'html' => $this->mdToHtml($content),
            $srcExt === 'md'   && $dstExt === 'txt'  => preg_replace('/[#*`_~]/', '', $content),
            default => throw new Exception("Conversion doc $srcExt → $dstExt non supportée.")
        };
        if (file_put_contents($dst, $out) === false) throw new Exception("Impossible d'écrire le fichier.");
    }

    private function mdToHtml(string $md): string {
        $md = preg_replace('/^# (.+)$/m', '<h1>$1</h1>', $md);
        $md = preg_replace('/^## (.+)$/m', '<h2>$1</h2>', $md);
        $md = preg_replace('/^### (.+)$/m', '<h3>$1</h3>', $md);
        $md = preg_replace('/\*\*(.+?)\*\*/', '<strong>$1</strong>', $md);
        $md = preg_replace('/\*(.+?)\*/', '<em>$1</em>', $md);
        $md = preg_replace('/`(.+?)`/', '<code>$1</code>', $md);
        $md = nl2br($md);
        return "<!DOCTYPE html><html><body>$md</body></html>";
    }

    private function getCategory(string $ext): ?string {
        foreach ($this->allowed as $cat => $exts) {
            if (in_array($ext, $exts)) return $cat;
        }
        return null;
    }

    private function validateMime(string $mime, string $category): bool {
        return match($category) {
            'image' => str_starts_with($mime, 'image/'),
            'data'  => in_array($mime, ['text/plain','text/csv','application/json','application/xml','text/xml','text/html','application/octet-stream']),
            'doc'   => in_array($mime, ['text/plain','text/html','text/markdown','application/octet-stream']),
            default => false
        };
    }

    public function getTargets(): array {
        return $this->targets;
    }

    public function getAllowed(): array {
        return $this->allowed;
    }

    private function error(string $msg): void {
        $_SESSION['error'] = $msg;
        header('Location: index.php');
        exit;
    }
}