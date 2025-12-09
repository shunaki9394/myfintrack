<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/layout.php';

$types     = ['income', 'expense', 'transfer'];
$accounts  = get_all_accounts();
$categories = get_all_categories();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$tx = [
    'booked_at'       => date('Y-m-d'),
    'type'            => 'expense',
    'amount'          => '',
    'from_account_id' => '',
    'to_account_id'   => '',
    'category_id'     => '',
    'description'     => '',
];

if ($id > 0 && $_SERVER['REQUEST_METHOD'] === 'GET') {
    $stmt = $pdo->prepare("SELECT * FROM transactions WHERE id = :id");
    $stmt->execute([':id' => $id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($row) {
        $tx = $row;
    } else {
        $id = 0; // not found; treat as new
    }
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;

    $tx['booked_at']       = $_POST['booked_at'] ?? date('Y-m-d');
    $tx['type']            = in_array($_POST['type'] ?? '', $types, true) ? $_POST['type'] : 'expense';
    $tx['amount']          = trim($_POST['amount'] ?? '');
    $tx['from_account_id'] = $_POST['from_account_id'] ?? '';
    $tx['to_account_id']   = $_POST['to_account_id'] ?? '';
    $tx['category_id']     = $_POST['category_id'] ?? '';
    $tx['description']     = $_POST['description'] ?? '';

    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $tx['booked_at'])) {
        $errors['booked_at'] = 'Invalid date.';
    }

    if ($tx['amount'] === '' || !is_numeric($tx['amount']) || (float)$tx['amount'] <= 0) {
        $errors['amount'] = 'Please enter a positive amount.';
    }

    // Basic account requirements
    if ($tx['type'] === 'income') {
        if ($tx['to_account_id'] === '') {
            $errors['to_account_id'] = 'Please choose the account receiving the income.';
        }
    } elseif ($tx['type'] === 'expense') {
        if ($tx['from_account_id'] === '') {
            $errors['from_account_id'] = 'Please choose the account paying the expense.';
        }
    } elseif ($tx['type'] === 'transfer') {
        if ($tx['from_account_id'] === '' || $tx['to_account_id'] === '') {
            $errors['from_account_id'] = 'Please choose both from and to accounts.';
        }
    }

    if ($tx['type'] !== 'transfer' && $tx['category_id'] === '') {
        $errors['category_id'] = 'Please choose a category.';
    }

    if (empty($errors)) {
        $params = [
            ':booked_at'       => $tx['booked_at'],
            ':type'            => $tx['type'],
            ':amount'          => (float)$tx['amount'],
            ':from_account_id' => $tx['from_account_id'] !== '' ? (int)$tx['from_account_id'] : null,
            ':to_account_id'   => $tx['to_account_id']   !== '' ? (int)$tx['to_account_id']   : null,
            ':category_id'     => $tx['category_id']     !== '' ? (int)$tx['category_id']     : null,
            ':description'     => $tx['description'] !== '' ? $tx['description'] : null,
        ];

        try {
            if ($id > 0) {
                $sql = "UPDATE transactions
                        SET booked_at = :booked_at,
                            type = :type,
                            amount = :amount,
                            from_account_id = :from_account_id,
                            to_account_id = :to_account_id,
                            category_id = :category_id,
                            description = :description
                        WHERE id = :id";
                $params[':id'] = $id;
            } else {
                $sql = "INSERT INTO transactions
                        (booked_at, type, amount, from_account_id, to_account_id, category_id, description)
                        VALUES (:booked_at, :type, :amount, :from_account_id, :to_account_id, :category_id, :description)";
            }

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);

            $month = substr($tx['booked_at'], 0, 7);
            header('Location: transactions.php?month=' . urlencode($month));
            exit;
        } catch (Throwable $e) {
            $errors['general'] = 'Failed to save transaction: ' . $e->getMessage();
        }
    }
}

$pageTitle = $id > 0 ? 'Edit transaction' : 'New transaction';

