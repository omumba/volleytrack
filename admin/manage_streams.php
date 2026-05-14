<?php
require_once __DIR__.'/../includes/config.php';
requireRole('Referee'); $currentPage='manage_streams'; $pageTitle='Streams'; $db=getDB(); $ok=$err='';
if($_SERVER['REQUEST_METHOD']==='POST'){
  $a=$_POST['action']??'';
  if(in_array($a,['add_cam','edit_cam'])){
    $cid=(int)($_POST['cam_id']??0);$mid=(int)$_POST['match_id'];$lbl=trim($_POST['label']??'');$prov=$_POST['provider']??'RTMP_HLS';$hls=trim($_POST['hls_url']??'')?:null;$emb=trim($_POST['embed_url']??'')?:null;$prim=isset($_POST['is_primary'])?1:0;$key=trim($_POST['stream_key']??'')?:('vt-m'.$mid.'-'.substr(bin2hex(random_bytes(4)),0,8));
    if(!$mid||!$lbl){$err='Match and label required.';}
    else{
      if($prim)$db->prepare("UPDATE stream_cameras SET is_primary=0 WHERE match_id=?")->execute([$mid]);
      if($a==='add_cam'){
        if(!$hls&&$prov==='RTMP_HLS')$hls='http://YOUR_SERVER/hls/'.$key.'.m3u8';
        $db->prepare("INSERT INTO stream_cameras(match_id,label,stream_key,hls_url,embed_url,provider,is_primary)VALUES(?,?,?,?,?,?,?)")->execute([$mid,$lbl,$key,$hls,$emb,$prov,$prim]);
        $ok='Camera added. Key: '.$key;
      }else{
        $db->prepare("UPDATE stream_cameras SET label=?,provider=?,hls_url=?,embed_url=?,stream_key=?,is_primary=? WHERE id=?")->execute([$lbl,$prov,$hls,$emb,$key,$prim,$cid]);
        $ok='Camera updated.';
      }
    }
  }elseif($a==='set_status'){
    $cid=(int)$_POST['cam_id'];$stat=$_POST['status']??'Offline';
    $db->prepare("UPDATE stream_cameras SET status=?,started_at=IF(?='Live',COALESCE(started_at,NOW()),started_at),ended_at=IF(?='Ended',NOW(),ended_at) WHERE id=?")->execute([$stat,$stat,$stat,$cid]);
    if($stat==='Live'){$m=$db->query("SELECT match_id FROM stream_cameras WHERE id=$cid")->fetchColumn();$db->prepare("UPDATE matches SET status='Live',started_at=COALESCE(started_at,NOW()) WHERE id=? AND status='Scheduled'")->execute([$m]);}
    $ok='Status updated.';
  }elseif($a==='del_cam'){
    $cid=(int)$_POST['cam_id'];
    $db->prepare("DELETE FROM stream_cameras WHERE id=?")->execute([$cid]);
    $ok='Camera removed.';
  }
}
$mf=(int)($_GET['match']??0);
$allMatches=$db->query("SELECT m.id,m.status,m.round,m.match_date,ht.name hn,at.name an,(SELECT COUNT(*) FROM stream_cameras sc WHERE sc.match_id=m.id) cc,(SELECT COUNT(*) FROM stream_cameras sc WHERE sc.match_id=m.id AND sc.status='Live') lc FROM matches m JOIN teams ht ON ht.id=m.home_team_id JOIN teams at ON at.id=m.away_team_id ORDER BY FIELD(m.status,'Live','Scheduled','Completed'),m.match_date DESC LIMIT 20")->fetchAll();
$cameras=[];$selMatch=null;
if($mf){
  $s=$db->prepare("SELECT m.*,ht.name hn,at.name an FROM matches m JOIN teams ht ON ht.id=m.home_team_id JOIN teams at ON at.id=m.away_team_id WHERE m.id=?");$s->execute([$mf]);$selMatch=$s->fetch();
  $cs=$db->prepare("SELECT * FROM stream_cameras WHERE match_id=? ORDER BY is_primary DESC,id ASC");$cs->execute([$mf]);$cameras=$cs->fetchAll();
}
include __DIR__.'/../includes/header.php';
?>
<div class="content">
  <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px;margin-bottom:20px">
    <h1 style="font-size:18px;font-weight:600">Live Streams</h1>
    <a href="<?=APP_URL?>/modules/streaming/index.php" target="_blank" class="btn btn-ghost btn-sm"><i class="bi bi-play-circle"></i>View Stream Page</a>
  </div>
  <?php if($ok):?><div class="alert a-ok"><i class="bi bi-check-circle-fill"></i><?=htmlspecialchars($ok)?></div><?php endif;?>
  <?php if($err):?><div class="alert a-err"><i class="bi bi-exclamation-circle-fill"></i><?=htmlspecialchars($err)?></div><?php endif;?>
  <div style="display:grid;grid-template-columns:240px 1fr;gap:16px">
    <div>
      <div style="font-size:11px;font-weight:600;color:var(--t3);text-transform:uppercase;letter-spacing:.04em;margin-bottom:8px">Select Match</div>
      <div class="card">
        <?php foreach($allMatches as $m):?>
        <a href="?match=<?=$m['id']?>" style="padding:10px 14px;border-bottom:1px solid var(--b0);text-decoration:none;display:block;transition:background var(--tr);<?=$m['id']==$mf?'background:var(--s3);border-left:2px solid var(--acc)':''?>" onmouseover="if(<?=$m['id']?>!=<?=$mf?>)this.style.background='var(--s2)'" onmouseout="if(<?=$m['id']?>!=<?=$mf?>)this.style.background=''">
          <div style="font-size:11px;color:var(--t3);margin-bottom:3px"><?=htmlspecialchars($m['round']??'')?> · <?=date('d M',strtotime($m['match_date']))?></div>
          <div style="font-size:13px;font-weight:600;color:var(--t1)"><?=htmlspecialchars($m['hn'])?> vs <?=htmlspecialchars($m['an'])?></div>
          <div style="display:flex;gap:8px;margin-top:3px;font-size:11px;color:var(--t3)"><span><i class="bi bi-camera-video me-1"></i><?=$m['cc']?> cameras</span><?php if($m['lc']>0):?><span style="color:var(--red)"><i class="bi bi-circle-fill" style="font-size:6px;vertical-align:middle"></i> <?=$m['lc']?> live</span><?php endif;?></span></div>
        </a>
        <?php endforeach;?>
      </div>
    </div>
    <div>
      <?php if(!$selMatch):?>
      <div class="card" style="padding:40px;text-align:center;color:var(--t3)"><i class="bi bi-broadcast" style="font-size:32px;display:block;margin-bottom:8px;opacity:.3"></i><div style="font-size:14px;color:var(--t2)">Select a match to manage cameras</div></div>
      <?php else:?>
      <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:8px;margin-bottom:12px">
        <div><div style="font-size:15px;font-weight:600"><?=htmlspecialchars($selMatch['hn'])?> vs <?=htmlspecialchars($selMatch['an'])?></div><div style="font-size:12px;color:var(--t3)"><?=date('D d M Y H:i',strtotime($selMatch['match_date']))?></div></div>
        <button class="btn btn-pri btn-sm" data-bs-toggle="modal" data-bs-target="#camModal"><i class="bi bi-plus"></i>Add Camera</button>
      </div>
      <?php if(empty($cameras)):?>
      <div class="card" style="padding:30px;text-align:center;color:var(--t3)"><i class="bi bi-camera-video-off" style="font-size:28px;display:block;margin-bottom:8px;opacity:.3"></i><div style="font-size:13px">No cameras configured</div><button class="btn btn-def btn-sm mt-3" data-bs-toggle="modal" data-bs-target="#camModal"><i class="bi bi-plus"></i>Add Camera</button></div>
      <?php else:?>
      <div style="display:flex;flex-direction:column;gap:8px">
        <?php foreach($cameras as $cam):?>
        <div class="card" style="<?=$cam['status']==='Live'?'border-color:rgba(248,81,73,.3)':''?>">
          <div style="padding:12px 14px">
            <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;margin-bottom:10px">
              <i class="bi bi-camera-video<?=$cam['is_primary']?'-fill':''?>" style="color:<?=$cam['status']==='Live'?'var(--red)':'var(--t3)'?>;font-size:16px"></i>
              <div style="flex:1;min-width:0"><div style="font-size:14px;font-weight:600"><?=htmlspecialchars($cam['label'])?><?php if($cam['is_primary']):?> <span class="badge b-sched" style="font-size:10px">primary</span><?php endif;?></div><div style="font-size:11px;color:var(--t3)"><?=$cam['provider']?><?php if($cam['viewer_count']):?> · <?=$cam['viewer_count']?> viewers<?php endif;?><?php if($cam['resolution']):?> · <?=$cam['resolution']?><?php endif;?></div></div>
              <span class="badge <?=$cam['status']==='Live'?'b-live':($cam['status']==='Ended'?'b-done':'b-sched')?>" style="font-size:10px"><?=$cam['status']?></span>
            </div>
            <div style="background:var(--s2);border:1px solid var(--b0);border-radius:var(--r);padding:8px 10px;margin-bottom:10px;font-size:12px">
              <div style="color:var(--t3);margin-bottom:3px">Stream Key</div>
              <div style="font-family:monospace;color:var(--acc);word-break:break-all"><?=htmlspecialchars($cam['stream_key'])?></div>
              <?php if($cam['hls_url']):?><div style="color:var(--t3);margin-top:6px;margin-bottom:3px">HLS URL</div><div style="font-family:monospace;color:var(--grn);word-break:break-all;font-size:11px"><?=htmlspecialchars($cam['hls_url'])?></div><?php endif;?>
            </div>
            <div style="display:flex;gap:6px;flex-wrap:wrap">
              <?php foreach(['Offline','Live','Ended'] as $st):?>
              <form method="POST" style="display:inline"><input type="hidden" name="action" value="set_status"><input type="hidden" name="cam_id" value="<?=$cam['id']?>"><input type="hidden" name="status" value="<?=$st?>"><button type="submit" class="btn <?=$cam['status']===$st?'btn-pri':'btn-ghost'?> btn-sm"><?=$st?></button></form>
              <?php endforeach;?>
              <button class="btn btn-ghost btn-sm btn-icon" onclick='loadEditCam(<?=htmlspecialchars(json_encode($cam))?>)' data-bs-toggle="modal" data-bs-target="#camModal"><i class="bi bi-pencil" style="font-size:12px"></i></button>
              <form method="POST" style="display:inline" onsubmit="return confirm('Delete camera?')"><input type="hidden" name="action" value="del_cam"><input type="hidden" name="cam_id" value="<?=$cam['id']?>"><button type="submit" class="btn btn-danger btn-sm btn-icon"><i class="bi bi-trash" style="font-size:12px"></i></button></form>
            </div>
          </div>
        </div>
        <?php endforeach;?>
      </div>
      <?php endif;?>
      <?php endif;?>
    </div>
  </div>
