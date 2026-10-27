// ===================================================
// Google Apps Script - 八咫烏不動産 DB連携
// スプレッドシートの「拡張機能」→「Apps Script」で貼り付け
// ※ 2026年8月 シート大幅改修版に対応
// ===================================================

var IMPORT_URL = 'https://yatagarasu-fudousan.co.jp/database/import.php';
var SECRET_KEY = 'yatagarasu_secret_2026';

// ===================================================
// スプレッドシート列順（database シートに合わせた正しいマッピング）
// ===================================================
// A(0)   管理番号
// B(1)   物件名
// C(2)   ステータス
// D(3)   カテゴリー
// E(4)   物件種目
// F(5)   用途1
// G(6)   用途2
// H(7)   用途3
// I(8)   用途4
// J(9)   用途5
// K(10)  用途6
// L(11)  用途7
// M(12)  用途8            ※ F〜M は結合して1つの yoto として送信
// N(13)  物件状態
// O(14)  市町村
// P(15)  所在地
// Q(16)  路線
// R(17)  最寄り駅
// S(18)  徒歩（分）
// T(19)  家賃/賃料（円）
// U(20)  消費税（円）
// V(21)  共益費/管理費
// W(22)  敷金
// X(23)  礼金
// Y(24)  面積（㎡）
// Z(25)  所在階
// AA(26) 号室
// AB(27) 間取り
// AC(28) 間口
// AD(29) 天井高
// AE(30) 構造規模
// AF(31) 築年月
// AG(32) 契約種別
// AH(33) 契約期間
// AI(34) 自動更新
// AJ(35) 駐車場
// AK(36) 本体設備
// AL(37) 各戸設備
// AM(38) 条件
// AN(39) 取引態様
// AO(40) 手数料有無
// AP(41) 手数料
// AQ(42) 現況
// AR(43) 案内方法選択（当社・不明・立会・現地）
// AS(44) 案内方法（詳細）
// AT(45) 広告掲載可否
// AU(46) 広告内容
// AV(47) 備考
// AW(48) 掲載日
// AX(49) 確認日
// AY(50) 管理会社
// AZ(51) 担当者名
// BA(52) 担当者連絡先
// BB(53) 画像名（例: img_001.png）
// ===================================================

var LAST_COL = 54; // A〜BB

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

  var sheet = SpreadsheetApp.getActiveSpreadsheet().getSheetByName('database')
            || SpreadsheetApp.getActiveSpreadsheet().getSheets()[0];
  var lastRow = sheet.getLastRow();

  if (lastRow < 2) {
    ui.alert('データがありません。');
    return;
  }

  var dataRange = sheet.getRange(2, 1, lastRow - 1, LAST_COL); // A2:BB最終行
  var rows = dataRange.getValues();

  // F(5)〜M(12) の8列（用途1〜8）はまとめて1つの yoto にする
  var YOTO_START = 5;
  var YOTO_END   = 12; // inclusive

  // yoto以降の項目は、F:M を1項目分に圧縮した後のインデックスに対応
  var keysAfterYoto = [
    'property_condition',  // N(13)  物件状態
    'shichoson',            // O(14)  市町村
    'shozaichi',             // P(15)  所在地
    'rosen',                 // Q(16)  路線
    'moyori_eki',            // R(17)  最寄り駅
    'toho_fun',              // S(18)  徒歩（分）
    'yachin',                // T(19)  家賃/賃料（円）
    'shohizei',              // U(20)  消費税（円）
    'kyoekihi',              // V(21)  共益費/管理費
    'shikikin',              // W(22)  敷金
    'reikin',                // X(23)  礼金
    'menseki_m2',            // Y(24)  面積（㎡）
    'shozai_kai',            // Z(25)  所在階
    'room_number',           // AA(26) 号室
    'madori',                // AB(27) 間取り
    'frontage',              // AC(28) 間口
    'ceiling_height',        // AD(29) 天井高
    'kozo_kibo',             // AE(30) 構造規模
    'chikunen_tsuki',        // AF(31) 築年月
    'contract_type',         // AG(32) 契約種別
    'keiyaku_kikan',         // AH(33) 契約期間
    'jido_koshin',           // AI(34) 自動更新
    'chushajo',              // AJ(35) 駐車場
    'hontai_setsubi',        // AK(36) 本体設備
    'kakuko_setsubi',        // AL(37) 各戸設備
    'joken',                 // AM(38) 条件
    'torihiki_yosu',         // AN(39) 取引態様
    'tesuryo_umu',           // AO(40) 手数料有無
    'tesuryo',               // AP(41) 手数料
    'current_situation',     // AQ(42) 現況
    'annai_hoho_kubun',      // AR(43) 案内方法選択
    'annai_hoho',            // AS(44) 案内方法（詳細）
    'ad_posting_kahi',       // AT(45) 広告掲載可否
    'ad_naiyo',              // AU(46) 広告内容
    'biko',                  // AV(47) 備考
    'keisai_date',           // AW(48) 掲載日
    'kakunin_date',          // AX(49) 確認日
    'kanri_gaisha',          // AY(50) 管理会社
    'staff_name',            // AZ(51) 担当者名
    'staff_contact',         // BA(52) 担当者連絡先
    'photo_path'             // BB(53) 画像名
  ];

  function normalize(val) {
    if (val instanceof Date) {
      return Utilities.formatDate(val, 'Asia/Tokyo', 'yyyy-MM-dd');
    }
    return (val === '' || val === null || val === undefined) ? null : String(val);
  }

  var records = [];
  rows.forEach(function(row) {
    if (!row[1]) return; // 物件名(B列)が空の行はスキップ

    var record = {};
    record['kanri_bangou'] = normalize(row[0]);
    record['bukken_mei']   = normalize(row[1]);
    record['status']       = normalize(row[2]);
    record['category']     = normalize(row[3]);
    record['bukken_shurui']= normalize(row[4]);

    // 用途1〜8（F:M）を「/」区切りで結合
    var yotoParts = [];
    for (var c = YOTO_START; c <= YOTO_END; c++) {
      var v = normalize(row[c]);
      if (v) yotoParts.push(v);
    }
    record['yoto'] = yotoParts.length ? yotoParts.join('/') : null;

    // N(13)以降を順番にマッピング
    keysAfterYoto.forEach(function(key, i) {
      var colIndex = (YOTO_END + 1) + i; // N列 = 13
      record[key] = normalize(row[colIndex]);
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
