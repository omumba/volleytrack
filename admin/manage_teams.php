<?php
require_once __DIR__.'/../includes/config.php';
requireRole('Admin'); $currentPage='manage_teams'; $pageTitle='Teams'; $db=getDB(); $ok=$err='';

define('TEAM_UPLOAD_DIR', __DIR__.'/../assets/uploads/teams/');
define('TEAM_UPLOAD_URL', APP_URL.'/assets/uploads/teams/');

function uploadLogo(int $tid, ?string $old): string|null|false {
  if (!isset($_FILES['logo']) || $_FILES['logo']['error'] === UPLOAD_ERR_NO_FILE) return false; // no file chosen
  if ($_FILES['logo']['error'] !== UPLOAD_ERR_OK) return false;
  $ext = ['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp'];
  $mime = mime_content_type($_FILES['logo']['tmp_name']);
  if (!isset($ext[$mime])) return false;
  if ($_FILES['logo']['size'] > 2097152) return false; // 2 MB
  if (!is_dir(TEAM_UPLOAD_DIR)) mkdir(TEAM_UPLOAD_DIR, 0755, true);
  if ($old && file_exists(TEAM_UPLOAD_DIR.$old)) unlink(TEAM_UPLOAD_DIR.$old);
  $name = 'team_'.$tid.'_'.time().'.'.$ext[$mime];
  move_uploaded_file($_FILES['logo']['tmp_name'], TEAM_UPLOAD_DIR.$name);
  return $name;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $a    = $_POST['action'] ?? '';
  $tid  = (int)($_POST['tid'] ?? 0);
  $name = trim($_POST['name'] ?? '');
  $short= strtoupper(trim($_POST['short_name'] ?? ''));
  $court= trim($_POST['home_court'] ?? '');
  $city = trim($_POST['city'] ?? '');
  $coach= trim($_POST['coach'] ?? '');
  $cp   = $_POST['color_primary'] ?? '#555555';

  if (in_array($a, ['add','edit'])) {
    if (!$name || !$short) { $err = 'Name and short name required.'; }
    else {
      if ($a === 'add') {
        $db->prepare("INSERT INTO teams(name,short_name,home_court,city,coach,color_primary,is_active)VALUES(?,?,?,?,?,?,1)")
           ->execute([$name,$short,$court,$city,$coach,$cp]);
        $newId = (int)$db->lastInsertId();
        $logo = uploadLogo($newId, null);
        if ($logo) $db->prepare("UPDATE teams SET logo=? WHERE id=?")->execute([$logo,$newId]);
        $ok = "Team \"$name\" added."; logActivity("Added team: $name",'team',$newId);
      } else {
        $row = $db->prepare("SELECT logo FROM teams WHERE id=?"); $row->execute([$tid]); $cur = $row->fetch();
        $oldLogo = $cur['logo'] ?? null;
        if (isset($_POST['remove_logo']) && $oldLogo) {
          if (file_exists(TEAM_UPLOAD_DIR.$oldLogo)) unlink(TEAM_UPLOAD_DIR.$oldLogo);
          $oldLogo = null;
        }
        $upload = uploadLogo($tid, $oldLogo);
        $finalLogo = ($upload !== false) ? $upload : $oldLogo;
        $db->prepare("UPDATE teams SET name=?,short_name=?,home_court=?,city=?,coach=?,color_primary=?,logo=? WHERE id=?")
           ->execute([$name,$short,$court,$city,$coach,$cp,$finalLogo,$tid]);
        $ok = "Team \"$name\" updated."; logActivity("Updated team: $name",'team',$tid);
      }
    }
  } elseif ($a === 'toggle') {
    $db->prepare("UPDATE teams SET is_active=NOT is_active WHERE id=?")->execute([$tid]); $ok = 'Status toggled.';
  }
}

