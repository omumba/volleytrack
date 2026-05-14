<?php
// api/match_score.php
require_once __DIR__.'/../includes/config.php';
$mid=(int)($_GET['match_id']??0);
if(!$mid) jsonResponse(['error'=>'match_id required'],400);
$db=getDB();
$s=$db->prepare("SELECT home_sets_won,away_sets_won,home_score_current_set,away_score_current_set,current_set,status FROM matches WHERE id=?");
$s->execute([$mid]);
$d=$s->fetch();
if(!$d) jsonResponse(['error'=>'Not found'],404);
jsonResponse($d);
