<?php
require_once __DIR__.'/../includes/config.php';
requireRole('Referee'); $currentPage='manage_match'; $pageTitle='Fixtures'; $db=getDB(); $ok=$err='';
if($_SERVER['REQUEST_METHOD']==='POST'){
  $a=$_POST['action']??'';
  if(in_array($a,['add','edit'])){
    $mid=(int)($_POST['match_id']??0);$sid=(int)$_POST['season_id'];$ht=(int)$_POST['home_team_id'];$at=(int)$_POST['away_team_id'];$md=$_POST['match_date']??'';$venue=trim($_POST['venue']??'');$round=trim($_POST['round']??'');$status=$_POST['status']??'Scheduled';
    if(!$sid||!$ht||!$at||!$md){$err='Season, teams and date required.';}
    elseif($ht===$at){$err='Home and away teams must differ.';}
    else{
      if($a==='add'){
        $db->prepare("INSERT INTO matches(season_id,home_team_id,away_team_id,match_date,venue,round,status)VALUES(?,?,?,?,?,?,?)")->execute([$sid,$ht,$at,$md,$venue,$round,$status]);
        foreach([$ht,$at] as $tid) $db->prepare("INSERT IGNORE INTO league_standings(season_id,team_id)VALUES(?,?)")->execute([$sid,$tid]);
        $ok='Fixture added.'; logActivity('Added match','match',(int)$db->lastInsertId());
      }else{
        $db->prepare("UPDATE matches SET season_id=?,home_team_id=?,away_team_id=?,match_date=?,venue=?,round=?,status=? WHERE id=?")->execute([$sid,$ht,$at,$md,$venue,$round,$status,$mid]);
        $ok='Fixture updated.'; logActivity('Updated match','match',$mid);
      }
    }
  }elseif($a==='delete'){
    $mid=(int)$_POST['match_id'];
    $db->prepare("DELETE FROM matches WHERE id=? AND status='Scheduled'")->execute([$mid]);
    $ok='Fixture deleted.';
  }
}
$sf=(int)($_GET['season']??1);
$seasons=$db->query("SELECT id,name FROM seasons ORDER BY start_date DESC")->fetchAll();
$teams=$db->query("SELECT id,name,short_name FROM teams WHERE is_active=1 ORDER BY name")->fetchAll();
$matches=$db->prepare("SELECT m.*,ht.name hn,at.name an,s.name sn FROM matches m JOIN teams ht ON ht.id=m.home_team_id JOIN teams at ON at.id=m.away_team_id JOIN seasons s ON s.id=m.season_id WHERE m.season_id=? ORDER BY m.match_date");
$matches->execute([$sf]);$matches=$matches->fetchAll();
include __DIR__.'/../includes/header.php';
?>
<div class="content">
  <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px;margin-bottom:20px">
    <h1 style="font-size:18px;font-weight:600">Fixtures</h1>
    <div style="display:flex;gap:6px">
      <a href="<?=APP_URL?>/admin/export_pdf.php?type=fixtures&season=<?=$sf?>" class="btn btn-ghost btn-sm" target="_blank"><i class="bi bi-file-earmark-pdf"></i>Export PDF</a>
      <button class="btn btn-pri btn-sm" data-bs-toggle="modal" data-bs-target="#mModal"><i class="bi bi-plus"></i>Add Fixture</button>
    </div>
  </div>
  <?php if($ok):?><div class="alert a-ok"><i class="bi bi-check-circle-fill"></i><?=htmlspecialchars($ok)?></div><?php endif;?>
  <?php if($err):?><div class="alert a-err"><i class="bi bi-exclamation-circle-fill"></i><?=htmlspecialchars($err)?></div><?php endif;?>
  <div style="display:flex;gap:6px;flex-wrap:wrap;margin-bottom:14px"><?php foreach($seasons as $s):?><a href="?season=<?=$s['id']?>" class="btn <?=$s['id']==$sf?'btn-pri':'btn-ghost'?> btn-sm"><?=htmlspecialchars($s['name'])?></a><?php endforeach;?></div>
  <div class="card">
    <div class="tbl-wrap">
      <table class="tbl">
        <thead><tr><th>Date</th><th>Round</th><th>Home</th><th class="text-center">Score</th><th>Away</th><th>Status</th><th>Actions</th></tr></thead>
        <tbody>
          <?php foreach($matches as $m):?>
          <tr>
            <td style="font-size:12px"><?=date('d M Y H:i',strtotime($m['match_date']))?></td>
            <td style="font-size:12px;color:var(--t2)"><?=htmlspecialchars($m['round']??'—')?></td>
            <td style="font-size:13px;font-weight:500"><?=htmlspecialchars($m['hn'])?></td>
            <td class="text-center" style="font-size:14px;font-weight:700"><?=in_array($m['status'],['Live','Completed'])?$m['home_sets_won'].':'.$m['away_sets_won']:'—'?></td>
            <td style="font-size:13px;font-weight:500"><?=htmlspecialchars($m['an'])?></td>
            <td><span class="badge <?=$m['status']==='Live'?'b-live':($m['status']==='Completed'?'b-done':($m['status']==='Postponed'?'b-post':'b-sched'))?>" style="font-size:10px"><?=$m['status']?></span></td>
            <td>
              <div style="display:flex;gap:4px">
                <a href="<?=APP_URL?>/admin/score_entry.php?match=<?=$m['id']?>" class="btn btn-pri btn-sm btn-icon" title="Score Entry"><i class="bi bi-pencil-square" style="font-size:12px"></i></a>
                <button class="btn btn-ghost btn-sm btn-icon" onclick='loadEdit(<?=htmlspecialchars(json_encode($m))?>)' data-bs-toggle="modal" data-bs-target="#mModal" title="Edit"><i class="bi bi-gear" style="font-size:12px"></i></button>
                <?php if($m['status']==='Scheduled'):?><form method="POST" style="display:inline" onsubmit="return confirm('Delete this fixture?')"><input type="hidden" name="action" value="delete"><input type="hidden" name="match_id" value="<?=$m['id']?>"><button type="submit" class="btn btn-danger btn-sm btn-icon"><i class="bi bi-trash" style="font-size:12px"></i></button></form><?php endif;?>
              </div>
            </td>
          </tr>
          <?php endforeach;?>
          <?php if(empty($matches)):?><tr><td colspan="7" style="text-align:center;padding:30px;color:var(--t3)">No fixtures in this season</td></tr><?php endif;?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<div class="modal fade" id="mModal" tabindex="-1"><div class="modal-dialog modal-dialog-centered modal-lg"><div class="modal-content"><div class="modal-header"><h5 class="modal-title" id="mTitle">Add Fixture</h5><button class="btn-close" data-bs-dismiss="modal"></button></div>
