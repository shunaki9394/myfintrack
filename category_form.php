<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/layout.php';

$kinds = ['income', 'expense'];
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$category = [
    'name'           => '',
    'kind'           => 'expense',
    'is_debt_payment'=> 0,
];

if ($id > 0 && $_SERVER['REQUEST_METHOD'] === 'GET') {
    $stmt = $pdo->prepare("SELECT * FROM categories WHERE id = :id");
    $stmt->execute([':id' => $id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($row) {
        $category = $row;
    } else {
        $id = 0;
    }
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;

    $category['name']           = trim($_POST['name'] ?? '');
    $category['kind']           = in_array($_POST['kind'] ?? '', $kinds, true) ? $_POST['kind'] : 'expense';
    $category['is_debt_payment']= isset($_POST['is_debt_payment']) ? 1 : 0;

    if ($category['name'] === '') {
        $errors['name'] = 'Name is required.';
    }

    if (empty($errors)) {
        $params = [
            ':name'           => $category['name'],
            ':kind'           => $category['kind'],
            ':is_debt_payment'=> $category['is_debt_payment'],
        ];

        try {
            if ($id > 0) {
                $sql = "UPDATE categories
                        SET name = :name,
                            kind = :kind,
                            is_debt_payment = :is_debt_payment
                        WHERE id = :id";
                $params[':id'] = $id;
            } else {
                $sql = "INSERT INTO categories (name, kind, is_debt_payment)
                        VALUES (:name, :kind, :is_debt_payment)";
            }

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);

            header('Location: categories.php');
            exit;
        } catch (Throwable $e) {
            $errors['general'] = 'Failed to save category: ' . $e->getMessage();
        }
    }
}

$pageTitle = $id > 0 ? 'Edit category' : 'New category';
render_layout_start($pageTitle, 'categories');
?>

<section class="card card-full">
    <h1><?= h($pageTitle) ?></h1>

    <?php if (!empty($errors['general'])): ?>
        <div class="alert alert-error"><?= h($errors['general']) ?></div>
    <?php endif; ?>

    <form method="post" class="form-grid">
        <input type="hidden" name="id" value="<?= (int)$id ?>">

        <div class="form-field">
            <label for="name">Name</label>
            <input type="text" id="name" name="name"
                   value="<?= h($category['name']) ?>">
            <?php if (!empty($errors['name'])): ?>
                <div class="field-error"><?= h($errors['name']) ?></div>
            <?php endif; ?>
        </div>

        <div class="form-field">
            <label for="kind">Kind</label>
            <select id="kind" name="kind">
                <?php foreach ($kinds as $k): ?>
                    <option value="<?= h($k) ?>"
                        <?= $category['kind'] === $k ? 'selected' : '' ?>>
                        <?= h(ucfirst($k)) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-field">
            <label class="checkbox">
                <input type="checkbox" name="is_debt_payment" value="1"
                    <?= $category['is_debt_payment'] ? 'checked' : '' ?>>
                This category is a loan / card payment (used for DSR)
            </label>
        </div>

        <div class="form-actions">
            <a href="categories.php" class="btn-secondary">Back</a>
            <button type="submit" class="btn-primary">Save</button>
        </div>
    </form>
</section>

<?php
render_layout_end();
