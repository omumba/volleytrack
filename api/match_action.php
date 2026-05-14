<?php
require_once __DIR__.'/../includes/config.php';
requireRoleApi('Scorer');           // minimum: Scorer to touch this API
verifyCsrf();                       // validate CSRF token from X-CSRF-Token header
header('Content-Type: application/json');
$data = json_decode(file_get_contents('php://input'),true) ?? [];
$action = $data['action'] ?? '';
$db = getDB();

// Actions that auto-score a point FOR the acting team
const POINT_FOR     = ['Ace','Attack Kill','Block'];
// Actions that auto-score a point AGAINST the acting team (opponent gets the point)
const POINT_AGAINST = ['Service Error','Attack Error','Attack Blocked'];

try {
  switch($action) {

  case 'start_match': {
    requireRoleApi('Referee');
    $mid=(int)($data['match_id']??0);
    if(!$mid) out(['error'=>'match_id required']);
    $m=getMatch($db,$mid);
    if(!$m) out(['error'=>'Match not found']);
    if($m['status']==='Completed') out(['error'=>'Match already completed']);
    $db->prepare("UPDATE matches SET status='Live',started_at=NOW(),current_set=1,home_score_current_set=0,away_score_current_set=0,home_sets_won=0,away_sets_won=0,set_scores='[]' WHERE id=?")->execute([$mid]);
    $db->prepare("INSERT IGNORE INTO match_sets(match_id,set_number,started_at)VALUES(?,1,NOW())")->execute([$mid]);
    logActivity("Started match #$mid",'match',$mid);
    out(['success'=>true]);
  }

  case 'end_match': {
    requireRoleApi('Referee');
    $mid=(int)($data['match_id']??0);
    if(!$mid) out(['error'=>'match_id required']);
    $db->prepare("UPDATE matches SET status='Completed',ended_at=NOW() WHERE id=?")->execute([$mid]);
    recalc($db,$mid);
    logActivity("Ended match #$mid",'match',$mid);
    out(['success'=>true]);
  }

  case 'add_point': {
    requireRoleApi('Scorer');
    $mid=(int)($data['match_id']??0);
    $team=$data['team']==='home'?'home':'away';
    $m=getLiveMatch($db,$mid);
    if(!$m) out(['error'=>'Match not live']);
    $res=applyPoint($db,$m,$mid,$team);
    out(array_merge(['success'=>true],$res));
  }

  case 'subtract_point': {
    requireRoleApi('Scorer');
    $mid=(int)($data['match_id']??0);
    $team=$data['team']==='home'?'home':'away';
    $field=$team.'_score_current_set';
    $m=getLiveMatch($db,$mid);
    if(!$m) out(['error'=>'Match not live']);
    if($m[$field]<=0) out(['error'=>'Cannot go below 0']);
    $db->prepare("UPDATE matches SET {$field}=? WHERE id=?")->execute([$m[$field]-1,$mid]);
    $u=getMatch($db,$mid);
    out(['success'=>true,'home_score'=>$u['home_score_current_set'],'away_score'=>$u['away_score_current_set']]);
  }

  case 'set_score': {
    requireRoleApi('Referee');
    $mid=(int)($data['match_id']??0);
    $team=$data['team']==='home'?'home':'away';
    $score=max(0,(int)($data['score']??0));
    $field=$team.'_score_current_set';
    $m=getLiveMatch($db,$mid);
    if(!$m) out(['error'=>'Match not live']);
    $db->prepare("UPDATE matches SET {$field}=? WHERE id=?")->execute([$score,$mid]);
    $u=getMatch($db,$mid);
    out(['success'=>true,'home_score'=>$u['home_score_current_set'],'away_score'=>$u['away_score_current_set']]);
  }

  case 'end_set': {
    requireRoleApi('Referee');
    $mid=(int)($data['match_id']??0);
    $m=getLiveMatch($db,$mid);
    if(!$m) out(['error'=>'Match not live']);
    $hs=$m['home_score_current_set'];$as=$m['away_score_current_set'];
    $sw=$hs>=$as?'home':'away';
    $sf=$sw.'_sets_won';$nw=$m[$sf]+1;
    $db->prepare("UPDATE match_sets SET home_score=?,away_score=?,winner=?,ended_at=NOW() WHERE match_id=? AND set_number=?")->execute([$hs,$as,$sw,$mid,$m['current_set']]);
    $sets=json_decode($m['set_scores']??'[]',true);
    $sets[]=['home'=>(int)$hs,'away'=>(int)$as];
    if($nw>=3){
      $db->prepare("UPDATE matches SET {$sf}=?,status='Completed',ended_at=NOW(),set_scores=?,home_score_current_set=0,away_score_current_set=0 WHERE id=?")->execute([$nw,json_encode($sets),$mid]);
      recalc($db,$mid);
      out(['success'=>true,'match_ended'=>true]);
    }else{
      $next=$m['current_set']+1;
      $db->prepare("UPDATE matches SET {$sf}=?,current_set=?,home_score_current_set=0,away_score_current_set=0,set_scores=? WHERE id=?")->execute([$nw,$next,json_encode($sets),$mid]);
      $db->prepare("INSERT IGNORE INTO match_sets(match_id,set_number,started_at)VALUES(?,?,NOW())")->execute([$mid,$next]);
      out(['success'=>true,'match_ended'=>false,'next_set'=>$next]);
    }
  }

  case 'record_action': {
    requireRoleApi('Scorer');
    $mid=(int)($data['match_id']??0);
    $pid=(int)($data['player_id']??0);
    $side=$data['team_side']??'';
    $type=trim($data['action_type']??'');
    if(!$mid||!$pid||!$type) out(['error'=>'match_id, player_id, action_type required']);
    if(!in_array($side,['home','away'])) out(['error'=>'Invalid team_side']);
    $m=getLiveMatch($db,$mid);
    if(!$m) out(['error'=>'Match must be live to record actions']);

    // Validate player belongs to the correct team
    $tid=$side==='home'?$m['home_team_id']:$m['away_team_id'];
    $chk=$db->prepare("SELECT id FROM players WHERE id=? AND team_id=?");
    $chk->execute([$pid,$tid]);
    if(!$chk->fetch()) out(['error'=>'Player does not belong to this team']);

    // Validate action type
    $okActions=['Ace','Attack Kill','Block','Dig','Set Assist','Reception',
                'Service Error','Attack Error','Attack Blocked','Reception Error','Block Error','Dig Error'];
    if(!in_array($type,$okActions)) out(['error'=>'Invalid action type']);

    $successActions=['Ace','Attack Kill','Block','Dig','Set Assist','Reception'];
    $errorActions=['Service Error','Attack Error','Attack Blocked','Reception Error','Block Error','Dig Error'];
    $result=in_array($type,$successActions)?'Success':(in_array($type,$errorActions)?'Error':'Neutral');

    $db->prepare("INSERT INTO player_actions(match_id,set_number,player_id,team_id,action_type,action_result,home_score_after,away_score_after)VALUES(?,?,?,?,?,?,?,?)")->execute([$mid,$m['current_set'],$pid,$tid,$type,$result,$m['home_score_current_set'],$m['away_score_current_set']]);
    $nid=$db->lastInsertId();

    // Auto-score: point-winning and point-losing actions update the scoreboard
    $scoreUpdate=[];
    if(in_array($type,POINT_FOR)){
      $scoreUpdate=applyPoint($db,$m,$mid,$side);
    } elseif(in_array($type,POINT_AGAINST)){
      $otherSide=$side==='home'?'away':'home';
      $scoreUpdate=applyPoint($db,$m,$mid,$otherSide);
    }

    // Fetch the recorded action with player/team details
    $r=$db->prepare("SELECT pa.*,CONCAT(p.first_name,' ',p.last_name) pn,p.jersey_number jn,t.name tn,t.color_primary tc FROM player_actions pa JOIN players p ON p.id=pa.player_id JOIN teams t ON t.id=pa.team_id WHERE pa.id=?");
    $r->execute([$nid]);
    out(array_merge(['success'=>true,'action'=>$r->fetch()],$scoreUpdate));
  }

  case 'delete_action': {
    requireRoleApi('Referee');
    $aid=(int)($data['action_id']??0);
    if(!$aid) out(['error'=>'action_id required']);
    $db->prepare("DELETE FROM player_actions WHERE id=?")->execute([$aid]);
    out(['success'=>true]);
  }

  default: out(['error'=>'Unknown action: '.$action]);
  }
} catch(Throwable $e) {
  error_log('match_action: '.$e->getMessage());
  out(['error'=>$e->getMessage()]);
}