<form method="POST"><div class="modal-body"><input type="hidden" name="action" id="mAction" value="add"><input type="hidden" name="match_id" id="mMid" value="">
<div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
  <div class="field"><label class="label">Season</label><select name="season_id" id="mSid" class="select" required><?php foreach($seasons as $s):?><option value="<?=$s['id']?>" <?=$s['id']==$sf?'selected':''?>><?=htmlspecialchars($s['name'])?></option><?php endforeach;?></select></div>
  <div class="field"><label class="label">Date &amp; Time</label><input type="datetime-local" name="match_date" id="mDate" class="input" required></div>
  <div class="field"><label class="label">Home Team</label><select name="home_team_id" id="mHt" class="select" required><option value="">Select...</option><?php foreach($teams as $t):?><option value="<?=$t['id']?>"><?=htmlspecialchars($t['name'])?></option><?php endforeach;?></select></div>
  <div class="field"><label class="label">Away Team</label><select name="away_team_id" id="mAt" class="select" required><option value="">Select...</option><?php foreach($teams as $t):?><option value="<?=$t['id']?>"><?=htmlspecialchars($t['name'])?></option><?php endforeach;?></select></div>
  <div class="field"><label class="label">Venue</label><input type="text" name="venue" id="mVenue" class="input" placeholder="Stadium name"></div>
  <div class="field"><label class="label">Round</label><input type="text" name="round" id="mRound" class="input" placeholder="Round 1"></div>
  <div class="field"><label class="label">Status</label><select name="status" id="mStatus" class="select"><option value="Scheduled">Scheduled</option><option value="Live">Live</option><option value="Completed">Completed</option><option value="Postponed">Postponed</option></select></div>
</div></div>
<div class="modal-footer"><button type="button" class="btn btn-ghost btn-sm" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-pri btn-sm">Save</button></div>
</form></div></div></div>
<script>
function loadEdit(m){document.getElementById('mTitle').textContent='Edit Fixture';document.getElementById('mAction').value='edit';document.getElementById('mMid').value=m.id;document.getElementById('mSid').value=m.season_id;document.getElementById('mHt').value=m.home_team_id;document.getElementById('mAt').value=m.away_team_id;document.getElementById('mDate').value=m.match_date?m.match_date.replace(' ','T').slice(0,16):'';document.getElementById('mVenue').value=m.venue||'';document.getElementById('mRound').value=m.round||'';document.getElementById('mStatus').value=m.status;}
document.getElementById('mModal').addEventListener('hidden.bs.modal',()=>{document.getElementById('mTitle').textContent='Add Fixture';document.getElementById('mAction').value='add';document.getElementById('mMid').value='';document.querySelector('#mModal form').reset();document.getElementById('mSid').value='<?=$sf?>';});
</script>
<?php include __DIR__.'/../includes/footer.php';?>
