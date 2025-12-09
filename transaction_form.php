<?php
require_once 'config.php';
require_once 'functions.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : null;

$accounts     = get_accounts($pdo);
$catsGrouped  = get_categories_grouped($pdo);
$error        = '';

// Default values
$transaction = [
    'id'              => null,
    'type'            => 'expense',
    'booked_at'       => date('Y-m-d'),
    'amount'          => '',
    'from_account_id' => '',
    'to_account_id'   => '',
    'category_id'     => '',
    'description'     => '',
];

// If editing, load existing row
if ($id) {
    $stmt = $pdo->prepare("SELECT * FROM transactions WHERE id = ? AND deleted_at IS NULL");
    $stmt->execute([$id]);
    if ($row = $stmt->fetch()) {
        $transaction = array_merge($transaction, $row);
    }
}

// Handle submit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id              = !empty($_POST['id']) ? (int)$_POST['id'] : null;
    $type            = $_POST['type'] ?? 'expense';
    $booked_at       = $_POST['booked_at'] ?? date('Y-m-d');
    $amount          = (float)($_POST['amount'] ?? 0);
    $from_account_id = !empty($_POST['from_account_id']) ? (int)$_POST['from_account_id'] : null;
    $to_account_id   = !empty($_POST['to_account_id']) ? (int)$_POST['to_account_id'] : null;
    $category_id     = !empty($_POST['category_id']) ? (int)$_POST['category_id'] : null;
    $description     = trim($_POST['description'] ?? '');

    // Validation
    if ($amount <= 0) {
        $error = 'Amount must be greater than zero.';
    } elseif ($type === 'income' && !$to_account_id) {
        $error = 'Income needs a “To account”.';
    } elseif ($type === 'expense' && !$from_account_id) {
        $error = 'Expense needs a “From account”.';
    } elseif ($type === 'transfer' && (!$from_account_id || !$to_account_id)) {
        $error = 'Transfer needs both From and To accounts.';
    }

    if (!$error) {
        if ($id) {
            // UPDATE existing
            $stmt = $pdo->prepare("
                UPDATE transactions
                SET booked_at = ?, type = ?, amount = ?, from_account_id = ?, to_account_id = ?,
                    category_id = ?, description = ?
                WHERE id = ? AND deleted_at IS NULL
            ");
            $stmt->execute([
                $booked_at,
                $type,
                abs($amount),
                $from_account_id,
                $to_account_id,
                $type === 'transfer' ? null : $category_id,
                $description ?: null,
                $id,
            ]);
        } else {
            // INSERT new
            $stmt = $pdo->prepare("
                INSERT INTO transactions
                  (booked_at, type, amount, from_account_id, to_account_id, category_id, description)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $booked_at,
                $type,
                abs($amount),
                $from_account_id,
                $to_account_id,
                $type === 'transfer' ? null : $category_id,
                $description ?: null,
            ]);
        }

        header('Location: transactions.php');
        exit;
    }

    // On error, keep values the user entered
    $transaction = [
        'id'              => $id,
        'type'            => $type,
        'booked_at'       => $booked_at,
        'amount'          => $amount,
        'from_account_id' => $from_account_id,
        'to_account_id'   => $to_account_id,
        'category_id'     => $category_id,
        'description'     => $description,
    ];
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title><?= $transaction['id'] ? 'Edit transaction' : 'New transaction' ?></title>
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
                Pick type: Expense (lunch), Income (salary), Transfer (bank → StashAway).
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
                <div class="topbar-title">
                    <?= $transaction['id'] ? 'Edit transaction' : 'New transaction' ?>
                </div>
                <div class="topbar-subtitle">
                    One row per event: lunch, bill, salary, or account transfer.
                </div>
            </div>
        </header>

        <div class="main-content">
            <div class="form-card">
                <?php if ($error): ?>
                    <div class="alert alert-danger small mb-3"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>

                <form method="post" class="row g-3">
                    <input type="hidden" name="id"
                           value="<?= htmlspecialchars((string)$transaction['id']) ?>">

                    <div class="col-12">
                        <label class="form-label">Type</label><br>
                        <div class="btn-group">
                            <?php $tType = $transaction['type']; ?>
                            <input type="radio" class="btn-check" name="type" id="type_expense"
                                   value="expense" <?= $tType === 'expense' ? 'checked' : '' ?>>
                            <label class="btn btn-outline-dark btn-sm" for="type_expense">Expense</label>

                            <input type="radio" class="btn-check" name="type" id="type_income"
                                   value="income" <?= $tType === 'income' ? 'checked' : '' ?>>
                            <label class="btn btn-outline-dark btn-sm" for="type_income">Income</label>

                            <input type="radio" class="btn-check" name="type" id="type_transfer"
                                   value="transfer" <?= $tType === 'transfer' ? 'checked' : '' ?>>
                            <label class="btn btn-outline-dark btn-sm" for="type_transfer">Transfer</label>
                        </div>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Date</label>
                        <input type="date" name="booked_at"
                               class="form-control form-control-sm"
                               value="<?= htmlspecialchars($transaction['booked_at']) ?>" required>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Amount (RM)</label>
                        <input type="number" step="0.01" name="amount"
                               class="form-control form-control-sm"
                               value="<?= htmlspecialchars((string)$transaction['amount']) ?>" required>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">From account</label>
                        <select name="from_account_id" class="form-select form-select-sm">
                            <option value="">–</option>
                            <?php foreach ($accounts as $acc): ?>
                                <option value="<?= $acc['id'] ?>"
                                    <?= (int)$transaction['from_account_id'] === (int)$acc['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($acc['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="form-text">
                            Used for expenses & transfers.
                        </div>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">To account</label>
                        <select name="to_account_id" class="form-select form-select-sm">
                            <option value="">–</option>
                            <?php foreach ($accounts as $acc): ?>
                                <option value="<?= $acc['id'] ?>"
                                    <?= (int)$transaction['to_account_id'] === (int)$acc['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($acc['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="form-text">
                            Used for income & transfers.
                        </div>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Category</label>
                        <select name="category_id" class="form-select form-select-sm">
                            <option value="">–</option>
                            <?php if (!empty($catsGrouped['income'])): ?>
                                <optgroup label="Income">
                                    <?php foreach ($catsGrouped['income'] as $cat): ?>
                                        <option value="<?= $cat['id'] ?>"
                                            <?= (int)$transaction['category_id'] === (int)$cat['id'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($cat['name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </optgroup>
                            <?php endif; ?>
                            <?php if (!empty($catsGrouped['expense'])): ?>
                                <optgroup label="Expense">
                                    <?php foreach ($catsGrouped['expense'] as $cat): ?>
                                        <option value="<?= $cat['id'] ?>"
                                            <?= (int)$transaction['category_id'] === (int)$cat['id'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($cat['name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </optgroup>
                            <?php endif; ?>
                        </select>
                        <div class="form-text">
                            For transfers, category is optional.
                        </div>
                    </div>

                    <div class="col-12">
                        <label class="form-label">Description (optional)</label>
                        <input type="text" name="description"
                               class="form-control form-control-sm"
                               value="<?= htmlspecialchars($transaction['description'] ?? '') ?>"
                               placeholder="Fei Fei Chicken Rice (Lunch)">
                    </div>

                    <div class="col-12 d-flex gap-2">
                        <button class="btn btn-dark btn-sm rounded-pill">
                            <?= $transaction['id'] ? 'Update transaction' : 'Save transaction' ?>
                        </button>
                        <a href="transactions.php"
                           class="btn btn-outline-secondary btn-sm rounded-pill">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </main>
</div>
</body>
</html>
