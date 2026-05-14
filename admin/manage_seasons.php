<?php
require_once __DIR__.'/../includes/config.php';
requireRole('Admin'); $currentPage='manage_seasons'; $pageTitle='Seasons'; $db=getDB(); $ok=$err='';

const CATEGORIES = ['League A Men','League A Women','League B Men','League B Women'];

if($_SERVER['REQUEST_METHOD']==='POST'){
  $a=$_POST['action']??'';
  if(in_array($a,['add','edit'])){
    $sid=(int)($_POST['sid']??0);$name=trim($_POST['name']??'');$type=$_POST['type']??'League';
    $cat=in_array($_POST['category']??'',CATEGORIES)?$_POST['category']:null;
    $sd=$_POST['start_date']??'';$ed=$_POST['end_date']?:null;$status=$_POST['status']??'Upcoming';
    if(!$name||!$sd){$err='Name and start date required.';}
    else{
      if($a==='add'){$db->prepare("INSERT INTO seasons(name,type,category,start_date,end_date,status)VALUES(?,?,?,?,?,?)")->execute([$name,$type,$cat,$sd,$ed,$status]);$ok="Season \"$name\" added.";logActivity("Added season: $name");}
      else{$db->prepare("UPDATE seasons SET name=?,type=?,category=?,start_date=?,end_date=?,status=? WHERE id=?")->execute([$name,$type,$cat,$sd,$ed,$status,$sid]);$ok="Season \"$name\" updated.";logActivity("Updated season: $name");}
    }
  }
}
$seasons=$db->query("SELECT s.*,(SELECT COUNT(*) FROM matches m WHERE m.season_id=s.id) mc,(SELECT COUNT(*) FROM league_standings ls WHERE ls.season_id=s.id) tc FROM seasons s ORDER BY s.category,s.start_date DESC")->fetchAll();
include __DIR__.'/../includes/header.php';
?>
<div class="content">
  <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px;margin-bottom:20px">
    <h1 style="font-size:18px;font-weight:600">Seasons</h1>
    <div style="display:flex;gap:6px">
      <a href="<?=APP_URL?>/admin/export_pdf.php?type=seasons" class="btn btn-ghost btn-sm" target="_blank"><i class="bi bi-file-earmark-pdf"></i>Export PDF</a>
      <button class="btn btn-pri btn-sm" data-bs-toggle="modal" data-bs-target="#sm"><i class="bi bi-plus"></i>Add Season</button>
    </div>
  </div>
  <?php if($ok): ?><div class="alert a-ok"><i class="bi bi-check-circle-fill"></i><?= htmlspecialchars($ok) ?></div><?php endif; ?>
  <?php if($err): ?><div class="alert a-err"><i class="bi bi-exclamation-circle-fill"></i><?= htmlspecialchars($err) ?></div><?php endif; ?>
  <div class="card">
    <div class="tbl-wrap">
      <table class="tbl">
        <thead><tr><th>Season</th><th>Category</th><th>Type</th><th>Start</th><th>End</th><th>Status</th><th>Teams</th><th>Matches</th><th></th></tr></thead>
        <tbody>
          <?php foreach($seasons as $s): ?>
          <tr>
            <td><div style="font-size:13px;font-weight:600"><?= htmlspecialchars($s['name']) ?></div></td>
            <td><?php if($s['category']): ?><span class="badge b-sched" style="font-size:10px"><?= htmlspecialchars($s['category']) ?></span><?php else: ?><span style="color:var(--t3);font-size:12px">—</span><?php endif; ?></td>
            <td style="font-size:12px;color:var(--t2)"><?= $s['type'] ?></td>
            <td style="font-size:12px;color:var(--t2)"><?= date('d M Y',strtotime($s['start_date'])) ?></td>
            <td style="font-size:12px;color:var(--t2)"><?= $s['end_date']?date('d M Y',strtotime($s['end_date'])):'—' ?></td>
            <td><span class="badge <?= $s['status']==='Active'?'b-live':($s['status']==='Completed'?'b-done':'b-sched') ?>" style="font-size:10px"><?= $s['status'] ?></span></td>
            <td style="font-size:12px;color:var(--t2)"><?= $s['tc'] ?></td>
            <td style="font-size:12px;color:var(--t2)"><?= $s['mc'] ?></td>
            <td><button class="btn btn-ghost btn-sm btn-icon" onclick='loadEditS(<?= htmlspecialchars(json_encode($s)) ?>)' data-bs-toggle="modal" data-bs-target="#sm"><i class="bi bi-pencil" style="font-size:12px"></i></button></td>
          </tr>
          <?php endforeach; ?>
          <?php if(empty($seasons)): ?><tr><td colspan="9" style="text-align:center;padding:30px;color:var(--t3)">No seasons yet</td></tr><?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<div class="modal fade" id="sm" tabindex="-1"><div class="modal-dialog modal-dialog-centered"><div class="modal-content"><div class="modal-header"><h5 class="modal-title" id="smTitle">Add Season</h5><button class="btn-close" data-bs-dismiss="modal"></button></div>
<form method="POST"><div class="modal-body" style="display:flex;flex-direction:column;gap:12px">
  <input type="hidden" name="action" id="smA" value="add"><input type="hidden" name="sid" id="smId" value="">
  <div class="field"><label class="label">Season Name *</label><input type="text" name="name" id="smN" class="input" required placeholder="Malawi Volleyball League 2026 — League A Men"></div>
  <div class="field"><label class="label">Category</label>
    <select name="category" id="smCat" class="select">
      <option value="">— No category —</option>
      <?php foreach(CATEGORIES as $c): ?><option value="<?= $c ?>"><?= $c ?></option><?php endforeach; ?>
    </select>
  </div>
  <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px">
    <div class="field"><label class="label">Type</label><select name="type" id="smT" class="select"><option value="League">League</option><option value="Cup">Cup</option><option value="Playoff">Playoff</option><option value="Friendly">Friendly</option></select></div>
    <div class="field"><label class="label">Status</label><select name="status" id="smSt" class="select"><option value="Upcoming">Upcoming</option><option value="Active">Active</option><option value="Completed">Completed</option></select></div>
  </div>
  <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px">
    <div class="field"><label class="label">Start Date *</label><input type="date" name="start_date" id="smSd" class="input" required></div>
    <div class="field"><label class="label">End Date</label><input type="date" name="end_date" id="smEd" class="input"></div>
  </div>
</div><div class="modal-footer"><button type="button" class="btn btn-ghost btn-sm" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-pri btn-sm">Save</button></div></form></div></div></div>

<script>
function loadEditS(s){
  document.getElementById('smTitle').textContent='Edit Season';
  document.getElementById('smA').value='edit';
  document.getElementById('smId').value=s.id;
  document.getElementById('smN').value=s.name;
  document.getElementById('smCat').value=s.category||'';
  document.getElementById('smT').value=s.type;
  document.getElementById('smSt').value=s.status;
  document.getElementById('smSd').value=s.start_date;
  document.getElementById('smEd').value=s.end_date||'';
}
document.getElementById('sm').addEventListener('hidden.bs.modal',()=>{
  document.getElementById('smTitle').textContent='Add Season';
  document.getElementById('smA').value='add';
  document.getElementById('smId').value='';
  document.querySelector('#sm form').reset();
});
</script>
<?php include __DIR__.'/../includes/footer.php'; ?>