function out(array $d): never { echo json_encode($d); exit; }
function getMatch(PDO $db, int $id): array|false { $s=$db->prepare("SELECT * FROM matches WHERE id=?");$s->execute([$id]);return $s->fetch(); }
function getLiveMatch(PDO $db, int $id): array|false { $s=$db->prepare("SELECT * FROM matches WHERE id=? AND status='Live'");$s->execute([$id]);return $s->fetch(); }

/** Add one point to $team, handle set/match end, return score data for JSON response. */
function applyPoint(PDO $db, array $m, int $mid, string $team): array {
  $field=$team.'_score_current_set';
  $newScore=$m[$field]+1;
  $db->prepare("UPDATE matches SET {$field}=? WHERE id=?")->execute([$newScore,$mid]);
  $hs=$team==='home'?$newScore:$m['home_score_current_set'];
  $as=$team==='away'?$newScore:$m['away_score_current_set'];
  $minPts=$m['current_set']>=5?15:25;
  $setEnded=false; $matchEnded=false; $setWinner=null;
  if(($hs>=$minPts||$as>=$minPts)&&abs($hs-$as)>=2){
    $setWinner=$hs>$as?'home':'away';
    $sf=$setWinner.'_sets_won'; $nw=$m[$sf]+1;
    $db->prepare("UPDATE match_sets SET home_score=?,away_score=?,winner=?,ended_at=NOW() WHERE match_id=? AND set_number=?")->execute([$hs,$as,$setWinner,$mid,$m['current_set']]);
    $sets=json_decode($m['set_scores']??'[]',true);
    $sets[]=['home'=>(int)$hs,'away'=>(int)$as];
    if($nw>=3){
      $matchEnded=true;
      $db->prepare("UPDATE matches SET {$sf}=?,status='Completed',ended_at=NOW(),set_scores=?,home_score_current_set=0,away_score_current_set=0 WHERE id=?")->execute([$nw,json_encode($sets),$mid]);
      recalc($db,$mid); $hs=0; $as=0;
    }else{
      $next=$m['current_set']+1;
      $db->prepare("UPDATE matches SET {$sf}=?,current_set=?,home_score_current_set=0,away_score_current_set=0,set_scores=? WHERE id=?")->execute([$nw,$next,json_encode($sets),$mid]);
      $db->prepare("INSERT IGNORE INTO match_sets(match_id,set_number,started_at)VALUES(?,?,NOW())")->execute([$mid,$next]);
      $setEnded=true; $hs=0; $as=0;
    }
  }
  $updated=getMatch($db,$mid);
  return ['home_score'=>$hs,'away_score'=>$as,'home_sets_won'=>$updated['home_sets_won'],'away_sets_won'=>$updated['away_sets_won'],'current_set'=>$updated['current_set'],'set_ended'=>$setEnded,'match_ended'=>$matchEnded,'set_winner'=>$setWinner];
}

