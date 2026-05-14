<?php
require_once __DIR__.'/../includes/config.php';
requireRole('Scorer');
$currentPage='score_entry'; $pageTitle='Score Entry';
$db=getDB();
$matchId=(int)($_GET['match']??0);
$activeMatches=$db->query("SELECT m.id,m.status,m.current_set,m.round,m.match_date,m.venue,ht.name hn,ht.short_name hs,ht.color_primary hc,at.name an,at.short_name as_,at.color_primary ac,m.home_sets_won,m.away_sets_won,m.home_score_current_set,m.away_score_current_set FROM matches m JOIN teams ht ON ht.id=m.home_team_id JOIN teams at ON at.id=m.away_team_id WHERE m.status IN('Scheduled','Live') ORDER BY FIELD(m.status,'Live','Scheduled'),m.match_date")->fetchAll();
$match=null;$hPlayers=[];$aPlayers=[];
if($matchId){
  $s=$db->prepare("SELECT m.*,sn.name sname,ht.name hn,ht.short_name hs,ht.id hid,ht.color_primary hc,at.name an,at.short_name as_,at.id aid,at.color_primary ac FROM matches m JOIN seasons sn ON sn.id=m.season_id JOIN teams ht ON ht.id=m.home_team_id JOIN teams at ON at.id=m.away_team_id WHERE m.id=?");
  $s->execute([$matchId]);$match=$s->fetch();
  if($match){
    $p=$db->prepare("SELECT id,first_name,last_name,jersey_number,position FROM players WHERE team_id=? AND is_active=1 ORDER BY jersey_number");
    $p->execute([$match['hid']]);$hPlayers=$p->fetchAll();
    $p->execute([$match['aid']]);$aPlayers=$p->fetchAll();
  }
}
$actions=[];
if($matchId){
  $s=$db->prepare("SELECT pa.id,pa.action_type,pa.action_result,pa.set_number,pa.home_score_after,pa.away_score_after,pa.recorded_at,CONCAT(p.first_name,' ',p.last_name) pn,p.jersey_number jn,t.color_primary tc FROM player_actions pa JOIN players p ON p.id=pa.player_id JOIN teams t ON t.id=pa.team_id WHERE pa.match_id=? ORDER BY pa.recorded_at DESC LIMIT 30");
  $s->execute([$matchId]);$actions=$s->fetchAll();
}
$prevSets=json_decode($match['set_scores']??'[]',true);
$tiles=[['Ace','bi-star-fill','var(--gld)'],['Service Error','bi-x-circle-fill','var(--red)'],['Attack Kill','bi-lightning-fill','var(--acc)'],['Attack Error','bi-exclamation-circle-fill','var(--red)'],['Attack Blocked','bi-shield-fill','var(--pri)'],['Block','bi-hand-index-thumb-fill','var(--pri)'],['Block Error','bi-shield-x','var(--red)'],['Dig','bi-arrow-down-circle-fill','var(--grn)'],['Dig Error','bi-arrow-down-circle','var(--red)'],['Set Assist','bi-arrow-up-right-circle-fill','var(--pur)'],['Reception','bi-hand-thumbs-up-fill','var(--grn)'],['Reception Error','bi-hand-thumbs-down-fill','var(--red)']];
// Actions unavailable for Libero/DS (cannot block or attack above net)
$noBlockPositions=['Libero','Defensive Specialist'];
$blockActions=['Block','Block Error','Attack Kill','Attack Error','Attack Blocked'];
// Build player position map for JS
$allPlayers=array_merge($hPlayers,$aPlayers);
$playerPositions=[];
foreach($allPlayers as $p) $playerPositions[$p['id']]=$p['position'];
$aIcons=['Ace'=>'bi-star-fill','Attack Kill'=>'bi-lightning-fill','Block'=>'bi-shield-fill','Dig'=>'bi-arrow-down-circle-fill','Set Assist'=>'bi-arrow-up-right-circle-fill','Service Error'=>'bi-x-circle-fill','Attack Error'=>'bi-x-circle-fill','Reception'=>'bi-hand-thumbs-up-fill'];
include __DIR__.'/../includes/header.php';
?>
<meta name="csrf" content="<?= csrfToken() ?>">
<style>
.scorer-grid{display:grid;grid-template-columns:220px 1fr 260px;height:calc(100vh - var(--th));overflow:hidden}
.sc-list,.sc-main,.sc-feed{overflow-y:auto}.sc-list{border-right:1px solid var(--b0);background:var(--s1)}.sc-main{background:var(--bg)}.sc-feed{border-left:1px solid var(--b0);display:flex;flex-direction:column;background:var(--s1)}
.ml-item{padding:10px 14px;border-bottom:1px solid var(--b0);text-decoration:none;display:block;transition:background var(--tr);color:var(--t1)}.ml-item:hover{background:var(--s2)}.ml-item.sel{background:var(--s3);border-left:2px solid var(--acc)}
.tile-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:6px}
.psel{background:var(--s2);border:1px solid var(--b1);color:var(--t1);border-radius:var(--r);padding:6px 10px;font-size:12px;font-family:inherit;width:100%;outline:none}.psel:focus{border-color:var(--pri)}.psel option{background:var(--s2)}
@media(max-width:900px){.scorer-grid{grid-template-columns:1fr;height:auto}.sc-list,.sc-feed{height:160px}}
</style>

