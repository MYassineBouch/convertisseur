<?php
session_start();
require_once '../models/Database.php';
require_once '../models/ConversionLog.php';
require_once '../controllers/ConversionController.php';

$controller = new ConversionController();

$action = $_GET['action'] ?? 'home';

match($action) {
    'convert'        => $controller->convert(),
    'download'       => $controller->download(),
    'history'        => $controller->history(),
    'delete_history' => $controller->deleteHistory(),
    'export_history' => $controller->exportHistory(),
    default          => $controller->home(),
};
