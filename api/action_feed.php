<?php
require_once __DIR__.'/../includes/config.php';
$mid=(int)($_GET['match_id']??0);
if(!$mid) exit;
$db=getDB();
$s=$db->prepare("SELECT pa.id,pa.action_type,pa.action_result,pa.set_number,pa.home_score_after,pa.away_score_after,pa.recorded_at,CONCAT(p.first_name,' ',p.last_name) pn,p.jersey_number jn,t.color_primary tc FROM player_actions pa JOIN players p ON p.id=pa.player_id JOIN teams t ON t.id=pa.team_id WHERE pa.match_id=? ORDER BY pa.recorded_at DESC LIMIT 30");
$s->execute([$mid]); $actions=$s->fetchAll();
$icons=['Ace'=>'bi-star-fill','Attack Kill'=>'bi-lightning-fill','Block'=>'bi-shield-fill','Dig'=>'bi-arrow-down-circle-fill','Set Assist'=>'bi-arrow-up-right-circle-fill','Service Error'=>'bi-x-circle-fill','Attack Error'=>'bi-x-circle-fill','Reception'=>'bi-hand-thumbs-up-fill'];
if(empty($actions)){ echo '<div style="padding:20px;text-align:center;color:var(--t3);font-size:12px" id="emptyFeed"><i class="bi bi-clipboard" style="font-size:28px;display:block;margin-bottom:6px;opacity:.3"></i>No actions recorded</div>'; exit; }
foreach($actions as $a){
  $rc=$a['action_result']==='Success'?'fd-ok':($a['action_result']==='Error'?'fd-err':'fd-neu');
  $ic=$icons[$a['action_type']]??'bi-dot';
  echo '<div class="feed-item" id="fa'.$a['id'].'">';
  echo '<div class="fdot '.$rc.'"><i class="bi '.$ic.'"></i></div>';
  echo '<div style="flex:1;min-width:0"><div style="font-size:12px;font-weight:600;white-space:nowrap;overflow:hidden;text-overflow:ellipsis"><span style="color:'.$a['tc'].'">#'.$a['jn'].'</span> '.htmlspecialchars($a['pn']).'</div>';
  echo '<div style="font-size:11px;color:var(--t3)">'.htmlspecialchars($a['action_type']).' · S'.$a['set_number'].' · '.date('H:i:s',strtotime($a['recorded_at'])).'</div></div>';
  echo '<div style="display:flex;flex-direction:column;align-items:flex-end;gap:2px;flex-shrink:0"><span style="font-size:11px;font-weight:600;color:var(--t2)">'.$a['home_score_after'].'–'.$a['away_score_after'].'</span><button onclick="delAction('.$a['id'].')" style="background:none;border:none;color:var(--t3);cursor:pointer;font-size:12px;padding:0;line-height:1"><i class="bi bi-x"></i></button></div>';
  echo '</div>';
}
