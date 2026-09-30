<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= esc($title ?? 'VetCare') ?> · VetCare Clinic</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="<?= base_url('assets/css/app.css') ?>" rel="stylesheet">
</head>
<body class="bg-body-tertiary">
<?php $user = current_user(); ?>
<?php if ($user): ?>
<nav class="navbar navbar-expand-lg navbar-dark bg-teal shadow-sm">
    <div class="container">
        <a class="navbar-brand fw-semibold" href="<?= site_url('dashboard') ?>"><i class="bi bi-heart-pulse"></i> VetCare</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="mainNav">
            <ul class="navbar-nav me-auto">
                <li class="nav-item"><a class="nav-link" href="<?= site_url('dashboard') ?>">Dashboard</a></li>
                <li class="nav-item"><a class="nav-link" href="<?= site_url('appointments') ?>">Appointments</a></li>
                <li class="nav-item"><a class="nav-link" href="<?= site_url('pets') ?>"><?= has_role('owner') ? 'My Pets' : 'Pets' ?></a></li>
                <?php if (has_role('admin', 'staff', 'vet')): ?>
                    <li class="nav-item"><a class="nav-link" href="<?= site_url('owners') ?>">Owners</a></li>
                <?php endif ?>
                <li class="nav-item"><a class="nav-link" href="<?= site_url('ai/symptom-checker') ?>"><i class="bi bi-stars"></i> AI Symptom Checker</a></li>
                <?php if (has_role('admin')): ?>
                    <li class="nav-item"><a class="nav-link" href="<?= site_url('users') ?>">Users</a></li>
                <?php endif ?>
            </ul>
            <span class="navbar-text text-white me-3 small">
                <i class="bi bi-person-circle"></i> <?= esc($user['name']) ?>
                <span class="badge bg-light text-dark ms-1"><?= esc(ucfirst($user['role'])) ?></span>
            </span>
            <form action="<?= site_url('logout') ?>" method="post" class="d-inline">
                <?= csrf_field() ?>
                <button class="btn btn-outline-light btn-sm">Logout</button>
            </form>
        </div>
    </div>
</nav>
<?php endif ?>

<main class="container py-4">
    <?= $this->include('partials/flash') ?>
    <?= $this->renderSection('content') ?>
</main>

<footer class="text-center text-muted small py-4">
    VetCare Clinic &middot; Appointment &amp; Pet Medical Record System &middot; <?= date('Y') ?>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<?= $this->renderSection('scripts') ?>
</body>
</html>
