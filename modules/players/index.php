<?php
require_once __DIR__.'/../../includes/config.php';
$currentPage='players'; $pageTitle='Players'; $db=getDB();
$tf=(int)($_GET['team']??0); $pf=$_GET['pos']??''; $sq=trim($_GET['q']??'');
$teams=$db->query("SELECT id,name,short_name,color_primary FROM teams WHERE is_active=1 ORDER BY name")->fetchAll();
$w=["p.is_active=1"]; $pm=[];
if($tf){$w[]="p.team_id=?";$pm[]=$tf;}
if($pf){$w[]="p.position=?";$pm[]=$pf;}
if($sq){$w[]="(p.first_name LIKE ? OR p.last_name LIKE ?)";$pm[]="%$sq%";$pm[]="%$sq%";}
$s=$db->prepare("SELECT p.*,t.name tn,t.short_name ts,t.color_primary tc,t.logo tlogo,
  SUM(pa.action_type='Ace') aces,
  SUM(pa.action_type='Attack Kill') kills,
  SUM(pa.action_type='Block') blocks,
  SUM(pa.action_type='Dig') digs,
  SUM(pa.action_type='Set Assist') assists,
  SUM(pa.action_type='Reception') receptions
  FROM players p JOIN teams t ON t.id=p.team_id
  LEFT JOIN player_actions pa ON pa.player_id=p.id
  WHERE ".implode(' AND ',$w)." GROUP BY p.id ORDER BY t.name,p.jersey_number");
$s->execute($pm); $players=$s->fetchAll();

$positions=['Setter','Outside Hitter','Middle Blocker','Opposite Hitter','Libero','Defensive Specialist'];
$posIcons=['Setter'=>'bi-hand-index-thumb-fill','Outside Hitter'=>'bi-lightning-fill','Middle Blocker'=>'bi-shield-fill','Opposite Hitter'=>'bi-arrow-up-right-circle-fill','Libero'=>'bi-arrow-down-circle-fill','Defensive Specialist'=>'bi-asterisk'];

// Stats shown per position — Libero/DS never show blocks; Setter highlights assists
$posStats=[
  'Setter'             =>[['Assists','assists','var(--pur)'],['Digs','digs','var(--grn)'],['Aces','aces','var(--gld)'],['Kills','kills','var(--acc)']],
  'Outside Hitter'     =>[['Kills','kills','var(--acc)'],['Aces','aces','var(--gld)'],['Digs','digs','var(--grn)'],['Blocks','blocks','var(--pri)']],
  'Middle Blocker'     =>[['Blocks','blocks','var(--pri)'],['Kills','kills','var(--acc)'],['Aces','aces','var(--gld)'],['Digs','digs','var(--grn)']],
  'Opposite Hitter'    =>[['Kills','kills','var(--acc)'],['Blocks','blocks','var(--pri)'],['Aces','aces','var(--gld)'],['Digs','digs','var(--grn)']],
  'Libero'             =>[['Digs','digs','var(--grn)'],['Recept','receptions','var(--pri)'],['Aces','aces','var(--gld)'],['Assists','assists','var(--pur)']],
  'Defensive Specialist'=>[['Digs','digs','var(--grn)'],['Recept','receptions','var(--pri)'],['Aces','aces','var(--gld)'],['Kills','kills','var(--acc)']],
];
$defaultStats=[['Kills','kills','var(--acc)'],['Aces','aces','var(--gld)'],['Digs','digs','var(--grn)'],['Blocks','blocks','var(--pri)']];

// Tooltip description per position
$posDesc=[
  'Setter'             =>'Runs the offence — Set Assists, serve & receive',
  'Outside Hitter'     =>'Primary attacker — Kills, serve, pass & occasional block',
  'Middle Blocker'     =>'Net presence — Blocks & quick attacks',
  'Opposite Hitter'    =>'Right-side power — Kills & blocking',
  'Libero'             =>'Back-row defensive specialist — cannot block or attack above net',
  'Defensive Specialist'=>'Serve-receive & digging specialist — limited attack role',
];
include __DIR__.'/../../includes/header.php';
?>
<div class="content">
  <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px;margin-bottom:20px">
    <div>
      <h1 style="font-size:18px;font-weight:600">Players</h1>
      <div style="font-size:12px;color:var(--t3);margin-top:2px">Statistics shown are tailored per position</div>
    </div>
    <?php if(isLoggedIn()): ?><a href="<?= APP_URL ?>/admin/manage_players.php" class="btn btn-pri btn-sm"><i class="bi bi-person-plus"></i>Add Player</a><?php endif; ?>
  </div>

  <!-- Position legend -->
  <div style="display:flex;flex-wrap:wrap;gap:6px;margin-bottom:14px">
    <?php foreach($positions as $pos):
      $ico=$posIcons[$pos]??'bi-person';
      $desc=$posDesc[$pos]??'';
      $active=$pf===$pos;
    ?>
    <a href="?pos=<?= urlencode($pos) ?><?= $tf?"&team=$tf":'' ?><?= $sq?"&q=".urlencode($sq):'' ?>"
       title="<?= htmlspecialchars($desc) ?>"
       style="display:inline-flex;align-items:center;gap:5px;font-size:11px;font-weight:600;padding:4px 10px;border-radius:20px;text-decoration:none;transition:all var(--tr);
              <?= $active ? 'background:var(--t1);color:#fff;border:1px solid var(--t1)' : 'background:var(--s2);color:var(--t2);border:1px solid var(--b1)' ?>">
      <i class="bi <?= $ico ?>" style="font-size:11px"></i><?= $pos ?>
      <?php if($pos==='Libero'||$pos==='Defensive Specialist'): ?>
      <span style="font-size:9px;opacity:.7;font-weight:400">(no blocks)</span>
      <?php endif; ?>
    </a>
    <?php endforeach; ?>
    <?php if($pf): ?><a href="?" style="display:inline-flex;align-items:center;gap:4px;font-size:11px;color:var(--t3);padding:4px 8px;border-radius:20px;border:1px solid var(--b0);text-decoration:none"><i class="bi bi-x"></i>All</a><?php endif; ?>
  </div>

  <div style="background:var(--s1);border:1px solid var(--b0);border-radius:var(--r);padding:12px 14px;margin-bottom:16px">
    <form method="GET" style="display:flex;gap:8px;flex-wrap:wrap;align-items:flex-end">
      <div class="field" style="flex:1;min-width:160px"><label class="label">Search</label><div class="search-box"><i class="bi bi-search"></i><input type="text" name="q" value="<?= htmlspecialchars($sq) ?>" placeholder="Player name..."></div></div>
      <div class="field"><label class="label">Team</label><select name="team" class="select" style="width:160px"><option value="">All teams</option><?php foreach($teams as $t): ?><option value="<?= $t['id'] ?>" <?= $tf==$t['id']?'selected':'' ?>><?= htmlspecialchars($t['name']) ?></option><?php endforeach; ?></select></div>
      <div class="field"><label class="label">Position</label><select name="pos" class="select" style="width:160px"><option value="">All positions</option><?php foreach($positions as $pos): ?><option value="<?= $pos ?>" <?= $pf===$pos?'selected':'' ?>><?= $pos ?></option><?php endforeach; ?></select></div>
      <div style="display:flex;gap:6px"><button type="submit" class="btn btn-def btn-sm"><i class="bi bi-search"></i>Search</button><a href="?" class="btn btn-ghost btn-sm"><i class="bi bi-x"></i>Clear</a></div>
      <span style="font-size:12px;color:var(--t3);align-self:flex-end"><?= count($players) ?> player<?= count($players)!==1?'s':'' ?></span>
    </form>
  </div>

  <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(210px,1fr));gap:10px">
    <?php foreach($players as $p):
      $pos=$p['position'];
      $ico=$posIcons[$pos]??'bi-person';
      $stats=$posStats[$pos]??$defaultStats;
      $isLibero=($pos==='Libero'||$pos==='Defensive Specialist');
    ?>
    <div style="background:var(--s1);border:1px solid var(--b0);border-radius:var(--r);overflow:hidden;transition:box-shadow var(--tr),border-color var(--tr)" onmouseover="this.style.borderColor='var(--b1)';this.style.boxShadow='0 2px 12px rgba(0,0,0,.08)'" onmouseout="this.style.borderColor='var(--b0)';this.style.boxShadow=''">
      <div style="height:3px;background:<?= $p['tc'] ?>"></div>
      <div style="padding:12px">
        <div style="display:flex;align-items:flex-start;gap:10px;margin-bottom:10px">
          <!-- Player photo or initials avatar -->
          <div style="position:relative;flex-shrink:0">
            <?php if(!empty($p['photo'])): ?>
            <img src="<?= APP_URL ?>/assets/uploads/players/<?= htmlspecialchars($p['photo']) ?>" alt=""
                 style="width:48px;height:48px;border-radius:50%;object-fit:cover;border:2px solid <?= $p['tc'] ?>">
            <?php else: ?>
            <div style="width:48px;height:48px;border-radius:50%;background:<?= $p['tc'] ?>;display:flex;align-items:center;justify-content:center;font-size:16px;font-weight:700;color:#fff"><?= strtoupper(substr($p['first_name'],0,1)) ?></div>
            <?php endif; ?>
            <!-- Jersey number badge -->
            <div style="position:absolute;bottom:-3px;right:-3px;width:20px;height:20px;border-radius:50%;background:<?= $p['tc'] ?>;border:2px solid #fff;display:flex;align-items:center;justify-content:center;font-size:9px;font-weight:700;color:#fff"><?= $p['jersey_number'] ?></div>
          </div>
          <div style="flex:1;min-width:0">
            <div style="font-size:13px;font-weight:600;color:var(--t1);white-space:nowrap;overflow:hidden;text-overflow:ellipsis"><?= htmlspecialchars($p['first_name'].' '.$p['last_name']) ?></div>
            <div style="font-size:11px;color:var(--t2);display:flex;align-items:center;gap:3px;margin-top:2px">
              <i class="bi <?= $ico ?>" style="color:<?= $p['tc'] ?>;font-size:10px"></i><?= $pos ?>
              <?php if($isLibero): ?><i class="bi bi-shield-slash" style="color:var(--t3);font-size:10px;margin-left:2px" title="No blocking"></i><?php endif; ?>
            </div>
            <!-- Team badge with logo -->
            <div style="display:flex;align-items:center;gap:4px;margin-top:5px">
              <?php if(!empty($p['tlogo'])): ?>
              <img src="<?= APP_URL ?>/assets/uploads/teams/<?= htmlspecialchars($p['tlogo']) ?>" alt=""
                   style="width:14px;height:14px;object-fit:contain">
              <?php else: ?>
              <div style="width:10px;height:10px;border-radius:50%;background:<?= $p['tc'] ?>;flex-shrink:0"></div>
              <?php endif; ?>
              <span style="font-size:10px;font-weight:600;color:<?= $p['tc'] ?>"><?= htmlspecialchars($p['ts']) ?></span>
            </div>
          </div>
        </div>
        <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:4px">
          <?php foreach($stats as [$sl,$sk,$sc]): $sv=(int)($p[$sk]??0); ?>
          <div style="background:var(--s2);border-radius:4px;padding:5px 2px;text-align:center">
            <div style="font-size:14px;font-weight:700;color:<?= $sc ?>;line-height:1"><?= $sv ?></div>
            <div style="font-size:9px;color:var(--t3);text-transform:uppercase;letter-spacing:.03em;margin-top:2px"><?= $sl ?></div>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
    <?php endforeach; ?>
    <?php if(empty($players)): ?>
    <div style="grid-column:1/-1;background:var(--s1);border:1px solid var(--b0);border-radius:var(--r);padding:40px;text-align:center;color:var(--t3)">
      <i class="bi bi-people" style="font-size:32px;display:block;margin-bottom:8px;opacity:.3"></i>
      <div style="font-size:14px;color:var(--t2)">No players found</div>
    </div>
    <?php endif; ?>
  </div>
</div>
<?php include __DIR__.'/../../includes/footer.php'; ?>
