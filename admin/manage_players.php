<?php
require_once __DIR__.'/../includes/config.php';
requireRole('Admin'); $currentPage='manage_players'; $pageTitle='Players'; $db=getDB(); $ok=$err='';

define('PLAYER_UPLOAD_DIR', __DIR__.'/../assets/uploads/players/');
define('PLAYER_UPLOAD_URL', APP_URL.'/assets/uploads/players/');

function uploadPhoto(int $pid, ?string $old): string|null|false {
  if (!isset($_FILES['photo']) || $_FILES['photo']['error'] === UPLOAD_ERR_NO_FILE) return false;
  if ($_FILES['photo']['error'] !== UPLOAD_ERR_OK) return false;
  $ext = ['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp'];
  $mime = mime_content_type($_FILES['photo']['tmp_name']);
  if (!isset($ext[$mime])) return false;
  if ($_FILES['photo']['size'] > 2097152) return false;
  if (!is_dir(PLAYER_UPLOAD_DIR)) mkdir(PLAYER_UPLOAD_DIR, 0755, true);
  if ($old && file_exists(PLAYER_UPLOAD_DIR.$old)) unlink(PLAYER_UPLOAD_DIR.$old);
  $name = 'player_'.$pid.'_'.time().'.'.$ext[$mime];
  move_uploaded_file($_FILES['photo']['tmp_name'], PLAYER_UPLOAD_DIR.$name);
  return $name;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $a   = $_POST['action'] ?? '';
  $pid = (int)($_POST['pid'] ?? 0);

  if (in_array($a, ['add','edit'])) {
    $tid = (int)$_POST['team_id'];
    $fn  = trim($_POST['first_name'] ?? '');
    $ln  = trim($_POST['last_name'] ?? '');
    $jn  = (int)$_POST['jersey_number'];
    $pos = $_POST['position'] ?? '';
    $ht  = $_POST['height_cm']  ? (int)$_POST['height_cm']     : null;
    $wt  = $_POST['weight_kg']  ? (float)$_POST['weight_kg']   : null;
    $dob = $_POST['date_of_birth'] ?: null;

    if (!$tid || !$fn || !$ln || !$jn || !$pos) {
      $err = 'Team, name, jersey number and position are required.';
    } else {
      if ($a === 'add') {
        $db->prepare("INSERT INTO players(team_id,first_name,last_name,jersey_number,position,height_cm,weight_kg,date_of_birth,is_active)VALUES(?,?,?,?,?,?,?,?,1)")
           ->execute([$tid,$fn,$ln,$jn,$pos,$ht,$wt,$dob]);
        $newId = (int)$db->lastInsertId();
        $photo = uploadPhoto($newId, null);
        if ($photo) $db->prepare("UPDATE players SET photo=? WHERE id=?")->execute([$photo,$newId]);
        $ok = "Player $fn $ln added."; logActivity("Added player: $fn $ln",'player',$newId);
      } else {
        $row = $db->prepare("SELECT photo FROM players WHERE id=?"); $row->execute([$pid]); $cur = $row->fetch();
        $oldPhoto = $cur['photo'] ?? null;
        if (isset($_POST['remove_photo']) && $oldPhoto) {
          if (file_exists(PLAYER_UPLOAD_DIR.$oldPhoto)) unlink(PLAYER_UPLOAD_DIR.$oldPhoto);
          $oldPhoto = null;
        }
        $upload = uploadPhoto($pid, $oldPhoto);
        $finalPhoto = ($upload !== false) ? $upload : $oldPhoto;
        $db->prepare("UPDATE players SET team_id=?,first_name=?,last_name=?,jersey_number=?,position=?,height_cm=?,weight_kg=?,date_of_birth=?,photo=? WHERE id=?")
           ->execute([$tid,$fn,$ln,$jn,$pos,$ht,$wt,$dob,$finalPhoto,$pid]);
        $ok = "Player $fn $ln updated."; logActivity("Updated player: $fn $ln",'player',$pid);
      }
    }
  } elseif ($a === 'toggle') {
    $db->prepare("UPDATE players SET is_active=NOT is_active WHERE id=?")->execute([$pid]); $ok = 'Player status toggled.';
  }
}

