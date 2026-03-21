<?php
session_start();
require_once __DIR__ . '/../config/database.php';

// Auth check
if (empty($_SESSION['logged_in']) || empty($_SESSION['user_id'])) {
    header('Location: /projectos/auth/login.php');
    exit;
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$pdo = getDB();
$errors = [];
$success = '';

function e($v){ return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }

// Handle POST actions: add, edit, delete
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $errors[] = 'Invalid CSRF token.';
    } else {
        try {
            // Create Devis
            if (isset($_POST['action']) && $_POST['action'] === 'add_devis') {
                $devis_number = trim((string)($_POST['devis_number'] ?? ''));
                $contract_id = $_POST['contract_id'] !== '' ? (int)$_POST['contract_id'] : null;
                $title = trim((string)($_POST['title'] ?? ''));
                $description = trim((string)($_POST['description'] ?? ''));
                $status = in_array($_POST['status'] ?? 'draft', ['draft','sent','accepted','rejected','expired']) ? $_POST['status'] : 'draft';
                $issue_date = $_POST['issue_date'] ?: null;
                $expiry_date = $_POST['expiry_date'] ?: null;
                $notes = trim((string)($_POST['notes'] ?? ''));
                $total_ht = isset($_POST['total_ht']) ? number_format((float)$_POST['total_ht'],2,'.','') : '0.00';
                $total_tva = isset($_POST['total_tva']) ? number_format((float)$_POST['total_tva'],2,'.','') : '0.00';
                $total_ttc = isset($_POST['total_ttc']) ? number_format((float)$_POST['total_ttc'],2,'.','') : '0.00';

                //if ($title === '') $errors[] = 'Title is required.';

                // auto-generate devis number if empty
                if ($devis_number === '') {
                    $y = date('Y');
                    $st = $pdo->prepare('SELECT COUNT(*) FROM devis WHERE YEAR(created_at)=:y');
                    $st->execute([':y'=>$y]);
                    $n = (int)$st->fetchColumn() + 1;
                    $devis_number = sprintf('profomat-%s-%04d', $y, $n);
                } else {
                    // ensure unique
                    $st = $pdo->prepare('SELECT COUNT(*) FROM devis WHERE devis_number = :dn AND deleted_at IS NULL');
                    $st->execute([':dn'=>$devis_number]);
                    if ($st->fetchColumn() > 0) $errors[] = 'Devis number already exists.';
                }

                if (empty($errors)) {
                    $stakeholder_id = isset($_POST['stakeholder_id']) && $_POST['stakeholder_id'] !== '' ? (int)$_POST['stakeholder_id'] : null;
                    $ins = $pdo->prepare('INSERT INTO devis (devis_number, contract_id, stakeholder_id, title, description, status, issue_date, expiry_date, total_ht, total_tva, total_ttc, notes, created_at, updated_at) VALUES (:dn,:contract_id,:stakeholder_id,:title,:desc,:status,:issue,:expiry,:tht,:ttva,:tttc,:notes,NOW(),NOW())');
                    $ins->execute([':dn'=>$devis_number,':contract_id'=>$contract_id,':stakeholder_id'=>$stakeholder_id,':title'=>$title,':desc'=>$description,':status'=>$status,':issue'=>$issue_date,':expiry'=>$expiry_date,':tht'=>$total_ht,':ttva'=>$total_tva,':tttc'=>$total_ttc,':notes'=>$notes]);
                    $devis_id = (int)$pdo->lastInsertId();

                    // insert articles: support nested `articles[...]` or legacy flat arrays
                    $articles = $_POST['articles'] ?? null;
                    $ain = $pdo->prepare('INSERT INTO devis_articles (devis_id, article_name, description, um, quantity, unit_price, tva_rate, total_ht, total_tva, total_ttc, created_at) VALUES (:devis_id,:name,:desc,:um,:qty,:unit,:tva,:tht,:ttva,:tttc,NOW())');
                    if (is_array($articles)) {
                        foreach ($articles as $a) {
                            $name = trim((string)($a['article_name'] ?? ''));
                            if ($name === '') continue;
                            $qty = isset($a['quantity']) ? (float)$a['quantity'] : 0.0;
                            $unit = isset($a['unit_price']) ? (float)$a['unit_price'] : 0.0;
                            $tva = isset($a['tva_rate']) ? (float)$a['tva_rate'] : 0.0;
                            $ttc = isset($a['total_ttc']) ? (float)$a['total_ttc'] : 0.0;
                            $um = trim((string)($a['um'] ?? ''));
                            if ($ttc > 0.0) {
                                $tht = $tva >= 0 ? $ttc / (1 + ($tva/100)) : $ttc;
                                $ttva = $ttc - $tht;
                            } else {
                                $tht = $qty * $unit;
                                $ttva = $tht * ($tva/100);
                                $ttc = $tht + $ttva;
                            }
                            $ain->execute([':devis_id'=>$devis_id,':name'=>$name,':desc'=>trim((string)($a['description'] ?? '')),':um'=>$um,':qty'=>number_format($qty,2,'.',''),':unit'=>number_format($unit,2,'.',''),':tva'=>number_format($tva,2,'.',''),':tht'=>number_format($tht,2,'.',''),':ttva'=>number_format($ttva,2,'.',''),':tttc'=>number_format($ttc,2,'.','')]);
                        }
                    } else {
                        $names = $_POST['article_name'] ?? [];
                        $descs = $_POST['article_description'] ?? [];
                        $ums = $_POST['article_um'] ?? [];
                        $qtys = $_POST['quantity'] ?? [];
                        $unit_prices = $_POST['unit_price'] ?? [];
                        $tva_rates = $_POST['tva_rate'] ?? [];
                        $a_tht = $_POST['article_total_ht'] ?? [];
                        $a_ttva = $_POST['article_total_tva'] ?? [];
                        $a_tttc = $_POST['article_total_ttc'] ?? [];
                        for ($i=0;$i<count($names);$i++) {
                            $n = trim((string)$names[$i]);
                            if ($n === '') continue;
                            $um = trim((string)($ums[$i] ?? ''));
                            $ain->execute([':devis_id'=>$devis_id,':name'=>$n,':desc'=>trim((string)$descs[$i]),':um'=>$um,':qty'=>number_format((float)$qtys[$i],2,'.',''),':unit'=>number_format((float)$unit_prices[$i],2,'.',''),':tva'=>number_format((float)$tva_rates[$i],2,'.',''),':tht'=>number_format((float)($a_tht[$i] ?? 0),2,'.',''),':ttva'=>number_format((float)($a_ttva[$i] ?? 0),2,'.',''),':tttc'=>number_format((float)($a_tttc[$i] ?? 0),2,'.','')]);
                        }
                    }

                    // recompute totals from inserted articles and update devis row
                    try {
                        $stot = $pdo->prepare('SELECT COALESCE(SUM(total_ht),0) AS stht, COALESCE(SUM(total_tva),0) AS sttva, COALESCE(SUM(total_ttc),0) AS stttc FROM devis_articles WHERE devis_id = :id');
                        $stot->execute([':id'=>$devis_id]);
                        $s = $stot->fetch();
                        $upd = $pdo->prepare('UPDATE devis SET total_ht = :tht, total_tva = :ttva, total_ttc = :tttc WHERE id = :id');
                        $upd->execute([':tht'=>number_format((float)($s['stht'] ?? 0),2,'.',''),':ttva'=>number_format((float)($s['sttva'] ?? 0),2,'.',''),':tttc'=>number_format((float)($s['stttc'] ?? 0),2,'.',''),':id'=>$devis_id]);
                    } catch (Exception $e) { /* ignore */ }

                    $success = 'Devis created.';
                }
            }

            // Edit Devis
            if (isset($_POST['action']) && $_POST['action'] === 'edit_devis') {
                $id = (int)($_POST['id'] ?? 0);
                if ($id <= 0) $errors[] = 'Invalid devis id.';
                $devis_number = trim((string)($_POST['devis_number'] ?? ''));
                $contract_id = $_POST['contract_id'] !== '' ? (int)$_POST['contract_id'] : null;
                $title = trim((string)($_POST['title'] ?? ''));
                $description = trim((string)($_POST['description'] ?? ''));
                $status = in_array($_POST['status'] ?? 'draft', ['draft','sent','accepted','rejected','expired']) ? $_POST['status'] : 'draft';
                $issue_date = $_POST['issue_date'] ?: null;
                $expiry_date = $_POST['expiry_date'] ?: null;
                $notes = trim((string)($_POST['notes'] ?? ''));
                $total_ht = isset($_POST['total_ht']) ? number_format((float)$_POST['total_ht'],2,'.','') : '0.00';
                $total_tva = isset($_POST['total_tva']) ? number_format((float)$_POST['total_tva'],2,'.','') : '0.00';
                $total_ttc = isset($_POST['total_ttc']) ? number_format((float)$_POST['total_ttc'],2,'.','') : '0.00';

                if ($title === '') $errors[] = 'Title is required.';

                // uniqueness
                if ($devis_number !== '') {
                    $st = $pdo->prepare('SELECT COUNT(*) FROM devis WHERE devis_number = :dn AND id != :id AND deleted_at IS NULL');
                    $st->execute([':dn'=>$devis_number,':id'=>$id]);
                    if ($st->fetchColumn() > 0) $errors[] = 'Devis number already exists.';
                }

                if (empty($errors)) {
                    $stakeholder_id = isset($_POST['stakeholder_id']) && $_POST['stakeholder_id'] !== '' ? (int)$_POST['stakeholder_id'] : null;
                    $up = $pdo->prepare('UPDATE devis SET devis_number=:dn, contract_id=:contract_id, stakeholder_id=:stakeholder_id, title=:title, description=:desc, status=:status, issue_date=:issue, expiry_date=:expiry, total_ht=:tht, total_tva=:ttva, total_ttc=:tttc, notes=:notes, updated_at=NOW() WHERE id = :id');
                    $up->execute([':dn'=>$devis_number,':contract_id'=>$contract_id,':stakeholder_id'=>$stakeholder_id,':title'=>$title,':desc'=>$description,':status'=>$status,':issue'=>$issue_date,':expiry'=>$expiry_date,':tht'=>$total_ht,':ttva'=>$total_tva,':tttc'=>$total_ttc,':notes'=>$notes,':id'=>$id]);

                    // delete existing articles and reinsert (supports nested `articles`)
                    $d = $pdo->prepare('DELETE FROM devis_articles WHERE devis_id = :id');
                    $d->execute([':id'=>$id]);

                    $articles = $_POST['articles'] ?? null;
                    $ain = $pdo->prepare('INSERT INTO devis_articles (devis_id, article_name, description, um, quantity, unit_price, tva_rate, total_ht, total_tva, total_ttc, created_at) VALUES (:devis_id,:name,:desc,:um,:qty,:unit,:tva,:tht,:ttva,:tttc,NOW())');
                    if (is_array($articles)) {
                        foreach ($articles as $a) {
                            $name = trim((string)($a['article_name'] ?? ''));
                            if ($name === '') continue;
                            $qty = isset($a['quantity']) ? (float)$a['quantity'] : 0.0;
                            $unit = isset($a['unit_price']) ? (float)$a['unit_price'] : 0.0;
                            $tva = isset($a['tva_rate']) ? (float)$a['tva_rate'] : 0.0;
                            $ttc = isset($a['total_ttc']) ? (float)$a['total_ttc'] : 0.0;
                            $um = trim((string)($a['um'] ?? ''));
                            if ($ttc > 0.0) {
                                $tht = $tva >= 0 ? $ttc / (1 + ($tva/100)) : $ttc;
                                $ttva = $ttc - $tht;
                            } else {
                                $tht = $qty * $unit;
                                $ttva = $tht * ($tva/100);
                                $ttc = $tht + $ttva;
                            }
                            $ain->execute([':devis_id'=>$id,':name'=>$name,':desc'=>trim((string)($a['description'] ?? '')),':um'=>$um,':qty'=>number_format($qty,2,'.',''),':unit'=>number_format($unit,2,'.',''),':tva'=>number_format($tva,2,'.',''),':tht'=>number_format($tht,2,'.',''),':ttva'=>number_format($ttva,2,'.',''),':tttc'=>number_format($ttc,2,'.','')]);
                        }
                    } else {
                        $names = $_POST['article_name'] ?? [];
                        $descs = $_POST['article_description'] ?? [];
                        $ums = $_POST['article_um'] ?? [];
                        $qtys = $_POST['quantity'] ?? [];
                        $unit_prices = $_POST['unit_price'] ?? [];
                        $tva_rates = $_POST['tva_rate'] ?? [];
                        $a_tht = $_POST['article_total_ht'] ?? [];
                        $a_ttva = $_POST['article_total_tva'] ?? [];
                        $a_tttc = $_POST['article_total_ttc'] ?? [];
                        for ($i=0;$i<count($names);$i++) {
                            $n = trim((string)$names[$i]);
                            if ($n === '') continue;
                            $um = trim((string)($ums[$i] ?? ''));
                            $ain->execute([':devis_id'=>$id,':name'=>$n,':desc'=>trim((string)$descs[$i]),':um'=>$um,':qty'=>number_format((float)$qtys[$i],2,'.',''),':unit'=>number_format((float)$unit_prices[$i],2,'.',''),':tva'=>number_format((float)$tva_rates[$i],2,'.',''),':tht'=>number_format((float)($a_tht[$i] ?? 0),2,'.',''),':ttva'=>number_format((float)($a_ttva[$i] ?? 0),2,'.',''),':tttc'=>number_format((float)($a_tttc[$i] ?? 0),2,'.','')]);
                        }
                    }

                    // recompute totals from articles
                    try {
                        $stot = $pdo->prepare('SELECT COALESCE(SUM(total_ht),0) AS stht, COALESCE(SUM(total_tva),0) AS sttva, COALESCE(SUM(total_ttc),0) AS stttc FROM devis_articles WHERE devis_id = :id');
                        $stot->execute([':id'=>$id]);
                        $s = $stot->fetch();
                        $upd = $pdo->prepare('UPDATE devis SET total_ht = :tht, total_tva = :ttva, total_ttc = :tttc WHERE id = :id');
                        $upd->execute([':tht'=>number_format((float)($s['stht'] ?? 0),2,'.',''),':ttva'=>number_format((float)($s['sttva'] ?? 0),2,'.',''),':tttc'=>number_format((float)($s['stttc'] ?? 0),2,'.',''),':id'=>$id]);
                    } catch (Exception $e) { /* ignore */ }

                    $success = 'Devis updated.';
                }
            }

            // Delete Devis (soft)
            if (isset($_POST['action']) && $_POST['action'] === 'delete_devis') {
                $id = (int)($_POST['id'] ?? 0);
                if ($id > 0) {
                    $d = $pdo->prepare('UPDATE devis SET deleted_at = NOW() WHERE id = :id');
                    $d->execute([':id'=>$id]);
                    $success = 'Devis deleted.';
                } else {
                    $errors[] = 'Invalid id for delete.';
                }
            }
        } catch (Exception $e) {
            $errors[] = 'Database error.';
        }
    }

    if (!empty($errors)) $_SESSION['errors'] = $errors;
    if ($success !== '') $_SESSION['success'] = $success;
    header('Location: /projectos/dashboard/devis.php');
    exit;
}

