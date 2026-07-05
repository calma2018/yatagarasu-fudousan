<?php
error_reporting(0);
ini_set('display_errors', '0');

// ===================================================
// import.php - Google Apps Scriptからのデータ受信・DB更新
// ===================================================

define('SECRET_KEY', 'yatagarasu_secret_2026');

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'POST only']); exit;
}

// DB接続（ヘッダー出力後にrequireすることでエラーをJSONで返せる）
try {
    require_once __DIR__ . '/config.php';
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'DB接続エラー: ' . $e->getMessage()]); exit;
}

$body = file_get_contents('php://input');
$json = json_decode($body, true);
if (!$json) { echo json_encode(['success' => false, 'message' => 'Invalid JSON']); exit; }
if (($json['secret'] ?? '') !== SECRET_KEY) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']); exit;
}

$records = $json['data'] ?? [];
if (empty($records)) { echo json_encode(['success' => false, 'message' => 'データがありません']); exit; }

// ===================================================
// DB更新処理
// ===================================================
try {
    $pdo->beginTransaction();

    // 既存データを全削除（TRUNCATEはトランザクションを破壊するのでDELETE使用）
    $pdo->exec('DELETE FROM bukken');

    $sql = '
        INSERT INTO bukken (
            kanri_bangou, bukken_mei, category, bukken_shurui, yoto,
            shozaichi, rosen, moyori_eki, toho_fun,
            yachin, shohizei, kyoekihi, shikikin, reikin,
            menseki_m2, tsubo, shozai_kai, madori, kozo_kibo,
            chikunen_tsuki, keiyaku_kikan, jido_koshin, chushajo,
            hontai_setsubi, kakuko_setsubi, joken,
            torihiki_yosu, tesuryo, annai_hoho, biko,
            keisai_date, kakunin_date, kanri_gaisha,
            status, ad_posting, contract_type, current_situation,
            property_condition, staff_name, staff_contact,
            frontage, ceiling_height, room_number, photo_path
        ) VALUES (
            :kanri_bangou, :bukken_mei, :category, :bukken_shurui, :yoto,
            :shozaichi, :rosen, :moyori_eki, :toho_fun,
            :yachin, :shohizei, :kyoekihi, :shikikin, :reikin,
            :menseki_m2, :tsubo, :shozai_kai, :madori, :kozo_kibo,
            :chikunen_tsuki, :keiyaku_kikan, :jido_koshin, :chushajo,
            :hontai_setsubi, :kakuko_setsubi, :joken,
            :torihiki_yosu, :tesuryo, :annai_hoho, :biko,
            :keisai_date, :kakunin_date, :kanri_gaisha,
            :status, :ad_posting, :contract_type, :current_situation,
            :property_condition, :staff_name, :staff_contact,
            :frontage, :ceiling_height, :room_number, :photo_path
        )
    ';
    $stmt = $pdo->prepare($sql);

    $toInt  = function($v) { return ($v !== null && $v !== '') ? (int)$v   : null; };
    $toFloat= function($v) { return ($v !== null && $v !== '') ? (float)$v : null; };
    $toDate = function($v) { return ($v !== null && $v !== '' && strtotime($v)) ? date('Y-m-d', strtotime($v)) : null; };
    $toStr  = function($v) { return ($v !== null && $v !== '') ? $v         : null; };

    $count = 0;
    foreach ($records as $row) {
        // 坪数は面積（㎡）×0.3025で自動計算
        $menseki = $toFloat($row['menseki_m2'] ?? null);
        $tsubo   = ($menseki !== null) ? round($menseki * 0.3025, 2) : null;

        $stmt->execute([
            ':kanri_bangou'      => $toStr($row['kanri_bangou']      ?? null),
            ':bukken_mei'        => $toStr($row['bukken_mei']        ?? ''),
            ':category'          => $toStr($row['category']          ?? null),
            ':bukken_shurui'     => $toStr($row['bukken_shurui']     ?? null),
            ':yoto'              => $toStr($row['yoto']              ?? null),
            ':shozaichi'         => $toStr($row['shozaichi']         ?? null),
            ':rosen'             => $toStr($row['rosen']             ?? null),
            ':moyori_eki'        => $toStr($row['moyori_eki']        ?? null),
            ':toho_fun'          => $toInt($row['toho_fun']          ?? null),
            ':yachin'            => $toInt($row['yachin']            ?? null),
            ':shohizei'          => $toInt($row['shohizei']          ?? null),
            ':kyoekihi'          => $toStr($row['kyoekihi']          ?? null),
            ':shikikin'          => $toInt($row['shikikin']          ?? null),
            ':reikin'            => $toInt($row['reikin']            ?? null),
            ':menseki_m2'        => $menseki,
            ':tsubo'             => $tsubo,
            ':shozai_kai'        => $toStr($row['shozai_kai']        ?? null),
            ':madori'            => $toStr($row['madori']            ?? null),
            ':kozo_kibo'         => $toStr($row['kozo_kibo']         ?? null),
            ':chikunen_tsuki'    => $toStr($row['chikunen_tsuki']    ?? null),
            ':keiyaku_kikan'     => $toStr($row['keiyaku_kikan']     ?? null),
            ':jido_koshin'       => $toStr($row['jido_koshin']       ?? null),
            ':chushajo'          => $toStr($row['chushajo']          ?? null),
            ':hontai_setsubi'    => $toStr($row['hontai_setsubi']    ?? null),
            ':kakuko_setsubi'    => $toStr($row['kakuko_setsubi']    ?? null),
            ':joken'             => $toStr($row['joken']             ?? null),
            ':torihiki_yosu'     => $toStr($row['torihiki_yosu']     ?? null),
            ':tesuryo'           => $toStr($row['tesuryo']           ?? null),
            ':annai_hoho'        => $toStr($row['annai_hoho']        ?? null),
            ':biko'              => $toStr($row['biko']              ?? null),
            ':keisai_date'       => $toDate($row['keisai_date']      ?? null),
            ':kakunin_date'      => $toDate($row['kakunin_date']     ?? null),
            ':kanri_gaisha'      => $toStr($row['kanri_gaisha']      ?? null),
            ':status'            => $toStr($row['status']            ?? null),
            ':ad_posting'        => $toStr($row['ad_posting']        ?? null),
            ':contract_type'     => $toStr($row['contract_type']     ?? null),
            ':current_situation' => $toStr($row['current_situation'] ?? null),
            ':property_condition'=> $toStr($row['property_condition']?? null),
            ':staff_name'        => $toStr($row['staff_name']        ?? null),
            ':staff_contact'     => $toStr($row['staff_contact']     ?? null),
            ':frontage'          => $toStr($row['frontage']          ?? null),
            ':ceiling_height'    => $toStr($row['ceiling_height']    ?? null),
            ':room_number'       => $toStr($row['room_number']       ?? null),
            ':photo_path'        => $toStr($row['photo_path']        ?? null),
        ]);
        $count++;
    }

    $pdo->commit();
    echo json_encode(['success' => true, 'message' => $count . '件のデータを更新しました']);

} catch (Exception $e) {
    if ($pdo->inTransaction()) { $pdo->rollBack(); }
    echo json_encode(['success' => false, 'message' => 'DBエラー: ' . $e->getMessage()]);
}
