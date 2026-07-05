// ===================================================
// Google Apps Script - 八咫烏不動産 DB連携
// スプレッドシートの「拡張機能」→「Apps Script」で貼り付け
// ===================================================

var IMPORT_URL = 'https://yatagarasu-fudousan.co.jp/database/import.php';
var SECRET_KEY = 'yatagarasu_secret_2026';

// ===================================================
// スプレッドシート列順（実際のシートに合わせた正しいマッピング）
// ===================================================
// A(0)  管理番号
// B(1)  物件名
// C(2)  ステータス
// D(3)  カテゴリー
// E(4)  物件種目
// F(5)  用途
// G(6)  物件状態
// H(7)  所在地
// I(8)  路線
// J(9)  最寄り駅
// K(10) 徒歩（分）
// L(11) 家賃/賃料（円）
// M(12) 消費税（円）
// N(13) 共益費/管理費
// O(14) 敷金
// P(15) 礼金
// Q(16) 面積（㎡）
// R(17) 所在階
// S(18) 号室
// T(19) 間取り
// U(20) 間口
// V(21) 天井高
// W(22) 構造規模
// X(23) 築年月
// Y(24) 契約種別
// Z(25) 契約期間
// AA(26) 自動更新
// AB(27) 駐車場
// AC(28) 本体設備
// AD(29) 各戸設備
// AE(30) 条件
// AF(31) 取引態様
// AG(32) 手数料
// AH(33) 現況
// AI(34) 案内方法
// AJ(35) 広告掲載
// AK(36) 備考
// AL(37) 掲載日
// AM(38) 確認日
// AN(39) 管理会社
// AO(40) 担当者名
// AP(41) 担当者連絡先
// AQ(42) 画像名（例: sample.jpg）
// ===================================================

function onOpen() {
  SpreadsheetApp.getUi()
    .createMenu('🏢 DB連携')
    .addItem('▶ DBを更新する', 'sendToDatabase')
    .addToUi();
}

function sendToDatabase() {
  var ui = SpreadsheetApp.getUi();

  var confirm = ui.alert(
    'DB更新の確認',
    'スプレッドシートのデータをDBに反映します。\n現在のDB内容はすべて上書きされます。\n\n実行しますか？',
    ui.ButtonSet.YES_NO
  );
  if (confirm !== ui.Button.YES) {
    ui.alert('キャンセルしました。');
    return;
  }

  var sheet = SpreadsheetApp.getActiveSpreadsheet().getSheets()[0];
  var lastRow = sheet.getLastRow();

  if (lastRow < 2) {
    ui.alert('データがありません。');
    return;
  }

  var dataRange = sheet.getRange(2, 1, lastRow - 1, 43); // A2:AQ最終行
  var rows = dataRange.getValues();

  var keys = [
    'kanri_bangou',       // A(0)  管理番号
    'bukken_mei',         // B(1)  物件名
    'status',             // C(2)  ステータス
    'category',           // D(3)  カテゴリー
    'bukken_shurui',      // E(4)  物件種目
    'yoto',               // F(5)  用途
    'property_condition', // G(6)  物件状態
    'shozaichi',          // H(7)  所在地
    'rosen',              // I(8)  路線
    'moyori_eki',         // J(9)  最寄り駅
    'toho_fun',           // K(10) 徒歩（分）
    'yachin',             // L(11) 家賃/賃料（円）
    'shohizei',           // M(12) 消費税（円）
    'kyoekihi',           // N(13) 共益費/管理費
    'shikikin',           // O(14) 敷金
    'reikin',             // P(15) 礼金
    'menseki_m2',         // Q(16) 面積（㎡）
    'shozai_kai',         // R(17) 所在階
    'room_number',        // S(18) 号室
    'madori',             // T(19) 間取り
    'frontage',           // U(20) 間口
    'ceiling_height',     // V(21) 天井高
    'kozo_kibo',          // W(22) 構造規模
    'chikunen_tsuki',     // X(23) 築年月
    'contract_type',      // Y(24) 契約種別
    'keiyaku_kikan',      // Z(25) 契約期間
    'jido_koshin',        // AA(26) 自動更新
    'chushajo',           // AB(27) 駐車場
    'hontai_setsubi',     // AC(28) 本体設備
    'kakuko_setsubi',     // AD(29) 各戸設備
    'joken',              // AE(30) 条件
    'torihiki_yosu',      // AF(31) 取引態様
    'tesuryo',            // AG(32) 手数料
    'current_situation',  // AH(33) 現況
    'annai_hoho',         // AI(34) 案内方法
    'ad_posting',         // AJ(35) 広告掲載
    'biko',               // AK(36) 備考
    'keisai_date',        // AL(37) 掲載日
    'kakunin_date',       // AM(38) 確認日
    'kanri_gaisha',       // AN(39) 管理会社
    'staff_name',         // AO(40) 担当者名
    'staff_contact',      // AP(41) 担当者連絡先
    'photo_path'          // AQ(42) 画像名（例: sample.jpg）
  ];

  var records = [];
  rows.forEach(function(row) {
    if (!row[1]) return; // 物件名(B列)が空の行はスキップ

    var record = {};
    keys.forEach(function(key, i) {
      var val = row[i];
      if (val instanceof Date) {
        val = Utilities.formatDate(val, 'Asia/Tokyo', 'yyyy-MM-dd');
      }
      record[key] = (val === '' || val === null || val === undefined) ? null : String(val);
    });
    records.push(record);
  });

  if (records.length === 0) {
    ui.alert('有効なデータがありません。');
    return;
  }

  var payload = JSON.stringify({ secret: SECRET_KEY, data: records });

  var options = {
    method:      'post',
    contentType: 'application/json',
    payload:     payload,
    muteHttpExceptions: true,
    headers: {
      'Authorization': 'Basic ' + Utilities.base64Encode('yatagarasu:bukkendb')
    }
  };

  var rawContent = '';
  try {
    var response = UrlFetchApp.fetch(IMPORT_URL, options);
    rawContent = response.getContentText();
    Logger.log('HTTP Status: ' + response.getResponseCode());
    Logger.log('Response: ' + rawContent.substring(0, 500));

    var result = JSON.parse(rawContent);
    if (result.success) {
      ui.alert('✅ 完了', result.message + '\n（' + records.length + '件を更新しました）', ui.ButtonSet.OK);
    } else {
      ui.alert('❌ エラー', result.message, ui.ButtonSet.OK);
    }
  } catch (e) {
    ui.alert('❌ 通信エラー', e.message + '\n\nサーバーの返答:\n' + rawContent.substring(0, 300), ui.ButtonSet.OK);
  }
}
