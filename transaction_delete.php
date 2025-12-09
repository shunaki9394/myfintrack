<?php
require_once 'config.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id > 0) {
    $stmt = $pdo->prepare("UPDATE transactions SET deleted_at = NOW() WHERE id = ?");
    $stmt->execute([$id]);
}

header('Location: transactions.php');
exit;
