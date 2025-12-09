<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/layout.php';

// restrict to credit card accounts
$allAccounts = get_all_accounts();
$cardAccounts = array_values(array_filter($allAccounts, function ($acc) {
    return $acc['type'] === 'credit_card';
}));

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$plan = [
    'account_id'      => '',
    'title'           => '',
    'merchant'        => '',
    'original_amount' => '',
    'term_months'     => '',
    'monthly_payment' => '',
    'start_date'      => date('Y-m-d'),
    'due_day'         => '',
    'closed_at'       => '',
    'notes'           => '',
];

if ($id > 0 && $_SERVER['REQUEST_METHOD'] === 'GET') {
    $stmt = $pdo->prepare("SELECT * FROM installment_plans WHERE id = :id");
    $stmt->execute([':id' => $id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($row) {
        $plan = $row;
    } else {
        $id = 0;
    }
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;

    $plan['account_id']      = $_POST['account_id'] ?? '';
    $plan['title']           = trim($_POST['title'] ?? '');
    $plan['merchant']        = trim($_POST['merchant'] ?? '');
    $plan['original_amount'] = trim($_POST['original_amount'] ?? '');
    $plan['term_months']     = trim($_POST['term_months'] ?? '');
    $plan['monthly_payment'] = trim($_POST['monthly_payment'] ?? '');
    $plan['start_date']      = $_POST['start_date'] ?? date('Y-m-d');
    $plan['due_day']         = trim($_POST['due_day'] ?? '');
    $plan['closed_at']       = trim($_POST['closed_at'] ?? '');
    $plan['notes']           = $_POST['notes'] ?? '';

    if ($plan['account_id'] === '') {
        $errors['account_id'] = 'Please choose the credit card account.';
    }
    if ($plan['title'] === '') {
        $errors['title'] = 'Title is required.';
    }
    if ($plan['original_amount'] === '' || !is_numeric($plan['original_amount']) || (float)$plan['original_amount'] <= 0) {
        $errors['original_amount'] = 'Original amount must be a positive number.';
    }
    if ($plan['term_months'] === '' || !ctype_digit($plan['term_months'])) {
        $errors['term_months'] = 'Term must be integer months.';
    }
    if ($plan['monthly_payment'] !== '' && !is_numeric($plan['monthly_payment'])) {
        $errors['monthly_payment'] = 'Monthly payment must be numeric.';
    }
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $plan['start_date'])) {
        $errors['start_date'] = 'Invalid start date.';
    }
    if ($plan['due_day'] !== '' && !ctype_digit($plan['due_day'])) {
        $errors['due_day'] = 'Due day must be 1–31.';
    }
    if ($plan['closed_at'] !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $plan['closed_at'])) {
        $errors['closed_at'] = 'Invalid closed date.';
    }

    if (empty($errors)) {
        $params = [
            ':account_id'      => (int)$plan['account_id'],
            ':title'           => $plan['title'],
            ':merchant'        => $plan['merchant'] !== '' ? $plan['merchant'] : null,
            ':original_amount' => (float)$plan['original_amount'],
            ':term_months'     => (int)$plan['term_months'],
            ':monthly_payment' => $plan['monthly_payment'] !== '' ? (float)$plan['monthly_payment'] : null,
            ':start_date'      => $plan['start_date'],
            ':due_day'         => $plan['due_day'] !== '' ? (int)$plan['due_day'] : null,
            ':closed_at'       => $plan['closed_at'] !== '' ? $plan['closed_at'] : null,
            ':notes'           => $plan['notes'] !== '' ? $plan['notes'] : null,
        ];

        try {
            if ($id > 0) {
                $sql = "UPDATE installment_plans
                        SET account_id = :account_id,
                            title = :title,
                            merchant = :merchant,
                            original_amount = :original_amount,
                            term_months = :term_months,
                            monthly_payment = :monthly_payment,
                            start_date = :start_date,
                            due_day = :due_day,
                            closed_at = :closed_at,
                            notes = :notes
                        WHERE id = :id";
                $params[':id'] = $id;
            } else {
                $sql = "INSERT INTO installment_plans
                        (account_id, title, merchant, original_amount, term_months,
                         monthly_payment, start_date, due_day, closed_at, notes)
                        VALUES (:account_id, :title, :merchant, :original_amount, :term_months,
                                :monthly_payment, :start_date, :due_day, :closed_at, :notes)";
            }

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);

            header('Location: installments.php');
            exit;
        } catch (Throwable $e) {
            $errors['general'] = 'Failed to save installment plan: ' . $e->getMessage();
        }
    }
}

