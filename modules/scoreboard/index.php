<?php
require_once __DIR__.'/../../includes/config.php';
$currentPage='scoreboard'; $pageTitle='Scoreboard'; $db=getDB();
$mid=(int)($_GET['match']??0);
$all=$db->query("SELECT m.*,ht.name hn,ht.short_name hs,ht.color_primary hc,at.name an,at.short_name as_,at.color_primary ac FROM matches m JOIN teams ht ON ht.id=m.home_team_id JOIN teams at ON at.id=m.away_team_id WHERE m.status IN('Live','Completed','Scheduled') ORDER BY FIELD(m.status,'Live','Scheduled','Completed'),m.match_date DESC LIMIT 20")->fetchAll();
if(!$mid){foreach($all as $m){if($m['status']==='Live'){$mid=$m['id'];break;}}if(!$mid&&!empty($all))$mid=$all[0]['id'];}
$sel=null;$acts=[];
if($mid){
  $s=$db->prepare("SELECT m.*,ht.name hn,ht.short_name hs,ht.color_primary hc,at.name an,at.short_name as_,at.color_primary ac FROM matches m JOIN teams ht ON ht.id=m.home_team_id JOIN teams at ON at.id=m.away_team_id WHERE m.id=?");
  $s->execute([$mid]);$sel=$s->fetch();
  $as=$db->prepare("SELECT pa.*,CONCAT(p.first_name,' ',p.last_name) pn,p.jersey_number jn,t.name tn,t.color_primary tc FROM player_actions pa JOIN players p ON p.id=pa.player_id JOIN teams t ON t.id=pa.team_id WHERE pa.match_id=? ORDER BY pa.recorded_at DESC LIMIT 30");
  $as->execute([$mid]);$acts=$as->fetchAll();
}
$aIcons=['Ace'=>'bi-star-fill','Attack Kill'=>'bi-lightning-fill','Block'=>'bi-shield-fill','Dig'=>'bi-arrow-down-circle-fill','Set Assist'=>'bi-arrow-up-right-circle-fill','Service Error'=>'bi-x-circle-fill','Attack Error'=>'bi-x-circle-fill','Reception'=>'bi-hand-thumbs-up-fill'];
include __DIR__.'/../../includes/header.php';
?>
<style>
.sb-layout{display:grid;grid-template-columns:200px 1fr 260px;height:calc(100vh - var(--th));overflow:hidden}
.sb-list{border-right:1px solid var(--b0);overflow-y:auto;background:var(--s1)}
.sb-main{overflow-y:auto;background:var(--bg)}
.sb-feed{border-left:1px solid var(--b0);overflow-y:auto;display:flex;flex-direction:column;background:var(--s1)}
.sb-item{padding:10px 14px;border-bottom:1px solid var(--b0);text-decoration:none;display:block;transition:background var(--tr);color:var(--t1)}
.sb-item:hover{background:var(--s2)}
.sb-item.sel{background:var(--s3);border-left:2px solid var(--acc)}
@media(max-width:900px){.sb-layout{grid-template-columns:1fr;height:auto}.sb-list,.sb-feed{height:150px}}
</style>
<div class="sb-layout">
  <div class="sb-list">
    <div style="padding:9px 14px 6px;font-size:11px;font-weight:600;color:var(--t3);border-bottom:1px solid var(--b0);text-transform:uppercase;letter-spacing:.04em;position:sticky;top:0;background:var(--s1);z-index:2">Matches</div>
    <?php foreach($all as $m): $bc=$m['status']==='Live'?'var(--red)':($m['status']==='Completed'?'var(--grn)':'var(--pri)'); ?>
    <a href="?match=<?= $m['id'] ?>" class="sb-item <?= $m['id']==$mid?'sel':'' ?>">
      <div style="display:flex;gap:5px;align-items:center;margin-bottom:4px"><span style="font-size:10px;font-weight:700;color:<?= $bc ?>;text-transform:uppercase"><?= $m['status'] ?></span><span style="font-size:11px;color:var(--t3)"><?= htmlspecialchars($m['round']??'') ?></span></div>
      <div style="font-size:12px;font-weight:600;display:flex;align-items:center;gap:4px"><div class="team-dot" style="background:<?= $m['hc'] ?>"></div><?= htmlspecialchars($m['hs']) ?></div>
      <div style="font-size:16px;font-weight:700;color:var(--t1);margin:.1rem 0;line-height:1"><?= $m['home_sets_won'] ?><span style="color:var(--t3);font-weight:400;font-size:13px">:</span><?= $m['away_sets_won'] ?></div>
      <div style="font-size:12px;font-weight:600;display:flex;align-items:center;gap:4px"><div class="team-dot" style="background:<?= $m['ac'] ?>"></div><?= htmlspecialchars($m['as_']) ?></div>
      <div style="font-size:11px;color:var(--t3);margin-top:3px"><?= date('d M, H:i',strtotime($m['match_date'])) ?></div>
    </a>
    <?php endforeach; ?>
  </div>

  <div class="sb-main">
    <?php if(!$sel): ?>
    <div style="display:flex;flex-direction:column;align-items:center;justify-content:center;height:100%;gap:8px;text-align:center;color:var(--t3);padding:2rem"><i class="bi bi-display" style="font-size:40px;opacity:.25"></i><div style="font-size:14px;color:var(--t2)">Select a match to view scoreboard</div></div>
    <?php else:
      $sets=json_decode($sel['set_scores']??'[]',true);
      $isLive=$sel['status']==='Live'; $isDone=$sel['status']==='Completed'; $hw=$sel['home_sets_won']>$sel['away_sets_won'];
      $bc=$isLive?'b-live':($isDone?'b-done':'b-sched');
    ?>
    <div style="padding:24px 28px">
      <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:8px;margin-bottom:20px">
        <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap">
          <span class="badge <?= $bc ?>"><?= $sel['status'] ?></span>
          <span style="font-size:12px;color:var(--t3)"><?= htmlspecialchars($sel['round']??'') ?></span>
          <span style="font-size:12px;color:var(--t3)"><i class="bi bi-calendar3 me-1"></i><?= date('D d M Y H:i',strtotime($sel['match_date'])) ?></span>
          <?php if($sel['venue']): ?><span style="font-size:12px;color:var(--t3)"><i class="bi bi-geo-alt me-1"></i><?= htmlspecialchars($sel['venue']) ?></span><?php endif; ?>
        </div>
        <?php if(isLoggedIn()): ?><a href="<?= APP_URL ?>/admin/score_entry.php?match=<?= $mid ?>" class="btn btn-pri btn-sm"><i class="bi bi-pencil-square"></i>Score Entry</a><?php endif; ?>
      </div>

      <div style="background:var(--s1);border:1px solid <?= $isLive?'rgba(248,81,73,.3)':'var(--b0)' ?>;border-radius:var(--r);padding:28px 24px">
        <div style="display:grid;grid-template-columns:1fr auto 1fr;align-items:center;gap:16px">
          <div style="text-align:center">
            <div style="width:48px;height:48px;border-radius:8px;background:<?= $sel['hc'] ?>;display:flex;align-items:center;justify-content:center;font-size:13px;font-weight:700;color:#fff;margin:0 auto 12px"><?= substr($sel['hs'],0,3) ?></div>
            <div style="font-size:15px;font-weight:600;margin-bottom:8px"><?= htmlspecialchars($sel['hn']) ?></div>
            <div style="display:flex;justify-content:center;gap:5px;margin-bottom:10px"><?php for($i=0;$i<3;$i++): ?><div class="set-dot <?= $i<$sel['home_sets_won']?'on':'' ?>"></div><?php endfor; ?></div>
            <div id="sbH" style="font-size:64px;font-weight:700;letter-spacing:-.04em;line-height:1;color:<?= $hw?'var(--acc)':'var(--t2)' ?>"><?= $sel['home_sets_won'] ?></div>
          </div>
          <div style="text-align:center;min-width:100px">
            <div style="font-size:18px;font-weight:400;color:var(--t3);line-height:1">:</div>
            <div style="font-size:11px;color:var(--t3);text-transform:uppercase;letter-spacing:.06em;margin:4px 0">sets</div>
            <?php if($isLive): ?>
            <div style="background:var(--s2);border:1px solid var(--b0);border-radius:var(--r);padding:8px 10px;margin-top:10px">
              <div style="font-size:10px;color:var(--t3);text-transform:uppercase;letter-spacing:.06em">Set <?= $sel['current_set'] ?></div>
              <div style="font-size:20px;font-weight:700;color:var(--gld)" id="sbCur"><?= $sel['home_score_current_set'] ?>–<?= $sel['away_score_current_set'] ?></div>
            </div>
            <?php endif; ?>
            <?php if(!empty($sets)): ?>
            <div style="display:flex;flex-direction:column;gap:3px;margin-top:12px">
              <?php foreach($sets as $i=>$sv): $shw=$sv['home']>$sv['away']; ?>
              <span class="set-tag <?= $shw?'hw':'aw' ?>" style="display:block;text-align:center">S<?= $i+1 ?>: <?= $sv['home'] ?>–<?= $sv['away'] ?></span>
              <?php endforeach; ?>
            </div>
            <?php endif; ?>
          </div>
          <div style="text-align:center">
            <div style="width:48px;height:48px;border-radius:8px;background:<?= $sel['ac'] ?>;display:flex;align-items:center;justify-content:center;font-size:13px;font-weight:700;color:#fff;margin:0 auto 12px"><?= substr($sel['as_'],0,3) ?></div>
            <div style="font-size:15px;font-weight:600;margin-bottom:8px"><?= htmlspecialchars($sel['an']) ?></div>
            <div style="display:flex;justify-content:center;gap:5px;margin-bottom:10px"><?php for($i=0;$i<3;$i++): ?><div class="set-dot <?= $i<$sel['away_sets_won']?'on':'' ?>"></div><?php endfor; ?></div>
            <div id="sbA" style="font-size:64px;font-weight:700;letter-spacing:-.04em;line-height:1;color:<?= !$hw&&$isDone?'var(--acc)':'var(--t2)' ?>"><?= $sel['away_sets_won'] ?></div>
          </div>
        </div>
      </div>
      <?php if($isLive): ?><div style="text-align:center;font-size:12px;color:var(--t3);margin-top:10px"><i class="bi bi-clock me-1"></i><span class="live-clock"></span> · Auto-updates every 5s</div><?php endif; ?>
    </div>
    <?php endif; ?>
  </div>

  <div class="sb-feed">
    <div style="padding:9px 14px;border-bottom:1px solid var(--b0);font-size:12px;font-weight:600;color:var(--t2);position:sticky;top:0;background:var(--s1);z-index:2">Action Log</div>
    <div style="flex:1;overflow-y:auto">
      <?php if(empty($acts)): ?><div style="padding:20px;text-align:center;color:var(--t3);font-size:12px"><i class="bi bi-clipboard" style="font-size:28px;display:block;margin-bottom:6px;opacity:.3"></i>No actions yet</div>
      <?php else: foreach($acts as $a): $rc=$a['action_result']==='Success'?'fd-ok':($a['action_result']==='Error'?'fd-err':'fd-neu');$ic=$aIcons[$a['action_type']]??'bi-dot'; ?>
      <div class="feed-item">
        <div class="fdot <?= $rc ?>"><i class="bi <?= $ic ?>"></i></div>
        <div style="flex:1;min-width:0"><div style="font-size:12px;font-weight:600;white-space:nowrap;overflow:hidden;text-overflow:ellipsis"><span style="color:<?= $a['tc'] ?>">#<?= $a['jn'] ?></span> <?= htmlspecialchars($a['pn']) ?></div><div style="font-size:11px;color:var(--t3)"><?= $a['action_type'] ?> · S<?= $a['set_number'] ?> · <?= date('H:i:s',strtotime($a['recorded_at'])) ?></div></div>
        <span style="font-size:11px;font-weight:600;color:var(--t2);flex-shrink:0"><?= $a['home_score_after'] ?>–<?= $a['away_score_after'] ?></span>
      </div>
      <?php endforeach; endif; ?>
    </div>
  </div>
</div>
<?php if(($isLive??false)&&$mid): ?>
<script>
setInterval(async()=>{try{const r=await fetch('<?= APP_URL ?>/api/match_score.php?match_id=<?= $mid ?>');const d=await r.json();if(d.error)return;const sh=document.getElementById('sbH'),sa=document.getElementById('sbA'),sc=document.getElementById('sbCur');if(sh)sh.textContent=d.home_sets_won;if(sa)sa.textContent=d.away_sets_won;if(sc)sc.textContent=`${d.home_score_current_set}–${d.away_score_current_set}`;}catch(e){}},5000);
</script>
<?php endif; ?>
<?php include __DIR__.'/../../includes/footer.php'; ?>