<div class="scorer-grid">
<div class="sc-list">
  <div style="padding:9px 14px 6px;font-size:11px;font-weight:600;color:var(--t3);border-bottom:1px solid var(--b0);text-transform:uppercase;letter-spacing:.04em;position:sticky;top:0;background:var(--s1);z-index:2">Matches</div>
  <?php foreach($activeMatches as $m):?>
  <a href="?match=<?=$m['id']?>" class="ml-item <?=$m['id']==$matchId?'sel':''?>">
    <div style="display:flex;gap:5px;align-items:center;margin-bottom:4px"><span class="badge <?=$m['status']==='Live'?'b-live':'b-sched'?>" style="font-size:10px"><?=$m['status']?></span><span style="font-size:11px;color:var(--t3)"><?=htmlspecialchars($m['round']??'')?></span></div>
    <div style="font-size:12px;font-weight:600;display:flex;align-items:center;gap:4px"><div class="team-dot" style="background:<?=$m['hc']?>"></div><?=htmlspecialchars($m['hs'])?></div>
    <div style="font-size:14px;font-weight:700;color:var(--t1);margin:.1rem 0;line-height:1"><?=$m['home_sets_won']?><span style="color:var(--t3);font-weight:400"> : </span><?=$m['away_sets_won']?><?php if($m['status']==='Live'):?> <span style="font-size:11px;color:var(--t3);font-weight:400">(<?=$m['home_score_current_set']?>-<?=$m['away_score_current_set']?>)</span><?php endif;?></div>
    <div style="font-size:12px;font-weight:600;display:flex;align-items:center;gap:4px"><div class="team-dot" style="background:<?=$m['ac']?>"></div><?=htmlspecialchars($m['as_'])?></div>
    <div style="font-size:11px;color:var(--t3);margin-top:4px"><?=date('d M, H:i',strtotime($m['match_date']))?></div>
  </a>
  <?php endforeach;?>
  <?php if(empty($activeMatches)):?><div style="padding:20px;text-align:center;color:var(--t3);font-size:12px">No active matches.<br><a href="<?=APP_URL?>/admin/manage_match.php" style="color:var(--acc)">Add fixture</a></div><?php endif;?>
</div>

