<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/layout.php';

// restrict accounts to type 'loan' or 'other_liability'
$allAccounts = get_all_accounts();
$loanAccounts = array_values(array_filter($allAccounts, function ($acc) {
    return in_array($acc['type'], ['loan', 'other_liability'], true);
}));

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$loan = [
    'account_id'       => '',
    'name'             => '',
    'lender'           => '',
    'principal_amount' => '',
    'start_date'       => date('Y-m-d'),
    'term_months'      => '',
    'nominal_rate'     => '',
    'monthly_payment'  => '',
    'due_day'          => '',
    'notes'            => '',
];

if ($id > 0 && $_SERVER['REQUEST_METHOD'] === 'GET') {
    $stmt = $pdo->prepare("SELECT * FROM loans WHERE id = :id");
    $stmt->execute([':id' => $id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($row) {
        $loan = $row;
    } else {
        $id = 0;
    }
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;

    $loan['account_id']       = $_POST['account_id'] ?? '';
    $loan['name']             = trim($_POST['name'] ?? '');
    $loan['lender']           = trim($_POST['lender'] ?? '');
    $loan['principal_amount'] = trim($_POST['principal_amount'] ?? '');
    $loan['start_date']       = $_POST['start_date'] ?? date('Y-m-d');
    $loan['term_months']      = trim($_POST['term_months'] ?? '');
    $loan['nominal_rate']     = trim($_POST['nominal_rate'] ?? '');
    $loan['monthly_payment']  = trim($_POST['monthly_payment'] ?? '');
    $loan['due_day']          = trim($_POST['due_day'] ?? '');
    $loan['notes']            = $_POST['notes'] ?? '';

    if ($loan['account_id'] === '') {
        $errors['account_id'] = 'Please choose the linked loan account.';
    }
    if ($loan['name'] === '') {
        $errors['name'] = 'Loan name is required.';
    }
    if ($loan['principal_amount'] === '' || !is_numeric($loan['principal_amount']) || (float)$loan['principal_amount'] <= 0) {
        $errors['principal_amount'] = 'Principal amount must be a positive number.';
    }
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $loan['start_date'])) {
        $errors['start_date'] = 'Invalid start date.';
    }
    if ($loan['monthly_payment'] !== '' && !is_numeric($loan['monthly_payment'])) {
        $errors['monthly_payment'] = 'Monthly payment must be numeric.';
    }
    if ($loan['nominal_rate'] !== '' && !is_numeric($loan['nominal_rate'])) {
        $errors['nominal_rate'] = 'Rate must be numeric (e.g. 4.50).';
    }
    if ($loan['term_months'] !== '' && !ctype_digit($loan['term_months'])) {
        $errors['term_months'] = 'Term must be integer months.';
    }
    if ($loan['due_day'] !== '' && !ctype_digit($loan['due_day'])) {
        $errors['due_day'] = 'Due day must be 1–31.';
    }

    if (empty($errors)) {
        $params = [
            ':account_id'       => (int)$loan['account_id'],
            ':name'             => $loan['name'],
            ':lender'           => $loan['lender'] !== '' ? $loan['lender'] : null,
            ':principal_amount' => (float)$loan['principal_amount'],
            ':start_date'       => $loan['start_date'],
            ':term_months'      => $loan['term_months'] !== '' ? (int)$loan['term_months'] : null,
            ':nominal_rate'     => $loan['nominal_rate'] !== '' ? (float)$loan['nominal_rate'] : null,
            ':monthly_payment'  => $loan['monthly_payment'] !== '' ? (float)$loan['monthly_payment'] : null,
            ':due_day'          => $loan['due_day'] !== '' ? (int)$loan['due_day'] : null,
            ':notes'            => $loan['notes'] !== '' ? $loan['notes'] : null,
        ];

        try {
            if ($id > 0) {
                $sql = "UPDATE loans
                        SET account_id = :account_id,
                            name = :name,
                            lender = :lender,
                            principal_amount = :principal_amount,
                            start_date = :start_date,
                            term_months = :term_months,
                            nominal_rate = :nominal_rate,
                            monthly_payment = :monthly_payment,
                            due_day = :due_day,
                            notes = :notes
                        WHERE id = :id";
                $params[':id'] = $id;
            } else {
                $sql = "INSERT INTO loans
                        (account_id, name, lender, principal_amount, start_date,
                         term_months, nominal_rate, monthly_payment, due_day, notes)
                        VALUES (:account_id, :name, :lender, :principal_amount, :start_date,
                                :term_months, :nominal_rate, :monthly_payment, :due_day, :notes)";
            }

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);

            header('Location: loans.php');
            exit;
        } catch (Throwable $e) {
            $errors['general'] = 'Failed to save loan: ' . $e->getMessage();
        }
    }
}

