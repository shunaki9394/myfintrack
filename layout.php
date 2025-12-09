<<<<<<< HEAD
<?php
// layout.php
// Shared page shell: sidebar + main content + table filters/sorting.

/**
 * Small helper to render sidebar links.
 */
function nav_link(string $href, string $label, string $activeNav, string $key): string
{
    $active   = ($activeNav === $key) ? 'nav-link active' : 'nav-link';
    $safeHref = htmlspecialchars($href, ENT_QUOTES, 'UTF-8');
    $safeLabel = htmlspecialchars($label, ENT_QUOTES, 'UTF-8');

    return '<a href="' . $safeHref . '" class="' . $active . '">' . $safeLabel . '</a>';
}

/**
 * Begin the layout shell.
 */
function render_layout_start(string $pageTitle, string $activeNav = 'dashboard'): void
{
    ?>
    <!doctype html>
    <html lang="en">
    <head>
        <meta charset="utf-8">
        <title><?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?> – Finance Desk</title>
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <link rel="stylesheet" href="styles.css">
    </head>
    <body>
    <div class="app-layout">
        <aside class="sidebar">
            <div>
                <div class="app-logo">FH</div>
                <div class="app-name">Finance Desk</div>
                <p class="sidebar-subtitle">Personal health monitor</p>
            </div>

            <nav class="sidebar-nav">
                <?= nav_link('index.php',        'Dashboard',    $activeNav, 'dashboard') ?>
                <?= nav_link('transactions.php', 'Transactions', $activeNav, 'transactions') ?>
                <?= nav_link('accounts.php',     'Accounts',     $activeNav, 'accounts') ?>
                <?= nav_link('categories.php',   'Categories',   $activeNav, 'categories') ?>
                <?= nav_link('loans.php',        'Loans',        $activeNav, 'loans') ?>
                <?= nav_link('installments.php', 'Installments', $activeNav, 'installments') ?>
            </nav>

            <div class="sidebar-note">
                <strong>Environment</strong>
                MVP finance tracker – daily entries go into Transactions.
                Dashboard is read-only.
            </div>
        </aside>

        <main class="main-content">
    <?php
}

/**
 * End the layout shell and inject global table filter/sort script.
 */
function render_layout_end(): void
{
    ?>
        </main>
    </div>

    <!-- Simple global table filtering + sorting for .data-table -->
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        const tables = document.querySelectorAll('table.data-table');

        tables.forEach((table, idx) => {
            if (!table.tHead || !table.tBodies.length) {
                return;
            }

            // Ensure table has an id
            if (!table.id) {
                table.id = 'data-table-' + (idx + 1);
            }

            const tbody = table.tBodies[0];

            // ----- Toolbar + text filter -----
            const toolbar = document.createElement('div');
            toolbar.className = 'table-toolbar';

            const label = document.createElement('span');
            label.textContent = 'Filter';

            const input = document.createElement('input');
            input.type = 'text';
            input.className = 'table-filter';
            input.placeholder = 'Type to filter rows…';

            toolbar.appendChild(label);
            toolbar.appendChild(input);

            // Insert toolbar just before the table
            table.parentNode.insertBefore(toolbar, table);

            // Filter logic (simple contains across entire row)
            input.addEventListener('input', () => {
                const term = input.value.trim().toLowerCase();
                const rows = Array.from(tbody.rows);

                rows.forEach(row => {
                    const text = row.textContent.toLowerCase();
                    row.style.display = text.includes(term) ? '' : 'none';
                });
            });

            // ----- Column sorting on header click -----
            const headers = Array.from(table.tHead.rows[0].cells);

            headers.forEach((th, colIndex) => {
                th.style.cursor = 'pointer';

                th.addEventListener('click', () => {
                    const currentDir = th.dataset.sortDir === 'asc' ? 'desc' : 'asc';

                    // reset others
                    headers.forEach(h => delete h.dataset.sortDir);
                    th.dataset.sortDir = currentDir;

                    const rows = Array.from(tbody.rows);

                    rows.sort((a, b) => {
                        let aText = a.cells[colIndex]?.innerText.trim() || '';
                        let bText = b.cells[colIndex]?.innerText.trim() || '';

                        // Try numeric (handles RM, commas, etc.)
                        const aNum = parseFloat(aText.replace(/[^0-9.\-]/g, ''));
                        const bNum = parseFloat(bText.replace(/[^0-9.\-]/g, ''));

                        let cmp;
                        if (!isNaN(aNum) && !isNaN(bNum)) {
                            cmp = aNum - bNum;
                        } else {
                            // Fallback to text compare
                            cmp = aText.localeCompare(
                                bText,
                                undefined,
                                {numeric: true, sensitivity: 'base'}
                            );
                        }

                        return currentDir === 'asc' ? cmp : -cmp;
                    });

                    // Re-append rows in new order
                    rows.forEach(row => tbody.appendChild(row));
                });
            });
        });
    });
    </script>

    </body>
    </html>
    <?php
}

