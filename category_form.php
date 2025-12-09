<?php
require_once 'config.php';
require_once 'functions.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : null;
$category = ['id' => null, 'name' => '', 'kind' => 'expense', 'is_debt_payment' => 0];
$error = '';

if ($id) {
    $stmt = $pdo->prepare("SELECT * FROM categories WHERE id = ?");
    $stmt->execute([$id]);
    if ($row = $stmt->fetch()) {
        $category = $row;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = !empty($_POST['id']) ? (int)$_POST['id'] : null;
    $name = trim($_POST['name'] ?? '');
    $kind = $_POST['kind'] ?? 'expense';
    $is_debt_payment = isset($_POST['is_debt_payment']) ? 1 : 0;

    if ($name === '') {
        $error = 'Name is required.';
    } else {
        if ($id) {
            $stmt = $pdo->prepare("
                UPDATE categories SET name = ?, kind = ?, is_debt_payment = ? WHERE id = ?
            ");
            $stmt->execute([$name, $kind, $is_debt_payment, $id]);
        } else {
            $stmt = $pdo->prepare("
                INSERT INTO categories (name, kind, is_debt_payment) VALUES (?, ?, ?)
            ");
            $stmt->execute([$name, $kind, $is_debt_payment]);
        }
        header('Location: categories.php');
        exit;
    }

    $category = [
        'id' => $id,
        'name' => $name,
        'kind' => $kind,
        'is_debt_payment' => $is_debt_payment,
    ];
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title><?= $category['id'] ? 'Edit category' : 'New category' ?></title>
    <link rel="stylesheet"
          href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="styles.css">
</head>
<body>
<div class="app-shell">

    <aside class="sidebar">
        <div class="sidebar-header">
            <div class="sidebar-logo">FH</div>
            <div class="sidebar-title">
                <span>Finance Desk</span>
                <span>Personal health monitor</span>
            </div>
        </div>

        <nav class="sidebar-nav">
            <a href="index.php">
                <span class="status-dot"></span>
                <span>Dashboard</span>
            </a>
            <a href="transactions.php">
                <span class="status-dot"></span>
                <span>Transactions</span>
            </a>
            <a href="accounts.php">
                <span class="status-dot"></span>
                <span>Accounts</span>
            </a>
            <a href="categories.php" class="active">
                <span class="status-dot"></span>
                <span>Categories</span>
            </a>
        </nav>

        <div class="sidebar-footer">
            <div class="sidebar-footer-label">
                <span>Environment</span>
                <span class="sidebar-footer-pill">Preview</span>
            </div>
            <div>
                Mark loan / card payment categories as “debt payment” to feed DSR.
            </div>
        </div>
    </aside>

    <main class="main">
        <header class="topbar">
            <div class="topbar-left">
                <div class="breadcrumb-row">
                    <span class="breadcrumb-pill">Personal</span>
                    <span>Categories</span>
                </div>
                <div class="topbar-title">
                    <?= $category['id'] ? 'Edit category' : 'New category' ?>
                </div>
            </div>
        </header>

        <div class="main-content">
            <div class="form-card">
                <?php if ($error): ?>
                    <div class="alert alert-danger small mb-3"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>

                <form method="post" class="row g-3">
                    <input type="hidden" name="id" value="<?= htmlspecialchars((string)$category['id']) ?>">

                    <div class="col-md-4">
                        <label class="form-label">Name</label>
                        <input type="text" name="name" class="form-control form-control-sm"
                               value="<?= htmlspecialchars($category['name']) ?>" required>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Kind</label>
                        <select name="kind" class="form-select form-select-sm">
                            <option value="income" <?= $category['kind'] === 'income' ? 'selected' : '' ?>>Income</option>
                            <option value="expense" <?= $category['kind'] === 'expense' ? 'selected' : '' ?>>Expense</option>
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Debt payment?</label>
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" id="is_debt_payment"
                                   name="is_debt_payment" value="1"
                                   <?= $category['is_debt_payment'] ? 'checked' : '' ?>>
                            <label class="form-check-label" for="is_debt_payment">
                                Count in Debt Service Ratio
                            </label>
                        </div>
                        <div class="form-text">
                            Tick for loan instalments, credit card payments, etc.
                        </div>
                    </div>

                    <div class="col-12 d-flex gap-2">
                        <button class="btn btn-dark btn-sm rounded-pill">Save category</button>
                        <a href="categories.php" class="btn btn-outline-secondary btn-sm rounded-pill">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </main>
</div>
</body>
</html>
