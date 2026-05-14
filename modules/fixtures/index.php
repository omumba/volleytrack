<?php
require_once __DIR__.'/../../includes/config.php';
$currentPage='fixtures'; $pageTitle='Fixtures'; $db=getDB();
$sf=$_GET['status']??''; $rf=$_GET['round']??''; $sq=trim($_GET['q']??'');
// Use the active season, fall back to the most recent one
$activeSeason=$db->query("SELECT id FROM seasons WHERE status='Active' ORDER BY start_date DESC LIMIT 1")->fetchColumn();
if(!$activeSeason) $activeSeason=$db->query("SELECT id FROM seasons ORDER BY start_date DESC LIMIT 1")->fetchColumn();
$sid=(int)($_GET['season']??$activeSeason??1);
$allSeasons=$db->query("SELECT id,name,status FROM seasons ORDER BY start_date DESC")->fetchAll();
$rounds=$db->prepare("SELECT DISTINCT round FROM matches WHERE season_id=? AND round IS NOT NULL ORDER BY round");
$rounds->execute([$sid]); $rounds=$rounds->fetchAll(PDO::FETCH_COLUMN);
$w="m.season_id=?"; $p=[$sid];
if($sf){$w.=" AND m.status=?";$p[]=$sf;}
if($rf){$w.=" AND m.round=?";$p[]=$rf;}
if($sq){$w.=" AND (ht.name LIKE ? OR at.name LIKE ?)";$p[]="%$sq%";$p[]="%$sq%";}
$s=$db->prepare("SELECT m.*,ht.name hn,ht.short_name hs,ht.color_primary hc,at.name an,at.short_name as_,at.color_primary ac FROM matches m JOIN teams ht ON ht.id=m.home_team_id JOIN teams at ON at.id=m.away_team_id WHERE $w ORDER BY m.match_date");
$s->execute($p); $matches=$s->fetchAll();
$grouped=[]; foreach($matches as $m) $grouped[$m['round']??'Other'][]=$m;
include __DIR__.'/../../includes/header.php';
?>
<div class="content">
  <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px;margin-bottom:20px">
    <h1 style="font-size:18px;font-weight:600">Fixtures</h1>
    <?php if(isLoggedIn()): ?><a href="<?= APP_URL ?>/admin/manage_match.php" class="btn btn-pri btn-sm"><i class="bi bi-plus"></i>Add Fixture</a><?php endif; ?>
  </div>
  <div style="background:var(--s1);border:1px solid var(--b0);border-radius:var(--r);padding:12px 14px;margin-bottom:16px">
    <form method="GET" style="display:flex;gap:8px;flex-wrap:wrap;align-items:flex-end">
      <div class="field" style="flex:1;min-width:160px"><label class="label">Search</label><div class="search-box"><i class="bi bi-search"></i><input type="text" name="q" value="<?= htmlspecialchars($sq) ?>" placeholder="Team name..."></div></div>
      <div class="field"><label class="label">Status</label><select name="status" class="select" style="width:130px"><option value="">All</option><?php foreach(['Live','Scheduled','Completed','Postponed'] as $st): ?><option value="<?= $st ?>" <?= $sf===$st?'selected':'' ?>><?= $st ?></option><?php endforeach; ?></select></div>
      <div class="field"><label class="label">Season</label><select name="season" class="select" style="width:130px"><?php foreach($allSeasons as $se): ?><option value="<?= $se['id'] ?>" <?= $se['id']==$sid?'selected':'' ?>><?= htmlspecialchars($se['name']) ?></option><?php endforeach; ?></select></div>
      <div class="field"><label class="label">Round</label><select name="round" class="select" style="width:130px"><option value="">All rounds</option><?php foreach($rounds as $r): ?><option value="<?= htmlspecialchars($r) ?>" <?= $rf===$r?'selected':'' ?>><?= htmlspecialchars($r) ?></option><?php endforeach; ?></select></div>
      <div style="display:flex;gap:6px"><button type="submit" class="btn btn-def btn-sm"><i class="bi bi-funnel"></i>Filter</button><a href="?" class="btn btn-ghost btn-sm"><i class="bi bi-x"></i>Clear</a></div>
      <span style="font-size:12px;color:var(--t3);align-self:flex-end"><?= count($matches) ?> match<?= count($matches)!==1?'es':'' ?></span>
    </form>
  </div>

  <?php if(empty($grouped)): ?>
  <div style="background:var(--s1);border:1px solid var(--b0);border-radius:var(--r);padding:40px;text-align:center;color:var(--t3)"><i class="bi bi-calendar-x" style="font-size:32px;display:block;margin-bottom:8px;opacity:.4"></i><div style="font-size:14px;color:var(--t2)">No fixtures found</div></div>
  <?php endif; ?>

  <?php foreach($grouped as $round=>$rms): ?>
  <div style="margin-bottom:20px">
    <div style="display:flex;align-items:center;gap:10px;margin-bottom:10px">
      <span style="font-size:12px;font-weight:600;color:var(--t3);text-transform:uppercase;letter-spacing:.04em"><?= htmlspecialchars($round) ?></span>
      <div style="flex:1;height:1px;background:var(--b0)"></div>
      <span style="font-size:11px;color:var(--t3)"><?= count($rms) ?></span>
    </div>
    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(320px,1fr));gap:8px">
      <?php foreach($rms as $m):
        $sets=json_decode($m['set_scores']??'[]',true);
        $isLive=$m['status']==='Live'; $isDone=$m['status']==='Completed';
        $hw=$isDone&&$m['home_sets_won']>$m['away_sets_won'];
        $bc=$isLive?'b-live':($isDone?'b-done':($m['status']==='Postponed'?'b-post':'b-sched'));
      ?>
      <div class="match-row <?= $isLive?'is-live':'' ?>">
        <div style="display:flex;align-items:center;gap:8px;margin-bottom:10px;flex-wrap:wrap">
          <span class="badge <?= $bc ?>"><?= $m['status'] ?></span>
          <span style="font-size:11px;color:var(--t3)"><i class="bi bi-calendar3 me-1"></i><?= date('D d M Y',strtotime($m['match_date'])) ?> <?= date('H:i',strtotime($m['match_date'])) ?></span>
          <?php if($m['venue']): ?><span style="font-size:11px;color:var(--t3)"><i class="bi bi-geo-alt me-1"></i><?= htmlspecialchars($m['venue']) ?></span><?php endif; ?>
        </div>
        <div style="display:grid;grid-template-columns:1fr auto 1fr;align-items:center;gap:8px">
          <div>
            <div style="display:flex;align-items:center;gap:5px;margin-bottom:5px">
              <div class="team-dot" style="background:<?= $m['hc'] ?>"></div>
              <span style="font-size:13px;font-weight:<?= $hw?'700':'500' ?>;color:<?= $hw?'var(--t1)':'var(--t2)' ?>"><?= htmlspecialchars($m['hn']) ?></span>
              <?php if($hw): ?><i class="bi bi-trophy-fill" style="font-size:10px;color:var(--gld)"></i><?php endif; ?>
            </div>
            <div style="display:flex;align-items:center;gap:5px">
              <div class="team-dot" style="background:<?= $m['ac'] ?>"></div>
              <span style="font-size:13px;font-weight:<?= !$hw&&$isDone?'700':'500' ?>;color:<?= !$hw&&$isDone?'var(--t1)':'var(--t2)' ?>"><?= htmlspecialchars($m['an']) ?></span>
              <?php if(!$hw&&$isDone): ?><i class="bi bi-trophy-fill" style="font-size:10px;color:var(--gld)"></i><?php endif; ?>
            </div>
          </div>
          <div style="text-align:center;min-width:60px">
            <?php if($isDone||$isLive): ?>
            <div style="font-size:22px;font-weight:700;letter-spacing:-.02em;line-height:1"><?= $m['home_sets_won'] ?><span style="color:var(--t3);font-weight:400;font-size:16px">:</span><?= $m['away_sets_won'] ?></div>
            <div style="font-size:10px;color:var(--t3);text-transform:uppercase;letter-spacing:.04em">sets</div>
            <?php if($isLive): ?><div style="font-size:12px;font-weight:600;color:var(--gld);margin-top:2px"><?= $m['home_score_current_set'] ?>–<?= $m['away_score_current_set'] ?></div><?php endif; ?>
            <?php else: ?>
            <div style="font-size:13px;font-weight:600;color:var(--t3)">vs</div>
            <div style="font-size:11px;color:var(--t3)"><?= date('H:i',strtotime($m['match_date'])) ?></div>
            <?php endif; ?>
          </div>
          <div style="text-align:right">
            <?php if(!empty($sets)): ?>
            <div style="display:flex;gap:3px;justify-content:flex-end;flex-wrap:wrap;margin-bottom:6px">
              <?php foreach($sets as $i=>$sv): $shw=$sv['home']>$sv['away']; ?><span class="set-tag <?= $shw?'hw':'aw' ?>">S<?= $i+1 ?>: <?= $sv['home'] ?>–<?= $sv['away'] ?></span><?php endforeach; ?>
            </div>
            <?php endif; ?>
            <div style="display:flex;gap:4px;justify-content:flex-end">
              <a href="<?= APP_URL ?>/modules/scoreboard/index.php?match=<?= $m['id'] ?>" class="btn btn-ghost btn-sm btn-icon" title="Scoreboard"><i class="bi bi-display" style="font-size:12px"></i></a>
              <?php if(hasRole('Scorer')): ?><a href="<?= APP_URL ?>/admin/score_entry.php?match=<?= $m['id'] ?>" class="btn btn-pri btn-sm btn-icon" title="Score Entry"><i class="bi bi-pencil" style="font-size:12px"></i></a><?php endif; ?>
            </div>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
  <?php endforeach; ?>
</div>
<?php include __DIR__.'/../../includes/footer.php'; ?>
