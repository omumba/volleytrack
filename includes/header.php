<?php
$currentPage = $currentPage ?? '';
$pageTitle   = $pageTitle ?? APP_NAME;
$db = getDB();
$liveMatches = (int)$db->query("SELECT COUNT(*) FROM matches WHERE status='Live'")->fetchColumn();
try { $liveStreams=(int)$db->query("SELECT COUNT(*) FROM stream_cameras WHERE status='Live'")->fetchColumn(); } catch(Exception $e){$liveStreams=0;}
$cu = currentUser();

$navLinks = [
  ['home',       '',                             'bi-house',          'Dashboard'],
  ['scoreboard', 'modules/scoreboard/index.php', 'bi-display',        'Scoreboard'],
  ['stats',      'modules/stats/index.php',      'bi-bar-chart-line', 'Stats'],
  ['fixtures',   'modules/fixtures/index.php',   'bi-calendar3',      'Fixtures'],
  ['tables',     'modules/tables/index.php',     'bi-bar-chart-steps','League Table'],
  ['players',    'modules/players/index.php',    'bi-people',         'Players'],
  ['news',       'modules/news/index.php',       'bi-newspaper',      'News'],
  ['streaming',  'modules/streaming/index.php',           'bi-broadcast',   'Live Stream'],
  ['replays',    'modules/streaming/index.php?tab=replays', 'bi-play-circle', 'Replays'],
];

// [page-key, href, icon, label, minRole]
$allAdminLinks = [
  ['score_entry',    'admin/score_entry.php',   'bi-pencil-square', 'Score Entry',     'Scorer'],
  ['admin',          'admin/index.php',          'bi-speedometer2',  'Admin Panel',     'Referee'],
  ['manage_match',   'admin/manage_match.php',   'bi-calendar-plus', 'Manage Fixtures', 'Referee'],
  ['manage_streams', 'admin/manage_streams.php', 'bi-broadcast',     'Manage Streams',  'Referee'],
  ['manage_teams',   'admin/manage_teams.php',   'bi-shield',        'Manage Teams',    'Admin'],
  ['manage_players', 'admin/manage_players.php', 'bi-people',        'Manage Players',  'Admin'],
  ['manage_news',    'admin/manage_news.php',    'bi-newspaper',     'Manage News',     'Admin'],
];
// Filter to links the current user is allowed to see
$adminLinks = array_filter($allAdminLinks, fn($l) => hasRole($l[4]));

$isAdminPage = in_array($currentPage, array_column($allAdminLinks, 0)) || $currentPage === 'users';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
  <title><?= htmlspecialchars($pageTitle) ?> — VolleyTrack</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <meta name="color-scheme" content="light">
  <link href="<?= APP_URL ?>/assets/css/app.css?v=<?= filemtime(__DIR__.'/../assets/css/app.css') ?>" rel="stylesheet">
</head>
<body>