<div class="sc-main">
  <?php if(!$match):?>
  <div style="display:flex;flex-direction:column;align-items:center;justify-content:center;height:100%;gap:8px;text-align:center;padding:2rem;color:var(--t3)"><i class="bi bi-cursor" style="font-size:36px;opacity:.3"></i><div style="font-size:14px;font-weight:600;color:var(--t2)">Select a match from the left panel</div></div>
  <?php else:?>
  <div style="background:var(--s1);border-bottom:1px solid var(--b0);padding:16px 18px">
    <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:8px;margin-bottom:14px">
      <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap">
        <span class="badge <?=$match['status']==='Live'?'b-live':'b-sched'?>"><?=$match['status']?></span>
        <?php if($match['status']==='Live'):?><span style="font-size:13px;font-weight:600;color:var(--gld)">Set <span id="setNum"><?=$match['current_set']?></span></span><span style="font-size:12px;color:var(--t3)"><span class="live-clock"></span></span><?php endif;?>
      </div>
      <div style="display:flex;gap:6px;flex-wrap:wrap">
        <?php if($match['status']==='Scheduled'):?><button class="btn btn-success btn-sm" onclick="doStart()"><i class="bi bi-play-fill"></i>Start</button>
        <?php elseif($match['status']==='Live'):?><button class="btn btn-def btn-sm" onclick="doEditScores()"><i class="bi bi-pencil"></i>Edit</button><button class="btn btn-def btn-sm" onclick="doEndSet()"><i class="bi bi-skip-end-fill"></i>End Set</button><button class="btn btn-danger btn-sm" onclick="doEnd()"><i class="bi bi-stop-fill"></i>End</button><?php endif;?>
        <a href="<?=APP_URL?>/modules/scoreboard/index.php?match=<?=$matchId?>" target="_blank" class="btn btn-ghost btn-sm btn-icon"><i class="bi bi-display" style="font-size:12px"></i></a>
      </div>
    </div>
    <div style="display:grid;grid-template-columns:1fr auto 1fr;align-items:center;gap:12px">
      <div style="text-align:center">
        <div style="display:flex;align-items:center;justify-content:center;gap:6px;margin-bottom:8px"><div style="width:20px;height:20px;border-radius:3px;background:<?=$match['hc']?>;display:flex;align-items:center;justify-content:center;font-size:9px;font-weight:700;color:#fff"><?=substr($match['hs'],0,3)?></div><span style="font-size:13px;font-weight:600"><?=htmlspecialchars($match['hn'])?></span></div>
        <div style="display:flex;justify-content:center;gap:4px;margin-bottom:8px"><?php for($i=0;$i<3;$i++):?><div class="set-dot <?=$i<$match['home_sets_won']?'on':''?>"></div><?php endfor;?></div>
        <div style="display:flex;align-items:center;justify-content:center;gap:8px">
          <?php if($match['status']==='Live'):?><button class="btn-sub" id="hSub" onclick="doSub('home')" <?=$match['home_score_current_set']<=0?'disabled':''?>><i class="bi bi-dash"></i></button><?php endif;?>
          <div class="score-num" id="hScore"><?=$match['home_score_current_set']?></div>
          <?php if($match['status']==='Live'):?><button class="btn-add" onclick="doAdd('home')"><i class="bi bi-plus-lg"></i></button><?php endif;?>
        </div>
        <div style="font-size:11px;color:var(--t3);margin-top:6px">Sets: <strong id="hSets"><?=$match['home_sets_won']?></strong></div>
      </div>
      <div style="text-align:center;min-width:70px">
        <div style="font-size:16px;font-weight:400;color:var(--t3)">:</div>
        <div style="font-size:10px;color:var(--t3);text-transform:uppercase;letter-spacing:.06em">sets</div>
        <?php if(!empty($prevSets)):?><div style="display:flex;flex-direction:column;gap:2px;margin-top:8px"><?php foreach($prevSets as $i=>$sv): $hw=$sv['home']>$sv['away'];?><span class="set-tag <?=$hw?'hw':'aw'?>" style="display:block;text-align:center">S<?=$i+1?>: <?=$sv['home']?>–<?=$sv['away']?></span><?php endforeach;?></div><?php endif;?>
      </div>
      <div style="text-align:center">
        <div style="display:flex;align-items:center;justify-content:center;gap:6px;margin-bottom:8px"><span style="font-size:13px;font-weight:600"><?=htmlspecialchars($match['an'])?></span><div style="width:20px;height:20px;border-radius:3px;background:<?=$match['ac']?>;display:flex;align-items:center;justify-content:center;font-size:9px;font-weight:700;color:#fff"><?=substr($match['as_'],0,3)?></div></div>
        <div style="display:flex;justify-content:center;gap:4px;margin-bottom:8px"><?php for($i=0;$i<3;$i++):?><div class="set-dot <?=$i<$match['away_sets_won']?'on':''?>"></div><?php endfor;?></div>
        <div style="display:flex;align-items:center;justify-content:center;gap:8px">
          <?php if($match['status']==='Live'):?><button class="btn-add" onclick="doAdd('away')"><i class="bi bi-plus-lg"></i></button><?php endif;?>
          <div class="score-num" id="aScore"><?=$match['away_score_current_set']?></div>
          <?php if($match['status']==='Live'):?><button class="btn-sub" id="aSub" onclick="doSub('away')" <?=$match['away_score_current_set']<=0?'disabled':''?>><i class="bi bi-dash"></i></button><?php endif;?>
        </div>
        <div style="font-size:11px;color:var(--t3);margin-top:6px">Sets: <strong id="aSets"><?=$match['away_sets_won']?></strong></div>
      </div>
    </div>
  </div>

  <?php if($match['status']==='Live'):?>
  <div style="padding:10px;display:flex;flex-direction:column;gap:8px">
    <?php foreach([['home',$match['hn'],$match['hc'],$match['hs'],'hPlayer',$hPlayers],['away',$match['an'],$match['ac'],$match['as_'],'aPlayer',$aPlayers]] as [$side,$name,$color,$short,$selId,$players]):?>
    <div style="background:var(--s1);border:1px solid var(--b0);border-radius:var(--r);overflow:hidden">
      <div style="padding:8px 12px;border-bottom:1px solid var(--b0);display:flex;align-items:center;gap:8px">
        <div style="width:16px;height:16px;border-radius:3px;background:<?=$color?>;display:flex;align-items:center;justify-content:center;font-size:9px;font-weight:700;color:#fff"><?=substr($short,0,3)?></div>
        <span style="font-size:13px;font-weight:600"><?=htmlspecialchars($name)?></span>
        <select id="<?=$selId?>" class="psel" style="margin-left:auto;width:auto;max-width:180px" onchange="updateTilesForPlayer(this.id)"><option value="">Select player</option><?php foreach($players as $p):?><option value="<?=$p['id']?>">#<?=$p['jersey_number']?> <?=htmlspecialchars($p['first_name'].' '.$p['last_name'])?> (<?=$p['position']?>)</option><?php endforeach;?></select>
      </div>
      <div style="padding:8px"><div class="tile-grid"><?php foreach($tiles as [$lbl,$ico,$c]):?><button class="a-tile" onclick="doAction(<?=json_encode($side)?>,<?=json_encode($selId)?>,<?=json_encode($lbl)?>)" style="border-color:transparent"><i class="bi <?=$ico?>" style="color:<?=$c?>"></i><span><?=$lbl?></span></button><?php endforeach;?></div></div>
    </div>
    <?php endforeach;?>
  </div>
  <?php elseif($match['status']==='Scheduled'):?>
  <div style="display:flex;flex-direction:column;align-items:center;justify-content:center;padding:3rem;gap:12px;text-align:center"><i class="bi bi-play-circle" style="font-size:40px;color:var(--t3);opacity:.5"></i><div style="font-size:14px;color:var(--t2)">Match not started yet</div><button class="btn btn-success" onclick="doStart()"><i class="bi bi-play-fill"></i>Start Match</button></div>
  <?php endif;?>
  <?php endif;?>
