<?php
require_once __DIR__.'/../../includes/config.php';
$currentPage='stats'; $pageTitle='Player Stats'; $db=getDB();

$mid = (int)($_GET['match'] ?? 0);

$allMatches = $db->query("SELECT m.id,m.status,m.round,m.match_date,m.home_sets_won,m.away_sets_won,ht.name hn,ht.short_name hs,ht.color_primary hc,at.name an,at.short_name as_,at.color_primary ac FROM matches m JOIN teams ht ON ht.id=m.home_team_id JOIN teams at ON at.id=m.away_team_id WHERE m.status IN('Live','Completed') ORDER BY FIELD(m.status,'Live','Completed'),m.match_date DESC LIMIT 30")->fetchAll();

if (!$mid && !empty($allMatches)) $mid = $allMatches[0]['id'];

$sel = null; $homeStats = []; $awayStats = []; $homeTotals = []; $awayTotals = [];

if ($mid) {
  $s = $db->prepare("SELECT m.*,ht.name hn,ht.short_name hs,ht.id hid,ht.color_primary hc,at.name an,at.short_name as_,at.id aid,at.color_primary ac FROM matches m JOIN teams ht ON ht.id=m.home_team_id JOIN teams at ON at.id=m.away_team_id WHERE m.id=?");
  $s->execute([$mid]); $sel = $s->fetch();

  if ($sel) {
    $statQ = $db->prepare("
      SELECT p.id, p.jersey_number, CONCAT(p.first_name,' ',p.last_name) pname, p.position,
        SUM(pa.action_type='Attack Kill')                                           AS kills,
        SUM(pa.action_type='Ace')                                                   AS aces,
        SUM(pa.action_type='Block')                                                 AS blocks,
        SUM(pa.action_type='Dig')                                                   AS digs,
        SUM(pa.action_type='Set Assist')                                            AS assists,
        SUM(pa.action_type='Reception')                                             AS receptions,
        SUM(pa.action_type IN('Attack Error','Attack Blocked','Service Error',
                               'Block Error','Dig Error','Reception Error'))        AS errors,
        SUM(pa.action_type IN('Attack Kill','Ace','Block'))                         AS points
      FROM player_actions pa
      JOIN players p ON p.id = pa.player_id
      WHERE pa.match_id = ? AND pa.team_id = ?
      GROUP BY p.id, p.jersey_number, p.first_name, p.last_name, p.position
      ORDER BY points DESC, kills DESC, aces DESC
    ");

    $statQ->execute([$mid, $sel['hid']]); $homeStats = $statQ->fetchAll();
    $statQ->execute([$mid, $sel['aid']]); $awayStats = $statQ->fetchAll();

    $totals = function(array $rows): array {
      return array_reduce($rows, function($c, $r) {
        foreach (['kills','aces','blocks','digs','assists','receptions','errors','points'] as $k)
          $c[$k] = ($c[$k] ?? 0) + $r[$k];
        return $c;
      }, []);
    };
    $homeTotals = $totals($homeStats);
    $awayTotals = $totals($awayStats);
  }
}

include __DIR__.'/../../includes/header.php';
?>
<style>
.stats-layout{display:grid;grid-template-columns:220px 1fr;height:calc(100vh - var(--th));overflow:hidden}
.st-list{border-right:1px solid var(--b0);overflow-y:auto;background:var(--s1)}
.st-main{overflow-y:auto;background:var(--bg);padding:20px}
.st-item{padding:10px 14px;border-bottom:1px solid var(--b0);text-decoration:none;display:block;transition:background var(--tr);color:var(--t1)}
.st-item:hover{background:var(--s2)}.st-item.sel{background:var(--s3);border-left:2px solid var(--acc)}
.stats-tbl{width:100%;border-collapse:collapse;font-size:12px}
.stats-tbl th{padding:7px 10px;text-align:center;font-size:10px;font-weight:600;color:var(--t3);text-transform:uppercase;letter-spacing:.04em;border-bottom:2px solid var(--b0);white-space:nowrap}
.stats-tbl th:first-child,.stats-tbl th:nth-child(2),.stats-tbl th:nth-child(3){text-align:left}
.stats-tbl td{padding:7px 10px;border-bottom:1px solid var(--b0);text-align:center;color:var(--t2)}
.stats-tbl td:first-child,.stats-tbl td:nth-child(2){text-align:left}
.stats-tbl td:nth-child(3){text-align:left;color:var(--t3);font-size:11px}
.stats-tbl tr:last-child td{border-bottom:none}
.stats-tbl tr:hover td{background:var(--s2)}
.stats-tbl .tot td{background:var(--s2);font-weight:600;color:var(--t1);border-top:2px solid var(--b0)}
.pts-cell{font-weight:700;color:var(--acc)}
.zero{color:var(--b2)}
@media(max-width:900px){.stats-layout{grid-template-columns:1fr;height:auto}.st-list{height:150px}}
</style>

<div class="stats-layout">
  <!-- Match list -->
  <div class="st-list">
    <div style="padding:9px 14px 6px;font-size:11px;font-weight:600;color:var(--t3);border-bottom:1px solid var(--b0);text-transform:uppercase;letter-spacing:.04em;position:sticky;top:0;background:var(--s1);z-index:2">Matches</div>
    <?php foreach ($allMatches as $m): $bc=$m['status']==='Live'?'var(--red)':'var(--grn)'; ?>
    <a href="?match=<?= $m['id'] ?>" class="st-item <?= $m['id']==$mid?'sel':'' ?>">
      <div style="display:flex;gap:5px;align-items:center;margin-bottom:4px">
        <span style="font-size:10px;font-weight:700;color:<?= $bc ?>;text-transform:uppercase"><?= $m['status'] ?></span>
        <span style="font-size:11px;color:var(--t3)"><?= htmlspecialchars($m['round']??'') ?></span>
      </div>
      <div style="font-size:12px;font-weight:600;display:flex;align-items:center;gap:4px"><div class="team-dot" style="background:<?= $m['hc'] ?>"></div><?= htmlspecialchars($m['hs']) ?></div>
      <div style="font-size:16px;font-weight:700;color:var(--t1);margin:.1rem 0;line-height:1"><?= $m['home_sets_won'] ?><span style="color:var(--t3);font-weight:400;font-size:13px">:</span><?= $m['away_sets_won'] ?></div>
      <div style="font-size:12px;font-weight:600;display:flex;align-items:center;gap:4px"><div class="team-dot" style="background:<?= $m['ac'] ?>"></div><?= htmlspecialchars($m['as_']) ?></div>
      <div style="font-size:11px;color:var(--t3);margin-top:3px"><?= date('d M, H:i',strtotime($m['match_date'])) ?></div>
    </a>
    <?php endforeach; ?>
    <?php if (empty($allMatches)): ?>
    <div style="padding:20px;text-align:center;color:var(--t3);font-size:12px">No matches with stats yet</div>
    <?php endif; ?>
  </div>

  <!-- Stats main -->
  <div class="st-main">
    <?php if (!$sel): ?>
    <div style="display:flex;flex-direction:column;align-items:center;justify-content:center;height:100%;gap:8px;text-align:center;color:var(--t3)">
      <i class="bi bi-bar-chart-line" style="font-size:40px;opacity:.25"></i>
      <div style="font-size:14px;color:var(--t2)">Select a match to view player stats</div>
    </div>
    <?php else:
      $sets = json_decode($sel['set_scores']??'[]',true);
      $isLive = $sel['status']==='Live';
    ?>

    <!-- Match header -->
    <div style="background:var(--s1);border:1px solid var(--b0);border-radius:var(--r);padding:14px 18px;margin-bottom:18px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px">
      <div style="display:flex;align-items:center;gap:14px;flex-wrap:wrap">
        <span class="badge <?= $isLive?'b-live':'b-done' ?>"><?= $sel['status'] ?></span>
        <div style="display:flex;align-items:center;gap:6px;font-size:14px;font-weight:600"><div class="team-dot" style="background:<?= $sel['hc'] ?>"></div><?= htmlspecialchars($sel['hn']) ?></div>
        <div style="font-size:22px;font-weight:700;letter-spacing:-.02em"><?= $sel['home_sets_won'] ?><span style="color:var(--t3);font-weight:400;font-size:16px">:</span><?= $sel['away_sets_won'] ?></div>
        <div style="display:flex;align-items:center;gap:6px;font-size:14px;font-weight:600"><div class="team-dot" style="background:<?= $sel['ac'] ?>"></div><?= htmlspecialchars($sel['an']) ?></div>
        <?php if ($isLive): ?>
        <span style="font-size:12px;color:var(--t3)">Set <?= $sel['current_set'] ?> · <span id="liveScore"><?= $sel['home_score_current_set'] ?>–<?= $sel['away_score_current_set'] ?></span></span>
        <?php endif; ?>
      </div>
      <div style="display:flex;gap:6px;align-items:center">
        <?php if ($isLive): ?>
        <span style="font-size:11px;color:var(--t3)" id="refreshTxt"><i class="bi bi-arrow-repeat me-1"></i>Auto-refresh 10s</span>
        <?php endif; ?>
        <a href="<?= APP_URL ?>/modules/scoreboard/index.php?match=<?= $mid ?>" class="btn btn-ghost btn-sm"><i class="bi bi-display"></i>Scoreboard</a>
        <?php if (isLoggedIn()): ?><a href="<?= APP_URL ?>/admin/score_entry.php?match=<?= $mid ?>" class="btn btn-ghost btn-sm"><i class="bi bi-pencil-square"></i>Score Entry</a><?php endif; ?>
      </div>
    </div>

    <?php if (empty($homeStats) && empty($awayStats)): ?>
    <div style="text-align:center;padding:3rem;color:var(--t3)">
      <i class="bi bi-clipboard-x" style="font-size:36px;display:block;margin-bottom:10px;opacity:.3"></i>
      <div style="font-size:14px;color:var(--t2)">No player actions recorded for this match yet</div>
    </div>
    <?php else: ?>

    <?php
    $statsTable = function(array $rows, array $totals, string $teamName, string $teamColor) {
      if (empty($rows)) { echo '<div style="padding:20px;text-align:center;color:var(--t3);font-size:12px">No actions recorded</div>'; return; }
      $z = fn($v) => $v > 0 ? $v : '<span class="zero">—</span>';
      echo '<div class="tbl-wrap"><table class="stats-tbl">';
      echo '<thead><tr><th>#</th><th>Player</th><th>Pos</th><th title="Attack Kills">K</th><th title="Aces">A</th><th title="Blocks">B</th><th title="Digs">D</th><th title="Set Assists">Ast</th><th title="Receptions">Rec</th><th title="Errors">Err</th><th title="Total Points (K+A+B)">Pts</th></tr></thead><tbody>';
      foreach ($rows as $r) {
        echo '<tr>';
        echo '<td><div style="width:26px;height:26px;border-radius:4px;background:'.$teamColor.';display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:700;color:#fff">'.$r['jersey_number'].'</div></td>';
        echo '<td style="font-weight:600;color:var(--t1);white-space:nowrap"><a href="'.APP_URL.'/modules/players/profile.php?id='.$r['id'].'" style="color:inherit;text-decoration:none" onmouseover="this.style.color=\'var(--acc)\'" onmouseout="this.style.color=\'\'">'.htmlspecialchars($r['pname']).'</a></td>';
        echo '<td>'.htmlspecialchars($r['position']).'</td>';
        echo '<td>'.$z($r['kills']).'</td>';
        echo '<td>'.$z($r['aces']).'</td>';
        echo '<td>'.$z($r['blocks']).'</td>';
        echo '<td>'.$z($r['digs']).'</td>';
        echo '<td>'.$z($r['assists']).'</td>';
        echo '<td>'.$z($r['receptions']).'</td>';
        echo '<td style="color:'.($r['errors']>0?'var(--red)':'var(--b2)').'">'.$z($r['errors']).'</td>';
        echo '<td class="pts-cell">'.$z($r['points']).'</td>';
        echo '</tr>';
      }
      // Totals row
      $z2 = fn($v) => $v > 0 ? $v : '—';
      echo '<tr class="tot"><td colspan="3" style="text-align:left;color:var(--t3);font-size:11px;text-transform:uppercase;letter-spacing:.04em">Team Total</td>';
      echo '<td>'.$z2($totals['kills']??0).'</td>';
      echo '<td>'.$z2($totals['aces']??0).'</td>';
      echo '<td>'.$z2($totals['blocks']??0).'</td>';
      echo '<td>'.$z2($totals['digs']??0).'</td>';
      echo '<td>'.$z2($totals['assists']??0).'</td>';
      echo '<td>'.$z2($totals['receptions']??0).'</td>';
      echo '<td style="color:'.($totals['errors']>0?'var(--red)':'var(--t2)').'">'.$z2($totals['errors']??0).'</td>';
      echo '<td class="pts-cell">'.$z2($totals['points']??0).'</td>';
      echo '</tr>';
      echo '</tbody></table></div>';
    };
    ?>

    <!-- Home team -->
    <div class="card" style="margin-bottom:14px" id="homeStatsCard">
      <div class="card-head">
        <h3 style="display:flex;align-items:center;gap:7px"><div class="team-dot" style="background:<?= $sel['hc'] ?>"></div><?= htmlspecialchars($sel['hn']) ?></h3>
        <span style="font-size:12px;color:var(--t3)"><?= number_format($homeTotals['points']??0) ?> pts · <?= number_format($homeTotals['kills']??0) ?> kills</span>
      </div>
      <?php $statsTable($homeStats, $homeTotals, $sel['hn'], $sel['hc']); ?>
    </div>

    <!-- Away team -->
    <div class="card" id="awayStatsCard">
      <div class="card-head">
        <h3 style="display:flex;align-items:center;gap:7px"><div class="team-dot" style="background:<?= $sel['ac'] ?>"></div><?= htmlspecialchars($sel['an']) ?></h3>
        <span style="font-size:12px;color:var(--t3)"><?= number_format($awayTotals['points']??0) ?> pts · <?= number_format($awayTotals['kills']??0) ?> kills</span>
      </div>
      <?php $statsTable($awayStats, $awayTotals, $sel['an'], $sel['ac']); ?>
    </div>

    <!-- Legend -->
    <div style="display:flex;gap:16px;flex-wrap:wrap;margin-top:12px;font-size:11px;color:var(--t3)">
      <?php foreach(['K'=>'Attack Kills','A'=>'Aces','B'=>'Blocks','D'=>'Digs','Ast'=>'Set Assists','Rec'=>'Receptions','Err'=>'Errors (all types)','Pts'=>'Points (K+A+B)'] as $abbr=>$full): ?>
      <span><strong><?= $abbr ?></strong> = <?= $full ?></span>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
    <?php endif; ?>
  </div>
</div>

<?php if (($isLive??false) && $mid): ?>
<script>
setInterval(async () => {
  try {
    const r = await fetch('<?= APP_URL ?>/api/match_score.php?match_id=<?= $mid ?>');
    const d = await r.json();
    if (d.error) return;
    const sc = document.getElementById('liveScore');
    if (sc) sc.textContent = `${d.home_score_current_set}–${d.away_score_current_set}`;
  } catch(e) {}

  // Reload stats tables
  try {
    const r = await fetch(location.href);
    const html = await r.text();
    const doc = new DOMParser().parseFromString(html, 'text/html');
    ['homeStatsCard','awayStatsCard'].forEach(id => {
      const fresh = doc.getElementById(id);
      const cur   = document.getElementById(id);
      if (fresh && cur) cur.innerHTML = fresh.innerHTML;
    });
  } catch(e) {}
}, 10000);
</script>
<?php endif; ?>

<?php include __DIR__.'/../../includes/footer.php'; ?>