// Search and fetch devis
$q = trim((string)($_GET['q'] ?? ''));
$params = [];
$where = 'WHERE d.deleted_at IS NULL';
if ($q !== '') {
    $like = '%' . $q . '%';
    $where .= ' AND (d.devis_number LIKE :like OR d.title LIKE :like OR d.status LIKE :like';
    $params[':like'] = $like;
    if (is_numeric($q)) {
        $where .= ' OR d.contract_id = :cid';
        $params[':cid'] = (int)$q;
    }
    $where .= ')';
}

$sql = "SELECT d.*, c.contract_number AS contract_number, c.title AS contract_title, s.name AS stakeholder_name, s.id AS stakeholder_id FROM devis d LEFT JOIN contracts c ON d.contract_id = c.id LEFT JOIN stakeholders s ON d.stakeholder_id = s.id $where ORDER BY d.created_at DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$devis = $stmt->fetchAll();

// fetch articles for each devis
foreach ($devis as &$dv) {
    $st = $pdo->prepare('SELECT * FROM devis_articles WHERE devis_id = :id ORDER BY id ASC');
    $st->execute([':id'=>$dv['id']]);
    $dv['articles'] = $st->fetchAll();
}
unset($dv);

// fetch contracts for dropdown
$st = $pdo->prepare('SELECT id, contract_number, title FROM contracts WHERE deleted_at IS NULL ORDER BY contract_number ASC');
$st->execute();
$contracts = $st->fetchAll();