</div>

<div class="sc-feed">
  <div style="padding:9px 14px;border-bottom:1px solid var(--b0);display:flex;align-items:center;justify-content:space-between;flex-shrink:0;position:sticky;top:0;background:var(--s1);z-index:2">
    <span style="font-size:12px;font-weight:600;color:var(--t2)">Action Log</span>
    <button onclick="refreshFeed()" class="btn btn-ghost btn-icon btn-sm"><i class="bi bi-arrow-clockwise" style="font-size:12px"></i></button>
  </div>
  <div style="flex:1;overflow-y:auto" id="actionFeed">
    <?php if(empty($actions)):?><div style="padding:20px;text-align:center;color:var(--t3);font-size:12px" id="emptyFeed"><i class="bi bi-clipboard" style="font-size:28px;display:block;margin-bottom:6px;opacity:.3"></i>No actions recorded</div><?php else: foreach($actions as $a): $rc=$a['action_result']==='Success'?'fd-ok':($a['action_result']==='Error'?'fd-err':'fd-neu');$ic=$aIcons[$a['action_type']]??'bi-dot';?>
    <div class="feed-item" id="fa<?=$a['id']?>">
      <div class="fdot <?=$rc?>"><i class="bi <?=$ic?>"></i></div>
      <div style="flex:1;min-width:0"><div style="font-size:12px;font-weight:600;white-space:nowrap;overflow:hidden;text-overflow:ellipsis"><span style="color:<?=$a['tc']?>">#<?=$a['jn']?></span> <?=htmlspecialchars($a['pn'])?></div><div style="font-size:11px;color:var(--t3)"><?=$a['action_type']?> · S<?=$a['set_number']?> · <?=date('H:i:s',strtotime($a['recorded_at']))?></div></div>
      <div style="display:flex;flex-direction:column;align-items:flex-end;gap:2px;flex-shrink:0"><span style="font-size:11px;font-weight:600;color:var(--t2)"><?=$a['home_score_after']?>–<?=$a['away_score_after']?></span><button onclick="delAction(<?=$a['id']?>)" style="background:none;border:none;color:var(--t3);cursor:pointer;font-size:12px;padding:0;line-height:1"><i class="bi bi-x"></i></button></div>
    </div>
    <?php endforeach;endif;?>
  </div>
