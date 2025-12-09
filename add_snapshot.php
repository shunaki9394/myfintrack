<?php
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $snapshot_date     = $_POST['snapshot_date'] ?? '';
    $income            = (float)($_POST['income'] ?? 0);
    $expenses          = (float)($_POST['expenses'] ?? 0);
    $total_assets      = (float)($_POST['total_assets'] ?? 0);
    $total_liabilities = (float)($_POST['total_liabilities'] ?? 0);
    $liquid_assets     = (float)($_POST['liquid_assets'] ?? 0);
    $debt_payments     = (float)($_POST['debt_payments'] ?? 0);
    $notes             = $_POST['notes'] ?? null;

    if ($snapshot_date) {
        $stmt = $pdo->prepare("
            INSERT INTO monthly_snapshots (
                snapshot_date, income, expenses, total_assets,
                total_liabilities, liquid_assets, debt_payments, notes
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $snapshot_date,
            $income,
            $expenses,
            $total_assets,
            $total_liabilities,
            $liquid_assets,
            $debt_payments,
            $notes
        ]);

        header('Location: index.php');
        exit;
    } else {
        $error = 'Please choose a date.';
    }
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Add Monthly Snapshot</title>
    <link rel="stylesheet"
          href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="styles.css">
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
            <a href="add_snapshot.php" class="active">
                <span class="status-dot"></span>
                <span>Add Snapshot</span>
            </a>
        </nav>

        <div class="sidebar-footer">
            <div class="sidebar-footer-label">
                <span>Environment</span>
                <span class="sidebar-footer-pill">Preview</span>
            </div>
            <div>
                Take one snapshot per month. Use values in RM (convert USD on that date).
            </div>
        </div>
    </aside>

    <!-- Main -->
    <main class="main">
        <!-- Topbar -->
        <header class="topbar">
            <div class="topbar-left">
                <div class="breadcrumb-row">
                    <span class="breadcrumb-pill">Personal</span>
                    <span>Financial Health workspace</span>
                </div>
                <div class="topbar-title">Add Monthly Snapshot</div>
                <div class="topbar-subtitle">
                    Enter one line of numbers to update your dashboard for this month.
                </div>
            </div>
            <div class="topbar-right">
                <div class="role-pill">
                    Mode
                    <strong>Data entry</strong>
                </div>
            </div>
        </header>

        <div class="main-content">
            <div class="form-card">
                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger small mb-3">
                        <?= htmlspecialchars($error) ?>
                    </div>
                <?php endif; ?>

                <form method="post" class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">Snapshot date</label>
                        <input type="date" name="snapshot_date" class="form-control form-control-sm" required>
                        <div class="form-text">
                            Date of this health check.  
                            Example: use <strong>1st of the month</strong> to represent last month.
                        </div>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Monthly income (RM)</label>
                        <input type="number" step="0.01" name="income" class="form-control form-control-sm" required>
                        <div class="form-text">
                            All money in: salary, OT, bonus, side income.  
                            Exclude transfers between your own accounts.
                        </div>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Monthly expenses (RM)</label>
                        <input type="number" step="0.01" name="expenses" class="form-control form-control-sm" required>
                        <div class="form-text">
                            All money out: living costs, bills, insurance, loan & card payments, etc.
                        </div>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Total assets (RM)</label>
                        <input type="number" step="0.01" name="total_assets" class="form-control form-control-sm" required>
                        <div class="form-text">
                            Everything you own with value: bank, cash, Simple MYR, USD Cash Yield,  
                            ETF portfolios, FD, ASB, EPF, etc. in RM.
                        </div>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Total liabilities (RM)</label>
                        <input type="number" step="0.01" name="total_liabilities" class="form-control form-control-sm" required>
                        <div class="form-text">
                            Everything you owe: credit cards, car / personal / education loans, etc.
                        </div>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Liquid assets (RM)</label>
                        <input type="number" step="0.01" name="liquid_assets" class="form-control form-control-sm" required>
                        <div class="form-text">
                            Money you can access quickly: bank accounts, cash, Simple MYR,  
                            USD Cash Yield, other money market / short-term funds.
                        </div>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Monthly debt payments (RM)</label>
                        <input type="number" step="0.01" name="debt_payments" class="form-control form-control-sm" required>
                        <div class="form-text">
                            Total paid this month towards debts: loan instalments, credit cards, BNPL, etc.
                        </div>
                    </div>

                    <div class="col-12">
                        <label class="form-label">Notes (optional)</label>
                        <textarea name="notes" class="form-control form-control-sm" rows="2"></textarea>
                        <div class="form-text">
                            Example: “Started new job”, “Big car repair RM1,200”, “Paid off personal loan”, etc.
                        </div>
                    </div>

                    <div class="col-12 d-flex gap-2">
                        <button class="btn btn-dark btn-sm rounded-pill">Save snapshot</button>
                        <a href="index.php" class="btn btn-outline-secondary btn-sm rounded-pill">
                            Cancel & back to dashboard
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </main>

</div>
</body>
</html>
