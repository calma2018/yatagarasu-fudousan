<?php
require_once __DIR__ . '/config.php';

// =============================================
// 絞り込み検索ロジック
// =============================================
$where  = [];
$params = [];
$g = $_GET;

// 用途の固定分類（元データの 用途1〜用途8 を結合して yoto に格納している）
$YOTO_CATEGORIES = ['飲食', 'オフィス・事務所', '美容・エステ・医療', '物販・サービス', '駐車場・倉庫', 'ガレージハウス・倉庫付き事務所', '沿道サービス・借地・工場', 'その他'];
// 案内方法（選択式）の固定分類
$ANNAI_HOHO_KUBUN = ['当社', '不明', '立会', '現地'];
// 手数料有無の固定分類
$TESURYO_UMU = ['あり', 'なし', '不明'];
// ステータスの固定分類
$STATUS_LIST = ['募集中', '入居済'];

// ステータス（プルダウン）
if (!empty($g['status'])) {
    $where[] = '`status` = :status'; $params[':status'] = $g['status'];
}

// 市（複数チェックボックス／市町村列で判定）
if (!empty($g['shicity'])) {
    $cc = [];
    $idx = 0;
    foreach ($g['shicity'] as $city) {
        if ($city === 'その他') {
            $cc[] = "(shichoson IS NULL OR shichoson = '' OR (shichoson NOT LIKE '%高槻%' AND shichoson NOT LIKE '%茨木%' AND shichoson NOT LIKE '%枚方%'))";
        } else {
            $k = ':cy' . $idx++; $cc[] = "shichoson LIKE $k"; $params[$k] = '%' . $city . '%';
        }
    }
    if ($cc) $where[] = '(' . implode(' OR ', $cc) . ')';
}

// 最寄り駅（プルダウン）
if (!empty($g['eki'])) {
    $where[] = 'moyori_eki = :eki'; $params[':eki'] = $g['eki'];
}

// 徒歩（分以内）
if (isset($g['toho_max']) && $g['toho_max'] !== '') {
    $where[] = 'toho_fun <= :toho_max'; $params[':toho_max'] = (int)$g['toho_max'];
}

// 数値項目にカンマや円マークが含まれていても解釈できるようにする
function numval($v) {
    if ($v === null) return null;
    $v = trim(str_replace([',', '，', '¥', '円'], '', $v));
    return $v === '' ? null : $v;
}

// 家賃/賃料（下限・上限）
$yachin_min = numval($g['yachin_min'] ?? null);
$yachin_max = numval($g['yachin_max'] ?? null);
if ($yachin_min !== null) {
    $where[] = 'yachin >= :yachin_min'; $params[':yachin_min'] = (int)$yachin_min;
}
if ($yachin_max !== null) {
    $where[] = 'yachin <= :yachin_max'; $params[':yachin_max'] = (int)$yachin_max;
}

