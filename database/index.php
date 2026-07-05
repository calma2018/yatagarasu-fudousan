<?php
require_once __DIR__ . '/config.php';

// =============================================
// 絞り込み検索ロジック
// =============================================
$where  = [];
$params = [];
$g = $_GET;

// ステータス（複数チェックボックス）
if (!empty($g['status'])) {
    $ph = [];
    foreach ($g['status'] as $i => $s) {
        $k = ':st' . $i; $ph[] = $k; $params[$k] = $s;
    }
    $where[] = '`status` IN (' . implode(',', $ph) . ')';
}

// 市（複数チェックボックス）
if (!empty($g['shicity'])) {
    $cc = [];
    foreach ($g['shicity'] as $city) {
        if ($city === 'その他') {
            $cc[] = "(shozaichi NOT LIKE '%高槻市%' AND shozaichi NOT LIKE '%茨木市%' AND shozaichi NOT LIKE '%枚方市%')";
        } else {
            $k = ':cy' . count($cc); $cc[] = "shozaichi LIKE $k"; $params[$k] = '%' . $city . '%';
        }
    }
    if ($cc) $where[] = '(' . implode(' OR ', $cc) . ')';
}

// 駅（複数チェックボックス）
if (!empty($g['eki'])) {
    $ec = [];
    foreach ($g['eki'] as $i => $e) {
        $k = ':eki' . $i; $ec[] = "moyori_eki = $k"; $params[$k] = $e;
    }
    $where[] = '(' . implode(' OR ', $ec) . ')';
}

// 徒歩（分以内）
if (isset($g['toho_max']) && $g['toho_max'] !== '') {
    $where[] = 'toho_fun <= :toho_max'; $params[':toho_max'] = (int)$g['toho_max'];
}

// 家賃レンジ
if (!empty($g['yachin_range'])) {
    $yr = $g['yachin_range'];
    if ($yr === '1') { $where[] = 'yachin BETWEEN 1 AND 50000'; }
    elseif ($yr === '2') { $where[] = 'yachin BETWEEN 50001 AND 100000'; }
    elseif ($yr === '3') { $where[] = 'yachin BETWEEN 100001 AND 200000'; }
    elseif ($yr === '4') { $where[] = 'yachin BETWEEN 200001 AND 300000'; }
    elseif ($yr === '5') { $where[] = 'yachin BETWEEN 300001 AND 400000'; }
    elseif ($yr === '6') { $where[] = 'yachin BETWEEN 400001 AND 500000'; }
    elseif ($yr === '7') { $where[] = 'yachin > 500000'; }
}

// 面積（㎡以上）
if (isset($g['menseki_min']) && $g['menseki_min'] !== '') {
    $where[] = 'menseki_m2 >= :menseki_min'; $params[':menseki_min'] = (float)$g['menseki_min'];
}

// 坪数（坪以上）
if (isset($g['tsubo_min']) && $g['tsubo_min'] !== '') {
    $where[] = 'tsubo >= :tsubo_min'; $params[':tsubo_min'] = (float)$g['tsubo_min'];
}

// カテゴリー
if (!empty($g['category'])) {
    $where[] = 'category = :category'; $params[':category'] = $g['category'];
}

// 物件種目
if (!empty($g['bukken_shurui'])) {
    $where[] = 'bukken_shurui = :bukken_shurui'; $params[':bukken_shurui'] = $g['bukken_shurui'];
}

// 用途
if (!empty($g['yoto'])) {
    $where[] = 'yoto = :yoto'; $params[':yoto'] = $g['yoto'];
}

// 所在階
if (!empty($g['shozai_kai'])) {
    $where[] = 'shozai_kai = :shozai_kai'; $params[':shozai_kai'] = $g['shozai_kai'];
}

// 築年月（以降）
if (!empty($g['chikunen_from'])) {
    $where[] = 'chikunen_tsuki >= :chikunen_from'; $params[':chikunen_from'] = $g['chikunen_from'];
}

