<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Téléchargement</title>
<link rel="stylesheet" href="css/style.css">
</head>
<body>
<nav>
    <a href="index.php">Convertir</a>
    <a href="index.php?action=history">Historique</a>
</nav>

<div class="card">
    <p class="app-title">Projet Alternatif</p>
    <p class="app-sub">Convertisseur de fichiers universel</p>

    <div class="success-box">
        <div class="success-icon">
            <svg width="20" height="20" viewBox="0 0 20 20" fill="none">
                <path d="M4 10l5 5 7-8" stroke="#3b6d11" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
        </div>
        <h2>Conversion réussie</h2>
        <p><?= htmlspecialchars($file) ?></p>
    </div>

    <div class="file-info">
        <div class="file-icon">
            <svg width="16" height="16" viewBox="0 0 16 16" fill="none">
                <rect x="2" y="1" width="12" height="14" rx="2.5" stroke="#185fa5" stroke-width="1.2"/>
                <path d="M5 6h6M5 9h4" stroke="#185fa5" stroke-width="1.2" stroke-linecap="round"/>
            </svg>
        </div>
        <div>
            <p class="name"><?= htmlspecialchars($file) ?></p>
            <p class="meta">Prêt au téléchargement</p>
        </div>
    </div>

    <a href="converted/<?= rawurlencode($file) ?>" download="<?= htmlspecialchars($file) ?>" class="btn-convert">Télécharger</a>
    <p class="ttl-hint" style="margin-top:12px">Fichier disponible pendant 30 minutes, puis supprimé du serveur.</p>

    <div class="divider" style="margin-top:20px"></div>
    <a href="index.php" class="btn-new">Nouvelle conversion</a>
</div>
</body>
</html>