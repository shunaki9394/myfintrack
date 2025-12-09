<?php
require_once 'config.php';
require_once 'functions.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : null;
$error = '';
$accounts = get_accounts($pdo);

// Only show liability-type accounts as a hint (but we still show all)
$defaultLoan = [
    'id'              => null,
    'account_id'      => '',
    'name'            => '',
    'lender'          => '',
    'principal_amount'=> '',
    'start_date'      => date('Y-m-d'),
    'term_months'     => '',
    'nominal_rate'    => '',
    'monthly_payment' => '',
    'due_day'         => '',
    'notes'           => '',
];

$loan = $defaultLoan;

if ($id) {
    $stmt = $pdo->prepare("SELECT * FROM loans WHERE id = ?");
    $stmt->execute([$id]);
    if ($row = $stmt->fetch()) {
        $loan = array_merge($loan, $row);
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = !empty($_POST['id']) ? (int)$_POST['id'] : null;
    $account_id      = (int)($_POST['account_id'] ?? 0);
    $name            = trim($_POST['name'] ?? '');
    $lender          = trim($_POST['lender'] ?? '');
    $principal       = (float)($_POST['principal_amount'] ?? 0);
    $start_date      = $_POST['start_date'] ?? '';
    $term_months     = $_POST['term_months'] !== '' ? (int)$_POST['term_months'] : null;
    $nominal_rate    = $_POST['nominal_rate'] !== '' ? (float)$_POST['nominal_rate'] : null;
    $monthly_payment = $_POST['monthly_payment'] !== '' ? (float)$_POST['monthly_payment'] : null;
    $due_day         = $_POST['due_day'] !== '' ? (int)$_POST['due_day'] : null;
    $notes           = trim($_POST['notes'] ?? '');

    if (!$account_id) {
        $error = 'Please choose the linked loan account.';
    } elseif ($name === '') {
        $error = 'Loan name is required.';
    } elseif ($principal <= 0) {
        $error = 'Principal amount must be greater than zero.';
    } elseif ($start_date === '') {
        $error = 'Start date is required.';
    } else {
        if ($id) {
            $stmt = $pdo->prepare("
                UPDATE loans
                SET account_id = ?, name = ?, lender = ?, principal_amount = ?,
                    start_date = ?, term_months = ?, nominal_rate = ?,
                    monthly_payment = ?, due_day = ?, notes = ?
                WHERE id = ?
            ");
            $stmt->execute([
                $account_id, $name, $lender, $principal,
                $start_date, $term_months, $nominal_rate,
                $monthly_payment, $due_day, $notes ?: null, $id
            ]);
        } else {
            $stmt = $pdo->prepare("
                INSERT INTO loans (
                    account_id, name, lender, principal_amount,
                    start_date, term_months, nominal_rate,
                    monthly_payment, due_day, notes
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $account_id, $name, $lender, $principal,
                $start_date, $term_months, $nominal_rate,
                $monthly_payment, $due_day, $notes ?: null
            ]);
        }

        header('Location: loans.php');
        exit;
    }

    // On error, repopulate form values
    $loan = [
        'id'              => $id,
        'account_id'      => $account_id,
        'name'            => $name,
        'lender'          => $lender,
        'principal_amount'=> $principal,
        'start_date'      => $start_date,
        'term_months'     => $term_months,
        'nominal_rate'    => $nominal_rate,
        'monthly_payment' => $monthly_payment,
        'due_day'         => $due_day,
        'notes'           => $notes,
    ];
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title><?= $loan['id'] ? 'Edit loan' : 'New loan' ?></title>
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
                Link each loan to a liability account (type = Loan / Credit card).
            </div>
        </div>
    </aside>

    <main class="main">
        <header class="topbar">
            <div class="topbar-left">
                <div class="breadcrumb-row">
                    <span class="breadcrumb-pill">Personal</span>
                    <span>Loans</span>
                </div>
                <div class="topbar-title">
                    <?= $loan['id'] ? 'Edit loan' : 'New loan' ?>
                </div>
                <div class="topbar-subtitle">
                    Principal, term and monthly instalment are used to compute progress and “on track” status.
                </div>
            </div>
        </header>

        <div class="main-content">
            <div class="form-card">
                <?php if ($error): ?>
                    <div class="alert alert-danger small mb-3"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>

                <form method="post" class="row g-3">
                    <input type="hidden" name="id" value="<?= htmlspecialchars((string)$loan['id']) ?>">

                    <div class="col-md-4">
                        <label class="form-label">Linked account</label>
                        <select name="account_id" class="form-select form-select-sm" required>
                            <option value="">– Choose account –</option>
                            <?php foreach ($accounts as $acc): ?>
                                <option value="<?= $acc['id'] ?>"
                                    <?= (int)$loan['account_id'] === (int)$acc['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($acc['name']) ?>
                                    (<?= htmlspecialchars(human_account_type($acc['type'])) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="form-text">
                            This account’s balance is treated as the loan’s outstanding amount.
                        </div>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Loan name</label>
                        <input type="text" name="name" class="form-control form-control-sm"
                               value="<?= htmlspecialchars($loan['name']) ?>" required>
                        <div class="form-text">
                            Example: “Car Loan – Myvi”, “Personal Loan – Maybank”.
                        </div>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Lender (optional)</label>
                        <input type="text" name="lender" class="form-control form-control-sm"
                               value="<?= htmlspecialchars($loan['lender']) ?>">
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Principal amount (RM)</label>
                        <input type="number" step="0.01" name="principal_amount"
                               class="form-control form-control-sm"
                               value="<?= htmlspecialchars((string)$loan['principal_amount']) ?>" required>
                        <div class="form-text">
                            Original loan amount.
                        </div>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Start date</label>
                        <input type="date" name="start_date" class="form-control form-control-sm"
                               value="<?= htmlspecialchars($loan['start_date']) ?>" required>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Term (months)</label>
                        <input type="number" name="term_months" class="form-control form-control-sm"
                               value="<?= htmlspecialchars((string)$loan['term_months']) ?>">
                        <div class="form-text">
                            Leave blank for open-ended (e.g. revolving credit).
                        </div>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Interest rate (% p.a.)</label>
                        <input type="number" step="0.01" name="nominal_rate"
                               class="form-control form-control-sm"
                               value="<?= htmlspecialchars((string)$loan['nominal_rate']) ?>">
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Monthly instalment (RM)</label>
                        <input type="number" step="0.01" name="monthly_payment"
                               class="form-control form-control-sm"
                               value="<?= htmlspecialchars((string)$loan['monthly_payment']) ?>">
                        <div class="form-text">
                            Used to judge “on track vs behind” vs. actual amount repaid.
                        </div>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Due day of month</label>
                        <input type="number" min="1" max="31" name="due_day"
                               class="form-control form-control-sm"
                               value="<?= htmlspecialchars((string)$loan['due_day']) ?>">
                        <div class="form-text">
                            Example: 7 = payment due on 7th each month (for next-due date).
                        </div>
                    </div>

                    <div class="col-12">
                        <label class="form-label">Notes (optional)</label>
                        <textarea name="notes" rows="2"
                                  class="form-control form-control-sm"><?= htmlspecialchars($loan['notes']) ?></textarea>
                    </div>

                    <div class="col-12 d-flex gap-2">
                        <button class="btn btn-dark btn-sm rounded-pill">Save loan</button>
                        <a href="loans.php"
                           class="btn btn-outline-secondary btn-sm rounded-pill">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </main>
</div>
</body>
</html>
