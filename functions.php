<?php
// functions.php
// Shared helpers + financial metrics

// HTML escape helper
function h(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

// Ensure month format YYYY-MM
function normalize_month(?string $ym): string
{
    if (!is_string($ym) || !preg_match('/^\d{4}-\d{2}$/', $ym)) {
        return date('Y-m');
    }
    return $ym;
}

// Get month start/end dates (Y-m-d)
function get_month_start_end(string $ym): array
{
    $ym = normalize_month($ym);
    $start = $ym . '-01';
    $d = new DateTime($start);
    $d->modify('last day of this month');
    $end = $d->format('Y-m-d');
    return [$start, $end];
}

// Accounts + balances as of a date
// returns [accountsById, balancesById]
function get_account_balances(string $asOfDate): array
{
    global $pdo;

    $stmt = $pdo->query("SELECT * FROM accounts ORDER BY name");
    $accounts = [];
    $balances = [];

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $id = (int)$row['id'];
        $accounts[$id] = $row;
        $balances[$id] = (float)$row['opening_balance'];
    }

    $txStmt = $pdo->prepare(
        "SELECT * FROM transactions
         WHERE booked_at <= :end
           AND (deleted_at IS NULL OR deleted_at = '0000-00-00 00:00:00')"
    );
    $txStmt->execute([':end' => $asOfDate]);

    while ($tx = $txStmt->fetch(PDO::FETCH_ASSOC)) {
        $amt = (float)$tx['amount'];
        $type = $tx['type'];
        $from = $tx['from_account_id'] ? (int)$tx['from_account_id'] : null;
        $to   = $tx['to_account_id']   ? (int)$tx['to_account_id']   : null;

        if ($type === 'income') {
            if ($to && array_key_exists($to, $balances)) {
                $balances[$to] += $amt;
            }
        } elseif ($type === 'expense') {
            if ($from && array_key_exists($from, $balances)) {
                $balances[$from] -= $amt;
            }
        } elseif ($type === 'transfer') {
            if ($from && array_key_exists($from, $balances)) {
                $balances[$from] -= $amt;
            }
            if ($to && array_key_exists($to, $balances)) {
                $balances[$to] += $amt;
            }
        }
    }

    return [$accounts, $balances];
}

// Month-level metrics for dashboard
function get_month_metrics(string $ym): array
{
    global $pdo;

    [$start, $end] = get_month_start_end($ym);

    // Income
    $stmt = $pdo->prepare(
        "SELECT COALESCE(SUM(amount),0)
         FROM transactions
         WHERE type='income'
           AND booked_at BETWEEN :s AND :e
           AND (deleted_at IS NULL OR deleted_at = '0000-00-00 00:00:00')"
    );
    $stmt->execute([':s' => $start, ':e' => $end]);
    $income = (float)$stmt->fetchColumn();

    // Expenses
    $stmt = $pdo->prepare(
        "SELECT COALESCE(SUM(amount),0)
         FROM transactions
         WHERE type='expense'
           AND booked_at BETWEEN :s AND :e
           AND (deleted_at IS NULL OR deleted_at = '0000-00-00 00:00:00')"
    );
    $stmt->execute([':s' => $start, ':e' => $end]);
    $expenses = (float)$stmt->fetchColumn();

    $savings = $income - $expenses;

    // Debt payments (categories flagged as is_debt_payment = 1)
    $stmt = $pdo->prepare(
        "SELECT COALESCE(SUM(t.amount),0)
         FROM transactions t
         JOIN categories c ON t.category_id = c.id
         WHERE t.type='expense'
           AND c.is_debt_payment = 1
           AND t.booked_at BETWEEN :s AND :e
           AND (t.deleted_at IS NULL OR t.deleted_at = '0000-00-00 00:00:00')"
    );
    $stmt->execute([':s' => $start, ':e' => $end]);
    $debtPayments = (float)$stmt->fetchColumn();

    // Net worth / assets / liabilities / liquid assets
    [$accounts, $balances] = get_account_balances($end);
    $totalAssets = 0.0;
    $totalLiabilities = 0.0;
    $liquidAssets = 0.0;

    foreach ($accounts as $id => $acc) {
        $bal = $balances[$id] ?? 0.0;
        $isNetWorth = (int)$acc['is_net_worth'] === 1;
        $isLiquid   = (int)$acc['is_liquid'] === 1;
        $type       = $acc['type'];

        $isLiabilityType = in_array($type, ['credit_card','loan','other_liability'], true);

        if ($isNetWorth) {
            if ($isLiabilityType) {
                if ($bal < 0) {
                    $totalLiabilities += -$bal;
                } else {
                    $totalLiabilities += $bal;
                }
            } else {
                if ($bal > 0) {
                    $totalAssets += $bal;
                }
            }
        }

        if ($isLiquid && !$isLiabilityType && $bal > 0) {
            $liquidAssets += $bal;
        }
    }

    $netWorth = $totalAssets - $totalLiabilities;

    $savingsRate = ($income > 0)
        ? ($savings / $income * 100.0)
        : null;

    $debtServiceRatio = ($income > 0 && $debtPayments > 0)
        ? ($debtPayments / $income * 100.0)
        : null;

    $emergencyMonths = ($expenses > 0 && $liquidAssets > 0)
        ? ($liquidAssets / $expenses)
        : null;

    return [
        'income'           => $income,
        'expenses'         => $expenses,
        'savings'          => $savings,
        'savingsRate'      => $savingsRate,
        'debtPayments'     => $debtPayments,
        'debtServiceRatio' => $debtServiceRatio,
        'totalAssets'      => $totalAssets,
        'totalLiabilities' => $totalLiabilities,
        'netWorth'         => $netWorth,
        'liquidAssets'     => $liquidAssets,
        'emergencyMonths'  => $emergencyMonths,
    ];
}

