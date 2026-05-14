<?php
require_once __DIR__.'/../../includes/config.php';
$currentPage='news'; $pageTitle='News'; $db=getDB();
$cat=$_GET['cat']??''; $pg=max(1,(int)($_GET['page']??1)); $pp=12;
$cats=$db->query("SELECT DISTINCT category FROM news_articles WHERE status='Published' ORDER BY category")->fetchAll(PDO::FETCH_COLUMN);
$w="status='Published'"; $p=[];
if($cat){$w.=" AND category=?";$p[]=$cat;}
$total=(int)$db->prepare("SELECT COUNT(*) FROM news_articles WHERE $w")->execute($p)&&$db->query("SELECT COUNT(*) FROM news_articles WHERE $w".($p?" AND category='$cat'":""))->fetchColumn();
$stmt=$db->prepare("SELECT na.*,u.full_name an FROM news_articles na LEFT JOIN users u ON u.id=na.author_id WHERE na.status='Published'".($cat?" AND na.category=?":"")." ORDER BY na.published_at DESC LIMIT $pp OFFSET ".(($pg-1)*$pp));
$stmt->execute($cat?[$cat]:[]);$articles=$stmt->fetchAll();
$featured=$db->query("SELECT na.*,u.full_name an FROM news_articles na LEFT JOIN users u ON u.id=na.author_id WHERE na.is_featured=1 AND na.status='Published' ORDER BY na.published_at DESC LIMIT 1")->fetch();
$catC=['Match Report'=>'var(--acc)','Analysis'=>'var(--pri)','General'=>'var(--grn)','Tournament'=>'var(--gld)','Interview'=>'var(--pur)','Transfer'=>'var(--acc)'];
include __DIR__.'/../../includes/header.php';
?>
<div class="content">
  <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px;margin-bottom:20px">
    <h1 style="font-size:18px;font-weight:600">News</h1>
    <?php if(isLoggedIn()): ?><a href="<?= APP_URL ?>/admin/manage_news.php?view=edit" class="btn btn-pri btn-sm"><i class="bi bi-pencil-square"></i>Write Article</a><?php endif; ?>
  </div>

  <?php if($featured&&!$cat): ?>
  <a href="<?= APP_URL ?>/modules/news/article.php?slug=<?= urlencode($featured['slug']) ?>" style="display:block;background:var(--s1);border:1px solid var(--b0);border-radius:var(--r);overflow:hidden;text-decoration:none;margin-bottom:20px;transition:border-color var(--tr)" onmouseover="this.style.borderColor='var(--b1)'" onmouseout="this.style.borderColor='var(--b0)'">
    <div style="display:grid;grid-template-columns:<?= $featured['featured_image']?'1fr 360px':'1fr' ?>">
      <div style="padding:20px 24px;display:flex;flex-direction:column;justify-content:center">
        <div style="font-size:11px;font-weight:600;color:<?= $catC[$featured['category']]??'var(--t2)' ?>;text-transform:uppercase;letter-spacing:.04em;margin-bottom:8px"><?= $featured['category'] ?></div>
        <div style="font-size:18px;font-weight:700;color:var(--t1);line-height:1.3;margin-bottom:10px"><?= htmlspecialchars($featured['title']) ?></div>
        <div style="font-size:13px;color:var(--t2);line-height:1.5;margin-bottom:12px;display:-webkit-box;-webkit-line-clamp:3;-webkit-box-orient:vertical;overflow:hidden"><?= htmlspecialchars(strip_tags($featured['summary']??'')) ?></div>
        <div style="font-size:11px;color:var(--t3)"><?= $featured['an']??'Staff' ?> · <?= date('d M Y',strtotime($featured['published_at'])) ?> · <?= number_format($featured['views']) ?> views</div>
      </div>
      <?php if($featured['featured_image']): ?>
      <div style="overflow:hidden"><img src="<?= APP_URL ?>/assets/uploads/news/<?= htmlspecialchars($featured['featured_image']) ?>" style="width:100%;height:100%;object-fit:cover;display:block"></div>
      <?php endif; ?>
    </div>
  </a>
  <?php endif; ?>

  <!-- Category filter -->
  <div style="display:flex;gap:6px;flex-wrap:wrap;margin-bottom:16px">
    <a href="?" class="btn <?= !$cat?'btn-pri':'btn-ghost' ?> btn-sm">All</a>
    <?php foreach($cats as $c): ?><a href="?cat=<?= urlencode($c) ?>" class="btn <?= $cat===$c?'btn-pri':'btn-ghost' ?> btn-sm" style="<?= $cat===$c?'':'color:'.$catC[$c]??'var(--t2)' ?>"><?= htmlspecialchars($c) ?></a><?php endforeach; ?>
  </div>

  <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:10px">
    <?php foreach($articles as $a): $c=$catC[$a['category']]??'var(--t2)'; ?>
    <a href="<?= APP_URL ?>/modules/news/article.php?slug=<?= urlencode($a['slug']) ?>" class="news-card">
      <div class="news-thumb">
        <?php if($a['featured_image']): ?><img src="<?= APP_URL ?>/assets/uploads/news/<?= htmlspecialchars($a['featured_image']) ?>" alt="<?= htmlspecialchars($a['title']) ?>">
        <?php else: ?><div style="display:flex;align-items:center;justify-content:center;width:100%;height:100%;min-height:120px;background:var(--s3)"><i class="bi bi-newspaper" style="font-size:32px;color:var(--t3);opacity:.4"></i></div><?php endif; ?>
      </div>
      <div class="news-body">
        <div class="news-cat" style="color:<?= $c ?>"><?= htmlspecialchars($a['category']) ?></div>
        <div class="news-title"><?= htmlspecialchars($a['title']) ?></div>
        <?php if($a['summary']): ?><div class="news-summary"><?= htmlspecialchars(strip_tags($a['summary'])) ?></div><?php endif; ?>
        <div class="news-meta">
          <span><i class="bi bi-person me-1"></i><?= htmlspecialchars($a['an']??'Staff') ?></span>
          <span><?= date('d M Y',strtotime($a['published_at'])) ?></span>
          <span><i class="bi bi-eye me-1"></i><?= number_format($a['views']) ?></span>
        </div>
      </div>
    </a>
    <?php endforeach; ?>
    <?php if(empty($articles)): ?><div style="grid-column:1/-1;background:var(--s1);border:1px solid var(--b0);border-radius:var(--r);padding:40px;text-align:center;color:var(--t3)"><i class="bi bi-newspaper" style="font-size:32px;display:block;margin-bottom:8px;opacity:.3"></i><div style="font-size:14px;color:var(--t2)">No articles found</div></div><?php endif; ?>
  </div>
</div>
<?php include __DIR__.'/../../includes/footer.php'; ?>
