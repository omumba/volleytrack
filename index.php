<?php
require_once __DIR__.'/includes/config.php';
$currentPage='home'; $pageTitle='Dashboard';
$db=getDB();
$stats=['teams'=>$db->query("SELECT COUNT(*) FROM teams WHERE is_active=1")->fetchColumn(),'players'=>$db->query("SELECT COUNT(*) FROM players WHERE is_active=1")->fetchColumn(),'matches'=>$db->query("SELECT COUNT(*) FROM matches")->fetchColumn(),'live'=>$db->query("SELECT COUNT(*) FROM matches WHERE status='Live'")->fetchColumn()];
$live=$db->query("SELECT m.*,ht.name hn,ht.short_name hs,ht.color_primary hc,at.name an,at.short_name as_,at.color_primary ac FROM matches m JOIN teams ht ON ht.id=m.home_team_id JOIN teams at ON at.id=m.away_team_id WHERE m.status='Live' LIMIT 3")->fetchAll();
$results=$db->query("SELECT m.*,ht.name hn,ht.short_name hs,ht.color_primary hc,at.name an,at.short_name as_,at.color_primary ac FROM matches m JOIN teams ht ON ht.id=m.home_team_id JOIN teams at ON at.id=m.away_team_id WHERE m.status='Completed' ORDER BY m.match_date DESC LIMIT 6")->fetchAll();
$upcoming=$db->query("SELECT m.*,ht.name hn,ht.short_name hs,at.name an,at.short_name as_ FROM matches m JOIN teams ht ON ht.id=m.home_team_id JOIN teams at ON at.id=m.away_team_id WHERE m.status='Scheduled' ORDER BY m.match_date ASC LIMIT 5")->fetchAll();
$standings=$db->query("SELECT ls.*,t.name,t.short_name,t.color_primary FROM league_standings ls JOIN teams t ON t.id=ls.team_id WHERE ls.season_id=1 ORDER BY ls.points DESC,ls.set_ratio DESC LIMIT 6")->fetchAll();
$news=$db->query("SELECT id,title,slug,category,published_at FROM news_articles WHERE status='Published' ORDER BY published_at DESC LIMIT 4")->fetchAll();
$catC=['Match Report'=>'var(--acc)','Analysis'=>'var(--pri)','General'=>'var(--grn)','Tournament'=>'var(--gld)','Interview'=>'var(--pur)','Transfer'=>'var(--acc)'];
include __DIR__.'/includes/header.php';
?>
<div class="content">
  <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px;margin-bottom:20px">
    <div><h1 style="font-size:18px;font-weight:600;margin-bottom:2px">Dashboard</h1><div style="font-size:12px;color:var(--t3)">Malawi Volleyball League 2025</div></div>
    <?php if(isLoggedIn()):?>
    <div style="display:flex;gap:6px">
      <a href="<?=APP_URL?>/admin/score_entry.php" class="btn btn-pri btn-sm"><i class="bi bi-pencil-square"></i>Score Entry</a>
      <a href="<?=APP_URL?>/admin/manage_match.php" class="btn btn-def btn-sm"><i class="bi bi-plus"></i>Add Match</a>
    </div>
    <?php endif;?>
  </div>

  <div class="g-stats" style="margin-bottom:20px">
    <?php foreach([['Teams',$stats['teams'],'bi-shield'],['Players',$stats['players'],'bi-people'],['Matches',$stats['matches'],'bi-calendar3'],['Live Now',$stats['live'],'bi-broadcast']] as [$l,$v,$i]):?>
    <div class="stat-card">
      <div style="display:flex;align-items:center;justify-content:space-between">
        <div><div class="stat-val"><?=$v?></div><div class="stat-lbl"><?=$l?></div></div>
        <i class="bi <?=$i?>" style="font-size:20px;color:var(--t3)"></i>
      </div>
    </div>
    <?php endforeach;?>
  </div>

  <div class="g-sidebar">
    <div style="display:flex;flex-direction:column;gap:16px">

      <?php if(!empty($live)):?>
      <div class="card">
        <div class="card-head">
          <h3 style="display:flex;align-items:center;gap:7px"><span class="live-dot" style="background:var(--red)"></span>Live Now</h3>
          <a href="<?=APP_URL?>/modules/scoreboard/index.php" class="btn btn-ghost btn-sm">Scoreboard</a>
        </div>
        <div style="padding:10px;display:flex;flex-direction:column;gap:8px">
          <?php foreach($live as $m): $sets=json_decode($m['set_scores']??'[]',true);?>
          <div class="match-row is-live">
            <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px">
              <div style="display:flex;flex-direction:column;gap:6px">
                <div style="display:flex;align-items:center;gap:5px"><div class="team-dot" style="background:<?=$m['hc']?>"></div><span style="font-size:13px;font-weight:600"><?=htmlspecialchars($m['hn'])?></span></div>
                <div style="display:flex;align-items:center;gap:5px"><div class="team-dot" style="background:<?=$m['ac']?>"></div><span style="font-size:13px;font-weight:600"><?=htmlspecialchars($m['an'])?></span></div>
              </div>
              <div style="text-align:center">
                <div style="font-size:28px;font-weight:700;letter-spacing:-.02em;line-height:1"><?=$m['home_sets_won']?><span style="color:var(--t3);font-weight:400;font-size:20px">:</span><?=$m['away_sets_won']?></div>
                <div style="font-size:11px;color:var(--t3)">sets</div>
                <div style="font-size:13px;font-weight:600;color:var(--gld);margin-top:2px"><?=$m['home_score_current_set']?>–<?=$m['away_score_current_set']?></div>
              </div>
              <div style="display:flex;flex-direction:column;gap:3px;align-items:flex-end">
                <?php foreach($sets as $i=>$sv): $hw=$sv['home']>$sv['away'];?><span class="set-tag <?=$hw?'hw':'aw'?>">S<?=$i+1?>: <?=$sv['home']?>–<?=$sv['away']?></span><?php endforeach;?>
                <div style="margin-top:4px"><a href="<?=APP_URL?>/modules/scoreboard/index.php?match=<?=$m['id']?>" class="btn btn-pri btn-sm"><i class="bi bi-display"></i>View</a></div>
              </div>
            </div>
          </div>
          <?php endforeach;?>
        </div>
      </div>
      <?php endif;?>

      <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
        <div class="card">
          <div class="card-head"><h3>Results</h3><a href="<?=APP_URL?>/modules/fixtures/index.php?status=Completed" class="btn btn-ghost btn-sm">All</a></div>
          <?php foreach($results as $m): $hw=$m['home_sets_won']>$m['away_sets_won'];?>
          <div style="display:flex;align-items:center;gap:10px;padding:9px 14px;border-bottom:1px solid var(--b0)">
            <div style="flex:1;min-width:0">
              <div style="font-size:12px;font-weight:<?=$hw?'600':'400'?>;color:<?=$hw?'var(--t1)':'var(--t2)'?>;display:flex;align-items:center;gap:4px"><div class="team-dot" style="background:<?=$m['hc']?>"></div><?=htmlspecialchars($m['hs'])?></div>
              <div style="font-size:12px;font-weight:<?=!$hw?'600':'400'?>;color:<?=!$hw?'var(--t1)':'var(--t2)'?>;display:flex;align-items:center;gap:4px;margin-top:3px"><div class="team-dot" style="background:<?=$m['ac']?>"></div><?=htmlspecialchars($m['as_'])?></div>
            </div>
            <div style="text-align:right;flex-shrink:0">
              <div style="font-size:14px;font-weight:700"><?=$m['home_sets_won']?><span style="color:var(--t3);font-weight:400">:</span><?=$m['away_sets_won']?></div>
              <div style="font-size:11px;color:var(--t3)"><?=date('d M',strtotime($m['match_date']))?></div>
            </div>
          </div>
          <?php endforeach;?>
        </div>
        <div class="card">
          <div class="card-head"><h3>Upcoming</h3><a href="<?=APP_URL?>/modules/fixtures/index.php" class="btn btn-ghost btn-sm">All</a></div>
          <?php foreach($upcoming as $m):?>
          <div style="display:flex;align-items:center;gap:10px;padding:9px 14px;border-bottom:1px solid var(--b0)">
            <div style="flex:1;min-width:0">
              <div style="font-size:12px;font-weight:600;color:var(--t1)"><?=htmlspecialchars($m['hs'])?></div>
              <div style="font-size:12px;color:var(--t2);margin-top:2px"><?=htmlspecialchars($m['as_'])?></div>
            </div>
            <div style="text-align:right;flex-shrink:0">
              <div style="font-size:12px;font-weight:600;color:var(--pri)"><?=date('d M',strtotime($m['match_date']))?></div>
              <div style="font-size:11px;color:var(--t3)"><?=date('H:i',strtotime($m['match_date']))?></div>
            </div>
          </div>
          <?php endforeach;?>
        </div>
      </div>

      <?php if(!empty($news)):?>
      <div class="card">
        <div class="card-head"><h3>Latest News</h3><a href="<?=APP_URL?>/modules/news/index.php" class="btn btn-ghost btn-sm">All news</a></div>
        <div style="display:grid;grid-template-columns:repeat(2,1fr)">
          <?php foreach($news as $i=>$n): $c=$catC[$n['category']]??'var(--t2)';?>
          <a href="<?=APP_URL?>/modules/news/article.php?slug=<?=urlencode($n['slug'])?>" style="padding:12px 14px;<?=$i%2===0?'border-right:1px solid var(--b0)':''?>;<?=$i<2?'border-bottom:1px solid var(--b0)':''?>;text-decoration:none;display:block;transition:background var(--tr)" onmouseover="this.style.background='var(--s2)'" onmouseout="this.style.background=''">
            <div style="font-size:11px;font-weight:600;color:<?=$c?>;margin-bottom:4px;text-transform:uppercase;letter-spacing:.04em"><?=$n['category']?></div>
            <div style="font-size:13px;font-weight:500;color:var(--t1);line-height:1.3;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden"><?=htmlspecialchars($n['title'])?></div>
            <div style="font-size:11px;color:var(--t3);margin-top:5px"><?=date('d M Y',strtotime($n['published_at']))?></div>
          </a>
          <?php endforeach;?>
        </div>
      </div>
      <?php endif;?>
    </div>

    <div style="display:flex;flex-direction:column;gap:14px">
      <div class="card">
        <div class="card-head"><h3>League Table</h3><a href="<?=APP_URL?>/modules/tables/index.php" class="btn btn-ghost btn-sm">Full</a></div>
        <div class="tbl-wrap">
          <table class="tbl">
            <thead><tr><th>#</th><th>Team</th><th>MP</th><th>W</th><th>Pts</th></tr></thead>
            <tbody>
              <?php foreach($standings as $i=>$st): $pos=$i+1; $pc=$pos===1?'pos-1':($pos===2?'pos-2':($pos===3?'pos-3':''));?>
              <tr>
                <td><span class="pos-num <?=$pc?>"><?=$pos?></span></td>
                <td><div style="display:flex;align-items:center;gap:5px"><div class="team-dot" style="background:<?=$st['color_primary']?>"></div><span style="font-size:12px;font-weight:500"><?=htmlspecialchars($st['short_name'])?></span></div></td>
                <td style="color:var(--t3);font-size:12px"><?=$st['matches_played']?></td>
                <td style="color:var(--t2);font-size:12px"><?=$st['matches_won']?></td>
                <td class="cell-pts"><?=$st['points']?></td>
              </tr>
              <?php endforeach;?>
            </tbody>
          </table>
        </div>
      </div>

      <?php if(isLoggedIn()):?>
      <div class="card">
        <div class="card-head"><h3>Quick Actions</h3></div>
        <div style="padding:10px;display:flex;flex-direction:column;gap:4px">
          <?php foreach([['bi-pencil-square','Score Entry','admin/score_entry.php','btn-pri'],['bi-plus','Add Match','admin/manage_match.php','btn-def'],['bi-shield','Teams','admin/manage_teams.php','btn-def'],['bi-people','Players','admin/manage_players.php','btn-def'],['bi-broadcast','Streams','admin/manage_streams.php','btn-def'],['bi-newspaper','News','admin/manage_news.php','btn-def']] as [$ic,$lbl,$href,$cls]):?>
          <a href="<?=APP_URL?>/<?=$href?>" class="btn <?=$cls?> btn-sm" style="justify-content:flex-start"><i class="bi <?=$ic?>"></i><?=$lbl?></a>
          <?php endforeach;?>
        </div>
      </div>
      <?php endif;?>
    </div>
  </div>
</div>
<?php include __DIR__.'/includes/footer.php';?>