// fetch stakeholders for client dropdown
$st2 = $pdo->prepare('SELECT id, name FROM stakeholders WHERE deleted_at IS NULL ORDER BY name ASC');
$st2->execute();
$stakeholders = $st2->fetchAll();

?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Devis</title>
    <link rel="stylesheet" href="/projectos/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
    /* Articles & table styles (compact invoice-like) */
    .table-responsive{overflow-x:auto;width:100%}
    table{width:100%;border-collapse:collapse;text-align:left}
    th{padding:0.9rem 0.5rem;font-weight:600;border-bottom:2px solid #f1f5f9;color:#1e293b}
    td{padding:0.9rem 0.5rem;border-bottom:1px solid #f1f5f9;vertical-align:middle}
    tr:hover{background:#f8fafc}

    input{width:100%;padding:0.45rem;border:0;border-bottom:1px solid #e6eef6;outline:none;font-family:inherit}
    input:focus{border-bottom-color:#2563eb}
    .input-box{border:1px solid #e6eef6;border-radius:6px;text-align:center;background:#fff}
    .text-right{text-align:right}.text-center{text-align:center}
    .item-description{font-size:0.9rem;color:#64748b}

    .btn-remove{background:none;border:0;color:#cbd5e1;cursor:pointer;padding:0.4rem;transition:color .15s}
    tr:hover .btn-remove{color:#ef4444}

    .btn-add{display:inline-flex;align-items:center;background:#f8fafc;border:1px solid #e6eef6;padding:0.45rem 0.85rem;border-radius:8px;font-size:0.9rem;font-weight:600;cursor:pointer;margin-top:0.75rem}
    .btn{padding:0.5rem 0.9rem;border-radius:8px}

    .summary-container{display:flex;justify-content:space-between;align-items:flex-start;margin-top:1rem}
    .totals-box{width:260px}
    .total-row{display:flex;justify-content:space-between;padding:0.4rem 0;color:#64748b}
    .total-row.grand-total{border-top:1px solid #e6eef6;margin-top:0.5rem;padding-top:0.75rem;font-size:1.1rem;font-weight:700;color:#1e293b}

    /* Make modal wider for article tables */
    .modal-window{max-width:1100px}

    /* small tweaks for existing table */
    .devis-table th, .devis-table td{padding:10px 12px;border-bottom:1px solid #eef2f7}
    .devis-table thead th{background:transparent;text-align:left}
    .actions .btn{padding:6px 8px}
    .totals-row{font-weight:700}
    @media(max-width:700px){ .devis-table thead{display:none} .devis-table tr{display:block;margin-bottom:12px} .devis-table td{display:flex;justify-content:space-between;padding:8px} }
    </style>
</head>
<body>
<?php require_once __DIR__ . '/../components/navbar.php'; ?>
<?php require_once __DIR__ . '/../components/sidebar.php'; ?>

<main class="main-content">
    <div class="container">
        <div class="page-header">
            <h1>Devis</h1>
            <p class="muted">Manage quotes (devis) — create, edit, view and soft-delete.</p>
        </div>

        <?php if (!empty($_SESSION['success'])): ?><div class="alert success"><?php echo e($_SESSION['success']); unset($_SESSION['success']); ?></div><?php endif; ?>
        <?php if (!empty($_SESSION['errors'])): ?><div class="alert errors"><?php foreach($_SESSION['errors'] as $er) echo '<div>'.e($er).'</div>'; unset($_SESSION['errors']); ?></div><?php endif; ?>

        <div class="controls">
            <form method="get" style="display:inline-block;">
                <input name="q" type="search" placeholder="Search number, title, status or contract id" value="<?php echo e($q); ?>">
            </form>
            <div style="float:right">
                <button class="btn btn-add" type="button" onclick="openModal('addDevisModal')"><i class="fa-solid fa-plus"></i> Add Devis</button>
            </div>
        </div>

        <div class="table-wrap">
            <table class="devis-table stake-table">
                <thead>
                    <tr>
                        <th hidden>ID</th>
                        <th>Devis #</th>
                        <th hidden >Title</th>
                        <th>Contract/Client</th>
                        <th>Status</th>
                        <th>Total TTC</th>
                        <th>Issue</th>
                        <th hidden>Expiry</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($devis)): ?>
                    <tr><td colspan="9" class="muted">No devis found.</td></tr>
                <?php endif; ?>
                <?php foreach ($devis as $d): ?>
                    <tr class="devis-row" data-id="<?php echo e($d['id']); ?>"
                        data-devis_number="<?php echo e($d['devis_number']); ?>"
                        data-title="<?php echo e($d['title']); ?>"
                        data-description="<?php echo e($d['description']); ?>"
                        data-contract_id="<?php echo e($d['contract_id']); ?>"
                        data-stakeholder_id="<?php echo e($d['stakeholder_id'] ?? ''); ?>"
                        data-stakeholder_name="<?php echo e($d['stakeholder_name'] ?? ''); ?>"
                        data-status="<?php echo e($d['status']); ?>"
                        data-issue_date="<?php echo e($d['issue_date']); ?>"
                        data-expiry_date="<?php echo e($d['expiry_date']); ?>"
                        data-total_ht="<?php echo e(number_format((float)$d['total_ht'],2,'.','')); ?>"
                        data-total_tva="<?php echo e(number_format((float)$d['total_tva'],2,'.','')); ?>"
                        data-total_ttc="<?php echo e(number_format((float)$d['total_ttc'],2,'.','')); ?>"
                        data-notes="<?php echo e($d['notes']); ?>"
                        data-articles='<?php echo json_encode($d['articles'], JSON_HEX_APOS|JSON_HEX_QUOT); ?>'
                    >
                        <td hidden><?php echo e($d['id']); ?></td>
                        <td><?php echo e($d['devis_number']); ?></td>
                        <td hidden ><?php echo e($d['title']); ?><div class="muted muted-small"><?php echo e(substr($d['description'] ?? '',0,80)); ?></div></td>
                        <td><?php echo e($d['contract_number'] ?? ($d['stakeholder_name'] ?? $d['contract_id'])); ?></td>
                        <td><?php echo e($d['status']); ?></td>
                        <td><?php echo e(number_format((float)$d['total_ttc'],2,'.',' ')); ?></td>
                        <td><?php echo e($d['issue_date']); ?></td>
                        <td hidden><?php echo e($d['expiry_date']); ?></td>
                        <td>
                            <div class="actions">
                                <button class="btn btn-view ghost" type="button" onclick="(function(b){var tr=b.closest('.devis-row'); openModal('viewDevisModal'); window.populateViewDevis(tr);})(this)" title="View"><i class="fa-solid fa-eye"></i></button>
                                <button class="btn btn-edit" type="button" onclick="(function(b){var tr=b.closest('.devis-row'); openModal('editDevisModal'); window.populateEditDevis(tr);})(this)" title="Edit"><i class="fa-solid fa-pen-to-square"></i></button>
                                <button class="btn" type="button" onclick="(function(b){var tr=b.closest('.devis-row'); window.openPrintDevis && window.openPrintDevis(tr);})(this)" title="Print"><i class="fa-solid fa-print"></i></button>
                                <button class="btn btn-delete" type="button" onclick="(function(b){var tr=b.closest('.devis-row'); fillForm('deleteDevisModal',{id:tr.getAttribute('data-id')}); openModal('deleteDevisModal');})(this)" title="Delete"><i class="fa-solid fa-trash"></i></button>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>

<!-- Add/Edit/Delete/View Modals -->
<?php // Add Devis Modal ?>
<div class="modal" id="addDevisModal" aria-hidden="true">
    <div class="modal-overlay" data-close="true"></div>
    <div class="modal-window" role="dialog" aria-modal="true">
        <header class="modal-header">
            <h2>Add Devis</h2>
            <button class="modal-close" data-close="true">✕</button>
        </header>
        <div class="modal-body">
            <form method="post" id="devisForm">
                <input type="hidden" name="csrf_token" value="<?php echo e($_SESSION['csrf_token']); ?>">
                <input type="hidden" name="action" value="add_devis">
                <label>Devis Number<input name="devis_number" type="text" placeholder="Leave empty to auto-generate"></label>
                <label>Contract<select name="contract_id">
                    <option value="">—</option>
                    <?php foreach($contracts as $c): ?>
                        <option value="<?php echo e($c['id']); ?>"><?php echo e($c['contract_number']); ?> — <?php echo e($c['title']); ?></option>
                    <?php endforeach; ?>
                </select></label>
                <label>Client (if no contract selected)
                    <select name="stakeholder_id">
                        <option value="">—</option>
                        <?php foreach($stakeholders as $s): ?>
                            <option value="<?php echo e($s['id']); ?>"><?php echo e($s['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label>Title<input name="title" type="text" required></label>
                <label>Description<textarea name="description"></textarea></label>
                <label>Status<select name="status">
                    <option value="draft">draft</option>
                    <option value="sent">sent</option>
                    <option value="accepted">accepted</option>
                    <option value="rejected">rejected</option>
                    <option value="expired">expired</option>
                </select></label>
                <label>Issue Date<input type="date" name="issue_date"></label>
                <label>Expiry Date<input type="date" name="expiry_date"></label>

                <h4>Articles</h4>
                <div class="articles-table-wrap">
                    <button type="button" class="btn" id="addArticleBtn">Add Article</button>
                    <div class="table-responsive">
                        <table class="articles-table" id="articlesTable">
                            <thead>
                                        <tr>
                                            <th>Article Name</th>
                                            <th>Description</th>
                                            <th style="width:80px;text-align:center">UM</th>
                                            <th style="width:90px;text-align:right">Qte</th>
                                            <th style="width:110px;text-align:right">Unit Price</th>
                                            <th style="width:90px;text-align:right">TVA (%)</th>
                                            <th style="width:140px;text-align:right">TTC</th>
                                            <th style="width:60px">Actions</th>
                                        </tr>
                            </thead>
                            <tbody>
                                <!-- rows appended by JS -->
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="5" style="text-align:right;font-weight:700">Sub Total (TTC)</td>
                                    <td style="text-align:right;font-weight:700"><span id="articlesSubtotal">0.00</span><input type="hidden" name="total_ttc" value="0.00"></td>
                                    <td></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>

                <label>Notes<textarea name="notes"></textarea></label>

                <div class="modal-actions">
                    <button type="submit" class="btn">Create</button>
                    <button type="button" class="btn ghost" data-close="true">Cancel</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Devis Modal -->
<div class="modal" id="editDevisModal" aria-hidden="true">
    <div class="modal-overlay" data-close="true"></div>
    <div class="modal-window" role="dialog" aria-modal="true">
        <header class="modal-header">
            <h2>Edit Devis</h2>
            <button class="modal-close" data-close="true">✕</button>
        </header>
        <div class="modal-body">
            <form method="post" id="editDevisForm">
                <input type="hidden" name="csrf_token" value="<?php echo e($_SESSION['csrf_token']); ?>">
                <input type="hidden" name="action" value="edit_devis">
                <input type="hidden" name="id" value="">
                <label>Devis Number<input name="devis_number" type="text"></label>
                <label>Contract<select name="contract_id">
                    <option value="">—</option>
                    <?php foreach($contracts as $c): ?>
                        <option value="<?php echo e($c['id']); ?>"><?php echo e($c['contract_number']); ?> — <?php echo e($c['title']); ?></option>
                    <?php endforeach; ?>
                </select></label>
                <label>Client (if no contract selected)
                    <select name="stakeholder_id">
                        <option value="">—</option>
                        <?php foreach($stakeholders as $s): ?>
                            <option value="<?php echo e($s['id']); ?>"><?php echo e($s['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label>Title<input name="title" type="text" required></label>
                <label>Description<textarea name="description"></textarea></label>
                <label>Status<select name="status">
                    <option value="draft">draft</option>
                    <option value="sent">sent</option>
                    <option value="accepted">accepted</option>
                    <option value="rejected">rejected</option>
                    <option value="expired">expired</option>
                </select></label>
                <label>Issue Date<input type="date" name="issue_date"></label>
                <label>Expiry Date<input type="date" name="expiry_date"></label>

                <h4>Articles</h4>
                <div class="articles-table-wrap">
                    <button type="button" class="btn" id="editAddArticleBtn">Add Article</button>
                    <div class="table-responsive">
                        <table class="articles-table" id="editArticlesTable">
                            <thead>
                                <tr>
                                    <th>Article Name</th>
                                    <th>Description</th>
                                    <th style="width:80px;text-align:center">UM</th>
                                    <th style="width:90px;text-align:right">Qte</th>
                                    <th style="width:110px;text-align:right">Unit Price</th>
                                    <th style="width:90px;text-align:right">TVA (%)</th>
                                    <th style="width:140px;text-align:right">TTC</th>
                                    <th style="width:60px">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- rows appended by JS -->
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="5" style="text-align:right;font-weight:700">Sub Total (TTC)</td>
                                    <td style="text-align:right;font-weight:700"><span id="editArticlesSubtotal">0.00</span><input type="hidden" name="total_ttc" value="0.00"></td>
                                    <td></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>

                <label>Notes<textarea name="notes"></textarea></label>

                <div class="modal-actions">
                    <button type="submit" class="btn">Save</button>
                    <button type="button" class="btn ghost" data-close="true">Cancel</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Devis Modal -->
<div class="modal" id="deleteDevisModal" aria-hidden="true">
    <div class="modal-overlay" data-close="true"></div>
    <div class="modal-window" role="dialog" aria-modal="true">
        <header class="modal-header">
            <h2>Delete Devis</h2>
            <button class="modal-close" data-close="true">✕</button>
        </header>
        <div class="modal-body">
            <form method="post">
                <input type="hidden" name="csrf_token" value="<?php echo e($_SESSION['csrf_token']); ?>">
                <input type="hidden" name="action" value="delete_devis">
                <input type="hidden" name="id" value="">
                <p>Are you sure you want to delete this devis? This will be a soft delete.</p>
                <div class="modal-actions">
                    <button type="submit" class="btn">Yes, delete</button>
                    <button type="button" class="btn ghost" data-close="true">Cancel</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- View Devis Modal -->
<div class="modal" id="viewDevisModal" aria-hidden="true">
    <div class="modal-overlay" data-close="true"></div>
    <div class="modal-window" role="dialog" aria-modal="true">
        <header class="modal-header">
            <h2>View Devis</h2>
            <button class="modal-close" data-close="true">✕</button>
        </header>
        <div class="modal-body" id="viewDevisBody">
            <!-- populated by JS -->
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../components/modal.php'; ?>
<script src="/projectos/js/modal.js"></script>
<script src="/projectos/js/devis.js"></script>
</body>
</html>
