# Convertisseur de fichiers

## Prérequis Windows

- PHP 8.1+ → https://windows.php.net/download (zip "Thread Safe")
- MySQL → https://dev.mysql.com/downloads/installer/

## Installation

### 1. PHP

Télécharge le zip PHP, extrais dans `C:\php`.
Ajoute `C:\php` au PATH Windows.

Active ces extensions dans `C:\php\php.ini` (décommente en retirant le `;`) :
```
extension=gd
extension=pdo_mysql
extension=mysqli
extension=fileinfo
```

### 2. MySQL

Installe MySQL, lance MySQL Workbench ou le CLI et crée la base :
```sql
CREATE DATABASE converter CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

Si ton mot de passe root n'est pas vide, modifie `config.php` :
```php
'db_pass' => 'ton_mot_de_passe',
```

### 3. Lancer l'app

Ouvre un terminal dans le dossier du projet, puis :
```
php -S localhost:8000 -t public
```

Ouvre http://localhost:8000 dans ton navigateur.

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
