<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>System Check · PawRecord</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=DM+Serif+Display&family=Nunito:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        :root { --paw-green: #2f7d5b; --paw-navy: #1c2b3e; --paw-cream: #f8f5ef; }
        body { background: var(--paw-cream); font-family: 'Nunito', system-ui, sans-serif; color: var(--paw-navy); }
        .brand { font-family: 'DM Serif Display', serif; }
        .hero { background: var(--paw-navy); color: #fff; }
        .hero .tag { color: #6fcf97; letter-spacing: .05em; font-size: .8rem; }
        .ok { color: var(--paw-green); } .bad { color: #c0392b; }
        .card { border: 1px solid #e6e0d4; border-radius: 14px; }
    </style>
</head>
<body>
<header class="hero text-center py-4 mb-4">
    <div class="brand fs-2">PawRecord</div>
    <div class="tag text-uppercase fw-semibold">Veterinary Clinic &amp; Pet Medical Record System</div>
    <div class="small opacity-75 mt-1">Step 2 · System Check</div>
</header>

<main class="container pb-5" style="max-width: 860px">
    <div class="alert <?= $allOk ? 'alert-success' : 'alert-warning' ?> fw-semibold">
        <?= $allOk ? '✔ Everything is ready. CodeIgniter is connected to the PawRecord database.'
                   : '⚠ Some items need attention — see the red marks below.' ?>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-md-6">
            <div class="card h-100"><div class="card-body">
                <h2 class="h6 fw-bold">PHP</h2>
                <p class="mb-2"><span class="<?= $phpOk ? 'ok' : 'bad' ?>"><?= $phpOk ? '✔' : '✘' ?></span>
                    Version <?= esc($php) ?> <small class="text-muted">(8.2+ required)</small></p>
                <?php foreach ($extensions as $ext => $loaded): ?>
                    <div><span class="<?= $loaded ? 'ok' : 'bad' ?>"><?= $loaded ? '✔' : '✘' ?></span> <?= esc($ext) ?>
                        <?php if (! $loaded): ?><small class="text-muted">— remove the ; before extension=<?= esc($ext) ?> in php.ini</small><?php endif ?>
                    </div>
                <?php endforeach ?>
                <div class="mt-2"><span class="<?= $writable ? 'ok' : 'bad' ?>"><?= $writable ? '✔' : '✘' ?></span> writable/ folder</div>
            </div></div>
        </div>
        <div class="col-md-6">
            <div class="card h-100"><div class="card-body">
                <h2 class="h6 fw-bold">Database</h2>
                <?php if ($db['connected']): ?>
                    <p class="mb-1"><span class="ok">✔</span> Connected</p>
                    <div class="small">Database: <strong><?= esc($db['database']) ?></strong></div>
                    <div class="small">Server: <?= esc($db['driver']) ?> <?= esc($db['version']) ?></div>
                    <div class="small">Migrations run: <?= (int) $db['migrated'] ?> / 13
                        <?php if ($db['migrated'] === 0): ?><span class="text-muted">(tables imported from the SQL file)</span><?php endif ?></div>
                <?php else: ?>
                    <p class="mb-1"><span class="bad">✘</span> Cannot connect</p>
                    <div class="small text-danger"><?= esc($db['error']) ?></div>
                    <div class="small mt-2">Check that MySQL is started in XAMPP and the <code>database.default.*</code> values in <code>.env</code>.</div>
                <?php endif ?>
            </div></div>
        </div>
    </div>

    <?php if ($db['connected']): ?>
    <div class="card">
        <div class="card-body">
            <h2 class="h6 fw-bold">Tables</h2>
            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0">
                    <thead><tr><th></th><th>Table</th><th>Used for</th><th class="text-end">Rows</th></tr></thead>
                    <tbody>
                    <?php foreach ($db['tables'] as $t): ?>
                        <tr>
                            <td class="<?= $t['exists'] ? 'ok' : 'bad' ?>"><?= $t['exists'] ? '✔' : '✘' ?></td>
                            <td><code><?= esc($t['name']) ?></code></td>
                            <td class="small"><?= esc($t['purpose']) ?></td>
                            <td class="text-end"><?= $t['exists'] ? (int) $t['rows'] : 'missing' ?></td>
                        </tr>
                    <?php endforeach ?>
                    </tbody>
                </table>
            </div>
            <?php if (! $tablesOk): ?>
                <p class="small mt-3 mb-0">Create the missing tables with <code>php spark migrate</code> (see README).</p>
            <?php endif ?>
        </div>
    </div>
    <?php endif ?>

    <p class="text-center small text-muted mt-4">CodeIgniter <?= \CodeIgniter\CodeIgniter::CI_VERSION ?> · PHP · MySQL/MariaDB · XAMPP</p>
</main>
</body>
</html>
