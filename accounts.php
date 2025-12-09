<?php
require_once 'config.php';
require_once 'functions.php';

$accounts = get_account_balances($pdo);
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Accounts</title>
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
            <a href="accounts.php" class="active">
                <span class="status-dot"></span>
                <span>Accounts</span>
            </a>
            <a href="categories.php">
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
                Asset accounts (bank, cash, investment) and liability accounts (credit card, loans).
            </div>
        </div>
    </aside>

    <main class="main">
        <header class="topbar">
            <div class="topbar-left">
                <div class="breadcrumb-row">
                    <span class="breadcrumb-pill">Personal</span>
                    <span>Accounts</span>
                </div>
                <div class="topbar-title">Accounts</div>
                <div class="topbar-subtitle">
                    Define bank, cash, StashAway and loan/credit-card accounts. Balances are calculated from transactions.
                </div>
            </div>
            <div class="topbar-right">
                <a href="account_form.php" class="btn-primary-cta">+ New account</a>
            </div>
        </header>

        <div class="main-content">
            <div class="summary-card">
                <div class="summary-header">
                    <div>
                        <div class="summary-title">Account list</div>
                        <div class="summary-subtitle">Opening balance + movements from all transactions.</div>
                    </div>
                </div>

                <div class="table-wrapper">
                    <table class="table table-light-sm mb-0">
                        <thead>
                        <tr>
                            <th>Name</th>
                            <th>Type</th>
                            <th>Liquid?</th>
                            <th>In Net Worth?</th>
                            <th class="text-end">Opening balance</th>
                            <th class="text-end">Current balance</th>
                            <th></th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($accounts as $acc): ?>
                            <?php
                            $isLiab = is_liability_type($acc['type']);
                            $open = (float)$acc['opening_balance'];
                            $bal = (float)$acc['balance'];
                            ?>
                            <tr>
                                <td><?= htmlspecialchars($acc['name']) ?></td>
                                <td><?= htmlspecialchars(human_account_type($acc['type'])) ?></td>
                                <td><?= $acc['is_liquid'] ? 'Yes' : 'No' ?></td>
                                <td><?= $acc['is_net_worth'] ? 'Yes' : 'No' ?></td>
                                <td class="text-end">
                                    <?= $isLiab
                                        ? 'RM' . number_format(abs($open), 2) . ' owed'
                                        : format_money($open) ?>
                                </td>
                                <td class="text-end">
                                    <?= $isLiab
                                        ? 'RM' . number_format(abs($bal), 2) . ' owed'
                                        : format_money($bal) ?>
                                </td>
                                <td class="text-end">
                                    <a href="account_form.php?id=<?= (int)$acc['id'] ?>" class="btn btn-sm btn-outline-secondary rounded-pill">
                                        Edit
                                    </a>
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