// 面積（下限・上限）
$menseki_min = numval($g['menseki_min'] ?? null);
$menseki_max = numval($g['menseki_max'] ?? null);
if ($menseki_min !== null) {
    $where[] = 'menseki_m2 >= :menseki_min'; $params[':menseki_min'] = (float)$menseki_min;
}
if ($menseki_max !== null) {
    $where[] = 'menseki_m2 <= :menseki_max'; $params[':menseki_max'] = (float)$menseki_max;
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

// 用途（チェックボックス複数選択／結合済みyotoに対してLIKE検索・OR条件）
if (!empty($g['yoto'])) {
    $yc = [];
    $idx = 0;
    foreach ($g['yoto'] as $y) {
        $k = ':yoto' . $idx++; $yc[] = "yoto LIKE $k"; $params[$k] = '%' . $y . '%';
    }
    if ($yc) $where[] = '(' . implode(' OR ', $yc) . ')';
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

// 手数料有無（プルダウン：あり／なし／不明）
// 「不明」は NULL・空文字だけでなく、"あり"/"なし" 以外の値（空白文字など）も含めて判定する
if (!empty($g['tesuryo_umu'])) {
    if ($g['tesuryo_umu'] === '不明') {
        $where[] = "(tesuryo_umu IS NULL OR TRIM(tesuryo_umu) = '' OR TRIM(tesuryo_umu) NOT IN ('あり', 'なし'))";
    } else {
        $where[] = 'tesuryo_umu = :tesuryo_umu'; $params[':tesuryo_umu'] = $g['tesuryo_umu'];
    }
}

// 現況
if (!empty($g['current_situation'])) {
    $where[] = 'current_situation LIKE :current_situation'; $params[':current_situation'] = '%' . $g['current_situation'] . '%';
}

// 案内方法（プルダウン：当社・不明・立会・現地）
if (!empty($g['annai_hoho_kubun'])) {
    $where[] = 'annai_hoho_kubun = :annai_hoho_kubun'; $params[':annai_hoho_kubun'] = $g['annai_hoho_kubun'];
}

// 広告掲載可否（プルダウン）
if (!empty($g['ad_posting_kahi'])) {
    $where[] = 'ad_posting_kahi = :ad_posting_kahi'; $params[':ad_posting_kahi'] = $g['ad_posting_kahi'];
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
$ekis        = $pdo->query('SELECT DISTINCT moyori_eki FROM bukken WHERE moyori_eki IS NOT NULL ORDER BY moyori_eki')->fetchAll($col);
$kais        = $pdo->query('SELECT DISTINCT shozai_kai FROM bukken WHERE shozai_kai IS NOT NULL ORDER BY shozai_kai')->fetchAll($col);
$torihikis   = $pdo->query('SELECT DISTINCT torihiki_yosu FROM bukken WHERE torihiki_yosu IS NOT NULL ORDER BY torihiki_yosu')->fetchAll($col);
$ad_kahis    = $pdo->query('SELECT DISTINCT ad_posting_kahi FROM bukken WHERE ad_posting_kahi IS NOT NULL AND ad_posting_kahi <> \'\' ORDER BY ad_posting_kahi')->fetchAll($col);

$sel_shicity = $g['shicity'] ?? [];
$sel_yoto    = $g['yoto'] ?? [];

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
    .card-photo { height: 260px; background: #e8e8e8 url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24" fill="%23ccc"><path d="M21 19V5c0-1.1-.9-2-2-2H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-1.1 2-2zM8.5 13.5l2.5 3.01L14.5 12l4.5 6H5l3.5-4.5z"/></svg>') center/32px no-repeat; overflow: hidden; }
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
        <div class="filter-group">
          <label class="group-label">ステータス</label>
          <select name="status">
            <option value="">すべて</option>
            <?php foreach ($STATUS_LIST as $st): ?>
              <option value="<?= h($st) ?>" <?= sel($g['status']??'',$st) ?>><?= h($st) ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <!-- 市 -->
        <div class="filter-group">
          <label class="group-label">市</label>
          <div class="cb-group">
            <label><input type="checkbox" name="shicity[]" value="高槻" <?= chk($sel_shicity,'高槻') ?>> 高槻市</label>
            <label><input type="checkbox" name="shicity[]" value="茨木" <?= chk($sel_shicity,'茨木') ?>> 茨木市</label>
            <label><input type="checkbox" name="shicity[]" value="枚方" <?= chk($sel_shicity,'枚方') ?>> 枚方市</label>
            <label><input type="checkbox" name="shicity[]" value="その他" <?= chk($sel_shicity,'その他') ?>> その他</label>
          </div>
        </div>

        <!-- 駅（動的） -->
        <div class="filter-group">
          <label class="group-label">最寄り駅</label>
          <select name="eki">
            <option value="">すべて</option>
            <?php foreach ($ekis as $eki): ?>
              <option value="<?= h($eki) ?>" <?= sel($g['eki']??'',$eki) ?>><?= h($eki) ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <!-- 家賃/賃料 -->
        <div class="filter-group">
          <label class="group-label">家賃/賃料（円）</label>
          <div class="inline-inputs">
            <input type="text" inputmode="numeric" name="yachin_min" value="<?= h($g['yachin_min']??'') ?>" placeholder="下限 例: 50000">
            <span>〜</span>
            <input type="text" inputmode="numeric" name="yachin_max" value="<?= h($g['yachin_max']??'') ?>" placeholder="上限（空欄可）">
          </div>
        </div>

        <!-- 面積 -->
        <div class="filter-group">
          <label class="group-label">面積（㎡）</label>
          <div class="inline-inputs">
            <input type="text" inputmode="decimal" name="menseki_min" value="<?= h($g['menseki_min']??'') ?>" placeholder="下限">
            <span>〜</span>
            <input type="text" inputmode="decimal" name="menseki_max" value="<?= h($g['menseki_max']??'') ?>" placeholder="上限（空欄可）">
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
          <div class="filter-group" style="grid-column: 1 / -1;">
            <label class="group-label">用途（複数選択可）</label>
            <div class="cb-group">
              <?php foreach ($YOTO_CATEGORIES as $y): ?>
                <label><input type="checkbox" name="yoto[]" value="<?= h($y) ?>" <?= chk($sel_yoto, $y) ?>> <?= h($y) ?></label>
              <?php endforeach; ?>
            </div>
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

          <!-- 手数料有無 -->
          <div class="filter-group">
            <label class="group-label">手数料</label>
            <select name="tesuryo_umu">
              <option value="">すべて</option>
              <?php foreach ($TESURYO_UMU as $tu): ?>
                <option value="<?= h($tu) ?>" <?= sel($g['tesuryo_umu']??'',$tu) ?>><?= h($tu) ?></option>
              <?php endforeach; ?>
            </select>
          </div>

          <!-- 現況 -->
          <div class="filter-group">
            <label class="group-label">現況</label>
            <input type="text" name="current_situation" value="<?= h($g['current_situation']??'') ?>" placeholder="例: 空室">
          </div>

          <!-- 案内方法 -->
          <div class="filter-group">
            <label class="group-label">案内方法</label>
            <select name="annai_hoho_kubun">
              <option value="">すべて</option>
              <?php foreach ($ANNAI_HOHO_KUBUN as $a): ?>
                <option value="<?= h($a) ?>" <?= sel($g['annai_hoho_kubun']??'',$a) ?>><?= h($a) ?></option>
              <?php endforeach; ?>
            </select>
          </div>

          <!-- 広告掲載可否 -->
          <div class="filter-group">
            <label class="group-label">広告掲載可否</label>
            <select name="ad_posting_kahi">
              <option value="">すべて</option>
              <?php foreach ($ad_kahis as $ak): ?>
                <option value="<?= h($ak) ?>" <?= sel($g['ad_posting_kahi']??'',$ak) ?>><?= h($ak) ?></option>
              <?php endforeach; ?>
            </select>
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
          <?php if ($b['yoto']): ?><br>🏷 <?= h(str_replace('/', '・', $b['yoto'])) ?><?php endif; ?>
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