<?php
// layout.php
// Shared page shell: sidebar + main content + table filters/sorting.

/**
 * Small helper to render sidebar links.
 */
function nav_link(string $href, string $label, string $activeNav, string $key): string
{
    $active   = ($activeNav === $key) ? 'nav-link active' : 'nav-link';
    $safeHref = htmlspecialchars($href, ENT_QUOTES, 'UTF-8');
    $safeLabel = htmlspecialchars($label, ENT_QUOTES, 'UTF-8');

    return '<a href="' . $safeHref . '" class="' . $active . '">' . $safeLabel . '</a>';
}

/**
 * Begin the layout shell.
 */
function render_layout_start(string $pageTitle, string $activeNav = 'dashboard'): void
{
    ?>
    <!doctype html>
    <html lang="en">
    <head>
        <meta charset="utf-8">
        <title><?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?> – Finance Desk</title>
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <link rel="stylesheet" href="styles.css">
    </head>
    <body>
    <div class="app-layout">
        <aside class="sidebar">
            <div>
                <div class="app-logo">FH</div>
                <div class="app-name">Finance Desk</div>
                <p class="sidebar-subtitle">Personal health monitor</p>
            </div>

            <nav class="sidebar-nav">
                <?= nav_link('index.php',        'Dashboard',    $activeNav, 'dashboard') ?>
                <?= nav_link('transactions.php', 'Transactions', $activeNav, 'transactions') ?>
                <?= nav_link('accounts.php',     'Accounts',     $activeNav, 'accounts') ?>
                <?= nav_link('categories.php',   'Categories',   $activeNav, 'categories') ?>
                <?= nav_link('loans.php',        'Loans',        $activeNav, 'loans') ?>
                <?= nav_link('installments.php', 'Installments', $activeNav, 'installments') ?>
            </nav>

            <div class="sidebar-note">
                <strong>Environment</strong>
                MVP finance tracker – daily entries go into Transactions.
                Dashboard is read-only.
            </div>
        </aside>

        <main class="main-content">
    <?php
}

/**
 * End the layout shell and inject global table filter/sort script.
 */
function render_layout_end(): void
{
    ?>
        </main>
    </div>

    <!-- Simple global table filtering + sorting for .data-table -->
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        const tables = document.querySelectorAll('table.data-table');

        tables.forEach((table, idx) => {
            if (!table.tHead || !table.tBodies.length) {
                return;
            }

            // Ensure table has an id
            if (!table.id) {
                table.id = 'data-table-' + (idx + 1);
            }

            const tbody = table.tBodies[0];

            // ----- Toolbar + text filter -----
            const toolbar = document.createElement('div');
            toolbar.className = 'table-toolbar';

            const label = document.createElement('span');
            label.textContent = 'Filter';

            const input = document.createElement('input');
            input.type = 'text';
            input.className = 'table-filter';
            input.placeholder = 'Type to filter rows…';

            toolbar.appendChild(label);
            toolbar.appendChild(input);

            // Insert toolbar just before the table
            table.parentNode.insertBefore(toolbar, table);

            // Filter logic (simple contains across entire row)
            input.addEventListener('input', () => {
                const term = input.value.trim().toLowerCase();
                const rows = Array.from(tbody.rows);

                rows.forEach(row => {
                    const text = row.textContent.toLowerCase();
                    row.style.display = text.includes(term) ? '' : 'none';
                });
            });

            // ----- Column sorting on header click -----
            const headers = Array.from(table.tHead.rows[0].cells);

            headers.forEach((th, colIndex) => {
                th.style.cursor = 'pointer';

                th.addEventListener('click', () => {
                    const currentDir = th.dataset.sortDir === 'asc' ? 'desc' : 'asc';

                    // reset others
                    headers.forEach(h => delete h.dataset.sortDir);
                    th.dataset.sortDir = currentDir;

                    const rows = Array.from(tbody.rows);

                    rows.sort((a, b) => {
                        let aText = a.cells[colIndex]?.innerText.trim() || '';
                        let bText = b.cells[colIndex]?.innerText.trim() || '';

                        // Try numeric (handles RM, commas, etc.)
                        const aNum = parseFloat(aText.replace(/[^0-9.\-]/g, ''));
                        const bNum = parseFloat(bText.replace(/[^0-9.\-]/g, ''));

                        let cmp;
                        if (!isNaN(aNum) && !isNaN(bNum)) {
                            cmp = aNum - bNum;
                        } else {
                            // Fallback to text compare
                            cmp = aText.localeCompare(
                                bText,
                                undefined,
                                {numeric: true, sensitivity: 'base'}
                            );
                        }

                        return currentDir === 'asc' ? cmp : -cmp;
                    });

                    // Re-append rows in new order
                    rows.forEach(row => tbody.appendChild(row));
                });
            });
        });
    });
    </script>

    </body>
    </html>
    <?php
}
>>>>>>> 7a7204f7ad64c6757b392e715114c5708c6b1200
