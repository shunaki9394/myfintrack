<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/layout.php';

$types = [
    'cash',
    'bank',
    'investment',
    'credit_card',
    'loan',
    'other_asset',
    'other_liability',
];

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$account = [
    'name'            => '',
    'type'            => 'bank',
    'is_liquid'       => 1,
    'is_net_worth'    => 1,
    'opening_balance' => '0.00',
];

if ($id > 0 && $_SERVER['REQUEST_METHOD'] === 'GET') {
    $stmt = $pdo->prepare("SELECT * FROM accounts WHERE id = :id");
    $stmt->execute([':id' => $id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($row) {
        $account = $row;
    } else {
        $id = 0;
    }
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;

    $account['name']            = trim($_POST['name'] ?? '');
    $account['type']            = in_array($_POST['type'] ?? '', $types, true) ? $_POST['type'] : 'bank';
    $account['is_liquid']       = isset($_POST['is_liquid']) ? 1 : 0;
    $account['is_net_worth']    = isset($_POST['is_net_worth']) ? 1 : 0;
    $account['opening_balance'] = trim($_POST['opening_balance'] ?? '0.00');

    if ($account['name'] === '') {
        $errors['name'] = 'Name is required.';
    }

    if ($account['opening_balance'] === '' || !is_numeric($account['opening_balance'])) {
        $errors['opening_balance'] = 'Opening balance must be a number.';
    }

    if (empty($errors)) {
        $params = [
            ':name'            => $account['name'],
            ':type'            => $account['type'],
            ':is_liquid'       => $account['is_liquid'],
            ':is_net_worth'    => $account['is_net_worth'],
            ':opening_balance' => (float)$account['opening_balance'],
        ];

        try {
            if ($id > 0) {
                $sql = "UPDATE accounts
                        SET name = :name,
                            type = :type,
                            is_liquid = :is_liquid,
                            is_net_worth = :is_net_worth,
                            opening_balance = :opening_balance
                        WHERE id = :id";
                $params[':id'] = $id;
            } else {
                $sql = "INSERT INTO accounts
                        (name, type, is_liquid, is_net_worth, opening_balance)
                        VALUES (:name, :type, :is_liquid, :is_net_worth, :opening_balance)";
            }

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);

            header('Location: accounts.php');
            exit;
        } catch (Throwable $e) {
            $errors['general'] = 'Failed to save account: ' . $e->getMessage();
        }
    }
}

$pageTitle = $id > 0 ? 'Edit account' : 'New account';
render_layout_start($pageTitle, 'accounts');
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
                   value="<?= h($account['name']) ?>">
            <?php if (!empty($errors['name'])): ?>
                <div class="field-error"><?= h($errors['name']) ?></div>
            <?php endif; ?>
        </div>

        <div class="form-field">
            <label for="type">Type</label>
            <select id="type" name="type">
                <?php foreach ($types as $t): ?>
                    <option value="<?= h($t) ?>"
                        <?= $account['type'] === $t ? 'selected' : '' ?>>
                        <?= h(ucwords(str_replace('_', ' ', $t))) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-field">
            <label class="checkbox">
                <input type="checkbox" name="is_liquid" value="1"
                    <?= $account['is_liquid'] ? 'checked' : '' ?>>
                Liquid asset (can be used in emergency fund)
            </label>
        </div>

        <div class="form-field">
            <label class="checkbox">
                <input type="checkbox" name="is_net_worth" value="1"
                    <?= $account['is_net_worth'] ? 'checked' : '' ?>>
                Include in net worth
            </label>
        </div>

        <div class="form-field">
            <label for="opening_balance">Opening balance (RM)</label>
            <input type="number" step="0.01" id="opening_balance" name="opening_balance"
                   value="<?= h($account['opening_balance']) ?>">
            <?php if (!empty($errors['opening_balance'])): ?>
                <div class="field-error"><?= h($errors['opening_balance']) ?></div>
            <?php endif; ?>
            <div class="field-help">For loans or card balances, use negative numbers.</div>
        </div>

        <div class="form-actions">
            <a href="accounts.php" class="btn-secondary">Back</a>
            <button type="submit" class="btn-primary">Save</button>
        </div>
    </form>
</section>

<?php
render_layout_end();
