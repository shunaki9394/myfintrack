<?php
require_once 'config.php';
require_once 'functions.php';

$month = isset($_GET['month']) ? $_GET['month'] : get_default_month();
[$year, $m] = parse_year_month($month);
$start = sprintf('%04d-%02d-01', $year, $m);
$startDt = new DateTime($start);
$endDt = clone $startDt;
$endDt->modify('last day of this month');
$end = $endDt->format('Y-m-d');

$sql = "
  SELECT
    t.*,
    fa.name AS from_account_name,
    ta.name AS to_account_name,
    c.name AS category_name,
    c.kind AS category_kind
  FROM transactions t
  LEFT JOIN accounts fa ON t.from_account_id = fa.id
  LEFT JOIN accounts ta ON t.to_account_id = ta.id
  LEFT JOIN categories c ON t.category_id = c.id
  WHERE t.deleted_at IS NULL
    AND t.booked_at BETWEEN :start AND :end
  ORDER BY t.booked_at DESC, t.id DESC
  LIMIT 200
";
$stmt = $pdo->prepare($sql);
$stmt->execute(['start' => $start, 'end' => $end]);
$transactions = $stmt->fetchAll();

$monthOptions = get_month_options(12);
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Transactions</title>
    <link rel="stylesheet"
          href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="styles.css?v=1">
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
            <a href="transactions.php" class="active">
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
        </nav>

        <div class="sidebar-footer">
            <div class="sidebar-footer-label">
                <span>Environment</span>
                <span class="sidebar-footer-pill">Preview</span>
            </div>
            <div>
                Every lunch, bill and transfer goes here.  
                Dashboard pulls its numbers from this list.
            </div>
        </div>
    </aside>

    <main class="main">
        <header class="topbar">
            <div class="topbar-left">
                <div class="breadcrumb-row">
                    <span class="breadcrumb-pill">Personal</span>
                    <span>Transactions</span>
                </div>
                <div class="topbar-title">Transactions</div>
                <div class="topbar-subtitle">
                    Showing entries for <?= $startDt->format('F Y') ?>.
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
                <a href="transaction_form.php" class="btn-primary-cta ms-2">+ New transaction</a>
            </div>
        </header>

        <div class="main-content">
            <div class="summary-card">
                <div class="summary-header">
                    <div>
                        <div class="summary-title">Transaction list</div>
                        <div class="summary-subtitle">Latest 200 entries in this month.</div>
                    </div>
                </div>

                <div class="table-wrapper">
                    <table class="table table-light-sm mb-0">
                        <thead>
                        <tr>
                            <th>Date</th>
                            <th>Type</th>
                            <th class="text-end">Amount</th>
                            <th>From</th>
                            <th>To</th>
                            <th>Category</th>
                            <th>Description</th>
                            <th class="text-end">Actions</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($transactions as $t): ?>
                            <tr>
                                <td><?= htmlspecialchars($t['booked_at']) ?></td>
                                <td><?= htmlspecialchars(ucfirst($t['type'])) ?></td>
                                <td class="text-end"><?= number_format($t['amount'], 2) ?></td>
                                <td><?= htmlspecialchars($t['from_account_name'] ?? '') ?></td>
                                <td><?= htmlspecialchars($t['to_account_name'] ?? '') ?></td>
                                <td><?= htmlspecialchars($t['category_name'] ?? '') ?></td>
                                <td><?= htmlspecialchars($t['description'] ?? '') ?></td>
                                <td class="text-end">
                                    <a href="transaction_form.php?id=<?= (int)$t['id'] ?>"
                                       class="btn btn-sm btn-outline-secondary rounded-pill me-1">
                                        Edit
                                    </a>
                                    <a href="transaction_delete.php?id=<?= (int)$t['id'] ?>"
                                       class="btn btn-sm btn-outline-danger rounded-pill"
                                       onclick="return confirm('Delete this transaction?');">
                                        Delete
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (!$transactions): ?>
                            <tr>
                                <td colspan="8" class="text-center text-muted">
                                    No transactions for this month yet.
                                </td>
                            </tr>
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