</div>
</div>

<div class="modal fade" id="editModal" tabindex="-1"><div class="modal-dialog modal-dialog-centered modal-sm"><div class="modal-content"><div class="modal-header"><h5 class="modal-title">Edit Scores</h5><button class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body" style="display:flex;flex-direction:column;gap:12px"><div class="field"><label class="label"><?=$match?htmlspecialchars($match['hn']):'Home'?></label><input type="number" id="editH" min="0" max="50" class="input" value="<?=$match?$match['home_score_current_set']:0?>"></div><div class="field"><label class="label"><?=$match?htmlspecialchars($match['an']):'Away'?></label><input type="number" id="editA" min="0" max="50" class="input" value="<?=$match?$match['away_score_current_set']:0?>"></div></div><div class="modal-footer"><button class="btn btn-ghost btn-sm" data-bs-dismiss="modal">Cancel</button><button class="btn btn-pri btn-sm" onclick="applyEdit()">Apply</button></div></div></div></div>

<script>
const MID=<?=(int)$matchId?>,API='<?=APP_URL?>/api/match_action.php',CSRF=document.querySelector('meta[name="csrf"]')?.content||'';
const AI=<?=json_encode($aIcons)?>;
const PLAYER_POS=<?=json_encode($playerPositions)?>;
const NO_BLOCK_POS=['Libero','Defensive Specialist'];
const BLOCK_ACTIONS=['Block','Block Error','Attack Kill','Attack Error','Attack Blocked'];

