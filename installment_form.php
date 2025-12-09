<?php
require_once 'config.php';
require_once 'functions.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : null;

$error = '';
$creditCards = get_credit_card_accounts($pdo);

$defaultPlan = [
    'id'              => null,
    'account_id'      => '',
    'title'           => '',
    'merchant'        => '',
    'original_amount' => '',
    'term_months'     => '',
    'monthly_payment' => '',
    'start_date'      => date('Y-m-d'),
    'due_day'         => '',
    'closed_at'       => '',
    'notes'           => '',
];

$plan = $defaultPlan;

// Editing: load existing
if ($id) {
    $stmt = $pdo->prepare("SELECT * FROM installment_plans WHERE id = ?");
    $stmt->execute([$id]);
    if ($row = $stmt->fetch()) {
        $plan = array_merge($plan, $row);
    }
}

// Handle POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id              = !empty($_POST['id']) ? (int)$_POST['id'] : null;
    $account_id      = (int)($_POST['account_id'] ?? 0);
    $title           = trim($_POST['title'] ?? '');
    $merchant        = trim($_POST['merchant'] ?? '');
    $original_amount = (float)($_POST['original_amount'] ?? 0);
    $term_months     = (int)($_POST['term_months'] ?? 0);
    $monthly_payment = (float)($_POST['monthly_payment'] ?? 0);
    $start_date      = $_POST['start_date'] ?? '';
    $due_day         = $_POST['due_day'] !== '' ? (int)$_POST['due_day'] : null;
    $closed_at       = $_POST['closed_at'] !== '' ? $_POST['closed_at'] : null;
    $notes           = trim($_POST['notes'] ?? '');

    if (!$account_id) {
        $error = 'Please choose the credit card account.';
    } elseif ($title === '') {
        $error = 'Title is required.';
    } elseif ($original_amount <= 0) {
        $error = 'Original amount must be greater than zero.';
    } elseif ($term_months <= 0) {
        $error = 'Term must be at least 1 month.';
    } elseif ($monthly_payment <= 0) {
        $error = 'Monthly payment must be greater than zero.';
    } elseif ($start_date === '') {
        $error = 'Start date is required.';
    } else {
        if ($id) {
            $stmt = $pdo->prepare("
                UPDATE installment_plans
                SET account_id = ?, title = ?, merchant = ?, original_amount = ?,
                    term_months = ?, monthly_payment = ?, start_date = ?, due_day = ?,
                    closed_at = ?, notes = ?
                WHERE id = ?
            ");
            $stmt->execute([
                $account_id,
                $title,
                $merchant,
                $original_amount,
                $term_months,
                $monthly_payment,
                $start_date,
                $due_day,
                $closed_at,
                $notes ?: null,
                $id,
            ]);
        } else {
            $stmt = $pdo->prepare("
                INSERT INTO installment_plans (
                    account_id, title, merchant, original_amount,
                    term_months, monthly_payment, start_date,
                    due_day, closed_at, notes
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $account_id,
                $title,
                $merchant,
                $original_amount,
                $term_months,
                $monthly_payment,
                $start_date,
                $due_day,
                $closed_at,
                $notes ?: null,
            ]);
        }

        header('Location: installments.php');
        exit;
    }

    // repopulate on error
    $plan = [
        'id'              => $id,
        'account_id'      => $account_id,
        'title'           => $title,
        'merchant'        => $merchant,
        'original_amount' => $original_amount,
        'term_months'     => $term_months,
        'monthly_payment' => $monthly_payment,
        'start_date'      => $start_date,
        'due_day'         => $due_day,
        'closed_at'       => $closed_at,
        'notes'           => $notes,
    ];
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title><?= $plan['id'] ? 'Edit installment plan' : 'New installment plan' ?></title>
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
                Use for instalment purchases (TV, phone, etc.) on your credit cards.
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
                <div class="topbar-title">
                    <?= $plan['id'] ? 'Edit installment plan' : 'New installment plan' ?>
                </div>
                <div class="topbar-subtitle">
                    Link each plan to the card, amount and monthly charge.
                </div>
            </div>
        </header>

        <div class="main-content">
            <div class="form-card">
                <?php if ($error): ?>
                    <div class="alert alert-danger small mb-3"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>

                <form method="post" class="row g-3">
                    <input type="hidden" name="id" value="<?= htmlspecialchars((string)$plan['id']) ?>">

                    <div class="col-md-4">
                        <label class="form-label">Credit card account</label>
                        <select name="account_id" class="form-select form-select-sm" required>
                            <option value="">– Choose card –</option>
                            <?php foreach ($creditCards as $acc): ?>
                                <option value="<?= $acc['id'] ?>"
                                    <?= (int)$plan['account_id'] === (int)$acc['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($acc['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="form-text">
                            Only accounts with type <code>credit_card</code> are shown.
                        </div>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Plan title</label>
                        <input type="text" name="title"
                               class="form-control form-control-sm"
                               value="<?= htmlspecialchars($plan['title']) ?>" required>
                        <div class="form-text">
                            Example: “LG 55&quot; TV”, “iPhone 16 Pro”.
                        </div>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Merchant (optional)</label>
                        <input type="text" name="merchant"
                               class="form-control form-control-sm"
                               value="<?= htmlspecialchars($plan['merchant']) ?>">
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Original amount (RM)</label>
                        <input type="number" step="0.01" name="original_amount"
                               class="form-control form-control-sm"
                               value="<?= htmlspecialchars((string)$plan['original_amount']) ?>" required>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Term (months)</label>
                        <input type="number" name="term_months"
                               class="form-control form-control-sm"
                               value="<?= htmlspecialchars((string)$plan['term_months']) ?>" required>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Monthly instalment (RM)</label>
                        <input type="number" step="0.01" name="monthly_payment"
                               class="form-control form-control-sm"
                               value="<?= htmlspecialchars((string)$plan['monthly_payment']) ?>" required>
                        <div class="form-text">
                            Usually shown on your card statement.
                        </div>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Start date</label>
                        <input type="date" name="start_date"
                               class="form-control form-control-sm"
                               value="<?= htmlspecialchars($plan['start_date']) ?>" required>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Due day of month</label>
                        <input type="number" min="1" max="31" name="due_day"
                               class="form-control form-control-sm"
                               value="<?= htmlspecialchars((string)$plan['due_day']) ?>">
                        <div class="form-text">
                            Example: 5 = 5th of each month.
                        </div>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Closed at (optional)</label>
                        <input type="date" name="closed_at"
                               class="form-control form-control-sm"
                               value="<?= htmlspecialchars((string)$plan['closed_at']) ?>">
                        <div class="form-text">
                            Set when the plan is fully settled ahead of schedule.
                        </div>
                    </div>

                    <div class="col-12">
                        <label class="form-label">Notes (optional)</label>
                        <textarea name="notes" rows="2"
                                  class="form-control form-control-sm"><?= htmlspecialchars($plan['notes']) ?></textarea>
                    </div>

                    <div class="col-12 d-flex gap-2">
                        <button class="btn btn-dark btn-sm rounded-pill">
                            <?= $plan['id'] ? 'Update plan' : 'Save plan' ?>
                        </button>
                        <a href="installments.php"
                           class="btn btn-outline-secondary btn-sm rounded-pill">
                            Cancel
                        </a>
                    </div>
                </form>

            </div>
        </div>
    </main>
</div>
</body>
</html>
