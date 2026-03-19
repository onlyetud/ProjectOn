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

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $errors[] = 'Invalid CSRF token.';
    } else {
        $pdo = getDB();

        // Add Project
        if (isset($_POST['action']) && $_POST['action'] === 'add_project') {
            $project_name = trim($_POST['project_name'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $wilaya = trim($_POST['wilaya'] ?? '');
            $commune = trim($_POST['commune'] ?? '');
            $latitude = $_POST['latitude'] !== '' ? (float)$_POST['latitude'] : null;
            $longitude = $_POST['longitude'] !== '' ? (float)$_POST['longitude'] : null;
            $client = trim($_POST['client'] ?? '');
            $realisateur = trim($_POST['realisateur'] ?? '');
            $bureau_etude = trim($_POST['bureau_etude'] ?? '');
            $start_date = $_POST['start_date'] ?: null;
            $end_date = $_POST['end_date'] ?: null;
            $status = trim($_POST['status'] ?? 'planned');
            $budget = $_POST['budget'] !== '' ? (float)$_POST['budget'] : null;
            $other_info = trim($_POST['other_info'] ?? '');

            $stmt = $pdo->prepare('INSERT INTO projects (project_name, description, wilaya, commune, latitude, longitude, client, realisateur, bureau_etude, start_date, end_date, status, budget, other_info, created_at, updated_at) VALUES (:pn,:desc,:wilaya,:commune,:lat,:lng,:client,:realisateur,:bureau,:start,:end,:status,:budget,:other,NOW(),NOW())');
            $stmt->execute([':pn'=>$project_name,':desc'=>$description,':wilaya'=>$wilaya,':commune'=>$commune,':lat'=>$latitude,':lng'=>$longitude,':client'=>$client,':realisateur'=>$realisateur,':bureau'=>$bureau_etude,':start'=>$start_date,':end'=>$end_date,':status'=>$status,':budget'=>$budget,':other'=>$other_info]);
            $success = 'Project added.';
        }

        // Edit Project
        if (isset($_POST['action']) && $_POST['action'] === 'edit_project') {
            $id = (int)($_POST['id'] ?? 0);
            $project_name = trim($_POST['project_name'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $wilaya = trim($_POST['wilaya'] ?? '');
            $commune = trim($_POST['commune'] ?? '');
            $latitude = $_POST['latitude'] !== '' ? (float)$_POST['latitude'] : null;
            $longitude = $_POST['longitude'] !== '' ? (float)$_POST['longitude'] : null;
            $client = trim($_POST['client'] ?? '');
            $realisateur = trim($_POST['realisateur'] ?? '');
            $bureau_etude = trim($_POST['bureau_etude'] ?? '');
            $start_date = $_POST['start_date'] ?: null;
            $end_date = $_POST['end_date'] ?: null;
            $status = trim($_POST['status'] ?? 'planned');
            $budget = $_POST['budget'] !== '' ? (float)$_POST['budget'] : null;
            $other_info = trim($_POST['other_info'] ?? '');

            $stmt = $pdo->prepare('UPDATE projects SET project_name=:pn, description=:desc, wilaya=:wilaya, commune=:commune, latitude=:lat, longitude=:lng, client=:client, realisateur=:realisateur, bureau_etude=:bureau, start_date=:start, end_date=:end, status=:status, budget=:budget, other_info=:other, updated_at=NOW() WHERE id = :id');
            $stmt->execute([':pn'=>$project_name,':desc'=>$description,':wilaya'=>$wilaya,':commune'=>$commune,':lat'=>$latitude,':lng'=>$longitude,':client'=>$client,':realisateur'=>$realisateur,':bureau'=>$bureau_etude,':start'=>$start_date,':end'=>$end_date,':status'=>$status,':budget'=>$budget,':other'=>$other_info,':id'=>$id]);
            $success = 'Project updated.';
        }

        // Delete Project (soft)
        if (isset($_POST['action']) && $_POST['action'] === 'delete_project') {
            $id = (int)($_POST['id'] ?? 0);
            $stmt = $pdo->prepare('UPDATE projects SET deleted_at = NOW() WHERE id = :id');
            $stmt->execute([':id'=>$id]);
            $success = 'Project removed.';
        }

        // Add Lot
        if (isset($_POST['action']) && $_POST['action'] === 'add_lot') {
            $project_id = (int)($_POST['project_id'] ?? 0);
            $lot_code = trim($_POST['lot_code'] ?? '');
            $lot_name = trim($_POST['lot_name'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $contractor_id = $_POST['contractor_id'] !== '' ? (int)$_POST['contractor_id'] : null;
            $bureau_etude_id = $_POST['bureau_etude_id'] !== '' ? (int)$_POST['bureau_etude_id'] : null;
            $status = trim($_POST['status'] ?? 'planned');

            $stmt = $pdo->prepare('INSERT INTO project_lots (project_id, lot_code, lot_name, description, contractor_id, bureau_etude_id, status, created_at, updated_at) VALUES (:pid,:code,:name,:desc,:contractor,:bureau,:status,NOW(),NOW())');
            $stmt->execute([':pid'=>$project_id,':code'=>$lot_code,':name'=>$lot_name,':desc'=>$description,':contractor'=>$contractor_id,':bureau'=>$bureau_etude_id,':status'=>$status]);
            $success = 'Lot added.';
        }

        // Edit Lot
        if (isset($_POST['action']) && $_POST['action'] === 'edit_lot') {
            $id = (int)($_POST['id'] ?? 0);
            $project_id = (int)($_POST['project_id'] ?? 0);
            $lot_code = trim($_POST['lot_code'] ?? '');
            $lot_name = trim($_POST['lot_name'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $contractor_id = $_POST['contractor_id'] !== '' ? (int)$_POST['contractor_id'] : null;
            $bureau_etude_id = $_POST['bureau_etude_id'] !== '' ? (int)$_POST['bureau_etude_id'] : null;
            $status = trim($_POST['status'] ?? 'planned');

            $stmt = $pdo->prepare('UPDATE project_lots SET project_id=:pid, lot_code=:code, lot_name=:name, description=:desc, contractor_id=:contractor, bureau_etude_id=:bureau, status=:status, updated_at=NOW() WHERE id = :id');
            $stmt->execute([':pid'=>$project_id,':code'=>$lot_code,':name'=>$lot_name,':desc'=>$description,':contractor'=>$contractor_id,':bureau'=>$bureau_etude_id,':status'=>$status,':id'=>$id]);
            $success = 'Lot updated.';
        }

        // Delete Lot (soft)
        if (isset($_POST['action']) && $_POST['action'] === 'delete_lot') {
            $id = (int)($_POST['id'] ?? 0);
            $stmt = $pdo->prepare('UPDATE project_lots SET deleted_at = NOW() WHERE id = :id');
            $stmt->execute([':id'=>$id]);
            $success = 'Lot removed.';
        }
    }

    header('Location: /projectos/dashboard/projects.php');
    exit;
}

// Search
$q = trim($_GET['q'] ?? '');

$pdo = getDB();
$params = [];
$where = 'WHERE deleted_at IS NULL';
if ($q !== '') {
    $like = '%' . $q . '%';
    $where .= ' AND (project_name LIKE :like OR
     wilaya LIKE :like1 OR
    commune LIKE :like2 OR
    client LIKE :like3 OR
    realisateur LIKE :like4 OR
    status LIKE :like5)';
    $params[':like1'] = $like;
    $params[':like'] = $like;
    $params[':like2'] = $like;
    $params[':like3'] = $like;
    $params[':like4'] = $like;
    $params[':like5'] = $like;
}

$stmt = $pdo->prepare("SELECT id, project_name, description, wilaya, commune, latitude, longitude, client, realisateur, bureau_etude, start_date, end_date, status, budget, other_info FROM projects $where ORDER BY created_at DESC");
$stmt->execute($params);
$projects = $stmt->fetchAll();

// Fetch lots grouped by project
$projIds = array_column($projects, 'id');
$lotsByProj = [];
if (!empty($projIds)) {
    $in = implode(',', array_fill(0, count($projIds), '?'));
    $stmt = $pdo->prepare("SELECT id, project_id, lot_code, lot_name, description, contractor_id, bureau_etude_id, status FROM project_lots WHERE project_id IN ($in) AND deleted_at IS NULL ORDER BY created_at DESC");
    $stmt->execute($projIds);
    $lots = $stmt->fetchAll();
    foreach ($lots as $l) $lotsByProj[$l['project_id']][] = $l;
}

// Fetch stakeholders for contractor/bureau dropdowns
$stmt = $pdo->prepare('SELECT id, name FROM stakeholders WHERE deleted_at IS NULL ORDER BY name ASC');
$stmt->execute();
$stakeholders = $stmt->fetchAll();

?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Projects</title>
    <link rel="stylesheet" href="/projectos/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
    /* specific small adjustments for projects */
    .project-table th, .project-table td{padding:10px 12px;border-bottom:1px solid #eef2f7}
    .project-table thead th{background:transparent;text-align:left}
    @media(max-width:600px){
        .project-table thead{display:none}
        .project-table tr{display:block;margin-bottom:12px}
        .project-table td{display:flex;justify-content:space-between;padding:8px}
    }
    </style>
</head>
<body>
<?php require_once __DIR__ . '/../components/navbar.php'; ?>
<?php require_once __DIR__ . '/../components/sidebar.php'; ?>

<main class="main-content">
    <div class="container">
        <div class="page-header">
            <h1>Projects</h1>
            <p class="muted">Create, edit, and delete projects.</p>
        </div>

        <div class="controls">
            <form method="get">
                <input name="q" type="search" placeholder="Search project name, wilaya, commune, client, realisateur, status" value="<?=htmlspecialchars($q, ENT_QUOTES, 'UTF-8')?>">
            </form>
            <div>
                <button class="btn" type="button" onclick="openModal('addProjectModal')"><i class="fa-solid fa-plus"></i> Add Project</button>
            </div>
        </div>

        <div class="table-wrap">
            <table class="project-table stake-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Project Name</th>
                        <th>Wilaya</th>
                        <th>Commune</th>
                        <th>Status</th>
                        <th>Client</th>
                        <th>Start</th>
                        <th>End</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php
                    // map stakeholders id=>name for display
                    $stakeMap = [];
                    foreach ($stakeholders as $s) $stakeMap[$s['id']] = $s['name'];
                ?>
                <?php foreach ($projects as $p): ?>
                    <tr class="proj-row" data-id="<?=htmlspecialchars($p['id'], ENT_QUOTES, 'UTF-8')?>"
                        data-project_name="<?=htmlspecialchars($p['project_name'], ENT_QUOTES, 'UTF-8')?>"
                        data-description="<?=htmlspecialchars($p['description'] ?? '', ENT_QUOTES, 'UTF-8')?>"
                        data-wilaya="<?=htmlspecialchars($p['wilaya'] ?? '', ENT_QUOTES, 'UTF-8')?>"
                        data-commune="<?=htmlspecialchars($p['commune'] ?? '', ENT_QUOTES, 'UTF-8')?>"
                        data-latitude="<?=htmlspecialchars($p['latitude'] ?? '', ENT_QUOTES, 'UTF-8')?>"
                        data-longitude="<?=htmlspecialchars($p['longitude'] ?? '', ENT_QUOTES, 'UTF-8')?>"
                        data-client="<?=htmlspecialchars($p['client'] ?? '', ENT_QUOTES, 'UTF-8')?>"
                        data-realisateur="<?=htmlspecialchars($p['realisateur'] ?? '', ENT_QUOTES, 'UTF-8')?>"
                        data-bureau_etude="<?=htmlspecialchars($p['bureau_etude'] ?? '', ENT_QUOTES, 'UTF-8')?>"
                        data-start_date="<?=htmlspecialchars($p['start_date'] ?? '', ENT_QUOTES, 'UTF-8')?>"
                        data-end_date="<?=htmlspecialchars($p['end_date'] ?? '', ENT_QUOTES, 'UTF-8')?>"
                        data-status="<?=htmlspecialchars($p['status'] ?? '', ENT_QUOTES, 'UTF-8')?>"
                        data-budget="<?=htmlspecialchars($p['budget'] ?? '', ENT_QUOTES, 'UTF-8')?>"
                        data-other_info="<?=htmlspecialchars($p['other_info'] ?? '', ENT_QUOTES, 'UTF-8')?>"
                    >
                        <td><?=htmlspecialchars($p['id'], ENT_QUOTES, 'UTF-8')?></td>
                        <td><?=htmlspecialchars($p['project_name'], ENT_QUOTES, 'UTF-8')?></td>
                        <td><?=htmlspecialchars($p['wilaya'] ?? '', ENT_QUOTES, 'UTF-8')?></td>
                        <td><?=htmlspecialchars($p['commune'] ?? '', ENT_QUOTES, 'UTF-8')?></td>
                        <td><?=htmlspecialchars($p['status'] ?? '', ENT_QUOTES, 'UTF-8')?></td>
                        <td><?=htmlspecialchars($p['client'] ?? '', ENT_QUOTES, 'UTF-8')?></td>
                        <td><?=htmlspecialchars($p['start_date'] ?? '', ENT_QUOTES, 'UTF-8')?></td>
                        <td><?=htmlspecialchars($p['end_date'] ?? '', ENT_QUOTES, 'UTF-8')?></td>
                        <td>
                            <div class="actions">
                                <button class="btn ghost open-project-details" title="Details"><i class="fa-solid fa-info-circle"></i></button>
                                <button class="btn ghost toggle-lots" title="Toggle lots" data-project-id="<?=htmlspecialchars($p['id'], ENT_QUOTES, 'UTF-8')?>"><i class="fa-solid fa-chevron-down"></i></button>
                                <button class="btn ghost open-add-lot" title="Add Lot" data-project-id="<?=htmlspecialchars($p['id'], ENT_QUOTES, 'UTF-8')?>"><i class="fa-solid fa-plus"></i></button>
                                <button class="btn ghost open-edit-project" title="Edit"><i class="fa-solid fa-pen"></i></button>
                                <button class="btn ghost open-delete-project" title="Delete"><i class="fa-solid fa-trash"></i></button>
                            </div>
                        </td>
                    </tr>
                    <tr class="lots-row" data-parent="<?=htmlspecialchars($p['id'], ENT_QUOTES, 'UTF-8')?>" style="display:none">
                        <td colspan="9">
                            <div class="nt-sub">
                                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px">
                                    <strong>Project Lots</strong>
                                </div>
                                <table class="nt-table">
                                    <thead><tr><th>ID</th><th>Lot Code</th><th>Lot Name</th><th>Status</th><th>Contractor</th><th>Bureau Etude</th><th>Actions</th></tr></thead>
                                    <tbody>
                                    <?php foreach ($lotsByProj[$p['id']] ?? [] as $l): ?>
                                        <tr>
                                            <td><?=htmlspecialchars($l['id'], ENT_QUOTES, 'UTF-8')?></td>
                                            <td><?=htmlspecialchars($l['lot_code'] ?? '', ENT_QUOTES, 'UTF-8')?></td>
                                            <td><?=htmlspecialchars($l['lot_name'] ?? '', ENT_QUOTES, 'UTF-8')?></td>
                                            <td><?=htmlspecialchars($l['status'] ?? '', ENT_QUOTES, 'UTF-8')?></td>
                                            <td><?=htmlspecialchars($stakeMap[$l['contractor_id']] ?? '', ENT_QUOTES, 'UTF-8')?></td>
                                            <td><?=htmlspecialchars($stakeMap[$l['bureau_etude_id']] ?? '', ENT_QUOTES, 'UTF-8')?></td>
                                            <td>
                                                <div class="actions">
                                                    <button class="btn ghost open-edit-lot"
                                                        data-id="<?=htmlspecialchars($l['id'], ENT_QUOTES, 'UTF-8')?>"
                                                        data-project_id="<?=htmlspecialchars($l['project_id'], ENT_QUOTES, 'UTF-8')?>"
                                                        data-lot_code="<?=htmlspecialchars($l['lot_code'] ?? '', ENT_QUOTES, 'UTF-8')?>"
                                                        data-lot_name="<?=htmlspecialchars($l['lot_name'] ?? '', ENT_QUOTES, 'UTF-8')?>"
                                                        data-description="<?=htmlspecialchars($l['description'] ?? '', ENT_QUOTES, 'UTF-8')?>"
                                                        data-contractor_id="<?=htmlspecialchars($l['contractor_id'] ?? '', ENT_QUOTES, 'UTF-8')?>"
                                                        data-bureau_etude_id="<?=htmlspecialchars($l['bureau_etude_id'] ?? '', ENT_QUOTES, 'UTF-8')?>"
                                                        data-status="<?=htmlspecialchars($l['status'] ?? '', ENT_QUOTES, 'UTF-8')?>"
                                                    ><i class="fa-solid fa-pen-to-square"></i></button>
                                                    <button class="btn ghost open-delete-lot" data-id="<?=htmlspecialchars($l['id'], ENT_QUOTES, 'UTF-8')?>"><i class="fa-solid fa-trash"></i></button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>

<?php require_once __DIR__ . '/../components/modal.php'; ?>
<script src="/projectos/js/modal.js"></script>
<script src="/projectos/js/projects.js"></script>
</body>
</html>
