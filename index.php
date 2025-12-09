<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/layout.php';

$selectedMonth = normalize_month($_GET['month'] ?? date('Y-m'));
$monthLabel    = date('F Y', strtotime($selectedMonth . '-01'));

$monthOptions   = get_month_options(6);
$metrics        = get_month_metrics($selectedMonth);
$loanSummary    = get_loan_summary();
$instSummary    = get_installment_summary();
[$start, $end]  = get_month_start_end($selectedMonth);
[$accounts, $balances] = get_account_balances($end);
$historyRows    = get_history_rows(6);

render_layout_start('Financial Overview', 'dashboard');
?>

<!-- Workspace header (small chips at the top) -->
<div class="workspace-header">
    <div class="workspace-left">
        <span class="workspace-pill workspace-pill-active">Personal</span>
        <span class="workspace-separator">·</span>
        <span class="workspace-label">Financial Health workspace</span>
    </div>
    <div class="workspace-right">
        <span class="acting-as-pill">Acting as <strong>Self</strong></span>
    </div>
</div>

<div class="page-header">
    <div>
        <span class="page-label">dashboard</span>
        <h1>Financial Overview</h1>
        <p>Month-by-month view of income, expenses, net worth, and debt pressure.</p>
    </div>

    <form method="get" class="month-selector">
        <label>
            Month
            <select name="month" onchange="this.form.submit()">
                <?php foreach ($monthOptions as $opt): ?>
                    <option value="<?= h($opt['value']) ?>"
                        <?= $opt['value'] === $selectedMonth ? 'selected' : '' ?>>
                        <?= h($opt['label']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>
        <button type="submit" class="btn-secondary">Apply</button>
    </form>
</div>

<!-- Top metric cards -->
<div class="cards-row">
    <!-- Net worth -->
    <section class="card">
        <h2>Net worth</h2>
        <p class="metric-large">RM<?= number_format($metrics['netWorth'], 2) ?></p>
        <p class="metric-sub">
            Assets: RM<?= number_format($metrics['totalAssets'], 2) ?> ·
            Liabilities: RM<?= number_format($metrics['totalLiabilities'], 2) ?>
        </p>
        <p class="metric-link">Net Worth = Assets − Liabilities</p>
    </section>

    <!-- Savings -->
    <section class="card">
        <h2>Savings (<?= h($monthLabel) ?>)</h2>
        <p class="metric-large">RM<?= number_format($metrics['savings'], 2) ?></p>
        <p class="metric-sub">
            Income: RM<?= number_format($metrics['income'], 2) ?> ·
            Expenses: RM<?= number_format($metrics['expenses'], 2) ?>
        </p>
        <p class="metric-link">Savings = Income − Expenses</p>
    </section>

    <!-- Savings rate -->
    <section class="card">
        <h2>Savings rate</h2>
        <p class="metric-large">
            <?= $metrics['savingsRate'] !== null
                ? number_format($metrics['savingsRate'], 1) . '%'
                : '-' ?>
        </p>
        <p class="metric-sub">
            Portion of income you kept this month.
        </p>
        <p class="metric-link">20–30%+ is a strong long-term target.</p>
    </section>

    <!-- Emergency fund -->
    <section class="card">
        <h2>Emergency fund</h2>
        <p class="metric-large">
            <?= $metrics['emergencyMonths'] !== null
                ? number_format($metrics['emergencyMonths'], 1) . ' months'
                : '-' ?>
        </p>
        <p class="metric-sub">
            Liquid assets: RM<?= number_format($metrics['liquidAssets'], 2) ?>
        </p>
        <p class="metric-link">Target: 3–6 months of essential expenses.</p>
    </section>
</div>

<!-- Ratios overview + accounts snapshot -->
<div class="cards-row">
    <section class="card card-wide">
        <h3>Ratios overview</h3>
        <p class="card-description">Quick view of safety &amp; debt pressure (for <?= h($monthLabel) ?>).</p>

        <div class="two-column">
            <div>
                <h4>Debt Service Ratio</h4>
                <p class="metric-med">
                    <?= $metrics['debtServiceRatio'] !== null
                        ? number_format($metrics['debtServiceRatio'], 1) . '%'
                        : '-' ?>
                </p>
                <p class="metric-sub">
                    Debt payments: RM<?= number_format($metrics['debtPayments'], 2) ?>.
                </p>
            </div>
            <div>
                <h4>Emergency buffer</h4>
                <p class="metric-med">
                    <?= $metrics['emergencyMonths'] !== null
                        ? number_format($metrics['emergencyMonths'], 1) . ' months'
                        : '-' ?>
                </p>
                <p class="metric-sub">Target: 3–6 months of essential expenses.</p>
            </div>
        </div>

        <p class="how-to-use">
            <strong>How to use</strong><br>
            Enter all daily income &amp; expenses in <b>Transactions</b>.
            Define your bank/card/StashAway as <b>Accounts</b>. This page updates
            automatically from those entries.
        </p>
    </section>

    <section class="card card-wide">
        <h3>Accounts snapshot</h3>
        <p class="card-description">Balances as of end of <?= h($monthLabel) ?>.</p>
        <ul class="accounts-list">
            <?php foreach ($accounts as $id => $acc): ?>
                <?php $bal = $balances[$id] ?? 0.0; ?>
                <li>
                    <span>
                        <?= h($acc['name']) ?> ·
                        <span class="badge"><?= h(ucwords(str_replace('_', ' ', $acc['type']))) ?></span>
                    </span>
                    <span>RM<?= number_format($bal, 2) ?></span>
                </li>
            <?php endforeach; ?>
        </ul>
    </section>
</div>

<!-- Debt snapshot -->
<div class="cards-row">
    <section class="card card-wide">
        <h3>Debt snapshot – Loans</h3>
        <p class="card-description">Fixed-term bank / personal / car loans.</p>

        <div class="two-column">
            <div>
                <p><strong>Active loans:</strong> <?= (int)$loanSummary['active_loans'] ?></p>
                <p><strong>Monthly instalments:</strong>
                    RM<?= number_format($loanSummary['monthly_installment'], 2) ?></p>
                <p><strong>Due in next 30 days:</strong> <?= (int)$loanSummary['due_soon'] ?></p>
            </div>
        </div>

        <a href="loans.php" class="btn-secondary">View loans</a>
    </section>

    <section class="card card-wide">
        <h3>Card instalments</h3>
        <p class="card-description">Instalment purchases on credit cards.</p>

        <div class="two-column">
            <div>
                <p><strong>Active plans:</strong> <?= (int)$instSummary['active_plans'] ?></p>
                <p><strong>Monthly instalments:</strong>
                    RM<?= number_format($instSummary['monthly_installment'], 2) ?></p>
                <p><strong>Due in next 30 days:</strong> <?= (int)$instSummary['due_soon'] ?></p>
            </div>
        </div>

        <a href="installments.php" class="btn-secondary">View installments</a>
    </section>
</div>

<!-- History -->
<section class="card card-full">
    <h3>History (last 6 months)</h3>
    <table class="data-table">
        <thead>
        <tr>
            <th>Month</th>
            <th>Income</th>
            <th>Expenses</th>
            <th>Savings</th>
            <th>Savings rate</th>
            <th>Net worth</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($historyRows as $row): ?>
            <tr>
                <td><?= h($row['label']) ?></td>
                <td>RM<?= number_format($row['income'], 2) ?></td>
                <td>RM<?= number_format($row['expenses'], 2) ?></td>
                <td>RM<?= number_format($row['savings'], 2) ?></td>
                <td>
                    <?= $row['savingsRate'] !== null
                        ? number_format($row['savingsRate'], 1) . '%'
                        : '-' ?>
                </td>
                <td>RM<?= number_format($row['netWorth'], 2) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</section>

<?php
render_layout_end();
