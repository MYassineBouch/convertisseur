<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Historique</title>
<link rel="stylesheet" href="css/style.css">
</head>
<body>
<nav>
    <a href="index.php">CONVERTIR</a>
    <a href="index.php?action=history">LOG DE CONVERSIONS</a>
</nav>

<div class="card">
    <p class="app-title">Projet Alternatif</p>
    <p class="app-sub">Historique des conversions</p>

    <div class="history-actions">
        <a href="index.php?action=export_history" class="btn-browse">Exporter CSV</a>
        <form action="index.php?action=delete_history" method="POST" style="display:inline"
              onsubmit="return confirm('Supprimer tout l\'historique ?')">
            <button type="submit" class="btn-delete">Tout supprimer</button>
        </form>
    </div>

    <?php if (empty($logs)): ?>
        <p style="font-size:13px;color:#888">Aucune conversion enregistrée.</p>
    <?php else: ?>
    <table>
        <tr>
            <th>Fichier</th>
            <th>Source</th>
            <th>Cible</th>
            <th>Statut</th>
            <th>Date</th>
        </tr>
        <?php foreach ($logs as $log): ?>
        <tr class="status-<?= $log['status'] ?>">
            <td><?= htmlspecialchars($log['filename']) ?></td>
            <td><?= strtoupper(htmlspecialchars($log['source_format'])) ?></td>
            <td><?= strtoupper(htmlspecialchars($log['destination_format'])) ?></td>
            <td><?= $log['status'] === 'success' ? '✓' : '✗' ?></td>
            <td><?= $log['date_time'] ?></td>
        </tr>
        <?php endforeach; ?>
    </table>
    <?php endif; ?>
</div>
</body>
</html>