$teams = $db->query("SELECT t.*,COUNT(p.id) pc FROM teams t LEFT JOIN players p ON p.team_id=t.id AND p.is_active=1 GROUP BY t.id ORDER BY t.is_active DESC,t.name")->fetchAll();
include __DIR__.'/../includes/header.php';
?>
<div class="content">
  <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px;margin-bottom:20px">
    <h1 style="font-size:18px;font-weight:600">Teams</h1>
    <div style="display:flex;gap:6px">
      <a href="<?=APP_URL?>/admin/export_pdf.php?type=teams" class="btn btn-ghost btn-sm" target="_blank"><i class="bi bi-file-earmark-pdf"></i>Export PDF</a>
      <button class="btn btn-pri btn-sm" onclick="openAdd()" data-bs-toggle="modal" data-bs-target="#tm"><i class="bi bi-plus"></i>Add Team</button>
    </div>
  </div>
  <?php if($ok): ?><div class="alert a-ok"><i class="bi bi-check-circle-fill"></i><?= htmlspecialchars($ok) ?></div><?php endif; ?>
  <?php if($err): ?><div class="alert a-err"><i class="bi bi-exclamation-circle-fill"></i><?= htmlspecialchars($err) ?></div><?php endif; ?>

  <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(270px,1fr));gap:10px">
    <?php foreach($teams as $t): ?>
    <div style="background:var(--s1);border:1px solid var(--b0);border-radius:var(--r);overflow:hidden;<?= !$t['is_active']?'opacity:.55':'' ?>">
      <div style="height:3px;background:<?= $t['color_primary'] ?>"></div>
      <div style="padding:14px">
        <div style="display:flex;align-items:flex-start;gap:10px;margin-bottom:12px">
          <?php if($t['logo']): ?>
          <img src="<?= TEAM_UPLOAD_URL.htmlspecialchars($t['logo']) ?>" alt="logo"
               style="width:44px;height:44px;border-radius:8px;object-fit:contain;background:#fff;border:1px solid var(--b0);flex-shrink:0;padding:3px">
          <?php else: ?>
          <div style="width:44px;height:44px;border-radius:8px;background:<?= $t['color_primary'] ?>;display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:700;color:#fff;flex-shrink:0"><?= substr($t['short_name'],0,3) ?></div>
          <?php endif; ?>
          <div style="flex:1;min-width:0">
            <div style="font-size:14px;font-weight:600;color:var(--t1)"><?= htmlspecialchars($t['name']) ?></div>
            <div style="font-size:11px;color:var(--t3);margin-top:2px;display:flex;align-items:center;gap:6px;flex-wrap:wrap">
              <?php if($t['city']): ?><span><i class="bi bi-geo-alt"></i> <?= htmlspecialchars($t['city']) ?></span><?php endif; ?>
              <span class="badge <?= $t['is_active']?'b-active':'b-off' ?>" style="font-size:9px"><?= $t['is_active']?'Active':'Inactive' ?></span>
            </div>
          </div>
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;margin-bottom:12px">
          <div style="background:var(--s2);border-radius:var(--r);padding:7px 10px">
            <div style="font-size:18px;font-weight:700;color:var(--t1)"><?= $t['pc'] ?></div>
            <div style="font-size:10px;color:var(--t3);text-transform:uppercase;letter-spacing:.04em">Players</div>
          </div>
          <div style="background:var(--s2);border-radius:var(--r);padding:7px 10px">
            <div style="font-size:12px;font-weight:600;color:var(--t1);white-space:nowrap;overflow:hidden;text-overflow:ellipsis"><?= htmlspecialchars($t['coach']??'TBA') ?></div>
            <div style="font-size:10px;color:var(--t3);text-transform:uppercase;letter-spacing:.04em">Coach</div>
          </div>
        </div>
        <?php if($t['home_court']): ?><div style="font-size:12px;color:var(--t3);margin-bottom:10px"><i class="bi bi-building me-1"></i><?= htmlspecialchars($t['home_court']) ?></div><?php endif; ?>
        <div style="display:flex;gap:6px">
          <button class="btn btn-ghost btn-sm" onclick='loadEdit(<?= htmlspecialchars(json_encode($t)) ?>)' data-bs-toggle="modal" data-bs-target="#tm"><i class="bi bi-pencil"></i>Edit</button>
          <form method="POST" style="display:inline"><input type="hidden" name="action" value="toggle"><input type="hidden" name="tid" value="<?= $t['id'] ?>"><button type="submit" class="btn btn-ghost btn-sm"><?= $t['is_active']?'Deactivate':'Activate' ?></button></form>
          <a href="<?= APP_URL ?>/modules/players/index.php?team=<?= $t['id'] ?>" class="btn btn-ghost btn-sm btn-icon" title="View players"><i class="bi bi-people" style="font-size:12px"></i></a>
        </div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
</div>

