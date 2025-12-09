<?php
require_once 'config.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id > 0) {
    $stmt = $pdo->prepare("DELETE FROM installment_plans WHERE id = ?");
    $stmt->execute([$id]);
}

header('Location: installments.php');
exit;
