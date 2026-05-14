</div><!-- /page-wrap -->

<!-- ═══ FOOTER ═══ -->
<footer class="site-footer">
  <div class="footer-inner">
    <div class="footer-col footer-brand">
      <div class="footer-logo">
        <div class="brand-icon"><i class="bi bi-trophy-fill"></i></div>
        <span class="brand-name">VolleyTrack</span>
      </div>
      <p>The official scoring, statistics, and live streaming platform for the Malawi Volleyball League.</p>
      <div class="footer-badges">
        <span><i class="bi bi-shield-check"></i> MVL Official</span>
        <span><i class="bi bi-broadcast"></i> Live Streaming</span>
      </div>
    </div>

    <div class="footer-col">
      <h4>Quick Links</h4>
      <a href="<?= APP_URL ?>/" class="footer-link"><i class="bi bi-house"></i>Dashboard</a>
      <a href="<?= APP_URL ?>/modules/scoreboard/index.php" class="footer-link"><i class="bi bi-display"></i>Scoreboard</a>
      <a href="<?= APP_URL ?>/modules/fixtures/index.php" class="footer-link"><i class="bi bi-calendar3"></i>Fixtures</a>
      <a href="<?= APP_URL ?>/modules/tables/index.php" class="footer-link"><i class="bi bi-bar-chart-steps"></i>League Table</a>
    </div>

    <div class="footer-col">
      <h4>More</h4>
      <a href="<?= APP_URL ?>/modules/players/index.php" class="footer-link"><i class="bi bi-people"></i>Players</a>
      <a href="<?= APP_URL ?>/modules/news/index.php" class="footer-link"><i class="bi bi-newspaper"></i>News</a>
      <a href="<?= APP_URL ?>/modules/streaming/index.php" class="footer-link"><i class="bi bi-broadcast"></i>Live Stream</a>
      <?php if(hasRole('Scorer')): ?>
      <a href="<?= APP_URL ?>/admin/score_entry.php" class="footer-link"><i class="bi bi-pencil-square"></i>Score Entry</a>
      <?php endif; ?>
      <?php if(hasRole('Referee')): ?>
      <a href="<?= APP_URL ?>/admin/index.php" class="footer-link"><i class="bi bi-speedometer2"></i>Admin Panel</a>
      <?php endif; ?>
    </div>

    <div class="footer-col">
      <h4>Season 2025</h4>
      <?php
        try {
          $db = getDB();
          $ft = $db->query("SELECT m.match_date, ht.short_name hn, at.short_name an FROM matches m JOIN teams ht ON ht.id=m.home_team_id JOIN teams at ON at.id=m.away_team_id WHERE m.status='Scheduled' ORDER BY m.match_date ASC LIMIT 3")->fetchAll();
          foreach($ft as $m):
      ?>
      <div class="footer-fixture">
        <span class="fx-date"><?= date('d M',strtotime($m['match_date'])) ?></span>
        <span class="fx-teams"><?= htmlspecialchars($m['hn']) ?> vs <?= htmlspecialchars($m['an']) ?></span>
      </div>
      <?php endforeach; } catch(Exception $e){} ?>
      <a href="<?= APP_URL ?>/modules/fixtures/index.php" class="footer-link" style="margin-top:8px"><i class="bi bi-arrow-right-circle"></i>All Fixtures</a>
    </div>
  </div>

  <div class="footer-bottom">
    <div class="footer-bottom-inner">
      <span>© <?= date('Y') ?> VolleyTrack · Malawi Volleyball League. All rights reserved.</span>
      <div class="footer-bottom-links">
        <a href="<?= APP_URL ?>/modules/news/index.php">News</a>
        <a href="<?= APP_URL ?>/modules/streaming/index.php">Live Stream</a>
        <?php if(!isLoggedIn()): ?><a href="<?= APP_URL ?>/login.php">Admin Login</a><?php endif; ?>
      </div>
    </div>
  </div>
</footer>
<!-- ═══════════════ -->

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= APP_URL ?>/assets/js/app.js"></script>
</body>
</html>
