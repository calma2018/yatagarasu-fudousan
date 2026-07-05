<?php
require_once __DIR__ . '/config.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) { header('Location: index.php'); exit; }

$stmt = $pdo->prepare('SELECT * FROM bukken WHERE id = :id LIMIT 1');
$stmt->execute([':id' => $id]);
$b = $stmt->fetch();
if (!$b) { header('Location: index.php'); exit; }

// 坪数・坪単価（自動計算）
$tsubo = $b['menseki_m2'] ? round($b['menseki_m2'] * 0.3025, 2) : null;
$yachin_per_tsubo = ($b['yachin'] && $tsubo && $tsubo > 0) ? round($b['yachin'] / $tsubo) : null;

// 表示セクション定義
$sections = [
    '基本情報' => [
        '管理番号'     => $b['kanri_bangou'],
        '物件名'       => $b['bukken_mei'],
        '号室'         => $b['room_number'],
        'ステータス'   => $b['status'],
        'カテゴリー'   => $b['category'],
        '物件種目'     => $b['bukken_shurui'],
        '用途'         => $b['yoto'],
        '広告掲載'     => $b['ad_posting'],
        '契約種別'     => $b['contract_type'],
    ],
    '立地・交通' => [
        '所在地'   => $b['shozaichi'],
        '路線'     => $b['rosen'],
        '最寄り駅' => $b['moyori_eki'],
        '徒歩'     => $b['toho_fun'] ? $b['toho_fun'] . '分' : null,
    ],
    '賃料' => [
        '家賃/賃料'     => $b['yachin']   ? '¥' . number_format($b['yachin'])   : null,
        '消費税'        => $b['shohizei'] ? '¥' . number_format($b['shohizei']) : null,
        '共益費/管理費' => $b['kyoekihi'],
        '敷金'          => $b['shikikin'] !== null ? '¥' . number_format($b['shikikin']) : null,
        '礼金'          => $b['reikin']   !== null ? '¥' . number_format($b['reikin'])   : null,
    ],
    '物件詳細' => [
        '面積'     => $b['menseki_m2'] ? number_format($b['menseki_m2'], 2) . ' ㎡' : null,
        '坪数'     => $tsubo ? number_format($tsubo, 2) . ' 坪' : null,
        '坪単価'   => $yachin_per_tsubo ? '¥' . number_format($yachin_per_tsubo) . ' / 坪' : null,
        '間口'     => $b['frontage'],
        '天井高'   => $b['ceiling_height'],
        '所在階'   => $b['shozai_kai'],
        '間取り'   => $b['madori'],
        '構造規模' => $b['kozo_kibo'],
        '築年月'   => $b['chikunen_tsuki'],
        '契約期間' => $b['keiyaku_kikan'],
        '自動更新' => $b['jido_koshin'],
        '駐車場'   => $b['chushajo'],
        '物件状態' => $b['property_condition'],
        '現況'     => $b['current_situation'],
    ],
    '設備・条件' => [
        '本体設備' => $b['hontai_setsubi'],
        '各戸設備' => $b['kakuko_setsubi'],
        '条件'     => $b['joken'],
    ],
    '取引情報' => [
        '取引態様' => $b['torihiki_yosu'],
        '手数料'   => $b['tesuryo'],
        '案内方法' => $b['annai_hoho'],
    ],
    '担当者情報' => [
        '担当者名'   => $b['staff_name'],
        '担当者連絡先' => $b['staff_contact'],
    ],
    '管理情報' => [
        '備考'     => $b['biko'],
        '掲載日'   => $b['keisai_date'],
        '確認日'   => $b['kakunin_date'],
        '管理会社' => $b['kanri_gaisha'],
    ],
];

