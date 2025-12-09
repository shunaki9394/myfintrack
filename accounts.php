<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/layout.php';

$message = null;
$error   = null;

// Current balances
[$accounts, $balances] = get_account_balances(date('Y-m-d'));

// Handle delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    $id = (int)$_POST['delete_id'];

    try {
        // Check usage
        $stmt = $pdo->prepare(
            "SELECT COUNT(*) FROM transactions
             WHERE from_account_id = :id OR to_account_id = :id"
        );
        $stmt->execute([':id' => $id]);
        $used = (int)$stmt->fetchColumn();

        $stmt = $pdo->prepare("SELECT COUNT(*) FROM loans WHERE account_id = :id");
        $stmt->execute([':id' => $id]);
        $used += (int)$stmt->fetchColumn();

        $stmt = $pdo->prepare("SELECT COUNT(*) FROM installment_plans WHERE account_id = :id");
        $stmt->execute([':id' => $id]);
        $used += (int)$stmt->fetchColumn();

        if ($used > 0) {
            $error = 'Cannot delete account – it is used by transactions/loans/installments.';
        } else {
            $stmt = $pdo->prepare("DELETE FROM accounts WHERE id = :id");
            $stmt->execute([':id' => $id]);
            $message = 'Account deleted.';
            // reload list
            [$accounts, $balances] = get_account_balances(date('Y-m-d'));
        }
    } catch (Throwable $e) {
        $error = 'Failed to delete account: ' . $e->getMessage();
    }
}

render_layout_start('Accounts', 'accounts');
?>

<div class="page-header">
    <div>
        <h1>Accounts</h1>
        <p>Bank, cash, investment, loan and card accounts used in your tracking.</p>
    </div>
    <a href="account_form.php" class="btn-primary">Add account</a>
</div>

<?php if ($message): ?>
    <div class="alert alert-success"><?= h($message) ?></div>
<?php endif; ?>
<?php if ($error): ?>
    <div class="alert alert-error"><?= h($error) ?></div>
<?php endif; ?>

<section class="card card-full">
    <h3>Accounts list</h3>

    <?php if (empty($accounts)): ?>
        <p>No accounts yet. Start by adding at least one bank account and cash wallet.</p>
    <?php else: ?>
        <table class="data-table">
            <thead>
            <tr>
                <th>Name</th>
                <th>Type</th>
                <th>Liquid?</th>
                <th>In net worth?</th>
                <th style="text-align:right;">Opening balance</th>
                <th style="text-align:right;">Current balance</th>
                <th style="width:1%;">Actions</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($accounts as $id => $acc): ?>
                <tr>
                    <td><?= h($acc['name']) ?></td>
                    <td><?= h($acc['type']) ?></td>
                    <td><?= (int)$acc['is_liquid'] ? 'Yes' : 'No' ?></td>
                    <td><?= (int)$acc['is_net_worth'] ? 'Yes' : 'No' ?></td>
                    <td style="text-align:right;">RM<?= number_format((float)$acc['opening_balance'], 2) ?></td>
                    <td style="text-align:right;">RM<?= number_format((float)($balances[$id] ?? 0), 2) ?></td>
                    <td>
                        <div class="table-actions">
                            <a href="account_form.php?id=<?= (int)$acc['id'] ?>">Edit</a>
                            <form method="post" onsubmit="return confirm('Delete this account?');">
                                <input type="hidden" name="delete_id" value="<?= (int)$acc['id'] ?>">
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
