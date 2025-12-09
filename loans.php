<?php
require_once 'config.php';
require_once 'functions.php';

$loans = get_loans_with_stats($pdo);
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Loans & Instalments</title>
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
            <a href="loans.php" class="active">
                <span class="status-dot"></span>
                <span>Loans</span>
            </a>
        </nav>

        <div class="sidebar-footer">
            <div class="sidebar-footer-label">
                <span>Environment</span>
                <span class="sidebar-footer-pill">Preview</span>
            </div>
            <div>
                Each loan links to a liability account.  
                Outstanding amount comes from account balance.
            </div>
        </div>
    </aside>

    <!-- Main -->
    <main class="main">
        <header class="topbar">
            <div class="topbar-left">
                <div class="breadcrumb-row">
                    <span class="breadcrumb-pill">Personal</span>
                    <span>Loans & instalments</span>
                </div>
                <div class="topbar-title">Loans & Instalments</div>
                <div class="topbar-subtitle">
                    See all debts, outstanding balance, monthly instalment and whether you’re on track.
                </div>
            </div>
            <div class="topbar-right">
                <a href="loan_form.php" class="btn-primary-cta">+ New loan</a>
            </div>
        </header>

        <div class="main-content">
            <div class="summary-card">
                <div class="summary-header">
                    <div>
                        <div class="summary-title">Loan overview</div>
                        <div class="summary-subtitle">
                            Outstanding = balance of linked account. Progress ≈ principal paid / original principal.
                        </div>
                    </div>
                </div>

                <div class="table-wrapper">
                    <table class="table table-light-sm mb-0">
                        <thead>
                        <tr>
                            <th>Loan</th>
                            <th>Lender</th>
                            <th>Account</th>
                            <th class="text-end">Principal</th>
                            <th class="text-end">Outstanding</th>
                            <th class="text-end">Monthly instalment</th>
                            <th>Term</th>
                            <th>Next due</th>
                            <th>Status</th>
                            <th></th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php if (!$loans): ?>
                            <tr>
                                <td colspan="10" class="text-center text-muted">
                                    No loans configured yet. Click “New loan” to add one.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($loans as $loan): ?>
                                <tr>
                                    <td>
                                        <strong><?= htmlspecialchars($loan['name']) ?></strong><br>
                                        <span class="text-muted small">
                                            Started <?= htmlspecialchars($loan['start_date']) ?>
                                            <?php if ($loan['months_elapsed'] !== null): ?>
                                                · <?= (int)$loan['months_elapsed'] ?> months elapsed
                                            <?php endif; ?>
                                        </span>
                                    </td>
                                    <td><?= htmlspecialchars($loan['lender'] ?? '-') ?></td>
                                    <td>
                                        <?= htmlspecialchars($loan['account_name']) ?><br>
                                        <span class="text-muted small">
                                            <?= htmlspecialchars(human_account_type($loan['account_type'])) ?>
                                        </span>
                                    </td>
                                    <td class="text-end"><?= number_format($loan['principal'], 2) ?></td>
                                    <td class="text-end"><?= number_format($loan['outstanding'], 2) ?></td>
                                    <td class="text-end">
                                        <?= $loan['monthly_payment'] !== null
                                            ? number_format($loan['monthly_payment'], 2)
                                            : '-' ?>
                                    </td>
                                    <td>
                                        <?php if ($loan['term_months'] !== null): ?>
                                            <?= (int)$loan['term_months'] ?> months<br>
                                            <span class="text-muted small">
                                                <?= (int)$loan['months_remaining'] ?> remaining
                                            </span>
                                        <?php else: ?>
                                            <span class="text-muted">Open-ended</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($loan['next_due_date']): ?>
                                            <?= htmlspecialchars($loan['next_due_date']) ?><br>
                                            <span class="text-muted small">
                                                day <?= (int)$loan['due_day'] ?> of month
                                            </span>
                                        <?php else: ?>
                                            <span class="text-muted">–</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="pill <?= htmlspecialchars($loan['status_class']) ?>">
                                            <?= htmlspecialchars($loan['status']) ?>
                                        </span><br>
                                        <?php if ($loan['progress_pct'] !== null): ?>
                                            <span class="text-muted small">
                                                <?= number_format($loan['progress_pct'], 1) ?>% paid
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end">
                                        <a href="loan_form.php?id=<?= (int)$loan['id'] ?>"
                                           class="btn btn-sm btn-outline-secondary rounded-pill">
                                            Edit
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