function recalc(PDO $db, int $mid): void {
  $m=getMatch($db,$mid);
  if(!$m||$m['status']!=='Completed') return;
  $hw=$m['home_sets_won']>$m['away_sets_won'];
  foreach([[$m['home_team_id'],$hw,$m['home_sets_won'],$m['away_sets_won'],$hw?3:0],[$m['away_team_id'],!$hw,$m['away_sets_won'],$m['home_sets_won'],$hw?0:3]] as [$tid,$won,$sw,$sl,$pts]){
    $db->prepare("INSERT INTO league_standings(season_id,team_id,matches_played,matches_won,matches_lost,sets_won,sets_lost,points)VALUES(?,?,1,?,?,?,?,?) ON DUPLICATE KEY UPDATE matches_played=matches_played+1,matches_won=matches_won+?,matches_lost=matches_lost+?,sets_won=sets_won+?,sets_lost=sets_lost+?,points=points+?,set_ratio=IF(sets_lost+?>0,ROUND((sets_won+?)/(sets_lost+?),3),sets_won+?)")->execute([$m['season_id'],$tid,$won?1:0,$won?0:1,$sw,$sl,$pts,$won?1:0,$won?0:1,$sw,$sl,$pts,$sl,$sw,$sl,$sw]);
  }
}
