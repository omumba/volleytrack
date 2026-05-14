<?php
require_once __DIR__.'/../../includes/config.php';
$pid = (int)($_GET['id'] ?? 0);
if (!$pid) { header('Location:'.APP_URL.'/modules/players/index.php'); exit; }

$db = getDB();

$player = $db->prepare("SELECT p.*,t.name tn,t.short_name ts,t.color_primary tc,t.logo tlogo FROM players p JOIN teams t ON t.id=p.team_id WHERE p.id=?");
$player->execute([$pid]); $player = $player->fetch();
if (!$player) { header('Location:'.APP_URL.'/modules/players/index.php'); exit; }

$pageTitle = htmlspecialchars($player['first_name'].' '.$player['last_name']);
$currentPage = 'players';

// Career totals
$totals = $db->prepare("
  SELECT
    COUNT(DISTINCT pa.match_id)                                                             AS matches,
    SUM(pa.action_type='Attack Kill')                                                       AS kills,
    SUM(pa.action_type='Ace')                                                               AS aces,
    SUM(pa.action_type='Block')                                                             AS blocks,
    SUM(pa.action_type='Dig')                                                               AS digs,
    SUM(pa.action_type='Set Assist')                                                        AS assists,
    SUM(pa.action_type='Reception')                                                         AS receptions,
    SUM(pa.action_type IN('Attack Error','Attack Blocked','Service Error',
                           'Block Error','Dig Error','Reception Error'))                    AS errors,
    SUM(pa.action_type IN('Attack Kill','Ace','Block'))                                     AS points
  FROM player_actions pa WHERE pa.player_id=?
");
$totals->execute([$pid]); $totals = $totals->fetch();

// Per-match breakdown (completed matches only)
$matches = $db->prepare("
  SELECT m.id, m.match_date, m.round, m.home_sets_won, m.away_sets_won,
         ht.name hn, ht.short_name hs, ht.color_primary hc,
         at.name an, at.short_name as_, at.color_primary ac,
         (m.home_team_id = ?) AS is_home,
         SUM(pa.action_type='Attack Kill')                                                       AS kills,
         SUM(pa.action_type='Ace')                                                               AS aces,
         SUM(pa.action_type='Block')                                                             AS blocks,
         SUM(pa.action_type='Dig')                                                               AS digs,
         SUM(pa.action_type='Set Assist')                                                        AS assists,
         SUM(pa.action_type='Reception')                                                         AS receptions,
         SUM(pa.action_type IN('Attack Error','Attack Blocked','Service Error',
                                'Block Error','Dig Error','Reception Error'))                    AS errors,
         SUM(pa.action_type IN('Attack Kill','Ace','Block'))                                     AS points
  FROM player_actions pa
  JOIN matches m ON m.id = pa.match_id
  JOIN teams ht ON ht.id = m.home_team_id
  JOIN teams at ON at.id = m.away_team_id
  WHERE pa.player_id = ? AND m.status = 'Completed'
  GROUP BY m.id, m.match_date, m.round, m.home_sets_won, m.away_sets_won,
           ht.name, ht.short_name, ht.color_primary, at.name, at.short_name, at.color_primary, is_home
  ORDER BY m.match_date DESC
");
$matches->execute([$player['team_id'], $pid]); $matches = $matches->fetchAll();

$mp  = (int)($totals['matches'] ?? 0);
$avg = fn($v) => $mp > 0 ? number_format($v / $mp, 1) : '—';

$posIcons = ['Setter'=>'bi-hand-index-thumb-fill','Outside Hitter'=>'bi-lightning-fill','Middle Blocker'=>'bi-shield-fill','Opposite Hitter'=>'bi-arrow-up-right-circle-fill','Libero'=>'bi-arrow-down-circle-fill','Defensive Specialist'=>'bi-asterisk'];
$ico = $posIcons[$player['position']] ?? 'bi-person';

include __DIR__.'/../../includes/header.php';
?>
<div class="content" style="max-width:900px;margin:0 auto">

  <!-- Back link -->
  <a href="<?= APP_URL ?>/modules/players/index.php?team=<?= $player['team_id'] ?>" style="display:inline-flex;align-items:center;gap:5px;font-size:12px;color:var(--t3);text-decoration:none;margin-bottom:16px"><i class="bi bi-arrow-left"></i>Back to Players</a>

  <!-- Player header -->
  <div class="card" style="margin-bottom:16px">
    <div style="padding:20px;display:flex;align-items:center;gap:20px;flex-wrap:wrap">
      <div style="position:relative;flex-shrink:0">
        <?php if (!empty($player['photo'])): ?>
        <img src="<?= APP_URL ?>/assets/uploads/players/<?= htmlspecialchars($player['photo']) ?>" alt=""
             style="width:84px;height:84px;border-radius:50%;object-fit:cover;border:3px solid <?= $player['tc'] ?>">
        <?php else: ?>
        <div style="width:84px;height:84px;border-radius:50%;background:<?= $player['tc'] ?>;display:flex;align-items:center;justify-content:center;font-size:28px;font-weight:700;color:#fff"><?= strtoupper(substr($player['first_name'],0,1)) ?></div>
        <?php endif; ?>
        <div style="position:absolute;bottom:0;right:0;width:28px;height:28px;border-radius:50%;background:<?= $player['tc'] ?>;border:2px solid var(--bg);display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:700;color:#fff"><?= $player['jersey_number'] ?></div>
      </div>
      <div style="flex:1;min-width:0">
        <h1 style="font-size:20px;font-weight:700;margin-bottom:4px"><?= htmlspecialchars($player['first_name'].' '.$player['last_name']) ?></h1>
        <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;margin-bottom:8px">
          <span style="display:inline-flex;align-items:center;gap:4px;font-size:12px;color:var(--t2)"><i class="bi <?= $ico ?>" style="color:<?= $player['tc'] ?>"></i><?= htmlspecialchars($player['position']) ?></span>
          <span style="display:inline-flex;align-items:center;gap:5px;font-size:12px;font-weight:600;color:<?= $player['tc'] ?>">
            <?php if (!empty($player['tlogo'])): ?>
            <img src="<?= APP_URL ?>/assets/uploads/teams/<?= htmlspecialchars($player['tlogo']) ?>" style="width:14px;height:14px;object-fit:contain">
            <?php else: ?>
            <div style="width:10px;height:10px;border-radius:50%;background:<?= $player['tc'] ?>"></div>
            <?php endif; ?>
            <?= htmlspecialchars($player['tn']) ?>
          </span>
          <span class="badge <?= $player['is_active']?'b-active':'b-off' ?>" style="font-size:10px"><?= $player['is_active']?'Active':'Inactive' ?></span>
        </div>
        <div style="display:flex;gap:16px;flex-wrap:wrap;font-size:12px;color:var(--t3)">
          <?php if ($player['height_cm']): ?><span><i class="bi bi-arrows-vertical me-1"></i><?= $player['height_cm'] ?> cm</span><?php endif; ?>
          <?php if ($player['weight_kg']): ?><span><i class="bi bi-speedometer me-1"></i><?= $player['weight_kg'] ?> kg</span><?php endif; ?>
          <?php if ($player['date_of_birth']): ?>
            <?php $age = (int)((time() - strtotime($player['date_of_birth'])) / 31557600); ?>
            <span><i class="bi bi-calendar3 me-1"></i><?= date('d M Y', strtotime($player['date_of_birth'])) ?> (<?= $age ?> yrs)</span>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>

  <!-- Career stat cards -->
  <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(110px,1fr));gap:8px;margin-bottom:16px">
    <?php foreach([
      ['Matches','matches','bi-calendar3','var(--t2)'],
      ['Points','points','bi-trophy-fill','var(--gld)'],
      ['Kills','kills','bi-lightning-fill','var(--acc)'],
      ['Aces','aces','bi-star-fill','var(--gld)'],
      ['Blocks','blocks','bi-shield-fill','var(--pri)'],
      ['Digs','digs','bi-arrow-down-circle-fill','var(--grn)'],
      ['Assists','assists','bi-arrow-up-right-circle-fill','var(--pur)'],
      ['Receptions','receptions','bi-hand-thumbs-up-fill','var(--grn)'],
      ['Errors','errors','bi-x-circle-fill','var(--red)'],
    ] as [$lbl,$key,$bic,$col]): $val = (int)($totals[$key]??0); ?>
    <div class="stat-card" style="text-align:center">
      <i class="bi <?= $bic ?>" style="font-size:16px;color:<?= $col ?>;display:block;margin-bottom:4px"></i>
      <div style="font-size:20px;font-weight:700;color:var(--t1);line-height:1"><?= $val ?></div>
      <div style="font-size:10px;color:var(--t3);text-transform:uppercase;letter-spacing:.04em;margin-top:3px"><?= $lbl ?></div>
    </div>
    <?php endforeach; ?>
  </div>

  <!-- Per-match averages -->
  <?php if ($mp > 0): ?>
  <div class="card" style="margin-bottom:16px">
    <div class="card-head"><h3>Per-Match Averages <span style="font-weight:400;color:var(--t3);font-size:12px">over <?= $mp ?> match<?= $mp!==1?'es':'' ?></span></h3></div>
    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(100px,1fr));padding:12px;gap:8px">
      <?php foreach([
        ['Pts/M','points','var(--gld)'],['Kills/M','kills','var(--acc)'],['Aces/M','aces','var(--gld)'],
        ['Blocks/M','blocks','var(--pri)'],['Digs/M','digs','var(--grn)'],['Ast/M','assists','var(--pur)'],
      ] as [$lbl,$key,$col]): ?>
      <div style="background:var(--s2);border-radius:var(--r);padding:8px;text-align:center">
        <div style="font-size:16px;font-weight:700;color:<?= $col ?>"><?= $avg($totals[$key]??0) ?></div>
        <div style="font-size:10px;color:var(--t3);text-transform:uppercase;letter-spacing:.04em;margin-top:2px"><?= $lbl ?></div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
  <?php endif; ?>

  <!-- Match-by-match breakdown -->
  <div class="card">
    <div class="card-head"><h3>Match History</h3><span style="font-size:11px;color:var(--t3)"><?= count($matches) ?> completed match<?= count($matches)!==1?'es':'' ?></span></div>
    <?php if (empty($matches)): ?>
    <div style="padding:30px;text-align:center;color:var(--t3);font-size:12px"><i class="bi bi-clipboard" style="font-size:28px;display:block;margin-bottom:6px;opacity:.3"></i>No match stats recorded yet</div>
    <?php else: ?>
    <div class="tbl-wrap">
      <table class="tbl" style="font-size:12px">
        <thead>
          <tr>
            <th>Date</th><th>Match</th><th>Result</th>
            <th title="Points (Kills+Aces+Blocks)">Pts</th>
            <th title="Kills">K</th><th title="Aces">A</th><th title="Blocks">B</th>
            <th title="Digs">D</th><th title="Assists">Ast</th><th title="Errors">Err</th>
          </tr>
        </thead>
        <tbody>
        <?php foreach ($matches as $m):
          $isHome  = (bool)$m['is_home'];
          $myScore = $isHome ? $m['home_sets_won'] : $m['away_sets_won'];
          $opScore = $isHome ? $m['away_sets_won']  : $m['home_sets_won'];
          $won     = $myScore > $opScore;
          $oppName = $isHome ? $m['an'] : $m['hn'];
          $oppColor= $isHome ? $m['ac'] : $m['hc'];
        ?>
        <tr>
          <td style="white-space:nowrap;color:var(--t3)"><?= date('d M Y', strtotime($m['match_date'])) ?></td>
          <td>
            <div style="display:flex;align-items:center;gap:5px">
              <div class="team-dot" style="background:<?= $oppColor ?>"></div>
              <?= $isHome ? 'vs' : '@' ?> <?= htmlspecialchars($oppName) ?>
              <?php if ($m['round']): ?><span style="color:var(--t3);font-size:11px"><?= htmlspecialchars($m['round']) ?></span><?php endif; ?>
            </div>
          </td>
          <td>
            <span style="font-weight:700;color:<?= $won?'var(--grn)':'var(--red)' ?>"><?= $won?'W':'L' ?></span>
            <span style="color:var(--t3);font-size:11px;margin-left:3px"><?= $myScore ?>–<?= $opScore ?></span>
          </td>
          <td style="font-weight:700;color:var(--gld)"><?= $m['points'] ?: '—' ?></td>
          <td style="color:var(--acc)"><?= $m['kills']  ?: '—' ?></td>
          <td style="color:var(--gld)"><?= $m['aces']   ?: '—' ?></td>
          <td style="color:var(--pri)"><?= $m['blocks'] ?: '—' ?></td>
          <td style="color:var(--grn)"><?= $m['digs']   ?: '—' ?></td>
          <td style="color:var(--pur)"><?= $m['assists']?: '—' ?></td>
          <td style="color:<?= $m['errors']>0?'var(--red)':'var(--t3)' ?>"><?= $m['errors'] ?: '—' ?></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php endif; ?>
  </div>

</div>
<?php include __DIR__.'/../../includes/footer.php'; ?>