<!-- Team modal -->
<div class="modal fade" id="tm" tabindex="-1"><div class="modal-dialog modal-dialog-centered"><div class="modal-content">
  <div class="modal-header"><h5 class="modal-title" id="tmTitle">Add Team</h5><button class="btn-close" data-bs-dismiss="modal"></button></div>
  <form method="POST" enctype="multipart/form-data">
    <div class="modal-body" style="display:flex;flex-direction:column;gap:12px">
      <input type="hidden" name="action" id="tmA" value="add">
      <input type="hidden" name="tid" id="tmId" value="">

      <!-- Logo upload -->
      <div class="field">
        <label class="label">Team Logo</label>
        <div style="display:flex;align-items:center;gap:12px">
          <div id="logoPreviewWrap" style="width:60px;height:60px;border-radius:8px;border:1px solid var(--b1);background:var(--s2);display:flex;align-items:center;justify-content:center;overflow:hidden;flex-shrink:0">
            <img id="logoPreview" src="" alt="" style="width:100%;height:100%;object-fit:contain;display:none">
            <i id="logoPlaceholder" class="bi bi-image" style="font-size:20px;color:var(--b2)"></i>
          </div>
          <div style="flex:1">
            <label style="display:inline-flex;align-items:center;gap:6px;padding:6px 12px;border:1px solid var(--b1);border-radius:var(--r);cursor:pointer;font-size:12px;color:var(--t2);background:var(--s1);transition:background var(--tr)" onmouseover="this.style.background='var(--s2)'" onmouseout="this.style.background='var(--s1)'">
              <i class="bi bi-upload"></i> Choose logo
              <input type="file" name="logo" id="logoInput" accept="image/jpeg,image/png,image/webp" style="display:none" onchange="previewLogo(this)">
            </label>
            <div style="font-size:11px;color:var(--t3);margin-top:4px">JPG, PNG or WEBP · max 2 MB</div>
            <div id="removeLogo" style="display:none;margin-top:4px">
              <label style="display:inline-flex;align-items:center;gap:4px;font-size:11px;color:var(--red);cursor:pointer">
                <input type="checkbox" name="remove_logo" id="rmLogo" onchange="toggleRemove(this)"> Remove current logo
              </label>
            </div>
          </div>
        </div>
      </div>

      <div style="display:grid;grid-template-columns:2fr 1fr;gap:10px">
        <div class="field"><label class="label">Team Name *</label><input type="text" name="name" id="tmN" class="input" required placeholder="Blantyre Spikers"></div>
        <div class="field"><label class="label">Short Name *</label><input type="text" name="short_name" id="tmS" class="input" required maxlength="6" placeholder="BLS"></div>
      </div>
      <div class="field"><label class="label">Home Court</label><input type="text" name="home_court" id="tmC" class="input" placeholder="Kamuzu Stadium Court"></div>
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px">
        <div class="field"><label class="label">City</label><input type="text" name="city" id="tmCi" class="input" placeholder="Blantyre"></div>
        <div class="field"><label class="label">Coach</label><input type="text" name="coach" id="tmCo" class="input" placeholder="Coach name"></div>
      </div>
      <div class="field"><label class="label">Team Colour</label><input type="color" name="color_primary" id="tmCol" class="input" value="#555555" style="height:38px;cursor:pointer;padding:3px 6px"></div>
    </div>
    <div class="modal-footer">
      <button type="button" class="btn btn-ghost btn-sm" data-bs-dismiss="modal">Cancel</button>
      <button type="submit" class="btn btn-pri btn-sm">Save Team</button>
    </div>
  </form>
</div></div></div>

<script>
const LOGO_URL = '<?= TEAM_UPLOAD_URL ?>';

function previewLogo(input) {
  const preview = document.getElementById('logoPreview');
  const placeholder = document.getElementById('logoPlaceholder');
  if (!input.files || !input.files[0]) return;
  const reader = new FileReader();
  reader.onload = e => {
    preview.src = e.target.result;
    preview.style.display = 'block';
    placeholder.style.display = 'none';
  };
  reader.readAsDataURL(input.files[0]);
  // Uncheck remove if user picks a new file
  const rm = document.getElementById('rmLogo');
  if (rm) { rm.checked = false; }
}

function toggleRemove(cb) {
  const preview = document.getElementById('logoPreview');
  const placeholder = document.getElementById('logoPlaceholder');
  if (cb.checked) { preview.style.display = 'none'; placeholder.style.display = ''; }
  else if (preview.dataset.existing) { preview.style.display = 'block'; placeholder.style.display = 'none'; }
}

function openAdd() {
  document.getElementById('tmTitle').textContent = 'Add Team';
  document.getElementById('tmA').value = 'add';
  document.getElementById('tmId').value = '';
  document.getElementById('tmN').value = '';
  document.getElementById('tmS').value = '';
  document.getElementById('tmC').value = '';
  document.getElementById('tmCi').value = '';
  document.getElementById('tmCo').value = '';
  document.getElementById('tmCol').value = '#555555';
  const preview = document.getElementById('logoPreview');
  preview.src = ''; preview.style.display = 'none'; preview.dataset.existing = '';
  document.getElementById('logoPlaceholder').style.display = '';
  document.getElementById('removeLogo').style.display = 'none';
  document.getElementById('logoInput').value = '';
}

function loadEdit(t) {
  document.getElementById('tmTitle').textContent = 'Edit Team';
  document.getElementById('tmA').value = 'edit';
  document.getElementById('tmId').value = t.id;
  document.getElementById('tmN').value = t.name;
  document.getElementById('tmS').value = t.short_name;
  document.getElementById('tmC').value = t.home_court || '';
  document.getElementById('tmCi').value = t.city || '';
  document.getElementById('tmCo').value = t.coach || '';
  document.getElementById('tmCol').value = t.color_primary || '#555555';
  document.getElementById('logoInput').value = '';

  const preview = document.getElementById('logoPreview');
  const placeholder = document.getElementById('logoPlaceholder');
  const rmWrap = document.getElementById('removeLogo');
  const rm = document.getElementById('rmLogo');
  if (t.logo) {
    preview.src = LOGO_URL + t.logo;
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
