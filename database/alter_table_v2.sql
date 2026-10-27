-- =============================================
-- bukkenテーブル 構造変更 v2
-- 元スプレッドシート大幅改修に伴う対応
-- phpMyAdmin で実行してください
-- =============================================

-- 1. 用途（用途1〜8列を結合して格納するため、長さを拡張）
ALTER TABLE bukken
  MODIFY COLUMN yoto VARCHAR(255) NULL COMMENT '用途（用途1〜8を結合）';

-- 2. 不要カラムの削除（広告掲載→広告掲載可否／広告内容に分割）
ALTER TABLE bukken
  DROP COLUMN ad_posting;

-- 3. 新規カラムの追加
ALTER TABLE bukken
  ADD COLUMN shichoson         VARCHAR(50)   NULL COMMENT '市町村'         AFTER property_condition,
  ADD COLUMN tesuryo_umu       VARCHAR(10)   NULL COMMENT '手数料有無'     AFTER tesuryo,
  ADD COLUMN annai_hoho_kubun  VARCHAR(20)   NULL COMMENT '案内方法（区分・検索用）' AFTER annai_hoho,
  ADD COLUMN ad_posting_kahi   VARCHAR(20)   NULL COMMENT '広告掲載可否'   AFTER annai_hoho_kubun,
  ADD COLUMN ad_naiyo          TEXT          NULL COMMENT '広告内容'       AFTER ad_posting_kahi;

-- 確認は phpMyAdmin の「構造」タブで行ってください
-- （共有サーバーの権限によっては INFORMATION_SCHEMA への問い合わせが拒否されるため、
--   確認用SELECT文はこのファイルから削除しています）
