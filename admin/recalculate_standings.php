<?php
require_once __DIR__.'/../includes/config.php';
requireRole('Admin');
$db=getDB();
$db->exec("UPDATE league_standings SET matches_played=0,matches_won=0,matches_lost=0,sets_won=0,sets_lost=0,points=0,set_ratio=0");
$ms=$db->query("SELECT * FROM matches WHERE status='Completed'")->fetchAll();
foreach($ms as $m){
  $hw=$m['home_sets_won']>$m['away_sets_won'];
  foreach([[$m['home_team_id'],$hw,$m['home_sets_won'],$m['away_sets_won'],$hw?3:0],[$m['away_team_id'],!$hw,$m['away_sets_won'],$m['home_sets_won'],$hw?0:3]] as [$tid,$won,$sw,$sl,$pts]){
    $db->prepare("INSERT INTO league_standings(season_id,team_id,matches_played,matches_won,matches_lost,sets_won,sets_lost,points)VALUES(?,?,1,?,?,?,?,?) ON DUPLICATE KEY UPDATE matches_played=matches_played+1,matches_won=matches_won+?,matches_lost=matches_lost+?,sets_won=sets_won+?,sets_lost=sets_lost+?,points=points+?")->execute([$m['season_id'],$tid,$won?1:0,$won?0:1,$sw,$sl,$pts,$won?1:0,$won?0:1,$sw,$sl,$pts]);
  }
}
$db->exec("UPDATE league_standings SET set_ratio=IF(sets_lost>0,ROUND(sets_won/sets_lost,3),sets_won)");
logActivity('Recalculated standings');
header('Location:'.APP_URL.'/admin/index.php?ok=standings');
exit;
