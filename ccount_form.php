<?php
require_once 'config.php';
require_once 'functions.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : null;
$error = '';
$account = [
    'id'              => null,
    'name'            => '',
    'type'            => 'bank',
    'is_liquid'       => 1,
    'is_net_worth'    => 1,
    'opening_balance' => 0.00,
];

if ($id) {
    $stmt = $pdo->prepare("SELECT * FROM accounts WHERE id = ?");
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    if ($row) {
        $account = $row;
        // For liabilities, show positive opening amount in form
        if (is_liability_type($account['type'])) {
            $account['opening_balance'] = abs((float)$account['opening_balance']);
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = !empty($_POST['id']) ? (int)$_POST['id'] : null;
    $name = trim($_POST['name'] ?? '');
    $type = $_POST['type'] ?? 'bank';
    $is_liquid = isset($_POST['is_liquid']) ? 1 : 0;
    $is_net_worth = isset($_POST['is_net_worth']) ? 1 : 0;
    $opening_balance = (float)($_POST['opening_balance'] ?? 0);

    if ($name === '') {
        $error = 'Name is required.';
    } else {
        // For liability accounts, store negative opening balance internally
        if (is_liability_type($type) && $opening_balance > 0) {
            $opening_balance = -$opening_balance;
        }

        if ($id) {
            $stmt = $pdo->prepare("
                UPDATE accounts
                SET name = ?, type = ?, is_liquid = ?, is_net_worth = ?, opening_balance = ?
                WHERE id = ?
            ");
            $stmt->execute([$name, $type, $is_liquid, $is_net_worth, $opening_balance, $id]);
        } else {
            $stmt = $pdo->prepare("
                INSERT INTO accounts (name, type, is_liquid, is_net_worth, opening_balance)
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->execute([$name, $type, $is_liquid, $is_net_worth, $opening_balance]);
        }

        header('Location: accounts.php');
        exit;
    }

    // If error, re-populate $account for display
    $account = [
        'id'              => $id,
        'name'            => $name,
        'type'            => $type,
        'is_liquid'       => $is_liquid,
        'is_net_worth'    => $is_net_worth,
        'opening_balance' => $opening_balance,
    ];
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title><?= $account['id'] ? 'Edit account' : 'New account' ?></title>
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
                For liability accounts (credit card / loan), enter the amount owed as a positive number.
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
                <div class="topbar-title">
                    <?= $account['id'] ? 'Edit account' : 'New account' ?>
                </div>
                <div class="topbar-subtitle">
                    Configure how this account is treated in net worth and emergency fund calculations.
                </div>
            </div>
        </header>

        <div class="main-content">
            <div class="form-card">
                <?php if ($error): ?>
                    <div class="alert alert-danger small mb-3"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>

                <form method="post" class="row g-3">
                    <input type="hidden" name="id" value="<?= htmlspecialchars((string)$account['id']) ?>">

                    <div class="col-md-4">
                        <label class="form-label">Name</label>
                        <input type="text" name="name" class="form-control form-control-sm"
                               value="<?= htmlspecialchars($account['name']) ?>" required>
                        <div class="form-text">
                            Example: Maybank Savings, Cash Wallet, StashAway Simple MYR, CIMB Credit Card.
                        </div>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Type</label>
                        <select name="type" class="form-select form-select-sm">
                            <?php
                            $types = [
                                'cash'            => 'Cash',
                                'bank'            => 'Bank',
                                'investment'      => 'Investment',
                                'credit_card'     => 'Credit card (liability)',
                                'loan'            => 'Loan (liability)',
                                'other_asset'     => 'Other asset',
                                'other_liability' => 'Other liability',
                            ];
                            foreach ($types as $value => $label): ?>
                                <option value="<?= $value ?>" <?= $account['type'] === $value ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($label) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="form-text">
                            Asset types increase your net worth; liability types reduce it.
                        </div>
                    </div>

                    <div class="col-md-2">
                        <label class="form-label">Liquid?</label>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="is_liquid" id="is_liquid"
                                   value="1" <?= $account['is_liquid'] ? 'checked' : '' ?>>
                            <label class="form-check-label" for="is_liquid">Include in emergency fund</label>
                        </div>
                        <div class="form-text">
                            Bank & cash are usually liquid; long-term ETFs usually not.
                        </div>
                    </div>

                    <div class="col-md-2">
                        <label class="form-label">In net worth?</label>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="is_net_worth" id="is_net_worth"
                                   value="1" <?= $account['is_net_worth'] ? 'checked' : '' ?>>
                            <label class="form-check-label" for="is_net_worth">Count in net worth</label>
                        </div>
                        <div class="form-text">
                            Uncheck only for special cases you want to ignore.
                        </div>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Opening balance (RM)</label>
                        <input type="number" step="0.01" name="opening_balance"
                               class="form-control form-control-sm"
                               value="<?= htmlspecialchars((string)$account['opening_balance']) ?>">
                        <div class="form-text">
                            Asset account: current amount on day you start using this app.  
                            Liability account: amount you <strong>owe</strong> (system will treat it as negative).
                        </div>
                    </div>

                    <div class="col-12 d-flex gap-2">
                        <button class="btn btn-dark btn-sm rounded-pill">Save account</button>
                        <a href="accounts.php" class="btn btn-outline-secondary btn-sm rounded-pill">
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