<!-- ═══ TOP NAVBAR ═══ -->
<nav class="site-nav">
  <div class="nav-inner">

    <!-- Brand -->
    <a href="<?= APP_URL ?>/" class="nav-brand">
      <div class="brand-icon"><i class="bi bi-trophy-fill"></i></div>
      <div class="brand-text">
        <span class="brand-name">VolleyTrack</span>
        <span class="brand-sub">Malawi Volleyball</span>
      </div>
    </a>

    <!-- Desktop nav links -->
    <div class="nav-links" id="navLinks">
      <?php
      $isReplaysTab = ($currentPage==='streaming' && ($_GET['tab']??'')==='replays');
      foreach($navLinks as [$pg,$href,$ic,$lbl]):
        $a = $pg==='replays' ? $isReplaysTab : ($currentPage===$pg && !($pg==='streaming' && $isReplaysTab));
      ?>
      <a href="<?= APP_URL ?>/<?= $href ?>" class="nav-link <?= $a?'active':'' ?>">
        <?php if($pg==='streaming'&&$liveStreams>0): ?><span class="nav-pulse"></span><?php endif; ?>
        <?= $lbl ?>
        <?php if($pg==='scoreboard'&&$liveMatches>0): ?><span class="nav-badge"><?= $liveMatches ?></span><?php endif; ?>
      </a>
      <?php endforeach; ?>

      <?php if(isLoggedIn()): ?>
      <div class="nav-drop" id="adminDrop">
        <button class="nav-link nav-drop-btn <?= $isAdminPage?'active':'' ?>" onclick="toggleDrop('adminDrop')">
          <i class="bi bi-speedometer2" style="font-size:12px"></i>
          Admin
          <i class="bi bi-chevron-down nav-caret"></i>
        </button>
        <div class="nav-drop-menu">
          <?php foreach($adminLinks as [$pg,$href,$ic,$lbl,$minRole]): $a=$currentPage===$pg; ?>
          <a href="<?= APP_URL ?>/<?= $href ?>" class="nav-drop-item <?= $a?'is-active':'' ?>">
            <i class="bi <?= $ic ?>"></i><?= $lbl ?>
          </a>
          <?php endforeach; ?>
          <?php if(hasRole('Admin')): ?>
          <div class="drop-sep"></div>
          <a href="<?= APP_URL ?>/admin/manage_users.php" class="nav-drop-item <?= $currentPage==='users'?'is-active':'' ?>">
            <i class="bi bi-person-badge"></i>Manage Users
          </a>
          <?php endif; ?>
        </div>
      </div>
      <?php endif; ?>
    </div>

    <!-- Right: live chip + user + toggle -->
    <div class="nav-end">
      <?php if($liveMatches>0): ?>
      <a href="<?= APP_URL ?>/modules/scoreboard/index.php" class="live-chip">
        <span class="live-dot"></span><?= $liveMatches ?> Live
      </a>
      <?php endif; ?>

      <?php if($cu): ?>
      <div class="nav-drop" id="userDrop">
        <button class="nav-user-btn nav-drop-btn" onclick="toggleDrop('userDrop')">
          <span class="nav-avatar"><?= strtoupper(substr($cu['name'],0,1)) ?></span>
          <span class="nav-uname"><?= htmlspecialchars(explode(' ',$cu['name'])[0]) ?></span>
          <i class="bi bi-chevron-down nav-caret"></i>
        </button>
        <div class="nav-drop-menu nav-drop-end">
          <div class="drop-user-head">
            <div class="drop-user-name"><?= htmlspecialchars($cu['name']) ?></div>
            <div class="drop-user-role"><?= $cu['role'] ?></div>
          </div>
          <div class="drop-sep"></div>
          <a href="<?= APP_URL ?>/admin/index.php" class="nav-drop-item"><i class="bi bi-speedometer2"></i>Admin Panel</a>
          <div class="drop-sep"></div>
          <a href="<?= APP_URL ?>/logout.php" class="nav-drop-item nav-drop-danger"><i class="bi bi-box-arrow-right"></i>Sign Out</a>
        </div>
      </div>
      <?php else: ?>
      <a href="<?= APP_URL ?>/login.php" class="btn-signin">Sign In</a>
      <?php endif; ?>

      <button class="nav-toggle" id="navToggle" aria-label="Toggle menu">
        <i class="bi bi-list" id="toggleIcon"></i>
      </button>
    </div>
  </div>

  <!-- Mobile menu panel -->
  <div class="mobile-nav" id="mobileNav">
    <div class="mob-section">Navigation</div>
    <?php foreach($navLinks as [$pg,$href,$ic,$lbl]):
      $a = $pg==='replays' ? $isReplaysTab : ($currentPage===$pg && !($pg==='streaming' && $isReplaysTab));
    ?>
    <a href="<?= APP_URL ?>/<?= $href ?>" class="mob-link <?= $a?'active':'' ?>">
      <i class="bi <?= $ic ?>"></i><?= $lbl ?>
      <?php if($pg==='streaming'&&$liveStreams>0): ?><span class="mob-live">LIVE</span><?php endif; ?>
    </a>
    <?php endforeach; ?>

    <?php if(isLoggedIn()): ?>
    <div class="mob-section">Admin</div>
    <?php foreach($adminLinks as [$pg,$href,$ic,$lbl,$minRole]): $a=$currentPage===$pg; ?>
    <a href="<?= APP_URL ?>/<?= $href ?>" class="mob-link <?= $a?'active':'' ?>">
      <i class="bi <?= $ic ?>"></i><?= $lbl ?>
    </a>
    <?php endforeach; ?>
    <?php if(hasRole('Admin')): ?>
    <a href="<?= APP_URL ?>/admin/manage_users.php" class="mob-link <?= $currentPage==='users'?'active':'' ?>">
      <i class="bi bi-person-badge"></i>Manage Users
    </a>
    <?php endif; ?>
    <div class="mob-section">Account</div>
    <a href="<?= APP_URL ?>/logout.php" class="mob-link mob-danger"><i class="bi bi-box-arrow-right"></i>Sign Out</a>
    <?php else: ?>
    <div class="mob-section">Account</div>
    <a href="<?= APP_URL ?>/login.php" class="mob-link"><i class="bi bi-box-arrow-in-right"></i>Sign In</a>
    <?php endif; ?>
  </div>
</nav>
<!-- ═══════════════════ -->

<div class="page-wrap">
