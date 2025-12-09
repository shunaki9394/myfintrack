<?php
// functions.php

function format_money(float $value): string
{
    $prefix = $value < 0 ? '- RM' : 'RM';
    return $prefix . number_format(abs($value), 2);
}

function format_percent(?float $value): string
{
    if ($value === null) {
        return '-';
    }
    return number_format($value, 1) . '%';
}

function is_liability_type(string $type): bool
{
    return in_array($type, ['credit_card', 'loan', 'other_liability'], true);
}

function human_account_type(string $type): string
{
    return ucwords(str_replace('_', ' ', $type));
}

function get_default_month(): string
{
    return date('Y-m'); // current YYYY-MM
}

function get_month_options(int $monthsBack = 12): array
{
    $options = [];
    $dt = new DateTime('first day of this month');
    for ($i = 0; $i < $monthsBack; $i++) {
        $ym = $dt->format('Y-m');
        $options[] = [
            'value' => $ym,
            'label' => $dt->format('F Y'),
        ];
        $dt->modify('-1 month');
    }
    return $options;
}

function parse_year_month(string $ym): array
{
    if (!preg_match('/^(\d{4})-(\d{2})$/', $ym, $m)) {
        throw new InvalidArgumentException('Invalid month format, expected YYYY-MM');
    }
    return [(int)$m[1], (int)$m[2]];
}

function get_accounts(PDO $pdo): array
{
    $stmt = $pdo->query("SELECT * FROM accounts ORDER BY name");
    return $stmt->fetchAll();
}

/**
 * Get accounts with calculated balances.
 * If $cutoffDate is provided (Y-m-d), only transactions up to that date are counted.
 */
function get_account_balances(PDO $pdo, ?string $cutoffDate = null): array
{
    $sql = "
      SELECT
        a.*,
        a.opening_balance
        + COALESCE((
            SELECT SUM(t.amount)
            FROM transactions t
            WHERE t.to_account_id = a.id
              AND t.deleted_at IS NULL
              AND (:cutoff IS NULL OR t.booked_at <= :cutoff)
          ), 0)
        - COALESCE((
            SELECT SUM(t.amount)
            FROM transactions t
            WHERE t.from_account_id = a.id
              AND t.deleted_at IS NULL
              AND (:cutoff IS NULL OR t.booked_at <= :cutoff)
          ), 0) AS balance
      FROM accounts a
      ORDER BY a.name
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['cutoff' => $cutoffDate]);
    return $stmt->fetchAll();
}


function get_categories_grouped(PDO $pdo): array
{
    $stmt = $pdo->query("SELECT * FROM categories ORDER BY kind, name");
    $grouped = ['income' => [], 'expense' => []];
    foreach ($stmt as $row) {
        $grouped[$row['kind']][] = $row;
    }
    return $grouped;
}

function get_categories_indexed(PDO $pdo): array
{
    $stmt = $pdo->query("SELECT * FROM categories");
    $out = [];
    foreach ($stmt as $row) {
        $out[$row['id']] = $row;
    }
    return $out;
}

/**
 * Compute metrics for a given month (YYYY-MM).
 */