$tf = (int)($_GET['team'] ?? 0);
$teams = $db->query("SELECT id,name,short_name,color_primary FROM teams WHERE is_active=1 ORDER BY name")->fetchAll();
$where = ''; $params = [];
if ($tf) { $where = "WHERE p.team_id=?"; $params[] = $tf; }
$stmt = $db->prepare("SELECT p.*,t.name tn,t.short_name ts,t.color_primary tc FROM players p JOIN teams t ON t.id=p.team_id $where ORDER BY t.name,p.jersey_number");
$stmt->execute($params); $players = $stmt->fetchAll();
$positions = ['Setter','Outside Hitter','Middle Blocker','Opposite Hitter','Libero','Defensive Specialist'];
include __DIR__.'/../includes/header.php';
?>
<div class="content">
  <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px;margin-bottom:20px">
    <h1 style="font-size:18px;font-weight:600">Players</h1>
    <div style="display:flex;gap:6px">
      <a href="<?=APP_URL?>/admin/export_pdf.php?type=players<?=$tf?'&team='.$tf:''?>" class="btn btn-ghost btn-sm" target="_blank"><i class="bi bi-file-earmark-pdf"></i>Export PDF</a>
      <button class="btn btn-pri btn-sm" onclick="openAddP()" data-bs-toggle="modal" data-bs-target="#pm"><i class="bi bi-person-plus"></i>Add Player</button>
    </div>
  </div>
  <?php if($ok): ?><div class="alert a-ok"><i class="bi bi-check-circle-fill"></i><?= htmlspecialchars($ok) ?></div><?php endif; ?>
  <?php if($err): ?><div class="alert a-err"><i class="bi bi-exclamation-circle-fill"></i><?= htmlspecialchars($err) ?></div><?php endif; ?>

  <div style="display:flex;gap:6px;flex-wrap:wrap;margin-bottom:14px">
    <a href="?" class="btn <?= !$tf?'btn-pri':'btn-ghost' ?> btn-sm">All</a>
    <?php foreach($teams as $t): ?>
    <a href="?team=<?= $t['id'] ?>" class="btn <?= $tf==$t['id']?'btn-pri':'btn-ghost' ?> btn-sm">
      <span style="display:inline-block;width:8px;height:8px;border-radius:50%;background:<?= $t['color_primary'] ?>;margin-right:4px"></span><?= htmlspecialchars($t['short_name']) ?>
    </a>
    <?php endforeach; ?>
    <span style="font-size:12px;color:var(--t3);align-self:center;margin-left:4px"><?= count($players) ?> players</span>
  </div>

  <div class="card">
    <div class="tbl-wrap">
      <table class="tbl">
        <thead><tr><th>Photo</th><th>#</th><th>Name</th><th>Team</th><th>Position</th><th>Height</th><th>DOB</th><th>Status</th><th>Actions</th></tr></thead>
        <tbody>
          <?php foreach($players as $p): ?>
          <tr style="<?= !$p['is_active']?'opacity:.5':'' ?>">
            <td>
              <?php if(!empty($p['photo'])): ?>
              <img src="<?= PLAYER_UPLOAD_URL.htmlspecialchars($p['photo']) ?>" alt=""
                   style="width:34px;height:34px;border-radius:50%;object-fit:cover;border:2px solid <?= $p['tc'] ?>">
              <?php else: ?>
              <div style="width:34px;height:34px;border-radius:50%;background:<?= $p['tc'] ?>;display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:700;color:#fff"><?= strtoupper(substr($p['first_name'],0,1)) ?></div>
              <?php endif; ?>
            </td>
            <td><div style="width:30px;height:30px;border-radius:5px;background:<?= $p['tc'] ?>;display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:700;color:#fff"><?= $p['jersey_number'] ?></div></td>
            <td><div style="font-size:13px;font-weight:600"><?= htmlspecialchars($p['first_name'].' '.$p['last_name']) ?></div></td>
            <td><div style="display:flex;align-items:center;gap:5px;font-size:12px"><div class="team-dot" style="background:<?= $p['tc'] ?>"></div><?= htmlspecialchars($p['tn']) ?></div></td>
            <td><span class="badge b-sched" style="font-size:10px"><?= $p['position'] ?></span></td>
            <td style="font-size:12px;color:var(--t2)"><?= $p['height_cm']?$p['height_cm'].'cm':'—' ?></td>
            <td style="font-size:12px;color:var(--t2)"><?= $p['date_of_birth']?date('d M Y',strtotime($p['date_of_birth'])):'—' ?></td>
            <td><span class="badge <?= $p['is_active']?'b-active':'b-off' ?>" style="font-size:10px"><?= $p['is_active']?'Active':'Inactive' ?></span></td>
            <td>
              <div style="display:flex;gap:4px">
                <button class="btn btn-ghost btn-sm btn-icon" onclick='loadEditP(<?= htmlspecialchars(json_encode($p)) ?>)' data-bs-toggle="modal" data-bs-target="#pm" title="Edit"><i class="bi bi-pencil" style="font-size:12px"></i></button>
                <form method="POST" style="display:inline"><input type="hidden" name="action" value="toggle"><input type="hidden" name="pid" value="<?= $p['id'] ?>"><button type="submit" class="btn btn-ghost btn-sm btn-icon" title="Toggle active"><i class="bi bi-<?= $p['is_active']?'pause':'play' ?>-fill" style="font-size:12px"></i></button></form>
              </div>
            </td>
          </tr>
          <?php endforeach; ?>
          <?php if(empty($players)): ?><tr><td colspan="9" style="text-align:center;padding:30px;color:var(--t3)">No players found</td></tr><?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- Player modal -->