// Month select options (last N months including current)
function get_month_options(int $monthsBack = 6): array
{
    $opts = [];
    $d = new DateTime('first day of this month');
    for ($i = 0; $i < $monthsBack; $i++) {
        $opts[] = [
            'value' => $d->format('Y-m'),
            'label' => $d->format('F Y'),
        ];
        $d->modify('-1 month');
    }
    return $opts;
}

// History rows for last N months (from current month backwards)
function get_history_rows(int $months = 6): array
{
    $rows = [];
    $d = new DateTime('first day of this month');

    for ($i = 0; $i < $months; $i++) {
        $ym = $d->format('Y-m');
        $label = $d->format('F Y');
        $m = get_month_metrics($ym);

        $rows[] = [
            'label'        => $label,
            'income'       => $m['income'],
            'expenses'     => $m['expenses'],
            'savings'      => $m['savings'],
            'savingsRate'  => $m['savingsRate'],
            'netWorth'     => $m['netWorth'],
        ];

        $d->modify('-1 month');
    }

    return $rows;
}

// Loan snapshot for dashboard
function get_loan_summary(): array
{
    global $pdo;

    $stmt = $pdo->query(
        "SELECT
             COUNT(*) AS cnt,
             COALESCE(SUM(COALESCE(monthly_payment,0)),0) AS monthly
         FROM loans"
    );
    $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: ['cnt' => 0, 'monthly' => 0];

    return [
        'active_loans'        => (int)$row['cnt'],
        'monthly_installment' => (float)$row['monthly'],
        'due_soon'            => 0, // placeholder, can improve later
    ];
}

// Card installment snapshot for dashboard
function get_installment_summary(): array
{
    global $pdo;

    $stmt = $pdo->query(
        "SELECT
             COUNT(*) AS cnt,
             COALESCE(SUM(monthly_payment),0) AS monthly
         FROM installment_plans
         WHERE closed_at IS NULL"
    );
    $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: ['cnt' => 0, 'monthly' => 0];

    return [
        'active_plans'        => (int)$row['cnt'],
        'monthly_installment' => (float)$row['monthly'],
        'due_soon'            => 0, // placeholder, can improve later
    ];
}

// Simple helpers for forms
function get_all_accounts(): array
{
    global $pdo;
    $stmt = $pdo->query("SELECT * FROM accounts ORDER BY name");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function get_all_categories(): array
{
    global $pdo;
    $stmt = $pdo->query("SELECT * FROM categories ORDER BY kind DESC, name");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
