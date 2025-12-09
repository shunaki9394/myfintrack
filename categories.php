<?php
require_once 'config.php';
require_once 'functions.php';

$stmt = $pdo->query("SELECT * FROM categories ORDER BY kind, name");
$categories = $stmt->fetchAll();
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Categories</title>
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
                Income & expense categories.  
                Mark some expense categories as debt payments for DSR calculation.
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
                <div class="topbar-title">Categories</div>
                <div class="topbar-subtitle">
                    Organise your transactions into income/expense buckets.
                </div>
            </div>
            <div class="topbar-right">
                <a href="category_form.php" class="btn-primary-cta">+ New category</a>
            </div>
        </header>

        <div class="main-content">
            <div class="summary-card">
                <div class="summary-header">
                    <div>
                        <div class="summary-title">All categories</div>
                    </div>
                </div>

                <div class="table-wrapper">
                    <table class="table table-light-sm mb-0">
                        <thead>
                        <tr>
                            <th>Name</th>
                            <th>Kind</th>
                            <th>Debt payment?</th>
                            <th></th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($categories as $cat): ?>
                            <tr>
                                <td><?= htmlspecialchars($cat['name']) ?></td>
                                <td><?= htmlspecialchars(ucfirst($cat['kind'])) ?></td>
                                <td><?= $cat['is_debt_payment'] ? 'Yes' : 'No' ?></td>
                                <td class="text-end">
                                    <a href="category_form.php?id=<?= (int)$cat['id'] ?>"
                                       class="btn btn-sm btn-outline-secondary rounded-pill">Edit</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

            </div>
        </div>
    </main>
</div>
</body>
</html>
