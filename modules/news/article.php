<?php
require_once __DIR__.'/../../includes/config.php';
$slug=$_GET['slug']??'';
if(!$slug){header('Location:'.APP_URL.'/modules/news/index.php');exit;}
$db=getDB();
$s=$db->prepare("SELECT na.*,u.full_name an FROM news_articles na LEFT JOIN users u ON u.id=na.author_id WHERE na.slug=? AND na.status='Published'");
$s->execute([$slug]);$art=$s->fetch();
if(!$art){http_response_code(404);$currentPage='news';$pageTitle='Not Found';include __DIR__.'/../../includes/header.php';echo '<div class="content" style="padding:60px 24px;text-align:center"><i class="bi bi-newspaper" style="font-size:40px;color:var(--t3);display:block;margin-bottom:12px;opacity:.3"></i><h2 style="color:var(--t2)">Article not found</h2><a href="'.APP_URL.'/modules/news/index.php" class="btn btn-ghost btn-sm" style="margin-top:12px">Back to News</a></div>';include __DIR__.'/../../includes/footer.php';exit;}
$db->prepare("UPDATE news_articles SET views=views+1 WHERE id=?")->execute([$art['id']]);
$related=$db->prepare("SELECT id,title,slug,category,published_at,featured_image FROM news_articles WHERE status='Published' AND id!=? AND category=? ORDER BY published_at DESC LIMIT 3");
$related->execute([$art['id'],$art['category']]);$related=$related->fetchAll();
$currentPage='news'; $pageTitle=htmlspecialchars($art['title']);
$catC=['Match Report'=>'var(--acc)','Analysis'=>'var(--pri)','General'=>'var(--grn)','Tournament'=>'var(--gld)','Interview'=>'var(--pur)','Transfer'=>'var(--acc)'];
include __DIR__.'/../../includes/header.php';
?>
<div class="content">
  <div style="max-width:800px">
    <div style="margin-bottom:16px">
      <a href="<?= APP_URL ?>/modules/news/index.php" style="font-size:12px;color:var(--t3);text-decoration:none;display:inline-flex;align-items:center;gap:4px" onmouseover="this.style.color='var(--t1)'" onmouseout="this.style.color='var(--t3)'">
        <i class="bi bi-arrow-left"></i>Back to News
      </a>
    </div>

    <?php if($art['featured_image']): ?>
    <div style="border-radius:var(--r);overflow:hidden;margin-bottom:20px;aspect-ratio:16/9">
      <img src="<?= APP_URL ?>/assets/uploads/news/<?= htmlspecialchars($art['featured_image']) ?>" alt="" style="width:100%;height:100%;object-fit:cover;display:block">
    </div>
    <?php endif; ?>

    <div style="font-size:11px;font-weight:600;color:<?= $catC[$art['category']]??'var(--t2)' ?>;text-transform:uppercase;letter-spacing:.04em;margin-bottom:10px"><?= htmlspecialchars($art['category']) ?></div>
    <h1 style="font-size:24px;font-weight:700;line-height:1.2;margin-bottom:12px;color:var(--t1)"><?= htmlspecialchars($art['title']) ?></h1>
    <div style="display:flex;align-items:center;gap:14px;font-size:12px;color:var(--t3);margin-bottom:20px;padding-bottom:16px;border-bottom:1px solid var(--b0)">
      <span><i class="bi bi-person me-1"></i><?= htmlspecialchars($art['an']??'Staff') ?></span>
      <span><i class="bi bi-calendar3 me-1"></i><?= date('d F Y',strtotime($art['published_at'])) ?></span>
      <span><i class="bi bi-eye me-1"></i><?= number_format($art['views']) ?> views</span>
      <?php if($art['tags']): ?><span><i class="bi bi-tags me-1"></i><?= htmlspecialchars($art['tags']) ?></span><?php endif; ?>
    </div>

    <?php if($art['summary']): ?>
    <div style="font-size:15px;font-weight:500;color:var(--t2);line-height:1.6;margin-bottom:20px;padding:14px 16px;background:var(--s2);border-radius:var(--r);border-left:3px solid var(--acc)"><?= htmlspecialchars($art['summary']) ?></div>
    <?php endif; ?>

    <div class="article-body"><?= $art['content'] ?></div>

    <?php if(isLoggedIn()): ?>
    <div style="margin-top:20px;padding-top:16px;border-top:1px solid var(--b0);display:flex;gap:8px">
      <a href="<?= APP_URL ?>/admin/manage_news.php?view=edit&id=<?= $art['id'] ?>" class="btn btn-ghost btn-sm"><i class="bi bi-pencil"></i>Edit Article</a>
    </div>
    <?php endif; ?>
  </div>

  <?php if(!empty($related)): ?>
  <div style="margin-top:32px;padding-top:20px;border-top:1px solid var(--b0)">
    <div style="font-size:13px;font-weight:600;color:var(--t2);margin-bottom:12px">More <?= htmlspecialchars($art['category']) ?></div>
    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:8px">
      <?php foreach($related as $r): $c=$catC[$r['category']]??'var(--t2)'; ?>
      <a href="<?= APP_URL ?>/modules/news/article.php?slug=<?= urlencode($r['slug']) ?>" class="news-card">
        <div class="news-thumb">
          <?php if($r['featured_image']): ?><img src="<?= APP_URL ?>/assets/uploads/news/<?= htmlspecialchars($r['featured_image']) ?>" alt="">
          <?php else: ?><div style="display:flex;align-items:center;justify-content:center;min-height:100px;background:var(--s3)"><i class="bi bi-newspaper" style="font-size:24px;color:var(--t3);opacity:.4"></i></div><?php endif; ?>
        </div>
        <div class="news-body" style="padding:10px">
          <div class="news-cat" style="color:<?= $c ?>"><?= htmlspecialchars($r['category']) ?></div>
          <div class="news-title" style="font-size:12px"><?= htmlspecialchars($r['title']) ?></div>
          <div class="news-meta" style="margin-top:6px;padding-top:6px"><span><?= date('d M Y',strtotime($r['published_at'])) ?></span></div>
        </div>
      </a>
      <?php endforeach; ?>
    </div>
  </div>
  <?php endif; ?>
</div>
<?php include __DIR__.'/../../includes/footer.php'; ?>