$pageTitle = $id > 0 ? 'Edit loan' : 'New loan';
render_layout_start($pageTitle, 'loans');
?>

<section class="card card-full">
    <h1><?= h($pageTitle) ?></h1>

    <?php if (!empty($errors['general'])): ?>
        <div class="alert alert-error"><?= h($errors['general']) ?></div>
    <?php endif; ?>

    <form method="post" class="form-grid">
        <input type="hidden" name="id" value="<?= (int)$id ?>">

        <div class="form-field">
            <label for="account_id">Loan account</label>
            <select id="account_id" name="account_id">
                <option value="">—</option>
                <?php foreach ($loanAccounts as $acc): ?>
                    <option value="<?= (int)$acc['id'] ?>"
                        <?= (string)$loan['account_id'] === (string)$acc['id'] ? 'selected' : '' ?>>
                        <?= h($acc['name']) ?> (<?= h($acc['type']) ?>)
                    </option>
                <?php endforeach; ?>
            </select>
            <?php if (!empty($errors['account_id'])): ?>
                <div class="field-error"><?= h($errors['account_id']) ?></div>
            <?php endif; ?>
            <div class="field-help">Use an account of type "loan" or "other_liability".</div>
        </div>

        <div class="form-field">
            <label for="name">Loan name</label>
            <input type="text" id="name" name="name"
                   value="<?= h($loan['name']) ?>">
            <?php if (!empty($errors['name'])): ?>
                <div class="field-error"><?= h($errors['name']) ?></div>
            <?php endif; ?>
        </div>

        <div class="form-field">
            <label for="lender">Lender</label>
            <input type="text" id="lender" name="lender"
                   value="<?= h($loan['lender']) ?>">
        </div>

        <div class="form-field">
            <label for="principal_amount">Principal amount (RM)</label>
            <input type="number" step="0.01" id="principal_amount" name="principal_amount"
                   value="<?= h($loan['principal_amount']) ?>">
            <?php if (!empty($errors['principal_amount'])): ?>
                <div class="field-error"><?= h($errors['principal_amount']) ?></div>
            <?php endif; ?>
        </div>

        <div class="form-field">
            <label for="start_date">Start date</label>
            <input type="date" id="start_date" name="start_date"
                   value="<?= h($loan['start_date']) ?>">
            <?php if (!empty($errors['start_date'])): ?>
                <div class="field-error"><?= h($errors['start_date']) ?></div>
            <?php endif; ?>
        </div>

        <div class="form-field">
            <label for="term_months">Term (months)</label>
            <input type="number" id="term_months" name="term_months"
                   value="<?= h($loan['term_months']) ?>">
            <?php if (!empty($errors['term_months'])): ?>
                <div class="field-error"><?= h($errors['term_months']) ?></div>
            <?php endif; ?>
        </div>

        <div class="form-field">
            <label for="nominal_rate">Interest rate (% per year)</label>
            <input type="number" step="0.01" id="nominal_rate" name="nominal_rate"
                   value="<?= h($loan['nominal_rate']) ?>">
            <?php if (!empty($errors['nominal_rate'])): ?>
                <div class="field-error"><?= h($errors['nominal_rate']) ?></div>
            <?php endif; ?>
        </div>

        <div class="form-field">
            <label for="monthly_payment">Monthly payment (RM)</label>
            <input type="number" step="0.01" id="monthly_payment" name="monthly_payment"
                   value="<?= h($loan['monthly_payment']) ?>">
            <?php if (!empty($errors['monthly_payment'])): ?>
                <div class="field-error"><?= h($errors['monthly_payment']) ?></div>
            <?php endif; ?>
        </div>

        <div class="form-field">
            <label for="due_day">Due day (1–31)</label>
            <input type="number" id="due_day" name="due_day"
                   value="<?= h($loan['due_day']) ?>">
            <?php if (!empty($errors['due_day'])): ?>
                <div class="field-error"><?= h($errors['due_day']) ?></div>
            <?php endif; ?>
        </div>

        <div class="form-field form-field-wide">
            <label for="notes">Notes</label>
            <textarea id="notes" name="notes" rows="3"><?= h($loan['notes']) ?></textarea>
        </div>

        <div class="form-actions">
            <a href="loans.php" class="btn-secondary">Back</a>
            <button type="submit" class="btn-primary">Save</button>
        </div>
    </form>
</section>

<?php
render_layout_end();
