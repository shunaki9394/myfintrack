<?php
require_once 'config.php';
require_once 'functions.php';

$month = isset($_GET['month']) ? $_GET['month'] : get_default_month();
$metrics = get_month_metrics($pdo, $month);
$monthOptions = get_month_options(12);

// History for last 6 months
$historyMonths = get_month_options(6);

// New: loan + installment summaries
$loanSummary = get_loan_summary($pdo);
$instSummary = get_installment_summary($pdo);
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Financial Health Dashboard</title>
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
            <a href="index.php" class="active">
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
            <a href="installments.php">
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
                MVP finance tracker – daily entries go into Transactions.
                Dashboard is read-only.
            </div>
        </div>
    </aside>

    <!-- Main -->
    <main class="main">
        <header class="topbar">
            <div class="topbar-left">
                <div class="breadcrumb-row">
                    <span class="breadcrumb-pill">Personal</span>
                    <span>Financial Health workspace</span>
                </div>
                <div class="topbar-title">Financial Overview</div>
                <div class="topbar-subtitle">
                    Month-by-month view of income, expenses, net worth, and debt pressure.
                </div>
            </div>
            <div class="topbar-right">
                <form method="get" class="d-flex align-items-center gap-2">
                    <label class="small text-muted">Month</label>
                    <select name="month" class="form-select form-select-sm">
                        <?php foreach ($monthOptions as $opt): ?>
                            <option value="<?= htmlspecialchars($opt['value']) ?>"
                                <?= $opt['value'] === $month ? 'selected' : '' ?>>
                                <?= htmlspecialchars($opt['label']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <button class="btn-primary-cta btn-sm" type="submit">Apply</button>
                </form>
            </div>
        </header>

        <div class="main-content">

            <!-- KPI row -->
            <div class="kpi-row">
                <div class="kpi-card">
                    <div class="kpi-label">Net worth</div>
                    <div class="kpi-value">
                        <?= format_money($metrics['net_worth']) ?>
                    </div>
                    <div class="kpi-sub">
                        Assets: <?= format_money($metrics['total_assets']) ?> ·
                        Liabilities: <?= format_money(-$metrics['total_liabilities']) ?>
                    </div>
                    <div class="kpi-highlight">
                        Net Worth = Assets − Liabilities
                    </div>
                </div>

                <div class="kpi-card">
                    <div class="kpi-label">Savings (<?= htmlspecialchars($metrics['month_label']) ?>)</div>
                    <div class="kpi-value">
                        <?= format_money($metrics['savings']) ?>
                    </div>
                    <div class="kpi-sub">
                        Income: <?= format_money($metrics['income']) ?> ·
                        Expenses: <?= format_money($metrics['expenses']) ?>
                    </div>
                    <div class="kpi-highlight">
                        Savings = Income − Expenses
                    </div>
                </div>

                <div class="kpi-card">
                    <div class="kpi-label">Savings rate</div>
                    <div class="kpi-value">
                        <?= format_percent($metrics['savings_rate']) ?>
                    </div>
                    <div class="kpi-sub">
                        Portion of income you kept this month.
                    </div>
                    <div class="kpi-highlight">
                        20–30%+ is a strong long-term target.
                    </div>
                </div>

                <div class="kpi-card">
                    <div class="kpi-label">Emergency fund</div>
                    <div class="kpi-value">
                        <?= $metrics['emergency_months'] === null
                            ? '-'
                            : number_format($metrics['emergency_months'], 1) . ' months' ?>
                    </div>
                    <div class="kpi-sub">
                        Liquid assets: <?= format_money($metrics['liquid_assets']) ?>
                    </div>
                    <div class="kpi-highlight">
                        Emergency Months = Liquid Assets ÷ Monthly Expenses
                    </div>
                </div>
            </div>

            <!-- Ratios + accounts -->
            <div class="summary-grid">
                <div class="summary-card">
                    <div class="summary-header">
                        <div>
                            <div class="summary-title">Ratios overview</div>
                            <div class="summary-subtitle">
                                Quick view of safety & debt pressure (for <?= htmlspecialchars($metrics['month_label']) ?>).
                            </div>
                        </div>
                    </div>

                    <div class="row g-2">
                        <div class="col-md-4">
                            <div class="small mb-1 text-muted">Debt Service Ratio</div>
                            <div class="d-flex align-items-baseline gap-2">
                                <span class="fw-semibold">
                                    <?= format_percent($metrics['dsr']) ?>
                                </span>
                                <?php
                                $dsr = $metrics['dsr'];
                                if ($dsr === null) {
                                    $dsrPill = '';
                                } elseif ($dsr <= 30) {
                                    $dsrPill = '<span class="pill pill-positive">Comfortable</span>';
                                } elseif ($dsr <= 50) {
                                    $dsrPill = '<span class="pill pill-warning">Watch</span>';
                                } else {
                                    $dsrPill = '<span class="pill pill-danger">High</span>';
                                }
                                echo $dsrPill;
                                ?>
                            </div>
                            <div class="small text-muted mt-1">
                                Debt payments: <?= format_money($metrics['debt_payments']) ?>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="small mb-1 text-muted">Emergency buffer</div>
                            <div class="d-flex align-items-baseline gap-2">
                                <span class="fw-semibold">
                                    <?= $metrics['emergency_months'] === null
                                        ? '-'
                                        : number_format($metrics['emergency_months'], 1) . ' months' ?>
                                </span>
                                <?php
                                $em = $metrics['emergency_months'];
                                if ($em === null) {
                                    $emPill = '';
                                } elseif ($em < 1) {
                                    $emPill = '<span class="pill pill-danger">Very thin</span>';
                                } elseif ($em < 3) {
                                    $emPill = '<span class="pill pill-warning">Building</span>';
                                } else {
                                    $emPill = '<span class="pill pill-positive">Solid</span>';
                                }
                                echo $emPill;
                                ?>
                            </div>
                            <div class="small text-muted mt-1">
                                Target: 3–6 months of essential expenses.
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="small mb-1 text-muted">How to use</div>
                            <div class="small text-muted">
                                Enter all daily income & expenses in <strong>Transactions</strong>.  
                                Define your bank/card/StashAway as <strong>Accounts</strong>.  
                                This page updates automatically.
                            </div>
                        </div>
                    </div>
                </div>

                <div class="summary-card">
                    <div class="summary-header">
                        <div>
                            <div class="summary-title">Accounts snapshot</div>
                            <div class="summary-subtitle">
                                Balances as of end of <?= htmlspecialchars($metrics['month_label']) ?>.
                            </div>
                        </div>
                    </div>
                    <div class="small">
                        <?php foreach ($metrics['accounts'] as $acc): ?>
                            <?php
                            $bal = (float)$acc['balance'];
                            $label = human_account_type($acc['type']);
                            $isLiab = is_liability_type($acc['type']);
                            ?>
                            <div class="d-flex justify-content-between">
                                <span>
                                    <?= htmlspecialchars($acc['name']) ?>
                                    <span class="text-muted">· <?= htmlspecialchars($label) ?></span>
                                </span>
                                <span>
                                    <?= $isLiab
                                        ? 'RM' . number_format(abs($bal), 2) . ' owed'
                                        : format_money($bal) ?>
                                </span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Debt snapshot -->
            <h2 class="h6 mt-4 mb-2">Debt snapshot</h2>
            <div class="summary-grid">
                <div class="summary-card">
                    <div class="summary-title">Loans</div>
                    <div class="summary-subtitle">
                        Fixed-term bank / personal / car loans.
                    </div>
                    <div class="mt-2 small">
                        <div>
                            Active loans:
                            <strong><?= (int)$loanSummary['active_count'] ?></strong>
                        </div>
                        <div>
                            Monthly instalments:
                            <strong><?= format_money($loanSummary['monthly_total']) ?></strong>
                        </div>
                        <div>
                            Due in next 30 days:
                            <strong><?= (int)$loanSummary['due_soon_count'] ?></strong>
                        </div>
                    </div>
                    <div class="mt-2">
                        <a href="loans.php" class="btn btn-sm btn-outline-secondary rounded-pill">
                            View loans
                        </a>
                    </div>
                </div>

                <div class="summary-card">
                    <div class="summary-title">Card installments</div>
                    <div class="summary-subtitle">
                        Instalment purchases on credit cards.
                    </div>
                    <div class="mt-2 small">
                        <div>
                            Active plans:
                            <strong><?= (int)$instSummary['active_count'] ?></strong>
                        </div>
                        <div>
                            Monthly instalments:
                            <strong><?= format_money($instSummary['monthly_total']) ?></strong>
                        </div>
                        <div>
                            Due in next 30 days:
                            <strong><?= (int)$instSummary['due_soon_count'] ?></strong>
                        </div>
                    </div>
                    <div class="mt-2">
                        <a href="installments.php" class="btn btn-sm btn-outline-secondary rounded-pill">
                            View installments
                        </a>
                    </div>
                </div>
            </div>

            <!-- History table -->
            <h2 class="h6 mt-4 mb-2">History (last 6 months)</h2>
            <div class="table-wrapper">
                <table class="table table-light-sm mb-0">
                    <thead>
                    <tr>
                        <th>Month</th>
                        <th class="text-end">Income</th>
                        <th class="text-end">Expenses</th>
                        <th class="text-end">Savings</th>
                        <th class="text-end">Savings rate</th>
                        <th class="text-end">Net worth</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($historyMonths as $opt): ?>
                        <?php $hm = get_month_metrics($pdo, $opt['value']); ?>
                        <tr>
                            <td><?= htmlspecialchars($hm['month_label']) ?></td>
                            <td class="text-end"><?= number_format($hm['income'], 2) ?></td>
                            <td class="text-end"><?= number_format($hm['expenses'], 2) ?></td>
                            <td class="text-end"><?= number_format($hm['savings'], 2) ?></td>
                            <td class="text-end"><?= format_percent($hm['savings_rate']) ?></td>
                            <td class="text-end"><?= number_format($hm['net_worth'], 2) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

        </div>
    </main>
</div>
</body>
</html>