<div class="modal fade" id="pm" tabindex="-1"><div class="modal-dialog modal-dialog-centered modal-lg"><div class="modal-content">
  <div class="modal-header"><h5 class="modal-title" id="pmTitle">Add Player</h5><button class="btn-close" data-bs-dismiss="modal"></button></div>
  <form method="POST" enctype="multipart/form-data">
    <div class="modal-body">
      <input type="hidden" name="action" id="pmA" value="add">
      <input type="hidden" name="pid" id="pmId" value="">

      <!-- Photo upload row -->
      <div style="display:flex;align-items:center;gap:16px;padding:12px;background:var(--s2);border:1px solid var(--b0);border-radius:var(--r);margin-bottom:14px">
        <div id="photoPreviewWrap" style="width:70px;height:70px;border-radius:50%;border:2px solid var(--b1);background:var(--s3);display:flex;align-items:center;justify-content:center;overflow:hidden;flex-shrink:0">
          <img id="photoPreview" src="" alt="" style="width:100%;height:100%;object-fit:cover;display:none">
          <i id="photoPlaceholder" class="bi bi-person" style="font-size:28px;color:var(--b2)"></i>
        </div>
        <div>
          <div style="font-size:13px;font-weight:600;color:var(--t1);margin-bottom:4px">Player Photo</div>
          <label style="display:inline-flex;align-items:center;gap:6px;padding:5px 12px;border:1px solid var(--b1);border-radius:var(--r);cursor:pointer;font-size:12px;color:var(--t2);background:var(--s1);transition:background var(--tr)" onmouseover="this.style.background='var(--s3)'" onmouseout="this.style.background='var(--s1)'">
            <i class="bi bi-camera"></i> Upload photo
            <input type="file" name="photo" id="photoInput" accept="image/jpeg,image/png,image/webp" style="display:none" onchange="previewPhoto(this)">
          </label>
          <div style="font-size:11px;color:var(--t3);margin-top:4px">JPG, PNG or WEBP · max 2 MB</div>
          <div id="removePhotoWrap" style="display:none;margin-top:4px">
            <label style="display:inline-flex;align-items:center;gap:4px;font-size:11px;color:var(--red);cursor:pointer">
              <input type="checkbox" name="remove_photo" id="rmPhoto" onchange="toggleRemovePhoto(this)"> Remove current photo
            </label>
          </div>
        </div>
      </div>

      <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
        <div class="field"><label class="label">First Name *</label><input type="text" name="first_name" id="pmFn" class="input" required placeholder="Chisomo"></div>
        <div class="field"><label class="label">Last Name *</label><input type="text" name="last_name" id="pmLn" class="input" required placeholder="Phiri"></div>
        <div class="field"><label class="label">Team *</label>
          <select name="team_id" id="pmTid" class="select" required>
            <option value="">Select team</option>
            <?php foreach($teams as $t): ?><option value="<?= $t['id'] ?>"><?= htmlspecialchars($t['name']) ?></option><?php endforeach; ?>
          </select>
        </div>
        <div class="field"><label class="label">Jersey Number *</label><input type="number" name="jersey_number" id="pmJn" class="input" required min="1" max="99" placeholder="7"></div>
        <div class="field"><label class="label">Position *</label>
          <select name="position" id="pmPos" class="select" required>
            <option value="">Select position</option>
            <?php foreach($positions as $pos): ?><option value="<?= $pos ?>"><?= $pos ?></option><?php endforeach; ?>
          </select>
        </div>
        <div class="field"><label class="label">Date of Birth</label><input type="date" name="date_of_birth" id="pmDob" class="input"></div>
        <div class="field"><label class="label">Height (cm)</label><input type="number" name="height_cm" id="pmHt" class="input" min="140" max="230" placeholder="185"></div>
        <div class="field"><label class="label">Weight (kg)</label><input type="number" name="weight_kg" id="pmWt" class="input" step="0.1" min="50" max="150" placeholder="80.0"></div>
      </div>
    </div>
    <div class="modal-footer">
      <button type="button" class="btn btn-ghost btn-sm" data-bs-dismiss="modal">Cancel</button>
      <button type="submit" class="btn btn-pri btn-sm">Save Player</button>
    </div>
  </form>
