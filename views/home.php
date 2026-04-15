<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Convertisseur Universel</title>
<link rel="stylesheet" href="css/style.css">
</head>
<body>
<nav>
    <a href="index.php">CONVERTIR</a>
    <a href="index.php?action=history">LOG DE CONVERSIONS</a>
</nav>

<div class="card">
    <p class="app-title">PROJET ALTERNATIF</p>
    <p class="app-sub">Convertisseur de fichiers universel</p>

    <?php if (!empty($_SESSION['error'])): ?>
        <div class="error"><?= htmlspecialchars($_SESSION['error']) ?></div>
        <?php unset($_SESSION['error']); ?>
    <?php endif; ?>

    <form action="index.php?action=convert" method="POST" enctype="multipart/form-data" id="convertForm">
        <input type="hidden" name="target" id="targetInput">

        <div class="dropzone" id="dropzone">
            <svg width="36" height="36" viewBox="0 0 36 36" fill="none">
                <circle cx="18" cy="18" r="18" fill="#f5f5f2"/>
                <path d="M18 24V14M18 14L14 18M18 14L22 18" stroke="#888" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M11 27h14" stroke="#888" stroke-width="1.6" stroke-linecap="round"/>
            </svg>
            <p class="main">Glissez votre fichier ici</p>
            <p class="or">ou</p>
            <button type="button" class="btn-browse" onclick="document.getElementById('fileInput').click()">Parcourir</button>
            <input type="file" name="file" id="fileInput" required style="display:none">
            <p class="hint">PNG, JPG, WEBP, CSV, JSON, XML, TXT, HTML…</p>
        </div>

        <div id="fileInfoBox" class="file-info" style="display:none">
            <div class="file-icon">
                <svg width="16" height="16" viewBox="0 0 16 16" fill="none">
                    <rect x="2" y="1" width="12" height="14" rx="2.5" stroke="#185fa5" stroke-width="1.2"/>
                    <path d="M5 6h6M5 9h4" stroke="#185fa5" stroke-width="1.2" stroke-linecap="round"/>
                </svg>
            </div>
            <div style="flex:1;min-width:0;">
                <p class="name" id="fileName">—</p>
                <p class="meta" id="fileMeta">—</p>
            </div>
            <span class="badge-valid">Valide</span>
        </div>

        <div id="targetSection" style="display:none">
            <div class="format-section">
                <p class="format-label">Convertir vers</p>
                <div class="format-pills" id="pills"></div>
            </div>
            <button type="submit" class="btn-convert">Convertir</button>
        </div>
    </form>
</div>

<script>
const allowed = <?= json_encode($allowed) ?>;
const targets  = <?= json_encode($targets) ?>;

function getCategory(ext) {
    for (const [cat, exts] of Object.entries(allowed)) {
        if (exts.includes(ext)) return cat;
    }
    return null;
}

function formatSize(bytes) {
    if (bytes < 1024) return bytes + ' o';
    if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' Ko';
    return (bytes / 1024 / 1024).toFixed(1) + ' Mo';
}

function handleFile(file) {
    const ext = file.name.split('.').pop().toLowerCase();
    const cat = getCategory(ext);

    if (!cat) {
        alert('Format non supporté : .' + ext);
        return;
    }

    document.getElementById('fileName').textContent = file.name;
    document.getElementById('fileMeta').textContent = ext.toUpperCase() + ' · ' + formatSize(file.size) + ' · Détecté automatiquement';
    document.getElementById('fileInfoBox').style.display = 'flex';

    const pills = document.getElementById('pills');
    pills.innerHTML = '';
    let first = true;
    targets[cat].forEach(t => {
        if (t === ext) return;
        const btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'pill' + (first ? ' selected' : '');
        btn.textContent = t.toUpperCase();
        if (first) {
            document.getElementById('targetInput').value = t;
            first = false;
        }
        btn.addEventListener('click', () => {
            document.querySelectorAll('.pill').forEach(p => p.classList.remove('selected'));
            btn.classList.add('selected');
            document.getElementById('targetInput').value = t;
        });
        pills.appendChild(btn);
    });

    document.getElementById('targetSection').style.display = 'block';
}

document.getElementById('fileInput').addEventListener('change', function() {
    if (this.files[0]) handleFile(this.files[0]);
});

const dropzone = document.getElementById('dropzone');
dropzone.addEventListener('dragover', e => { e.preventDefault(); dropzone.classList.add('drag-over'); });
dropzone.addEventListener('dragleave', () => dropzone.classList.remove('drag-over'));
dropzone.addEventListener('drop', e => {
    e.preventDefault();
    dropzone.classList.remove('drag-over');
    const file = e.dataTransfer.files[0];
    if (file) {
        const dt = new DataTransfer();
        dt.items.add(file);
        document.getElementById('fileInput').files = dt.files;
        handleFile(file);
    }
});
</script>
</body>
</html>