$pageTitle = $id > 0 ? 'Edit installment plan' : 'New installment plan';
render_layout_start($pageTitle, 'installments');
?>

<section class="card card-full">
    <h1><?= h($pageTitle) ?></h1>

    <?php if (!empty($errors['general'])): ?>
        <div class="alert alert-error"><?= h($errors['general']) ?></div>
    <?php endif; ?>

    <form method="post" class="form-grid">
        <input type="hidden" name="id" value="<?= (int)$id ?>">

        <div class="form-field">
            <label for="account_id">Credit card account</label>
            <select id="account_id" name="account_id">
                <option value="">—</option>
                <?php foreach ($cardAccounts as $acc): ?>
                    <option value="<?= (int)$acc['id'] ?>"
                        <?= (string)$plan['account_id'] === (string)$acc['id'] ? 'selected' : '' ?>>
                        <?= h($acc['name']) ?> (<?= h($acc['type']) ?>)
                    </option>
                <?php endforeach; ?>
            </select>
            <?php if (!empty($errors['account_id'])): ?>
                <div class="field-error"><?= h($errors['account_id']) ?></div>
            <?php endif; ?>
        </div>

        <div class="form-field">
            <label for="title">Title</label>
            <input type="text" id="title" name="title"
                   value="<?= h($plan['title']) ?>">
            <?php if (!empty($errors['title'])): ?>
                <div class="field-error"><?= h($errors['title']) ?></div>
            <?php endif; ?>
        </div>

        <div class="form-field">
            <label for="merchant">Merchant</label>
            <input type="text" id="merchant" name="merchant"
                   value="<?= h($plan['merchant']) ?>">
        </div>

        <div class="form-field">
            <label for="original_amount">Original amount (RM)</label>
            <input type="number" step="0.01" id="original_amount" name="original_amount"
                   value="<?= h($plan['original_amount']) ?>">
            <?php if (!empty($errors['original_amount'])): ?>
                <div class="field-error"><?= h($errors['original_amount']) ?></div>
            <?php endif; ?>
        </div>

        <div class="form-field">
            <label for="term_months">Term (months)</label>
            <input type="number" id="term_months" name="term_months"
                   value="<?= h($plan['term_months']) ?>">
            <?php if (!empty($errors['term_months'])): ?>
                <div class="field-error"><?= h($errors['term_months']) ?></div>
            <?php endif; ?>
        </div>

        <div class="form-field">
            <label for="monthly_payment">Monthly payment (RM)</label>
            <input type="number" step="0.01" id="monthly_payment" name="monthly_payment"
                   value="<?= h($plan['monthly_payment']) ?>">
            <?php if (!empty($errors['monthly_payment'])): ?>
                <div class="field-error"><?= h($errors['monthly_payment']) ?></div>
            <?php endif; ?>
        </div>

        <div class="form-field">
            <label for="start_date">Start date</label>
            <input type="date" id="start_date" name="start_date"
                   value="<?= h($plan['start_date']) ?>">
            <?php if (!empty($errors['start_date'])): ?>
                <div class="field-error"><?= h($errors['start_date']) ?></div>
            <?php endif; ?>
        </div>

        <div class="form-field">
            <label for="due_day">Due day (1–31)</label>
            <input type="number" id="due_day" name="due_day"
                   value="<?= h($plan['due_day']) ?>">
            <?php if (!empty($errors['due_day'])): ?>
                <div class="field-error"><?= h($errors['due_day']) ?></div>
            <?php endif; ?>
        </div>

        <div class="form-field">
            <label for="closed_at">Closed at (optional)</label>
            <input type="date" id="closed_at" name="closed_at"
                   value="<?= h($plan['closed_at']) ?>">
            <?php if (!empty($errors['closed_at'])): ?>
                <div class="field-error"><?= h($errors['closed_at']) ?></div>
            <?php endif; ?>
        </div>

        <div class="form-field form-field-wide">
            <label for="notes">Notes</label>
            <textarea id="notes" name="notes" rows="3"><?= h($plan['notes']) ?></textarea>
        </div>

        <div class="form-actions">
            <a href="installments.php" class="btn-secondary">Back</a>
            <button type="submit" class="btn-primary">Save</button>
        </div>
    </form>
</section>

<?php
render_layout_end();
