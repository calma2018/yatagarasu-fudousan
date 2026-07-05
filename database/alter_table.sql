-- =============================================
-- bukkenテーブル 構造変更
-- phpMyAdmin で実行してください
-- =============================================

-- 1. 不要カラムの削除
ALTER TABLE bukken
  DROP COLUMN hosho_gaisha,
  DROP COLUMN hoken;

-- 2. 新規カラムの追加
ALTER TABLE bukken
  ADD COLUMN status             VARCHAR(100)  NULL COMMENT 'ステータス'     AFTER kanri_gaisha,
  ADD COLUMN ad_posting         VARCHAR(255)  NULL COMMENT '広告掲載'       AFTER status,
  ADD COLUMN contract_type      VARCHAR(50)   NULL COMMENT '契約種別'       AFTER ad_posting,
  ADD COLUMN current_situation  TEXT          NULL COMMENT '現況'           AFTER contract_type,
  ADD COLUMN property_condition VARCHAR(50)   NULL COMMENT '物件状態'       AFTER current_situation,
  ADD COLUMN staff_name         VARCHAR(100)  NULL COMMENT '担当者名'       AFTER property_condition,
  ADD COLUMN staff_contact      VARCHAR(100)  NULL COMMENT '担当者連絡先'   AFTER staff_name,
  ADD COLUMN frontage           VARCHAR(100)  NULL COMMENT '間口'           AFTER staff_contact,
  ADD COLUMN ceiling_height     VARCHAR(100)  NULL COMMENT '天井高'         AFTER frontage,
  ADD COLUMN room_number        VARCHAR(50)   NULL COMMENT '号室'           AFTER ceiling_height,
  ADD COLUMN photo_path         VARCHAR(500)  NULL COMMENT '物件写真パス'   AFTER room_number;

-- 確認
SELECT COLUMN_NAME, COLUMN_TYPE, COLUMN_COMMENT
FROM INFORMATION_SCHEMA.COLUMNS
WHERE TABLE_NAME = 'bukken'
ORDER BY ORDINAL_POSITION;