</div></div></div>

<script>
const PHOTO_URL = '<?= PLAYER_UPLOAD_URL ?>';

function previewPhoto(input) {
  const preview = document.getElementById('photoPreview');
  const placeholder = document.getElementById('photoPlaceholder');
  if (!input.files || !input.files[0]) return;
  const reader = new FileReader();
  reader.onload = e => {
    preview.src = e.target.result;
    preview.style.display = 'block';
    placeholder.style.display = 'none';
  };
  reader.readAsDataURL(input.files[0]);
  const rm = document.getElementById('rmPhoto');
  if (rm) rm.checked = false;
}

function toggleRemovePhoto(cb) {
  const preview = document.getElementById('photoPreview');
  const placeholder = document.getElementById('photoPlaceholder');
  if (cb.checked) { preview.style.display = 'none'; placeholder.style.display = ''; }
  else if (preview.dataset.existing) { preview.style.display = 'block'; placeholder.style.display = 'none'; }
}

function openAddP() {
  document.getElementById('pmTitle').textContent = 'Add Player';
  document.getElementById('pmA').value = 'add';
  document.getElementById('pmId').value = '';
  ['pmFn','pmLn','pmJn','pmHt','pmWt'].forEach(id => document.getElementById(id).value = '');
  ['pmTid','pmPos'].forEach(id => document.getElementById(id).value = '');
  document.getElementById('pmDob').value = '';
  document.getElementById('photoInput').value = '';
  const preview = document.getElementById('photoPreview');
  preview.src = ''; preview.style.display = 'none'; preview.dataset.existing = '';
  document.getElementById('photoPlaceholder').style.display = '';
  document.getElementById('removePhotoWrap').style.display = 'none';
}

function loadEditP(p) {
  document.getElementById('pmTitle').textContent = 'Edit Player';
  document.getElementById('pmA').value = 'edit';
  document.getElementById('pmId').value = p.id;
  document.getElementById('pmFn').value = p.first_name;
  document.getElementById('pmLn').value = p.last_name;
  document.getElementById('pmTid').value = p.team_id;
  document.getElementById('pmJn').value = p.jersey_number;
  document.getElementById('pmPos').value = p.position;
  document.getElementById('pmDob').value = p.date_of_birth || '';
  document.getElementById('pmHt').value = p.height_cm || '';
  document.getElementById('pmWt').value = p.weight_kg || '';
  document.getElementById('photoInput').value = '';

  const preview = document.getElementById('photoPreview');
  const placeholder = document.getElementById('photoPlaceholder');
  const rmWrap = document.getElementById('removePhotoWrap');
  const rm = document.getElementById('rmPhoto');
  if (p.photo) {
    preview.src = PHOTO_URL + p.photo;
    preview.style.display = 'block';
    preview.dataset.existing = '1';
    placeholder.style.display = 'none';
    rmWrap.style.display = 'block';
    rm.checked = false;
  } else {
    preview.src = ''; preview.style.display = 'none'; preview.dataset.existing = '';
    placeholder.style.display = '';
    rmWrap.style.display = 'none';
  }
}
</script>
<?php include __DIR__.'/../includes/footer.php'; ?>
