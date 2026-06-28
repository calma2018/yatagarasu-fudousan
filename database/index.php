<?php
require_once __DIR__ . '/config.php';

// 検索・絞り込み
$where  = [];
$params = [];

if (!empty($_GET['eki'])) {
    $where[]        = 'moyori_eki LIKE :eki';
    $params[':eki'] = '%' . $_GET['eki'] . '%';
}
if (!empty($_GET['category'])) {
    $where[]             = 'category = :category';
    $params[':category'] = $_GET['category'];
}
if (!empty($_GET['yachin_max'])) {
    $where[]               = 'yachin <= :yachin_max';
    $params[':yachin_max'] = (int)$_GET['yachin_max'];
}

$sql = 'SELECT * FROM bukken';
if ($where) {
    $sql .= ' WHERE ' . implode(' AND ', $where);
}
$sql .= ' ORDER BY id DESC';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$bukken_list = $stmt->fetchAll();

// カテゴリー一覧（絞り込み用）
$cat_stmt = $pdo->query('SELECT DISTINCT category FROM bukken WHERE category IS NOT NULL ORDER BY category');
$categories = $cat_stmt->fetchAll(PDO::FETCH_COLUMN);
?>
<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>物件一覧 | 八咫烏不動産</title>
  <style>
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body { font-family: 'Noto Sans JP', sans-serif; background: #f5f5f5; color: #333; }
    header { background: #1a1a2e; color: #fff; padding: 16px 24px; }
    header h1 { font-size: 1.4rem; }
    .container { max-width: 1100px; margin: 0 auto; padding: 24px 16px; }

    /* 検索フォーム */
    .search-box { background: #fff; border-radius: 8px; padding: 20px; margin-bottom: 24px; box-shadow: 0 1px 4px rgba(0,0,0,.1); }
    .search-box h2 { font-size: 1rem; margin-bottom: 12px; color: #555; }
    .search-row { display: flex; gap: 12px; flex-wrap: wrap; }
    .search-row input, .search-row select { padding: 8px 12px; border: 1px solid #ccc; border-radius: 4px; font-size: 0.9rem; }
    .search-row button { padding: 8px 20px; background: #1a1a2e; color: #fff; border: none; border-radius: 4px; cursor: pointer; }
    .search-row button:hover { background: #2e2e5e; }

    /* 件数 */
    .count { margin-bottom: 12px; color: #666; font-size: 0.9rem; }

    /* 物件カード */
    .bukken-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 16px; }
    .bukken-card { background: #fff; border-radius: 8px; box-shadow: 0 1px 4px rgba(0,0,0,.1); overflow: hidden; transition: box-shadow .2s; }
    .bukken-card:hover { box-shadow: 0 4px 12px rgba(0,0,0,.15); }
    .card-header { background: #1a1a2e; color: #fff; padding: 10px 14px; font-size: 0.8rem; display: flex; justify-content: space-between; }
    .card-body { padding: 14px; }
    .card-title { font-size: 1.05rem; font-weight: bold; margin-bottom: 8px; }
    .card-info { font-size: 0.85rem; color: #555; line-height: 1.7; }
    .card-price { font-size: 1.2rem; font-weight: bold; color: #c0392b; margin: 10px 0; }
    .card-footer { padding: 10px 14px; border-top: 1px solid #eee; text-align: right; }
    .btn-detail { display: inline-block; padding: 6px 16px; background: #1a1a2e; color: #fff; text-decoration: none; border-radius: 4px; font-size: 0.85rem; }
    .btn-detail:hover { background: #2e2e5e; }
    .category-badge { display: inline-block; padding: 2px 8px; border-radius: 12px; font-size: 0.75rem; background: #e8f4f8; color: #1a6e9e; }
    .no-result { text-align: center; color: #999; padding: 60px 0; }
  </style>
</head>
<body>
<header>
  <h1>🏢 八咫烏不動産 物件一覧</h1>
</header>
<div class="container">

  <!-- 検索フォーム -->
  <div class="search-box">
    <h2>絞り込み検索</h2>
    <form method="get" action="">
      <div class="search-row">
        <input type="text" name="eki" placeholder="駅名で検索" value="<?= htmlspecialchars($_GET['eki'] ?? '') ?>">
        <select name="category">
          <option value="">カテゴリーすべて</option>
          <?php foreach ($categories as $cat): ?>
            <option value="<?= htmlspecialchars($cat) ?>" <?= (($_GET['category'] ?? '') === $cat) ? 'selected' : '' ?>>
              <?= htmlspecialchars($cat) ?>
            </option>
          <?php endforeach; ?>
        </select>
        <input type="number" name="yachin_max" placeholder="家賃上限（円）" value="<?= htmlspecialchars($_GET['yachin_max'] ?? '') ?>">
        <button type="submit">検索</button>
        <a href="?" style="padding:8px 12px;color:#666;font-size:.9rem;">リセット</a>
      </div>
    </form>
  </div>

  <p class="count">全 <?= count($bukken_list) ?> 件</p>

  <!-- 物件一覧 -->
  <?php if ($bukken_list): ?>
  <div class="bukken-grid">
    <?php foreach ($bukken_list as $b): ?>
    <div class="bukken-card">
      <div class="card-header">
        <span><?= htmlspecialchars($b['bukken_shurui'] ?? '') ?></span>
        <span><?= htmlspecialchars($b['kanri_bangou'] ?? '') ?></span>
      </div>
      <div class="card-body">
        <div class="card-title"><?= htmlspecialchars($b['bukken_mei']) ?></div>
        <div class="card-info">
          <?php if ($b['category']): ?>
            <span class="category-badge"><?= htmlspecialchars($b['category']) ?></span><br>
          <?php endif; ?>
          📍 <?= htmlspecialchars($b['shozaichi'] ?? '') ?><br>
          🚃 <?= htmlspecialchars($b['rosen'] ?? '') ?> <?= htmlspecialchars($b['moyori_eki'] ?? '') ?>
          <?php if ($b['toho_fun']): ?> 徒歩<?= (int)$b['toho_fun'] ?>分<?php endif; ?><br>
          📐 <?= $b['menseki_m2'] ? number_format($b['menseki_m2'], 2) . '㎡' : '－' ?>
          <?= $b['tsubo'] ? '（' . number_format($b['tsubo'], 1) . '坪）' : '' ?>
          <?php if ($b['shozai_kai']): ?> / <?= htmlspecialchars($b['shozai_kai']) ?><?php endif; ?>
        </div>
        <div class="card-price">
          <?= $b['yachin'] ? '¥' . number_format($b['yachin']) . ' / 月' : '要相談' ?>
        </div>
      </div>
      <div class="card-footer">
        <a href="detail.php?id=<?= (int)$b['id'] ?>" class="btn-detail">詳細を見る →</a>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
  <?php else: ?>
  <div class="no-result">条件に合う物件が見つかりませんでした。</div>
  <?php endif; ?>

</div>
</body>
</html>