// 駐車場
if (!empty($g['chushajo'])) {
    $where[] = 'chushajo LIKE :chushajo'; $params[':chushajo'] = '%' . $g['chushajo'] . '%';
}

// 取引態様
if (!empty($g['torihiki_yosu'])) {
    $where[] = 'torihiki_yosu = :torihiki_yosu'; $params[':torihiki_yosu'] = $g['torihiki_yosu'];
}

// 手数料
if (!empty($g['tesuryo'])) {
    $where[] = 'tesuryo LIKE :tesuryo'; $params[':tesuryo'] = '%' . $g['tesuryo'] . '%';
}

// 現況
if (!empty($g['current_situation'])) {
    $where[] = 'current_situation LIKE :current_situation'; $params[':current_situation'] = '%' . $g['current_situation'] . '%';
}

// 案内方法
if (!empty($g['annai_hoho'])) {
    $where[] = 'annai_hoho LIKE :annai_hoho'; $params[':annai_hoho'] = '%' . $g['annai_hoho'] . '%';
}

// 広告掲載
if (!empty($g['ad_posting'])) {
    $where[] = 'ad_posting LIKE :ad_posting'; $params[':ad_posting'] = '%' . $g['ad_posting'] . '%';
}

// 掲載日（以降）
if (!empty($g['keisai_date_from'])) {
    $where[] = 'keisai_date >= :keisai_date_from'; $params[':keisai_date_from'] = $g['keisai_date_from'];
}

// 管理会社
if (!empty($g['kanri_gaisha'])) {
    $where[] = 'kanri_gaisha LIKE :kanri_gaisha'; $params[':kanri_gaisha'] = '%' . $g['kanri_gaisha'] . '%';
}

$sql = 'SELECT * FROM bukken';
if ($where) $sql .= ' WHERE ' . implode(' AND ', $where);
$sql .= ' ORDER BY id DESC';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$bukken_list = $stmt->fetchAll();

// フィルター選択肢
$col = PDO::FETCH_COLUMN;
$categories  = $pdo->query('SELECT DISTINCT category FROM bukken WHERE category IS NOT NULL ORDER BY category')->fetchAll($col);
$shuruis     = $pdo->query('SELECT DISTINCT bukken_shurui FROM bukken WHERE bukken_shurui IS NOT NULL ORDER BY bukken_shurui')->fetchAll($col);
$yotos       = $pdo->query('SELECT DISTINCT yoto FROM bukken WHERE yoto IS NOT NULL ORDER BY yoto')->fetchAll($col);
$ekis        = $pdo->query('SELECT DISTINCT moyori_eki FROM bukken WHERE moyori_eki IS NOT NULL ORDER BY moyori_eki')->fetchAll($col);
$kais        = $pdo->query('SELECT DISTINCT shozai_kai FROM bukken WHERE shozai_kai IS NOT NULL ORDER BY shozai_kai')->fetchAll($col);
$torihikis   = $pdo->query('SELECT DISTINCT torihiki_yosu FROM bukken WHERE torihiki_yosu IS NOT NULL ORDER BY torihiki_yosu')->fetchAll($col);
$annais      = $pdo->query('SELECT DISTINCT annai_hoho FROM bukken WHERE annai_hoho IS NOT NULL ORDER BY annai_hoho')->fetchAll($col);

$sel_status  = $g['status']  ?? [];
$sel_shicity = $g['shicity'] ?? [];
$sel_eki     = $g['eki']     ?? [];