function updateTilesForPlayer(selId){
  const sel=document.getElementById(selId);
  if(!sel)return;
  const pid=parseInt(sel.value);
  const pos=PLAYER_POS[pid]||'';
  const restricted=NO_BLOCK_POS.includes(pos);
  // tile-grid is in the next sibling div of the header div that contains the select
  const headerDiv=sel.closest('div[style*="border-bottom"]');
  const panelDiv=headerDiv?.parentElement;
  const grid=panelDiv?.querySelector('.tile-grid');
  if(!grid)return;
  grid.querySelectorAll('.a-tile').forEach(btn=>{
    const label=btn.querySelector('span')?.textContent?.trim();
    if(BLOCK_ACTIONS.includes(label)){
      if(restricted){
        btn.disabled=true; btn.style.opacity='.25'; btn.style.cursor='not-allowed';
        btn.title=pos+' cannot perform this action';
      } else {
        btn.disabled=false; btn.style.opacity=''; btn.style.cursor=''; btn.title='';
      }
    }
  });
}
async function call(a,x={}){const r=await fetch(API,{method:'POST',headers:{'Content-Type':'application/json','X-CSRF-Token':CSRF},body:JSON.stringify({action:a,match_id:MID,...x})});return r.json();}
function bump(id){const e=document.getElementById(id);if(!e)return;e.style.animation='';void e.offsetWidth;e.style.animation='sbump .2s ease';}
function ui(d){if(d.home_score!==undefined){document.getElementById('hScore').textContent=d.home_score;bump('hScore');const b=document.getElementById('hSub');if(b)b.disabled=d.home_score<=0;}if(d.away_score!==undefined){document.getElementById('aScore').textContent=d.away_score;bump('aScore');const b=document.getElementById('aSub');if(b)b.disabled=d.away_score<=0;}if(d.home_sets_won!==undefined)document.getElementById('hSets').textContent=d.home_sets_won;if(d.away_sets_won!==undefined)document.getElementById('aSets').textContent=d.away_sets_won;if(d.current_set!==undefined){const e=document.getElementById('setNum');if(e)e.textContent=d.current_set;}}
async function doStart(){if(!confirm('Start match now?'))return;const d=await call('start_match');if(d.success){toast('Match started','ok');location.reload();}else toast(d.error||'Error','err');}
async function doEnd(){if(!confirm('End match? Standings will be updated.'))return;const d=await call('end_match');if(d.success){toast('Match ended. Standings updated.','ok');setTimeout(()=>location.reload(),1000);}else toast(d.error||'Error','err');}
async function doAdd(team){const d=await call('add_point',{team});if(!d.success){toast(d.error||'Error','err');return;}ui(d);if(d.set_ended&&!d.match_ended){toast('Set ended — next set begins','info');setTimeout(()=>location.reload(),1000);}if(d.match_ended){toast('Match complete!','ok');setTimeout(()=>location.reload(),1200);}}
async function doSub(team){const d=await call('subtract_point',{team});if(d.success){ui(d);toast('Correction: -1 '+team,'warn');}else toast(d.error||'Cannot go below 0','err');}
async function doEndSet(){const sn=document.getElementById('setNum')?.textContent||'?';if(!confirm('End Set '+sn+'?'))return;const d=await call('end_set');if(d.success){toast(d.match_ended?'Match over!':'Set ended','info');setTimeout(()=>location.reload(),900);}else toast(d.error||'Error','err');}
async function doAction(side,selId,type){
  const sel=document.getElementById(selId);
  if(!sel?.value){sel?.focus();toast('Select a player first','warn');return;}
  const d=await call('record_action',{player_id:+sel.value,team_side:side,action_type:type});
  if(!d.success){toast(d.error||'Error','err');return;}
  toast(type+' recorded','ok');
  if(d.action)prepend(d.action);
  // Update scoreboard if this action automatically scored a point
  if(d.home_score!==undefined){ui(d);}
  if(d.set_ended&&!d.match_ended){toast('Set ended — next set begins','info');setTimeout(()=>location.reload(),1000);}
  if(d.match_ended){toast('Match complete!','ok');setTimeout(()=>location.reload(),1200);}
}
async function delAction(id){if(!confirm('Remove?'))return;const d=await call('delete_action',{action_id:id});if(d.success){document.getElementById('fa'+id)?.remove();toast('Removed','info');}else toast(d.error||'Error','err');}
function doEditScores(){document.getElementById('editH').value=document.getElementById('hScore')?.textContent||0;document.getElementById('editA').value=document.getElementById('aScore')?.textContent||0;new bootstrap.Modal(document.getElementById('editModal')).show();}
async function applyEdit(){const hs=+document.getElementById('editH').value,as_=+document.getElementById('editA').value;if(isNaN(hs)||isNaN(as_)||hs<0||as_<0){toast('Invalid','err');return;}const[dh,da]=await Promise.all([call('set_score',{team:'home',score:hs}),call('set_score',{team:'away',score:as_})]);if(dh.success&&da.success){ui({home_score:hs,away_score:as_});bootstrap.Modal.getInstance(document.getElementById('editModal'))?.hide();toast('Updated','ok');}else toast('Error','err');}
async function refreshFeed(){if(!MID)return;const r=await fetch('<?=APP_URL?>/api/action_feed.php?match_id='+MID);document.getElementById('actionFeed').innerHTML=await r.text();}
function prepend(a){const feed=document.getElementById('actionFeed');document.getElementById('emptyFeed')?.remove();const rc=a.action_result==='Success'?'fd-ok':(a.action_result==='Error'?'fd-err':'fd-neu');const ic=AI[a.action_type]||'bi-dot';const t=new Date(a.recorded_at).toLocaleTimeString('en-GB',{hour:'2-digit',minute:'2-digit',second:'2-digit'});const el=document.createElement('div');el.className='feed-item';el.id='fa'+a.id;el.innerHTML=`<div class="fdot ${rc}"><i class="bi ${ic}"></i></div><div style="flex:1;min-width:0"><div style="font-size:12px;font-weight:600;white-space:nowrap;overflow:hidden;text-overflow:ellipsis"><span style="color:${a.tc}">#${a.jn}</span> ${x(a.pn)}</div><div style="font-size:11px;color:var(--t3)">${x(a.action_type)} · S${a.set_number} · ${t}</div></div><div style="display:flex;flex-direction:column;align-items:flex-end;gap:2px;flex-shrink:0"><span style="font-size:11px;font-weight:600;color:var(--t2)">${a.home_score_after}–${a.away_score_after}</span><button onclick="delAction(${a.id})" style="background:none;border:none;color:var(--t3);cursor:pointer;font-size:12px;padding:0"><i class="bi bi-x"></i></button></div>`;feed.prepend(el);}
function x(s){return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');}
document.addEventListener('DOMContentLoaded',()=>{const hs=parseInt(document.getElementById('hScore')?.textContent||'0'),as_=parseInt(document.getElementById('aScore')?.textContent||'0');const hb=document.getElementById('hSub');if(hb)hb.disabled=hs<=0;const ab=document.getElementById('aSub');if(ab)ab.disabled=as_<=0;});
</script>
<?php include __DIR__.'/../includes/footer.php';?>