</div>

<div class="modal fade" id="camModal" tabindex="-1"><div class="modal-dialog modal-dialog-centered"><div class="modal-content"><div class="modal-header"><h5 class="modal-title" id="cTitle">Add Camera</h5><button class="btn-close" data-bs-dismiss="modal"></button></div>
<form method="POST"><div class="modal-body" style="display:flex;flex-direction:column;gap:12px">
  <input type="hidden" name="action" id="cAction" value="add_cam"><input type="hidden" name="cam_id" id="cId" value=""><input type="hidden" name="match_id" value="<?=$mf?>">
  <div style="display:grid;grid-template-columns:2fr 1fr;gap:10px">
    <div class="field"><label class="label">Camera Label</label><input type="text" name="label" id="cLabel" class="input" required placeholder="Main Court Camera"></div>
    <div class="field"><label class="label">Provider</label><select name="provider" id="cProv" class="select"><option value="RTMP_HLS">RTMP/HLS</option><option value="YouTube">YouTube</option><option value="Facebook">Facebook</option><option value="Custom">Custom</option></select></div>
  </div>
  <div class="field"><label class="label">Stream Key</label><input type="text" name="stream_key" id="cKey" class="input" placeholder="Auto-generated if blank"></div>
  <div class="field"><label class="label">HLS Playback URL</label><input type="url" name="hls_url" id="cHls" class="input" placeholder="http://YOUR_SERVER/hls/KEY.m3u8"></div>
  <div class="field"><label class="label">Embed URL (YouTube/Facebook)</label><input type="url" name="embed_url" id="cEmb" class="input" placeholder="https://www.youtube.com/embed/LIVE_ID"></div>
  <label style="display:flex;align-items:center;gap:8px;cursor:pointer"><input type="checkbox" name="is_primary" id="cPrim" value="1" style="accent-color:var(--pri)"><span style="font-size:13px">Primary camera (shown first)</span></label>
</div>
<div class="modal-footer"><button type="button" class="btn btn-ghost btn-sm" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-pri btn-sm">Save</button></div>
</form></div></div></div>
<script>
function loadEditCam(c){document.getElementById('cTitle').textContent='Edit Camera';document.getElementById('cAction').value='edit_cam';document.getElementById('cId').value=c.id;document.getElementById('cLabel').value=c.label;document.getElementById('cProv').value=c.provider;document.getElementById('cKey').value=c.stream_key;document.getElementById('cHls').value=c.hls_url||'';document.getElementById('cEmb').value=c.embed_url||'';document.getElementById('cPrim').checked=c.is_primary==1;}
document.getElementById('camModal').addEventListener('hidden.bs.modal',()=>{document.getElementById('cTitle').textContent='Add Camera';document.getElementById('cAction').value='add_cam';document.getElementById('cId').value='';document.querySelector('#camModal form').reset();});
</script>
<?php include __DIR__.'/../includes/footer.php';?>