function h($s) { return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8'); }
function chk($arr, $val) { return in_array($val, (array)$arr) ? 'checked' : ''; }
function sel($cur, $val) { return ($cur ?? '') === $val ? 'selected' : ''; }
?>
<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>物件一覧 | 八咫烏不動産</title>
  <style>
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body { font-family: 'Noto Sans JP', sans-serif; background: #f5f5f5; color: #333; font-size: 14px; }
    header { background: #1a1a2e; color: #fff; padding: 14px 24px; }
    header h1 { font-size: 1.3rem; }
    .container { max-width: 1200px; margin: 0 auto; padding: 20px 16px; }

    /* ===== フィルターパネル ===== */
    .filter-panel { background: #fff; border-radius: 8px; padding: 20px; margin-bottom: 20px; box-shadow: 0 1px 4px rgba(0,0,0,.1); }
    .filter-panel h2 { font-size: .95rem; color: #555; margin-bottom: 14px; display: flex; align-items: center; gap: 8px; }
    .filter-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 12px 20px; }
    .filter-group label.group-label { display: block; font-size: .78rem; color: #888; margin-bottom: 4px; font-weight: bold; }
    .filter-group input[type=text],
    .filter-group input[type=number],
    .filter-group input[type=date],
    .filter-group select { width: 100%; padding: 6px 10px; border: 1px solid #ccc; border-radius: 4px; font-size: .88rem; }
    .filter-group .inline-inputs { display: flex; gap: 6px; align-items: center; }
    .filter-group .inline-inputs input { flex: 1; }
    .filter-group .inline-inputs span { color: #888; font-size: .8rem; white-space: nowrap; }

    /* チェックボックスグループ */
    .cb-group { display: flex; flex-wrap: wrap; gap: 4px 12px; }
    .cb-group label { display: flex; align-items: center; gap: 4px; font-size: .85rem; cursor: pointer; }
    .cb-scroll { max-height: 100px; overflow-y: auto; border: 1px solid #eee; border-radius: 4px; padding: 6px 8px; }
    .cb-scroll label { display: flex; align-items: center; gap: 6px; padding: 2px 0; }

    .filter-advanced { margin-top: 12px; border-top: 1px solid #eee; padding-top: 12px; }

    /* ボタン */
    .filter-actions { display: flex; gap: 10px; margin-top: 14px; align-items: center; }
    .btn-search { padding: 8px 24px; background: #1a1a2e; color: #fff; border: none; border-radius: 4px; cursor: pointer; font-size: .9rem; }
    .btn-search:hover { background: #2e2e5e; }
    .btn-reset { color: #888; text-decoration: none; font-size: .85rem; }

    /* 件数 */
    .count { margin-bottom: 12px; color: #666; font-size: .88rem; }

    /* ===== カードグリッド ===== */
    .bukken-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 16px; }
    .bukken-card { background: #fff; border-radius: 8px; box-shadow: 0 1px 4px rgba(0,0,0,.1); overflow: hidden; transition: box-shadow .2s; display: flex; flex-direction: column; }
    .bukken-card:hover { box-shadow: 0 4px 12px rgba(0,0,0,.15); }
    .card-photo { height: 160px; background: #e8e8e8 url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24" fill="%23ccc"><path d="M21 19V5c0-1.1-.9-2-2-2H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-1.1 2-2zM8.5 13.5l2.5 3.01L14.5 12l4.5 6H5l3.5-4.5z"/></svg>') center/32px no-repeat; overflow: hidden; }
    .card-photo img { width: 100%; height: 100%; object-fit: cover; }
    .card-header { background: #1a1a2e; color: #fff; padding: 8px 12px; font-size: .78rem; display: flex; justify-content: space-between; align-items: center; }
    .card-body { padding: 12px 14px; flex: 1; }
    .card-title { font-size: 1rem; font-weight: bold; margin-bottom: 6px; }
    .card-badges { display: flex; flex-wrap: wrap; gap: 4px; margin-bottom: 8px; }
    .badge { display: inline-block; padding: 2px 8px; border-radius: 12px; font-size: .72rem; }
    .badge-cat  { background: #e8f4f8; color: #1a6e9e; }
    .badge-status-募集中 { background: #d4edda; color: #155724; }
    .badge-status-入居済 { background: #f8d7da; color: #721c24; }
    .badge-status { background: #fff3cd; color: #856404; }
    .card-info { font-size: .83rem; color: #555; line-height: 1.8; }
    .card-price { font-size: 1.15rem; font-weight: bold; color: #c0392b; margin: 8px 0 4px; }
    .card-tsubo { font-size: .8rem; color: #888; }
    .card-footer { padding: 10px 14px; border-top: 1px solid #eee; text-align: right; }
    .btn-detail { display: inline-block; padding: 6px 14px; background: #1a1a2e; color: #fff; text-decoration: none; border-radius: 4px; font-size: .82rem; }
    .btn-detail:hover { background: #2e2e5e; }
    .no-result { text-align: center; color: #999; padding: 60px 0; }
  </style>
</head>
<body>
<header>
  <h1>🏢 八咫烏不動産 物件一覧</h1>
</header>
<div class="container">

  <!-- ===== フィルターパネル ===== -->
  <div class="filter-panel">
    <h2>🔍 絞り込み検索</h2>
    <form method="get" action="" id="filterForm">

      <!-- ===== メイン条件 ===== -->
      <div class="filter-grid">

        <!-- ステータス -->
        <div class="filter-group" style="grid-column: 1 / -1;">
          <label class="group-label">ステータス</label>
          <div class="cb-group">
            <label><input type="checkbox" name="status[]" value="募集中" <?= chk($sel_status,'募集中') ?>> 募集中</label>
            <label><input type="checkbox" name="status[]" value="入居済" <?= chk($sel_status,'入居済') ?>> 入居済</label>
          </div>
        </div>

        <!-- 市 -->
        <div class="filter-group">
          <label class="group-label">市</label>
          <div class="cb-group">
            <label><input type="checkbox" name="shicity[]" value="高槻市" <?= chk($sel_shicity,'高槻市') ?>> 高槻市</label>
            <label><input type="checkbox" name="shicity[]" value="茨木市" <?= chk($sel_shicity,'茨木市') ?>> 茨木市</label>
            <label><input type="checkbox" name="shicity[]" value="枚方市" <?= chk($sel_shicity,'枚方市') ?>> 枚方市</label>
            <label><input type="checkbox" name="shicity[]" value="その他" <?= chk($sel_shicity,'その他') ?>> その他</label>
          </div>
        </div>

        <!-- 駅（動的） -->
        <div class="filter-group">
          <label class="group-label">最寄り駅（複数選択可）</label>
          <div class="cb-scroll cb-group">
            <?php foreach ($ekis as $eki): ?>
              <label><input type="checkbox" name="eki[]" value="<?= h($eki) ?>" <?= chk($sel_eki, $eki) ?>> <?= h($eki) ?></label>
            <?php endforeach; ?>
            <?php if (empty($ekis)): ?><span style="color:#aaa;font-size:.8rem">データなし</span><?php endif; ?>
          </div>
        </div>

        <!-- 家賃レンジ -->
        <div class="filter-group">
          <label class="group-label">家賃/賃料</label>
          <select name="yachin_range">
            <option value="">すべて</option>
            <option value="1" <?= sel($g['yachin_range']??'','1') ?>>〜50,000円</option>
            <option value="2" <?= sel($g['yachin_range']??'','2') ?>>50,001〜100,000円</option>
            <option value="3" <?= sel($g['yachin_range']??'','3') ?>>100,001〜200,000円</option>
            <option value="4" <?= sel($g['yachin_range']??'','4') ?>>200,001〜300,000円</option>
            <option value="5" <?= sel($g['yachin_range']??'','5') ?>>300,001〜400,000円</option>
            <option value="6" <?= sel($g['yachin_range']??'','6') ?>>400,001〜500,000円</option>
            <option value="7" <?= sel($g['yachin_range']??'','7') ?>>500,001円〜</option>
          </select>
        </div>

        <!-- 面積 -->
        <div class="filter-group">
          <label class="group-label">面積（㎡以上）</label>
          <div class="inline-inputs">
            <input type="number" name="menseki_min" value="<?= h($g['menseki_min']??'') ?>" placeholder="例: 50" step="0.01">
            <span>㎡〜</span>
          </div>
        </div>

      </div><!-- /filter-grid -->

      <div class="filter-advanced">
        <div class="filter-grid" style="margin-top:12px;">

          <!-- カテゴリー -->
          <div class="filter-group">
            <label class="group-label">カテゴリー</label>
            <select name="category">
              <option value="">すべて</option>
              <?php foreach ($categories as $c): ?>
                <option value="<?= h($c) ?>" <?= sel($g['category']??'',$c) ?>><?= h($c) ?></option>
              <?php endforeach; ?>
            </select>
          </div>

          <!-- 物件種目 -->
          <div class="filter-group">
            <label class="group-label">物件種目</label>
            <select name="bukken_shurui">
              <option value="">すべて</option>
              <?php foreach ($shuruis as $s): ?>
                <option value="<?= h($s) ?>" <?= sel($g['bukken_shurui']??'',$s) ?>><?= h($s) ?></option>
              <?php endforeach; ?>
            </select>
          </div>

          <!-- 用途 -->
          <div class="filter-group">
            <label class="group-label">用途</label>
            <select name="yoto">
              <option value="">すべて</option>
              <?php foreach ($yotos as $y): ?>
                <option value="<?= h($y) ?>" <?= sel($g['yoto']??'',$y) ?>><?= h($y) ?></option>
              <?php endforeach; ?>
            </select>
          </div>

          <!-- 徒歩 -->
          <div class="filter-group">
            <label class="group-label">徒歩（分以内）</label>
            <div class="inline-inputs">
              <input type="number" name="toho_max" value="<?= h($g['toho_max']??'') ?>" placeholder="例: 10" min="1">
              <span>分以内</span>
            </div>
          </div>

          <!-- 坪数 -->
          <div class="filter-group">
            <label class="group-label">坪数（坪以上）</label>
            <div class="inline-inputs">
              <input type="number" name="tsubo_min" value="<?= h($g['tsubo_min']??'') ?>" placeholder="例: 10" step="0.1">
              <span>坪〜</span>
            </div>
          </div>

          <!-- 所在階 -->
          <div class="filter-group">
            <label class="group-label">所在階</label>
            <select name="shozai_kai">
              <option value="">すべて</option>
              <?php foreach ($kais as $k): ?>
                <option value="<?= h($k) ?>" <?= sel($g['shozai_kai']??'',$k) ?>><?= h($k) ?></option>
              <?php endforeach; ?>
            </select>
          </div>

          <!-- 築年月 -->
          <div class="filter-group">
            <label class="group-label">築年月（以降）</label>
            <input type="text" name="chikunen_from" value="<?= h($g['chikunen_from']??'') ?>" placeholder="例: 2000/01">
          </div>

          <!-- 駐車場 -->
          <div class="filter-group">
            <label class="group-label">駐車場</label>
            <input type="text" name="chushajo" value="<?= h($g['chushajo']??'') ?>" placeholder="例: 有">
          </div>

          <!-- 取引態様 -->
          <div class="filter-group">
            <label class="group-label">取引態様</label>
            <select name="torihiki_yosu">
              <option value="">すべて</option>
              <?php foreach ($torihikis as $t): ?>
                <option value="<?= h($t) ?>" <?= sel($g['torihiki_yosu']??'',$t) ?>><?= h($t) ?></option>
              <?php endforeach; ?>
            </select>
          </div>

          <!-- 手数料 -->
          <div class="filter-group">
            <label class="group-label">手数料</label>
            <input type="text" name="tesuryo" value="<?= h($g['tesuryo']??'') ?>" placeholder="例: 分かれ">
          </div>

          <!-- 現況 -->
          <div class="filter-group">
            <label class="group-label">現況</label>
            <input type="text" name="current_situation" value="<?= h($g['current_situation']??'') ?>" placeholder="例: 空室">
          </div>

          <!-- 案内方法 -->
          <div class="filter-group">
            <label class="group-label">案内方法</label>
            <select name="annai_hoho">
              <option value="">すべて</option>
              <?php foreach ($annais as $a): ?>
                <option value="<?= h($a) ?>" <?= sel($g['annai_hoho']??'',$a) ?>><?= h($a) ?></option>
              <?php endforeach; ?>
            </select>
          </div>

          <!-- 広告掲載 -->
          <div class="filter-group">
            <label class="group-label">広告掲載</label>
            <input type="text" name="ad_posting" value="<?= h($g['ad_posting']??'') ?>" placeholder="例: 可">
          </div>

          <!-- 掲載日 -->
          <div class="filter-group">
            <label class="group-label">掲載日（以降）</label>
            <input type="date" name="keisai_date_from" value="<?= h($g['keisai_date_from']??'') ?>">
          </div>

          <!-- 管理会社 -->
          <div class="filter-group">
            <label class="group-label">管理会社</label>
            <input type="text" name="kanri_gaisha" value="<?= h($g['kanri_gaisha']??'') ?>" placeholder="会社名で検索">
          </div>

        </div>
      </div><!-- /filter-advanced -->

      <div class="filter-actions">
        <button type="submit" class="btn-search">検索する</button>
        <a href="?" class="btn-reset">リセット</a>
      </div>

    </form>
  </div>

  <p class="count">全 <?= count($bukken_list) ?> 件</p>

  <!-- ===== 物件カード一覧 ===== -->
  <?php if ($bukken_list): ?>
  <div class="bukken-grid">
    <?php foreach ($bukken_list as $b):
      $tsubo = $b['menseki_m2'] ? round($b['menseki_m2'] * 0.3025, 1) : null;
      $yachin_per_tsubo = ($b['yachin'] && $tsubo && $tsubo > 0) ? round($b['yachin'] / $tsubo) : null;
    ?>
    <div class="bukken-card">
      <!-- 写真 -->
      <div class="card-photo">
        <?php if (!empty($b['photo_path'])): ?>
          <img src="/common/img/bukken/<?= h($b['photo_path']) ?>" alt="<?= h($b['bukken_mei']) ?>" loading="lazy" onerror="this.style.display='none'">
        <?php endif; ?>
      </div>

      <div class="card-header">
        <span><?= h($b['bukken_shurui']) ?></span>
        <span>No.<?= h($b['kanri_bangou']) ?></span>
      </div>

      <div class="card-body">
        <div class="card-badges">
          <?php if ($b['category']): ?>
            <span class="badge badge-cat"><?= h($b['category']) ?></span>
          <?php endif; ?>
          <?php if ($b['status']): ?>
            <?php $sc = 'badge-status-' . $b['status']; ?>
            <span class="badge <?= in_array($sc, ['badge-status-募集中','badge-status-入居済']) ? $sc : 'badge-status' ?>"><?= h($b['status']) ?></span>
          <?php endif; ?>
          <?php if ($b['contract_type']): ?>
            <span class="badge" style="background:#f3e5ff;color:#6a1b9a"><?= h($b['contract_type']) ?></span>
          <?php endif; ?>
        </div>

        <div class="card-title"><?= h($b['bukken_mei']) ?></div>

        <div class="card-info">
          📍 <?= h($b['shozaichi']) ?><br>
          🚃 <?= h($b['rosen']) ?> <?= h($b['moyori_eki']) ?>
          <?php if ($b['toho_fun']): ?> 徒歩<?= (int)$b['toho_fun'] ?>分<?php endif; ?><br>
          📐 <?= $b['menseki_m2'] ? number_format($b['menseki_m2'], 2) . '㎡' : '－' ?>
          <?= $tsubo ? '（' . $tsubo . '坪）' : '' ?>
          <?php if ($b['shozai_kai']): ?> / <?= h($b['shozai_kai']) ?><?php endif; ?>
          <?php if ($b['room_number']): ?> <?= h($b['room_number']) ?><?php endif; ?>
        </div>

        <div class="card-price">
          <?= $b['yachin'] ? '¥' . number_format($b['yachin']) . ' / 月' : '要相談' ?>
        </div>
        <?php if ($yachin_per_tsubo): ?>
          <div class="card-tsubo">坪単価 ¥<?= number_format($yachin_per_tsubo) ?></div>
        <?php endif; ?>
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
