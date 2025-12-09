<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/layout.php';

$message = null;
$error   = null;

// Current balances
[$accMap, $balances] = get_account_balances(date('Y-m-d'));

// Handle delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    $id = (int)$_POST['delete_id'];

    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM transactions WHERE loan_id = :id");
        $stmt->execute([':id' => $id]);
        $used = (int)$stmt->fetchColumn();

        if ($used > 0) {
            $error = 'Cannot delete loan – it is linked to transactions.';
        } else {
            $stmt = $pdo->prepare("DELETE FROM loans WHERE id = :id");
            $stmt->execute([':id' => $id]);
            $message = 'Loan deleted.';
        }
    } catch (Throwable $e) {
        $error = 'Failed to delete loan: ' . $e->getMessage();
    }
}

// Load loans
$stmt = $pdo->query(
    "SELECT l.*, a.name AS account_name, a.type AS account_type
     FROM loans l
     JOIN accounts a ON l.account_id = a.id
     ORDER BY l.start_date DESC, l.id DESC"
);
$loans = $stmt->fetchAll(PDO::FETCH_ASSOC);

render_layout_start('Loans', 'loans');
?>

<div class="page-header">
    <div>
        <h1>Loans</h1>
        <p>Fixed-term loans (car, personal, etc.) with metadata and monthly payment.</p>
    </div>
    <a href="loan_form.php" class="btn-primary">Add loan</a>
</div>

<?php if ($message): ?>
    <div class="alert alert-success"><?= h($message) ?></div>
<?php endif; ?>
<?php if ($error): ?>
    <div class="alert alert-error"><?= h($error) ?></div>
<?php endif; ?>

<section class="card card-full">
    <h3>Loans list</h3>

    <?php if (empty($loans)): ?>
        <p>No loans recorded yet.</p>
    <?php else: ?>
        <table class="data-table">
            <thead>
            <tr>
                <th>Name</th>
                <th>Account</th>
                <th>Lender</th>
                <th>Principal</th>
                <th>Monthly payment</th>
                <th>Start date</th>
                <th>Term (months)</th>
                <th>Rate (%)</th>
                <th style="text-align:right;">Account balance</th>
                <th style="width:1%;">Actions</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($loans as $loan): ?>
                <?php
                $bal = $balances[$loan['account_id']] ?? 0.0;
                ?>
                <tr>
                    <td><?= h($loan['name']) ?></td>
                    <td><?= h($loan['account_name']) ?> (<?= h($loan['account_type']) ?>)</td>
                    <td><?= h($loan['lender'] ?? '') ?></td>
                    <td>RM<?= number_format((float)$loan['principal_amount'], 2) ?></td>
                    <td>RM<?= number_format((float)$loan['monthly_payment'], 2) ?></td>
                    <td><?= h($loan['start_date']) ?></td>
                    <td><?= $loan['term_months'] !== null ? (int)$loan['term_months'] : '-' ?></td>
                    <td><?= $loan['nominal_rate'] !== null ? number_format((float)$loan['nominal_rate'], 2) : '-' ?></td>
                    <td style="text-align:right;">RM<?= number_format($bal, 2) ?></td>
                    <td>
                        <div class="table-actions">
                            <a href="loan_form.php?id=<?= (int)$loan['id'] ?>">Edit</a>
                            <form method="post" onsubmit="return confirm('Delete this loan?');">
                                <input type="hidden" name="delete_id" value="<?= (int)$loan['id'] ?>">
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
