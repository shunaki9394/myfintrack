<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/layout.php';

$message = null;
$error   = null;

// Handle delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    $id = (int)$_POST['delete_id'];

    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM transactions WHERE installment_id = :id");
        $stmt->execute([':id' => $id]);
        $used = (int)$stmt->fetchColumn();

        if ($used > 0) {
            $error = 'Cannot delete installment plan – it is linked to transactions.';
        } else {
            $stmt = $pdo->prepare("DELETE FROM installment_plans WHERE id = :id");
            $stmt->execute([':id' => $id]);
            $message = 'Installment plan deleted.';
        }
    } catch (Throwable $e) {
        $error = 'Failed to delete installment plan: ' . $e->getMessage();
    }
}

// Load plans
$stmt = $pdo->query(
    "SELECT i.*, a.name AS account_name, a.type AS account_type
     FROM installment_plans i
     JOIN accounts a ON i.account_id = a.id
     ORDER BY i.start_date DESC, i.id DESC"
);
$plans = $stmt->fetchAll(PDO::FETCH_ASSOC);

render_layout_start('Installments', 'installments');
?>

<div class="page-header">
    <div>
        <h1>Installments</h1>
        <p>Credit card installment purchases and similar payment plans.</p>
    </div>
    <a href="installment_form.php" class="btn-primary">Add installment plan</a>
</div>

<?php if ($message): ?>
    <div class="alert alert-success"><?= h($message) ?></div>
<?php endif; ?>
<?php if ($error): ?>
    <div class="alert alert-error"><?= h($error) ?></div>
<?php endif; ?>

<section class="card card-full">
    <h3>Installment plans</h3>

    <?php if (empty($plans)): ?>
        <p>No installment plans recorded yet.</p>
    <?php else: ?>
        <table class="data-table">
            <thead>
            <tr>
                <th>Title</th>
                <th>Card account</th>
                <th>Merchant</th>
                <th>Original amount</th>
                <th>Monthly payment</th>
                <th>Term (months)</th>
                <th>Start date</th>
                <th>Due day</th>
                <th>Closed at</th>
                <th style="width:1%;">Actions</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($plans as $plan): ?>
                <tr>
                    <td><?= h($plan['title']) ?></td>
                    <td><?= h($plan['account_name']) ?> (<?= h($plan['account_type']) ?>)</td>
                    <td><?= h($plan['merchant']) ?></td>
                    <td>RM<?= number_format((float)$plan['original_amount'], 2) ?></td>
                    <td>RM<?= number_format((float)$plan['monthly_payment'], 2) ?></td>
                    <td><?= (int)$plan['term_months'] ?></td>
                    <td><?= h($plan['start_date']) ?></td>
                    <td><?= $plan['due_day'] !== null ? (int)$plan['due_day'] : '-' ?></td>
                    <td><?= $plan['closed_at'] ?? '-' ?></td>
                    <td>
                        <div class="table-actions">
                            <a href="installment_form.php?id=<?= (int)$plan['id'] ?>">Edit</a>
                            <form method="post" onsubmit="return confirm('Delete this plan?');">
                                <input type="hidden" name="delete_id" value="<?= (int)$plan['id'] ?>">
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
