<?php
require_once __DIR__ . '/config.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    header('Location: index.php');
    exit;
}

$stmt = $pdo->prepare('SELECT * FROM bukken WHERE id = :id LIMIT 1');
$stmt->execute([':id' => $id]);
$b = $stmt->fetch();

if (!$b) {
    header('Location: index.php');
    exit;
}

// 表示ラベルと値のマッピング
$sections = [
    '基本情報' => [
        '管理番号'   => $b['kanri_bangou'],
        '物件名'     => $b['bukken_mei'],
        'カテゴリー' => $b['category'],
        '物件種目'   => $b['bukken_shurui'],
        '用途'       => $b['yoto'],
    ],
    '立地・交通' => [
        '所在地'     => $b['shozaichi'],
        '路線'       => $b['rosen'],
        '最寄り駅'   => $b['moyori_eki'],
        '徒歩'       => $b['toho_fun'] ? $b['toho_fun'] . '分' : null,
    ],
    '賃料' => [
        '家賃/賃料'     => $b['yachin']   ? '¥' . number_format($b['yachin'])   : null,
        '消費税'        => $b['shohizei'] ? '¥' . number_format($b['shohizei']) : null,
        '共益費/管理費' => $b['kyoekihi'],
        '敷金'          => $b['shikikin'] !== null ? '¥' . number_format($b['shikikin']) : null,
        '礼金'          => $b['reikin']   !== null ? '¥' . number_format($b['reikin'])   : null,
    ],
    '物件詳細' => [
        '面積'       => $b['menseki_m2'] ? number_format($b['menseki_m2'], 2) . ' ㎡' : null,
        '坪数'       => $b['tsubo']      ? number_format($b['tsubo'], 1) . ' 坪'     : null,
        '所在階'     => $b['shozai_kai'],
        '間取り'     => $b['madori'],
        '構造規模'   => $b['kozo_kibo'],
        '築年月'     => $b['chikunen_tsuki'],
        '契約期間'   => $b['keiyaku_kikan'],
        '自動更新'   => $b['jido_koshin'],
        '駐車場'     => $b['chushajo'],
    ],
    '設備・条件' => [
        '本体設備'   => $b['hontai_setsubi'],
        '各戸設備'   => $b['kakuko_setsubi'],
        '保証会社'   => $b['hosho_gaisha'],
        '保険'       => $b['hoken'],
        '条件'       => $b['joken'],
    ],
    '取引情報' => [
        '取引態様'   => $b['torihiki_yosu'],
        '手数料'     => $b['tesuryo'],
        '案内方法'   => $b['annai_hoho'],
    ],
    '管理情報' => [
        '備考'       => $b['biko'],
        '掲載日'     => $b['keisai_date'],
        '確認日'     => $b['kakunin_date'],
        '管理会社'   => $b['kanri_gaisha'],
    ],
];
?>
<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($b['bukken_mei']) ?> | 八咫烏不動産</title>
  <style>
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body { font-family: 'Noto Sans JP', sans-serif; background: #f5f5f5; color: #333; }
    header { background: #1a1a2e; color: #fff; padding: 16px 24px; display: flex; align-items: center; gap: 16px; }
    header a { color: #aaa; text-decoration: none; font-size: 0.9rem; }
    header a:hover { color: #fff; }
    header h1 { font-size: 1.2rem; }
    .container { max-width: 860px; margin: 0 auto; padding: 24px 16px; }

    /* ヒーロー部分 */
    .hero { background: #fff; border-radius: 8px; padding: 24px; margin-bottom: 20px; box-shadow: 0 1px 4px rgba(0,0,0,.1); }
    .hero-title { font-size: 1.5rem; font-weight: bold; margin-bottom: 8px; }
    .hero-sub { color: #666; font-size: 0.9rem; margin-bottom: 16px; }
    .hero-price { font-size: 2rem; font-weight: bold; color: #c0392b; }
    .hero-price span { font-size: 1rem; font-weight: normal; color: #666; }
    .badges { display: flex; gap: 8px; flex-wrap: wrap; margin-top: 12px; }
    .badge { padding: 3px 10px; border-radius: 12px; font-size: 0.8rem; background: #e8f4f8; color: #1a6e9e; }

    /* 詳細テーブル */
    .section { background: #fff; border-radius: 8px; margin-bottom: 16px; box-shadow: 0 1px 4px rgba(0,0,0,.1); overflow: hidden; }
    .section-title { background: #1a1a2e; color: #fff; padding: 10px 16px; font-size: 0.95rem; font-weight: bold; }
    table { width: 100%; border-collapse: collapse; }
    tr { border-bottom: 1px solid #f0f0f0; }
    tr:last-child { border-bottom: none; }
    th { width: 140px; background: #f8f8f8; padding: 10px 14px; font-size: 0.85rem; color: #555; font-weight: normal; text-align: left; vertical-align: top; }
    td { padding: 10px 14px; font-size: 0.9rem; white-space: pre-wrap; word-break: break-all; }

    /* 戻るボタン */
    .back-btn { display: inline-block; margin-bottom: 16px; padding: 8px 16px; background: #fff; border: 1px solid #ccc; border-radius: 4px; text-decoration: none; color: #333; font-size: 0.9rem; }
    .back-btn:hover { background: #f0f0f0; }
  </style>
</head>
<body>
<header>
  <a href="index.php">← 一覧に戻る</a>
  <h1>物件詳細</h1>
</header>
<div class="container">

  <!-- ヒーロー -->
  <div class="hero">
    <div class="hero-title"><?= htmlspecialchars($b['bukken_mei']) ?></div>
    <div class="hero-sub">
      📍 <?= htmlspecialchars($b['shozaichi'] ?? '') ?> ／
      🚃 <?= htmlspecialchars($b['rosen'] ?? '') ?> <?= htmlspecialchars($b['moyori_eki'] ?? '') ?>
      <?= $b['toho_fun'] ? ' 徒歩' . (int)$b['toho_fun'] . '分' : '' ?>
    </div>
    <div class="hero-price">
      <?= $b['yachin'] ? '¥' . number_format($b['yachin']) : '要相談' ?>
      <span>/ 月</span>
      <?php if ($b['shohizei']): ?>
        <span style="font-size:.9rem">（消費税 ¥<?= number_format($b['shohizei']) ?>）</span>
      <?php endif; ?>
    </div>
    <div class="badges">
      <?php if ($b['bukken_shurui']): ?><span class="badge"><?= htmlspecialchars($b['bukken_shurui']) ?></span><?php endif; ?>
      <?php if ($b['category']): ?><span class="badge"><?= htmlspecialchars($b['category']) ?></span><?php endif; ?>
      <?php if ($b['menseki_m2']): ?><span class="badge"><?= number_format($b['menseki_m2'], 2) ?>㎡</span><?php endif; ?>
      <?php if ($b['madori']): ?><span class="badge"><?= htmlspecialchars($b['madori']) ?></span><?php endif; ?>
    </div>
  </div>

  <!-- 詳細セクション -->
  <?php foreach ($sections as $section_name => $fields): ?>
  <?php
    // 値がすべてnullのセクションはスキップ
    $has_value = array_filter($fields, fn($v) => $v !== null && $v !== '');
    if (!$has_value) continue;
  ?>
  <div class="section">
    <div class="section-title"><?= htmlspecialchars($section_name) ?></div>
    <table>
      <?php foreach ($fields as $label => $value): ?>
        <?php if ($value === null || $value === '') continue; ?>
        <tr>
          <th><?= htmlspecialchars($label) ?></th>
          <td><?= htmlspecialchars($value) ?></td>
        </tr>
      <?php endforeach; ?>
    </table>
  </div>
  <?php endforeach; ?>

</div>
</body>
</html>