function get_month_metrics(PDO $pdo, string $ym): array
{
    [$year, $month] = parse_year_month($ym);

    $start = sprintf('%04d-%02d-01', $year, $month);
    $startDt = new DateTime($start);
    $endDt = clone $startDt;
    $endDt->modify('last day of this month');
    $end = $endDt->format('Y-m-d');

    // Income
    $stmt = $pdo->prepare("
        SELECT COALESCE(SUM(amount), 0) AS total
        FROM transactions
        WHERE type = 'income'
          AND deleted_at IS NULL
          AND booked_at BETWEEN :start AND :end
    ");
    $stmt->execute(['start' => $start, 'end' => $end]);
    $income = (float)$stmt->fetchColumn();

    // Expenses
    $stmt = $pdo->prepare("
        SELECT COALESCE(SUM(amount), 0) AS total
        FROM transactions
        WHERE type = 'expense'
          AND deleted_at IS NULL
          AND booked_at BETWEEN :start AND :end
    ");
    $stmt->execute(['start' => $start, 'end' => $end]);
    $expenses = (float)$stmt->fetchColumn();

    // Debt payments (for DSR)
    $stmt = $pdo->prepare("
        SELECT COALESCE(SUM(t.amount), 0) AS total
        FROM transactions t
        JOIN categories c ON t.category_id = c.id
        WHERE t.type = 'expense'
          AND t.deleted_at IS NULL
          AND c.is_debt_payment = 1
          AND t.booked_at BETWEEN :start AND :end
    ");
    $stmt->execute(['start' => $start, 'end' => $end]);
    $debtPayments = (float)$stmt->fetchColumn();

    $savings = $income - $expenses;
    $savingsRate = $income > 0 ? ($savings / $income * 100.0) : null;
    $dsr = $income > 0 ? ($debtPayments / $income * 100.0) : null;

    // Balances as of end of month (get_account_balances already ignores deleted)
    $accounts = get_account_balances($pdo, $end);
    $assetTypes      = ['cash', 'bank', 'investment', 'other_asset'];
    $liabilityTypes  = ['credit_card', 'loan', 'other_liability'];

    $totalAssets = 0.0;
    $totalLiabilities = 0.0;
    $liquidAssets = 0.0;

    foreach ($accounts as $acc) {
        $balance = (float)$acc['balance'];

        if ($acc['is_net_worth']) {
            if (in_array($acc['type'], $assetTypes, true)) {
                $totalAssets += $balance;
            } elseif (in_array($acc['type'], $liabilityTypes, true)) {
                $totalLiabilities += abs($balance);
            }
        }
        if ($acc['is_liquid'] && in_array($acc['type'], $assetTypes, true)) {
            $liquidAssets += $balance;
        }
    }

    $netWorth = $totalAssets - $totalLiabilities;
    $emergencyMonths = $expenses > 0 ? ($liquidAssets / $expenses) : null;

    return [
        'month'            => $ym,
        'month_label'      => $startDt->format('F Y'),
        'start_date'       => $start,
        'end_date'         => $end,
        'income'           => $income,
        'expenses'         => $expenses,
        'savings'          => $savings,
        'savings_rate'     => $savingsRate,
        'debt_payments'    => $debtPayments,
        'dsr'              => $dsr,
        'total_assets'     => $totalAssets,
        'total_liabilities'=> $totalLiabilities,
        'net_worth'        => $netWorth,
        'liquid_assets'    => $liquidAssets,
        'emergency_months' => $emergencyMonths,
        'accounts'         => $accounts,
    ];
}


// Simple month difference between two dates (ignores days)
function months_between_dates(string $startDate, string $endDate): int
{
    $start = new DateTime($startDate);
    $end   = new DateTime($endDate);
    if ($end < $start) {
        return 0;
    }
    $diff = $start->diff($end);
    return $diff->y * 12 + $diff->m;
}

/**
 * Get next due date based on a fixed day-of-month.
 */
function compute_next_due_date(?int $dueDay, ?string $fromDate = null): ?string
{
    if ($dueDay === null) {
        return null;
    }
    $from = new DateTime($fromDate ?: 'today');
    $year = (int)$from->format('Y');
    $month = (int)$from->format('m');

    // Candidate this month
    $candidate = new DateTime();
    $candidate->setDate($year, $month, 1);
    $daysInMonth = (int)$candidate->format('t');
    $day = min(max($dueDay, 1), $daysInMonth);
    $candidate->setDate($year, $month, $day);

    if ($candidate < $from) {
        // Move to next month
        $candidate->modify('first day of next month');
        $daysInMonth = (int)$candidate->format('t');
        $day = min(max($dueDay, 1), $daysInMonth);
        $candidate->setDate(
            (int)$candidate->format('Y'),
            (int)$candidate->format('m'),
            $day
        );
    }

    return $candidate->format('Y-m-d');
}

/**
 * Return all loans with computed stats:
 *  - outstanding (from linked account balance)
 *  - months elapsed / remaining
 *  - percent paid (approx principal repaid)
 *  - status (On track / Behind / Completed)
 */
function get_loans_with_stats(PDO $pdo): array
{
    // Get balances for all accounts as of today
    $accounts = get_account_balances($pdo, null);
    $accountById = [];
    foreach ($accounts as $acc) {
        $accountById[$acc['id']] = $acc;
    }

    $sql = "
        SELECT
          l.*,
          a.name AS account_name,
          a.type AS account_type
        FROM loans l
        JOIN accounts a ON l.account_id = a.id
        ORDER BY l.start_date, l.id
    ";
    $stmt = $pdo->query($sql);
    $loans = [];
    $today = date('Y-m-d');

    foreach ($stmt as $row) {
        $principal = (float)$row['principal_amount'];
        $termMonths = $row['term_months'] !== null ? (int)$row['term_months'] : null;
        $monthlyPayment = $row['monthly_payment'] !== null ? (float)$row['monthly_payment'] : null;

        $acc = $accountById[$row['account_id']] ?? null;
        $balance = $acc ? (float)$acc['balance'] : 0.0;
        // Outstanding is "amount still owed" = absolute value of liability balance
        $outstanding = abs($balance);

        $monthsElapsed = months_between_dates($row['start_date'], $today);
        if ($termMonths !== null) {
            $monthsElapsed = min($monthsElapsed, $termMonths);
        }
        $monthsRemaining = $termMonths !== null
            ? max($termMonths - $monthsElapsed, 0)
            : null;

        // Approx principal repaid = principal - outstanding (clamped)
        $paidApprox = max($principal - $outstanding, 0.0);
        $progressPct = $principal > 0 ? ($paidApprox / $principal * 100.0) : null;

        $expectedPaid = null;
        if ($monthlyPayment !== null && $monthsElapsed > 0) {
            $expectedPaid = $monthlyPayment * $monthsElapsed;
        }

        $status = 'Active';
        $statusClass = 'pill-warning';

        if ($outstanding <= 1) {
            $status = 'Completed';
            $statusClass = 'pill-positive';
        } elseif ($expectedPaid !== null) {
            // Allow small tolerance of RM100 to avoid noise
            if ($paidApprox + 100 < $expectedPaid) {
                $status = 'Behind schedule';
                $statusClass = 'pill-danger';
            } else {
                $status = 'On track';
                $statusClass = 'pill-positive';
            }
        }

        $nextDue = compute_next_due_date(
            $row['due_day'] !== null ? (int)$row['due_day'] : null,
            $today
        );

        $loans[] = [
            'id'               => (int)$row['id'],
            'name'             => $row['name'],
            'lender'           => $row['lender'],
            'principal'        => $principal,
            'start_date'       => $row['start_date'],
            'term_months'      => $termMonths,
            'months_elapsed'   => $monthsElapsed,
            'months_remaining' => $monthsRemaining,
            'rate'             => $row['nominal_rate'],
            'monthly_payment'  => $monthlyPayment,
            'due_day'          => $row['due_day'],
            'next_due_date'    => $nextDue,
            'notes'            => $row['notes'],
            'account_id'       => (int)$row['account_id'],
            'account_name'     => $row['account_name'],
            'account_type'     => $row['account_type'],
            'outstanding'      => $outstanding,
            'paid_approx'      => $paidApprox,
            'progress_pct'     => $progressPct,
            'status'           => $status,
            'status_class'     => $statusClass,
        ];
    }

    return $loans;
}

// ---------------------------------------------------------------------
// Credit card installment plans
// ---------------------------------------------------------------------

/**
 * Returns only credit card accounts (type = 'credit_card').
 */
function get_credit_card_accounts(PDO $pdo): array
{
    $stmt = $pdo->prepare("SELECT * FROM accounts WHERE type = 'credit_card' ORDER BY name");
    $stmt->execute();
    return $stmt->fetchAll();
}

/**
 * Get all installment plans with computed stats:
 *  - months_elapsed
 *  - remaining_count (instalments left)
 *  - progress_pct
 *  - next_due_date
 *  - status (Active / Completed)
 */
function get_installment_plans_with_stats(PDO $pdo): array
{
    $sql = "
      SELECT
        i.*,
        a.name AS account_name,
        a.type AS account_type
      FROM installment_plans i
      JOIN accounts a ON i.account_id = a.id
      ORDER BY i.start_date, i.id
    ";
    $stmt = $pdo->query($sql);

    $plans = [];
    $today = date('Y-m-d');

    foreach ($stmt as $row) {
        $termMonths     = (int)$row['term_months'];
        $monthlyPayment = (float)$row['monthly_payment'];
        $startDate      = $row['start_date'];

        $monthsElapsed = months_between_dates($startDate, $today);
        if ($monthsElapsed < 0) {
            $monthsElapsed = 0;
        }
        if ($termMonths > 0) {
            $monthsElapsed = min($monthsElapsed, $termMonths);
        }

        // If closed_at is set, treat as fully paid.
        if (!empty($row['closed_at'])) {
            $monthsElapsed = $termMonths;
        }

        $paidCount      = $monthsElapsed;
        $remainingCount = max($termMonths - $paidCount, 0);
        $progressPct    = $termMonths > 0 ? ($paidCount / $termMonths * 100.0) : null;

        $dueDay = $row['due_day'] !== null ? (int)$row['due_day'] : null;
        $nextDueDate = compute_next_due_date($dueDay, $today);

        $status = 'Active';
        $statusClass = 'pill-warning';

        if ($remainingCount <= 0) {
            $status = 'Completed';
            $statusClass = 'pill-positive';
        } else {
            // We are not tracking actual payment vs expected here, so just mark as Active.
            $status = 'Active';
            $statusClass = 'pill-positive';
        }

        $plans[] = [
            'id'              => (int)$row['id'],
            'title'           => $row['title'],
            'merchant'        => $row['merchant'],
            'original_amount' => (float)$row['original_amount'],
            'term_months'     => $termMonths,
            'monthly_payment' => $monthlyPayment,
            'start_date'      => $startDate,
            'due_day'         => $row['due_day'],
            'next_due_date'   => $nextDueDate,
            'closed_at'       => $row['closed_at'],
            'notes'           => $row['notes'],
            'account_id'      => (int)$row['account_id'],
            'account_name'    => $row['account_name'],
            'account_type'    => $row['account_type'],
            'months_elapsed'  => $monthsElapsed,
            'remaining_count' => $remainingCount,
            'progress_pct'    => $progressPct,
            'status'          => $status,
            'status_class'    => $statusClass,
        ];
    }

    return $plans;
}

/**
 * Summary for dashboard: loans only.
 * Active loans = those with outstanding > small tolerance.
 */
function get_loan_summary(PDO $pdo): array
{
    $loans = get_loans_with_stats($pdo);

    $today          = new DateTimeImmutable('today');
    $thirtyDaysAhead = $today->modify('+30 days');

    $activeCount = 0;
    $monthlyTotal = 0.0;
    $dueSoonCount = 0;

    foreach ($loans as $loan) {
        if ($loan['outstanding'] <= 1) {
            continue; // treated as completed
        }

        $activeCount++;

        if (!empty($loan['monthly_payment'])) {
            $monthlyTotal += (float)$loan['monthly_payment'];
        }

        if (!empty($loan['next_due_date'])) {
            try {
                $nd = new DateTimeImmutable($loan['next_due_date']);
                if ($nd >= $today && $nd <= $thirtyDaysAhead) {
                    $dueSoonCount++;
                }
            } catch (Exception $e) {
                // ignore invalid date
            }
        }
    }

    return [
        'active_count'  => $activeCount,
        'monthly_total' => $monthlyTotal,
        'due_soon_count'=> $dueSoonCount,
    ];
}

/**
 * Summary for dashboard: credit card installment plans only.
 */
function get_installment_summary(PDO $pdo): array
{
    $plans = get_installment_plans_with_stats($pdo);

    $today          = new DateTimeImmutable('today');
    $thirtyDaysAhead = $today->modify('+30 days');

    $activeCount = 0;
    $monthlyTotal = 0.0;
    $dueSoonCount = 0;

    foreach ($plans as $p) {
        if ($p['status'] === 'Completed') {
            continue;
        }

        $activeCount++;
        $monthlyTotal += (float)$p['monthly_payment'];

        if (!empty($p['next_due_date'])) {
            try {
                $nd = new DateTimeImmutable($p['next_due_date']);
                if ($nd >= $today && $nd <= $thirtyDaysAhead) {
                    $dueSoonCount++;
                }
            } catch (Exception $e) {
                // ignore
            }
        }
    }

    return [
        'active_count'   => $activeCount,
        'monthly_total'  => $monthlyTotal,
        'due_soon_count' => $dueSoonCount,
    ];
}