render_layout_start($pageTitle, 'transactions');
?>

<section class="card card-full">
    <h1><?= h($pageTitle) ?></h1>

    <?php if (!empty($errors['general'])): ?>
        <div class="alert alert-error"><?= h($errors['general']) ?></div>
    <?php endif; ?>

    <form method="post" class="form-grid">
        <input type="hidden" name="id" value="<?= (int)$id ?>">

        <div class="form-field">
            <label for="booked_at">Date</label>
            <input type="date" id="booked_at" name="booked_at"
                   value="<?= h($tx['booked_at']) ?>">
            <?php if (!empty($errors['booked_at'])): ?>
                <div class="field-error"><?= h($errors['booked_at']) ?></div>
            <?php endif; ?>
        </div>

        <div class="form-field">
            <label for="type">Type</label>
            <select id="type" name="type">
                <?php foreach ($types as $t): ?>
                    <option value="<?= h($t) ?>"
                        <?= $tx['type'] === $t ? 'selected' : '' ?>>
                        <?= h(ucfirst($t)) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-field">
            <label for="amount">Amount (RM)</label>
            <input type="number" step="0.01" min="0" id="amount" name="amount"
                   value="<?= h($tx['amount']) ?>">
            <?php if (!empty($errors['amount'])): ?>
                <div class="field-error"><?= h($errors['amount']) ?></div>
            <?php endif; ?>
        </div>

        <div class="form-field">
            <label for="from_account_id">From account</label>
            <select id="from_account_id" name="from_account_id">
                <option value="">—</option>
                <?php foreach ($accounts as $acc): ?>
                    <option value="<?= (int)$acc['id'] ?>"
                        <?= (string)$tx['from_account_id'] === (string)$acc['id'] ? 'selected' : '' ?>>
                        <?= h($acc['name']) ?> (<?= h($acc['type']) ?>)
                    </option>
                <?php endforeach; ?>
            </select>
            <?php if (!empty($errors['from_account_id'])): ?>
                <div class="field-error"><?= h($errors['from_account_id']) ?></div>
            <?php endif; ?>
            <div class="field-help">For expenses and transfers.</div>
        </div>

        <div class="form-field">
            <label for="to_account_id">To account</label>
            <select id="to_account_id" name="to_account_id">
                <option value="">—</option>
                <?php foreach ($accounts as $acc): ?>
                    <option value="<?= (int)$acc['id'] ?>"
                        <?= (string)$tx['to_account_id'] === (string)$acc['id'] ? 'selected' : '' ?>>
                        <?= h($acc['name']) ?> (<?= h($acc['type']) ?>)
                    </option>
                <?php endforeach; ?>
            </select>
            <?php if (!empty($errors['to_account_id'])): ?>
                <div class="field-error"><?= h($errors['to_account_id']) ?></div>
            <?php endif; ?>
            <div class="field-help">For income and transfers.</div>
        </div>

        <div class="form-field">
            <label for="category_id">Category</label>
            <select id="category_id" name="category_id">
                <option value="">—</option>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?= (int)$cat['id'] ?>"
                        <?= (string)$tx['category_id'] === (string)$cat['id'] ? 'selected' : '' ?>>
                        <?= h($cat['name']) ?> (<?= h($cat['kind']) ?>)
                    </option>
                <?php endforeach; ?>
            </select>
            <?php if (!empty($errors['category_id'])): ?>
                <div class="field-error"><?= h($errors['category_id']) ?></div>
            <?php endif; ?>
        </div>

        <div class="form-field form-field-wide">
            <label for="description">Description</label>
            <input type="text" id="description" name="description"
                   value="<?= h($tx['description']) ?>">
        </div>

        <div class="form-actions">
            <a href="transactions.php" class="btn-secondary">Back</a>
            <button type="submit" class="btn-primary">Save</button>
        </div>
    </form>
</section>

<?php
render_layout_end();
