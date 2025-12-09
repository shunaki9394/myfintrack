<?php
require_once 'config.php';
require_once 'functions.php';

$plans = get_installment_plans_with_stats($pdo);
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Card Installments</title>
    <link rel="stylesheet"
          href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="styles.css?v=1">
</head>
<body>
<div class="app-shell">

    <!-- Sidebar -->
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
            <a href="categories.php">
                <span class="status-dot"></span>
                <span>Categories</span>
            </a>
            <a href="loans.php">
                <span class="status-dot"></span>
                <span>Loans</span>
            </a>
            <a href="installments.php" class="active">
                <span class="status-dot"></span>
                <span>Installments</span>
            </a>
        </nav>

        <div class="sidebar-footer">
            <div class="sidebar-footer-label">
                <span>Environment</span>
                <span class="sidebar-footer-pill">Preview</span>
            </div>
            <div>
                Each row is a card instalment plan (e.g. TV, phone).
                Monthly charges come from your card provider.
            </div>
        </div>
    </aside>

    <!-- Main -->
    <main class="main">
        <header class="topbar">
            <div class="topbar-left">
                <div class="breadcrumb-row">
                    <span class="breadcrumb-pill">Personal</span>
                    <span>Card installments</span>
                </div>
                <div class="topbar-title">Card Installment Plans</div>
                <div class="topbar-subtitle">
                    Separate tracking for instalment purchases on your credit cards.
                </div>
            </div>
            <div class="topbar-right">
                <a href="installment_form.php" class="btn-primary-cta">+ New plan</a>
            </div>
        </header>

        <div class="main-content">
            <div class="summary-card">

                <div class="summary-header">
                    <div>
                        <div class="summary-title">Overview</div>
                        <div class="summary-subtitle">
                            One row per instalment plan (TV, phone, PC, etc) on your cards.
                        </div>
                    </div>
                </div>

                <div class="table-wrapper">
                    <table class="table table-light-sm mb-0">
                        <thead>
                        <tr>
                            <th>Plan</th>
                            <th>Merchant</th>
                            <th>Card</th>
                            <th class="text-end">Original amount</th>
                            <th class="text-end">Monthly instalment</th>
                            <th>Term</th>
                            <th>Next due</th>
                            <th>Status</th>
                            <th class="text-end">Actions</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php if (!$plans): ?>
                            <tr>
                                <td colspan="9" class="text-center text-muted">
                                    No installment plans yet. Click “New plan” to add one.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($plans as $p): ?>
                                <tr>
                                    <td>
                                        <strong><?= htmlspecialchars($p['title']) ?></strong><br>
                                        <span class="text-muted small">
                                            Started <?= htmlspecialchars($p['start_date']) ?>
                                            <?php if ($p['months_elapsed'] !== null): ?>
                                                · <?= (int)$p['months_elapsed'] ?> months elapsed
                                            <?php endif; ?>
                                        </span>
                                    </td>
                                    <td><?= htmlspecialchars($p['merchant'] ?? '-') ?></td>
                                    <td>
                                        <?= htmlspecialchars($p['account_name']) ?><br>
                                        <span class="text-muted small">
                                            <?= htmlspecialchars(human_account_type($p['account_type'])) ?>
                                        </span>
                                    </td>
                                    <td class="text-end"><?= number_format($p['original_amount'], 2) ?></td>
                                    <td class="text-end"><?= number_format($p['monthly_payment'], 2) ?></td>
                                    <td>
                                        <?= (int)$p['term_months'] ?> months<br>
                                        <span class="text-muted small">
                                            <?= (int)$p['remaining_count'] ?> remaining
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($p['next_due_date']): ?>
                                            <?= htmlspecialchars($p['next_due_date']) ?><br>
                                            <?php if ($p['due_day']): ?>
                                                <span class="text-muted small">
                                                    day <?= (int)$p['due_day'] ?> of month
                                                </span>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <span class="text-muted">–</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="pill <?= htmlspecialchars($p['status_class']) ?>">
                                            <?= htmlspecialchars($p['status']) ?>
                                        </span><br>
                                        <?php if ($p['progress_pct'] !== null): ?>
                                            <span class="text-muted small">
                                                <?= number_format($p['progress_pct'], 1) ?>% paid
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end">
                                        <a href="installment_form.php?id=<?= (int)$p['id'] ?>"
                                           class="btn btn-sm btn-outline-secondary rounded-pill me-1">
                                            Edit
                                        </a>
                                        <a href="installment_delete.php?id=<?= (int)$p['id'] ?>"
                                           class="btn btn-sm btn-outline-danger rounded-pill"
                                           onclick="return confirm('Delete this installment plan? This does not change card balance history.');">
                                            Delete
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>

            </div>
        </div>
    </main>
</div>
</body>
</html>