function h($s) { return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8'); }
?>
<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= h($b['bukken_mei']) ?> | 八咫烏不動産</title>
  <style>
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body { font-family: 'Noto Sans JP', sans-serif; background: #f5f5f5; color: #333; }
    header { background: #1a1a2e; color: #fff; padding: 14px 24px; display: flex; align-items: center; gap: 16px; }
    header a { color: #aaa; text-decoration: none; font-size: .9rem; }
    header a:hover { color: #fff; }
    header h1 { font-size: 1.2rem; }
    .container { max-width: 860px; margin: 0 auto; padding: 24px 16px; }

    /* 写真 */
    .photo-area { margin-bottom: 20px; border-radius: 8px; overflow: hidden; max-height: 400px; background: #e8e8e8; display: flex; align-items: center; justify-content: center; }
    .photo-area img { width: 100%; object-fit: cover; max-height: 400px; display: block; }
    .photo-area .no-photo { color: #bbb; font-size: .9rem; padding: 60px 0; }

    /* ヒーロー */
    .hero { background: #fff; border-radius: 8px; padding: 24px; margin-bottom: 20px; box-shadow: 0 1px 4px rgba(0,0,0,.1); }
    .hero-title { font-size: 1.5rem; font-weight: bold; margin-bottom: 6px; }
    .hero-sub { color: #666; font-size: .9rem; margin-bottom: 14px; }
    .hero-price { font-size: 2rem; font-weight: bold; color: #c0392b; }
    .hero-price span { font-size: .9rem; font-weight: normal; color: #666; }
    .hero-tsubo { font-size: .9rem; color: #888; margin-top: 4px; }
    .badges { display: flex; gap: 8px; flex-wrap: wrap; margin-top: 12px; }
    .badge { padding: 3px 10px; border-radius: 12px; font-size: .78rem; }
    .badge-cat    { background: #e8f4f8; color: #1a6e9e; }
    .badge-status-募集中 { background: #d4edda; color: #155724; }
    .badge-status-入居済 { background: #f8d7da; color: #721c24; }
    .badge-status { background: #fff3cd; color: #856404; }
    .badge-contract { background: #f3e5ff; color: #6a1b9a; }

    /* 詳細テーブル */
    .section { background: #fff; border-radius: 8px; margin-bottom: 16px; box-shadow: 0 1px 4px rgba(0,0,0,.1); overflow: hidden; }
    .section-title { background: #1a1a2e; color: #fff; padding: 10px 16px; font-size: .92rem; font-weight: bold; }
    table { width: 100%; border-collapse: collapse; }
    tr { border-bottom: 1px solid #f0f0f0; }
    tr:last-child { border-bottom: none; }
    th { width: 140px; background: #f8f8f8; padding: 10px 14px; font-size: .83rem; color: #555; font-weight: normal; text-align: left; vertical-align: top; }
    td { padding: 10px 14px; font-size: .9rem; white-space: pre-wrap; word-break: break-all; }
  </style>
</head>
<body>
<header>
  <a href="index.php">← 一覧に戻る</a>
  <h1>物件詳細</h1>
</header>
<div class="container">

  <!-- 写真 -->
  <div class="photo-area">
    <?php if (!empty($b['photo_path'])): ?>
      <img src="/common/img/bukken/<?= h($b['photo_path']) ?>" alt="<?= h($b['bukken_mei']) ?>">
    <?php else: ?>
      <div class="no-photo">📷 写真なし</div>
    <?php endif; ?>
  </div>

  <!-- ヒーロー -->
  <div class="hero">
    <div class="hero-title"><?= h($b['bukken_mei']) ?><?= $b['room_number'] ? '　' . h($b['room_number']) : '' ?></div>
    <div class="hero-sub">
      📍 <?= h($b['shozaichi']) ?>
      <?php if ($b['rosen'] || $b['moyori_eki']): ?>
        ／ 🚃 <?= h($b['rosen']) ?> <?= h($b['moyori_eki']) ?><?= $b['toho_fun'] ? ' 徒歩' . (int)$b['toho_fun'] . '分' : '' ?>
      <?php endif; ?>
    </div>
    <div class="hero-price">
      <?= $b['yachin'] ? '¥' . number_format($b['yachin']) : '要相談' ?>
      <span>/ 月</span>
      <?php if ($b['shohizei']): ?>
        <span>（消費税 ¥<?= number_format($b['shohizei']) ?>）</span>
      <?php endif; ?>
    </div>
    <?php if ($tsubo || $yachin_per_tsubo): ?>
    <div class="hero-tsubo">
      <?= $tsubo ? number_format($tsubo, 2) . '坪' : '' ?>
      <?= ($tsubo && $yachin_per_tsubo) ? '　｜　' : '' ?>
      <?= $yachin_per_tsubo ? '坪単価 ¥' . number_format($yachin_per_tsubo) : '' ?>
    </div>
    <?php endif; ?>
    <div class="badges">
      <?php if ($b['bukken_shurui']): ?><span class="badge badge-cat"><?= h($b['bukken_shurui']) ?></span><?php endif; ?>
      <?php if ($b['category']): ?><span class="badge badge-cat"><?= h($b['category']) ?></span><?php endif; ?>
      <?php if ($b['status']): ?>
        <?php $sc = 'badge-status-' . $b['status']; ?>
        <span class="badge <?= in_array($sc, ['badge-status-募集中','badge-status-入居済']) ? $sc : 'badge-status' ?>"><?= h($b['status']) ?></span>
      <?php endif; ?>
      <?php if ($b['contract_type']): ?><span class="badge badge-contract"><?= h($b['contract_type']) ?></span><?php endif; ?>
      <?php if ($b['menseki_m2']): ?><span class="badge" style="background:#eee;color:#555"><?= number_format($b['menseki_m2'], 2) ?>㎡</span><?php endif; ?>
      <?php if ($b['property_condition']): ?><span class="badge" style="background:#fff0e6;color:#c85000"><?= h($b['property_condition']) ?></span><?php endif; ?>
    </div>
  </div>

  <!-- 詳細セクション -->
  <?php foreach ($sections as $section_name => $fields):
    $has_value = array_filter($fields, fn($v) => $v !== null && $v !== '');
    if (!$has_value) continue;
  ?>
  <div class="section">
    <div class="section-title"><?= h($section_name) ?></div>
    <table>
      <?php foreach ($fields as $label => $value):
        if ($value === null || $value === '') continue;
      ?>
        <tr>
          <th><?= h($label) ?></th>
          <td><?= h($value) ?></td>
        </tr>
      <?php endforeach; ?>
    </table>
  </div>
  <?php endforeach; ?>

</div>
</body>
</html>
