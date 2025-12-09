<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/layout.php';

$message = null;
$error   = null;

// Load categories
$stmt = $pdo->query("SELECT * FROM categories ORDER BY kind DESC, name");
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Handle delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    $id = (int)$_POST['delete_id'];

    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM transactions WHERE category_id = :id");
        $stmt->execute([':id' => $id]);
        $used = (int)$stmt->fetchColumn();

        if ($used > 0) {
            $error = 'Cannot delete category – it is used by transactions.';
        } else {
            $stmt = $pdo->prepare("DELETE FROM categories WHERE id = :id");
            $stmt->execute([':id' => $id]);
            $message = 'Category deleted.';

            $stmt = $pdo->query("SELECT * FROM categories ORDER BY kind DESC, name");
            $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
    } catch (Throwable $e) {
        $error = 'Failed to delete category: ' . $e->getMessage();
    }
}

render_layout_start('Categories', 'categories');
?>

<div class="page-header">
    <div>
        <h1>Categories</h1>
        <p>Income and expense categories used to classify your transactions.</p>
    </div>
    <a href="category_form.php" class="btn-primary">Add category</a>
</div>

<?php if ($message): ?>
    <div class="alert alert-success"><?= h($message) ?></div>
<?php endif; ?>
<?php if ($error): ?>
    <div class="alert alert-error"><?= h($error) ?></div>
<?php endif; ?>

<section class="card card-full">
    <h3>Categories list</h3>

    <?php if (empty($categories)): ?>
        <p>No categories yet.</p>
    <?php else: ?>
        <table class="data-table">
            <thead>
            <tr>
                <th>Name</th>
                <th>Kind</th>
                <th>Debt payment?</th>
                <th style="width:1%;">Actions</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($categories as $cat): ?>
                <tr>
                    <td><?= h($cat['name']) ?></td>
                    <td><?= h(ucfirst($cat['kind'])) ?></td>
                    <td><?= (int)$cat['is_debt_payment'] ? 'Yes' : 'No' ?></td>
                    <td>
                        <div class="table-actions">
                            <a href="category_form.php?id=<?= (int)$cat['id'] ?>">Edit</a>
                            <form method="post" onsubmit="return confirm('Delete this category?');">
                                <input type="hidden" name="delete_id" value="<?= (int)$cat['id'] ?>">
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
