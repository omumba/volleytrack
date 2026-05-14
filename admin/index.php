<?php
require_once __DIR__.'/../includes/config.php';
requireRole('Referee'); $currentPage='admin'; $pageTitle='Admin Panel'; $db=getDB();
$s=['teams'=>$db->query("SELECT COUNT(*) FROM teams WHERE is_active=1")->fetchColumn(),'players'=>$db->query("SELECT COUNT(*) FROM players WHERE is_active=1")->fetchColumn(),'matches'=>$db->query("SELECT COUNT(*) FROM matches")->fetchColumn(),'live'=>$db->query("SELECT COUNT(*) FROM matches WHERE status='Live'")->fetchColumn(),'news'=>$db->query("SELECT COUNT(*) FROM news_articles WHERE status='Published'")->fetchColumn(),'users'=>$db->query("SELECT COUNT(*) FROM users WHERE is_active=1")->fetchColumn()];
if(isset($_GET['ok'])&&$_GET['ok']==='standings'){echo '<meta http-equiv="refresh" content="0;url='.APP_URL.'/admin/index.php">';}
$log=$db->query("SELECT al.*,u.full_name FROM activity_log al LEFT JOIN users u ON u.id=al.user_id ORDER BY al.created_at DESC LIMIT 15")->fetchAll();
include __DIR__.'/../includes/header.php';
?>
<div class="content">
  <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px;margin-bottom:20px">
    <h1 style="font-size:18px;font-weight:600">Admin Panel</h1>
    <span style="font-size:12px;color:var(--t3)"><?= date('l, d F Y') ?></span>
  </div>

  <div style="display:grid;grid-template-columns:repeat(6,1fr);gap:10px;margin-bottom:20px">
    <?php foreach([['Teams',$s['teams'],'bi-shield'],['Players',$s['players'],'bi-people'],['Matches',$s['matches'],'bi-calendar3'],['Live',$s['live'],'bi-broadcast'],['Articles',$s['news'],'bi-newspaper'],['Users',$s['users'],'bi-person-badge']] as [$l,$v,$i]): ?>
    <div class="stat-card">
      <div style="display:flex;align-items:center;justify-content:space-between">
        <div><div class="stat-val"><?= $v ?></div><div class="stat-lbl"><?= $l ?></div></div>
        <i class="bi <?= $i ?>" style="font-size:18px;color:var(--t3)"></i>
      </div>
    </div>
    <?php endforeach; ?>
  </div>

  <div style="display:grid;grid-template-columns:1fr 340px;gap:16px">
    <div>
      <div style="font-size:11px;font-weight:600;color:var(--t3);text-transform:uppercase;letter-spacing:.04em;margin-bottom:10px">Management</div>
      <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:10px">
        <?php
        // [label, icon, href, description, minRole]
        $tiles=[
          ['Score Entry',  'bi-pencil-square',   'admin/score_entry.php',           'Record live match scores and player actions','Scorer'],
          ['Matches',      'bi-calendar3',        'admin/manage_match.php',          'Add and manage fixtures',                   'Referee'],
          ['Live Streams', 'bi-broadcast',        'admin/manage_streams.php',        'Configure cameras and streaming',           'Referee'],
          ['Teams',        'bi-shield',           'admin/manage_teams.php',          'Manage clubs and team info',                'Admin'],
          ['Players',      'bi-people',           'admin/manage_players.php',        'Player roster management',                  'Admin'],
          ['News',         'bi-newspaper',        'admin/manage_news.php',           'Write and publish articles',                'Admin'],
          ['Seasons',      'bi-trophy',           'admin/manage_seasons.php',        'Manage league seasons',                     'Admin'],
          ['Standings',    'bi-arrow-clockwise',  'admin/recalculate_standings.php', 'Recalculate league table',                  'Admin'],
          ['Users',        'bi-person-badge',     'admin/manage_users.php',          'Manage accounts and roles',                 'Admin'],
        ];
        foreach($tiles as [$lbl,$ic,$href,$desc,$minRole]):
          if(!hasRole($minRole)) continue;
        ?>
        <a href="<?= APP_URL ?>/<?= $href ?>"
           style="background:var(--s1);border:1px solid var(--b0);border-radius:var(--r);padding:14px;text-decoration:none;display:block;transition:border-color var(--tr),background var(--tr)"
           onmouseover="this.style.borderColor='var(--b1)';this.style.background='var(--s2)'"
           onmouseout="this.style.borderColor='var(--b0)';this.style.background='var(--s1)'">
          <i class="bi <?= $ic ?>" style="font-size:18px;color:var(--t2);display:block;margin-bottom:8px"></i>
          <div style="font-size:13px;font-weight:600;color:var(--t1);margin-bottom:3px"><?= $lbl ?></div>
          <div style="font-size:11px;color:var(--t3);line-height:1.4"><?= $desc ?></div>
        </a>
        <?php endforeach; ?>
      </div>
    </div>

    <div>
      <div style="font-size:11px;font-weight:600;color:var(--t3);text-transform:uppercase;letter-spacing:.04em;margin-bottom:10px">Activity Log</div>
      <div class="card">
        <?php foreach($log as $l): ?>
        <div style="display:flex;gap:10px;padding:9px 14px;border-bottom:1px solid var(--b0)">
          <i class="bi bi-activity" style="font-size:13px;color:var(--t3);margin-top:2px;flex-shrink:0"></i>
          <div style="flex:1;min-width:0">
            <div style="font-size:12px;color:var(--t1);white-space:nowrap;overflow:hidden;text-overflow:ellipsis"><?= htmlspecialchars($l['action']) ?></div>
            <div style="font-size:11px;color:var(--t3)"><?= htmlspecialchars($l['full_name']??'System') ?> · <?= date('d M H:i',strtotime($l['created_at'])) ?></div>
          </div>
        </div>
        <?php endforeach; ?>
        <?php if(empty($log)): ?><div style="padding:20px;text-align:center;color:var(--t3);font-size:12px">No activity yet</div><?php endif; ?>
      </div>
    </div>
  </div>
</div>
<?php include __DIR__.'/../includes/footer.php'; ?>
