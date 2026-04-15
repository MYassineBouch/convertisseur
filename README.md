# Convertisseur de fichiers

## Prérequis Windows

- PHP 8.1+
- MySQL 
## Structure MVC

```
converter/
├── config.php               ← config BDD
├── models/
│   ├── Database.php         ← connexion PDO + init table
│   └── ConversionLog.php    ← CRUD historique
├── controllers/
│   └── ConversionController.php  ← logique métier + conversions
├── views/
│   ├── home.php             ← page principale
│   ├── download.php         ← page téléchargement
│   └── history.php          ← historique
└── public/                  ← document root
    ├── index.php            ← point d'entrée
    ├── css/style.css
    ├── uploads/             ← fichiers temporaires
    └── converted/           ← fichiers convertis
```

## Formats supportés

| Catégorie | Source | Cible |
|-----------|--------|-------|
| Images | PNG, JPG, WEBP, GIF, BMP, TIFF, ICO | PNG, JPG, WEBP, BMP, ICO |
| Données | CSV, JSON, XML, YAML, TOML | CSV, JSON, XML, YAML, HTML |
| Documents | TXT, HTML, MD | TXT, HTML, MD |
