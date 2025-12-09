<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/layout.php';

$selectedMonth = normalize_month($_GET['month'] ?? date('Y-m'));
[$start, $end] = get_month_start_end($selectedMonth);
$monthLabel    = date('F Y', strtotime($selectedMonth . '-01'));

$message = null;
$error   = null;

// Handle soft delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    $id = (int)$_POST['delete_id'];

    try {
        $stmt = $pdo->prepare("UPDATE transactions SET deleted_at = NOW() WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $message = 'Transaction deleted.';
    } catch (Throwable $e) {
        $error = 'Failed to delete transaction: ' . $e->getMessage();
    }
}

// Load transactions for the month
$stmt = $pdo->prepare(
    "SELECT
         t.*,
         fa.name AS from_account_name,
         ta.name AS to_account_name,
         c.name  AS category_name,
         c.kind  AS category_kind
     FROM transactions t
     LEFT JOIN accounts   fa ON t.from_account_id = fa.id
     LEFT JOIN accounts   ta ON t.to_account_id   = ta.id
     LEFT JOIN categories c  ON t.category_id     = c.id
     WHERE t.booked_at BETWEEN :s AND :e
       AND (t.deleted_at IS NULL OR t.deleted_at = '0000-00-00 00:00:00')
     ORDER BY t.booked_at DESC, t.id DESC
     LIMIT 200"
);
$stmt->execute([':s' => $start, ':e' => $end]);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

$monthOptions = get_month_options(12);

render_layout_start('Transactions', 'transactions');
?>

<div class="page-header">
    <div>
        <h1>Transactions</h1>
        <p>All income, expenses and transfers. This drives your dashboard.</p>
    </div>

    <div class="page-header-actions">
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

        <a href="transaction_form.php" class="btn-primary">Add transaction</a>
    </div>
</div>

<?php if ($message): ?>
    <div class="alert alert-success"><?= h($message) ?></div>
<?php endif; ?>
<?php if ($error): ?>
    <div class="alert alert-error"><?= h($error) ?></div>
<?php endif; ?>

<section class="card card-full">
    <h3><?= h($monthLabel) ?> transactions</h3>

    <?php if (empty($rows)): ?>
        <p>No transactions recorded for this month yet.</p>
    <?php else: ?>
        <table class="data-table">
            <thead>
            <tr>
                <th>Date</th>
                <th>Type</th>
                <th>From</th>
                <th>To</th>
                <th>Category</th>
                <th>Description</th>
                <th style="text-align:right;">Amount (RM)</th>
                <th style="width:1%;">Actions</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($rows as $tx): ?>
                <tr>
                    <td><?= h($tx['booked_at']) ?></td>
                    <td><?= h(ucfirst($tx['type'])) ?></td>
                    <td><?= h($tx['from_account_name'] ?? '') ?></td>
                    <td><?= h($tx['to_account_name'] ?? '') ?></td>
                    <td><?= h($tx['category_name'] ?? '') ?></td>
                    <td><?= h($tx['description'] ?? '') ?></td>
                    <td style="text-align:right;">
                        <?= number_format((float)$tx['amount'], 2) ?>
                    </td>
                    <td>
                        <div class="table-actions">
                            <a href="transaction_form.php?id=<?= (int)$tx['id'] ?>">Edit</a>
                            <form method="post" onsubmit="return confirm('Delete this transaction?');">
                                <input type="hidden" name="delete_id" value="<?= (int)$tx['id'] ?>">
                                <button type="submit" class="link-danger">Delete</button>
                            </form>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</section>

<?php
render_layout_end();
