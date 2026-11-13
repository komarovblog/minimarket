-- MiniMarket 0.6.18: cumulative demo catalog UPDATE for MySQL 8.4 / Joomla 6.
-- Demo product data unchanged. Zone/promo schema is handled by Joomla extension migrations. Do not import this SQL for UI or schema changes.
-- Database: minimarket; prefix nnop3_. Import into EXISTING catalog after installing component.
-- First-time seed AND repeated imports supported. Existing demo rows and manual edits are preserved;
-- missing demo rows, SKU, relationships, bundles, pricing, badges and benefits are added.
-- Stable demo IDs/aliases/SKUs are checked before write; a collision aborts safely.
-- DO NOT import fresh-only 0.5.56 SQL into a populated catalog.
-- Never resets Joomla or user rows. Back up the database before import.
-- Pictures sample-01.png..sample-10.png are REFERENCES; files must exist separately.
-- Note: global shop type is configured in Joomla, not by this SQL.

-- Base records originate from the original 0.5.50 demo dataset (MySQL 8.4 / Joomla 6).
-- This file has been adapted for cumulative, repeatable updates on an EXISTING demo catalog.
-- Prepared for database minimarket; requires 10 files images/minimarket/sample-01.png ... sample-10.png.
-- All catalog and price data are FICTIONAL, for tests only.
-- Never touches Joomla system tables, existing images or PHP files.
-- No permanent DROP, DELETE, ALTER, TRUNCATE, or FOREIGN_KEY_CHECKS=0.
-- Make a complete database backup before import.
USE `minimarket`;
SET NAMES utf8mb4;
START TRANSACTION;

-- Guard all reserved demo IDs and natural keys before inserting anything.
-- A collision with a non-demo record aborts with CHECK error 3819. No user records are overwritten.
DROP TEMPORARY TABLE IF EXISTS `_mm_catalog_identity_guard`;
CREATE TEMPORARY TABLE `_mm_catalog_identity_guard` (
 collisions BIGINT UNSIGNED NOT NULL,
 CONSTRAINT `ck_mm_catalog_identity` CHECK (collisions=0)
) ENGINE=InnoDB;
INSERT INTO `_mm_catalog_identity_guard` (collisions)
SELECT
 (SELECT COUNT(*) FROM `nnop3_minimarket_categories` WHERE `id` IN (101,102,103,104,105,106,107,108,109,110,111,112,113,114,115,116,117,118,119,120,121,122,123,124,125,126,127) AND NOT (`alias` <=> CASE `id` WHEN 101 THEN 'myagkie-igrushki' WHEN 102 THEN 'plyushevye-medvedi' WHEN 103 THEN 'zveryata-iz-plyusha' WHEN 104 THEN 'konstruktory' WHEN 105 THEN 'blochnye-konstruktory' WHEN 106 THEN 'magnitnye-konstruktory' WHEN 107 THEN 'nastolnye-igry' WHEN 108 THEN 'semejnye-igry' WHEN 109 THEN 'obuchayushchie-igry' WHEN 110 THEN 'igrushki-dlya-malyshej' WHEN 111 THEN 'sortery-i-piramidki' WHEN 112 THEN 'muzykalnye-igrushki' WHEN 113 THEN 'mashinki-i-transport' WHEN 114 THEN 'legkovye-mashinki' WHEN 115 THEN 'spetstekhnika' WHEN 116 THEN 'tvorchestvo-i-hobbi' WHEN 117 THEN 'risovanie' WHEN 118 THEN 'lepka-i-modelirovanie' WHEN 119 THEN 'kukly-i-aksessuary' WHEN 120 THEN 'kukly' WHEN 121 THEN 'kukolnye-domiki' WHEN 122 THEN 'pazly' WHEN 123 THEN 'derevyannye-pazly' WHEN 124 THEN 'kartonnye-pazly' WHEN 125 THEN 'nastennye-razvivayushchie-paneli' WHEN 126 THEN 'magnitnye-steny' WHEN 127 THEN 'trassy-dlya-sharikov' END))
 + (SELECT COUNT(*) FROM `nnop3_minimarket_products` WHERE `id` IN (1001,1002,1003,1004,1005,1006,1007,1008,1009,1010,1011,1012,1013,1014,1015,1016,1017,1018,1019,1020,1021,1022,1023,1024,1025,1026,1027,1028,1029,1030,1031,1032,1033,1034,1035,1036,1037,1038,1039,1040,1041,1042,1043,1044,1045,1046,1047,1048,1049,1050,1051,1052,1053,1054,1055,1056,1057,1058,1059,1060,1061,1062,1063,1064,1065,1066,1067,1068,1069,1070,1071,1072,1073,1074,1075,1076,1077,1090,1091,1092) AND NOT (`alias` <=> CASE `id` WHEN 1001 THEN 'bear-tyoma' WHEN 1002 THEN 'bear-polar' WHEN 1003 THEN 'bear-bruno' WHEN 1004 THEN 'bear-honey' WHEN 1005 THEN 'bear-sailor' WHEN 1006 THEN 'rabbit-ushastik' WHEN 1007 THEN 'fox-ryzhik' WHEN 1008 THEN 'cat-murchik' WHEN 1009 THEN 'hedgehog-shurshik' WHEN 1010 THEN 'panda-sonya' WHEN 1011 THEN 'city-blocks' WHEN 1012 THEN 'cosmo-block' WHEN 1013 THEN 'castle-bricks' WHEN 1014 THEN 'train-bricks' WHEN 1015 THEN 'dino-park' WHEN 1016 THEN 'mag-geomir' WHEN 1017 THEN 'mag-farm' WHEN 1018 THEN 'mag-orbit' WHEN 1019 THEN 'mag-mosaic' WHEN 1020 THEN 'game-forest-race' WHEN 1021 THEN 'game-fastest' WHEN 1022 THEN 'game-color-stories' WHEN 1023 THEN 'game-travel' WHEN 1024 THEN 'game-questions' WHEN 1025 THEN 'edu-memory' WHEN 1026 THEN 'edu-count' WHEN 1027 THEN 'edu-letters' WHEN 1028 THEN 'edu-clock' WHEN 1029 THEN 'sorter-house' WHEN 1030 THEN 'rings-rainbow' WHEN 1031 THEN 'busy-cube' WHEN 1032 THEN 'rainbow-arch' WHEN 1033 THEN 'music-drum' WHEN 1034 THEN 'music-xylophone' WHEN 1035 THEN 'music-rattle' WHEN 1036 THEN 'music-piano' WHEN 1037 THEN 'car-city' WHEN 1038 THEN 'car-bolid' WHEN 1039 THEN 'car-bus' WHEN 1040 THEN 'car-offroad' WHEN 1041 THEN 'truck-fire' WHEN 1042 THEN 'truck-excavator' WHEN 1043 THEN 'truck-mixer' WHEN 1044 THEN 'truck-crane' WHEN 1045 THEN 'art-first-album' WHEN 1046 THEN 'art-markers' WHEN 1047 THEN 'art-watercolor' WHEN 1048 THEN 'art-artist-kit' WHEN 1049 THEN 'clay-dough' WHEN 1050 THEN 'clay-sand' WHEN 1051 THEN 'clay-soft' WHEN 1052 THEN 'clay-molds' WHEN 1053 THEN 'doll-anya' WHEN 1054 THEN 'doll-nastya' WHEN 1055 THEN 'doll-liza' WHEN 1056 THEN 'doll-sonya' WHEN 1057 THEN 'dollhouse-sunny' WHEN 1058 THEN 'dollhouse-furniture' WHEN 1059 THEN 'dollhouse-kitchen' WHEN 1060 THEN 'dollhouse-bedroom' WHEN 1061 THEN 'puzzle-africa' WHEN 1062 THEN 'puzzle-alphabet' WHEN 1063 THEN 'puzzle-map' WHEN 1064 THEN 'puzzle-cars' WHEN 1065 THEN 'puzzle-space' WHEN 1066 THEN 'puzzle-ocean' WHEN 1067 THEN 'puzzle-castle' WHEN 1068 THEN 'puzzle-kittens' WHEN 1069 THEN 'wall-magnetic' WHEN 1070 THEN 'wall-gears' WHEN 1071 THEN 'wall-draw' WHEN 1072 THEN 'track-marble' WHEN 1073 THEN 'track-zigzag' WHEN 1074 THEN 'track-waterfall' WHEN 1075 THEN 'track-turn' WHEN 1076 THEN 'mag-spiral-prototype' WHEN 1077 THEN 'track-loop-prototype' WHEN 1090 THEN 'demo-bundle-bears' WHEN 1091 THEN 'demo-bundle-engineer' WHEN 1092 THEN 'demo-bundle-friends' END AND `sku` <=> CASE `id` WHEN 1001 THEN 'TOY-1001' WHEN 1002 THEN 'TOY-1002' WHEN 1003 THEN 'TOY-1003' WHEN 1004 THEN 'TOY-1004' WHEN 1005 THEN 'TOY-1005' WHEN 1006 THEN 'TOY-1006' WHEN 1007 THEN 'TOY-1007' WHEN 1008 THEN 'TOY-1008' WHEN 1009 THEN 'TOY-1009' WHEN 1010 THEN 'TOY-1010' WHEN 1011 THEN 'TOY-1011' WHEN 1012 THEN 'TOY-1012' WHEN 1013 THEN 'TOY-1013' WHEN 1014 THEN 'TOY-1014' WHEN 1015 THEN 'TOY-1015' WHEN 1016 THEN 'TOY-1016' WHEN 1017 THEN 'TOY-1017' WHEN 1018 THEN 'TOY-1018' WHEN 1019 THEN 'TOY-1019' WHEN 1020 THEN 'TOY-1020' WHEN 1021 THEN 'TOY-1021' WHEN 1022 THEN 'TOY-1022' WHEN 1023 THEN 'TOY-1023' WHEN 1024 THEN 'TOY-1024' WHEN 1025 THEN 'TOY-1025' WHEN 1026 THEN 'TOY-1026' WHEN 1027 THEN 'TOY-1027' WHEN 1028 THEN 'TOY-1028' WHEN 1029 THEN 'TOY-1029' WHEN 1030 THEN 'TOY-1030' WHEN 1031 THEN 'TOY-1031' WHEN 1032 THEN 'TOY-1032' WHEN 1033 THEN 'TOY-1033' WHEN 1034 THEN 'TOY-1034' WHEN 1035 THEN 'TOY-1035' WHEN 1036 THEN 'TOY-1036' WHEN 1037 THEN 'TOY-1037' WHEN 1038 THEN 'TOY-1038' WHEN 1039 THEN 'TOY-1039' WHEN 1040 THEN 'TOY-1040' WHEN 1041 THEN 'TOY-1041' WHEN 1042 THEN 'TOY-1042' WHEN 1043 THEN 'TOY-1043' WHEN 1044 THEN 'TOY-1044' WHEN 1045 THEN 'TOY-1045' WHEN 1046 THEN 'TOY-1046' WHEN 1047 THEN 'TOY-1047' WHEN 1048 THEN 'TOY-1048' WHEN 1049 THEN 'TOY-1049' WHEN 1050 THEN 'TOY-1050' WHEN 1051 THEN 'TOY-1051' WHEN 1052 THEN 'TOY-1052' WHEN 1053 THEN 'TOY-1053' WHEN 1054 THEN 'TOY-1054' WHEN 1055 THEN 'TOY-1055' WHEN 1056 THEN 'TOY-1056' WHEN 1057 THEN 'TOY-1057' WHEN 1058 THEN 'TOY-1058' WHEN 1059 THEN 'TOY-1059' WHEN 1060 THEN 'TOY-1060' WHEN 1061 THEN 'TOY-1061' WHEN 1062 THEN 'TOY-1062' WHEN 1063 THEN 'TOY-1063' WHEN 1064 THEN 'TOY-1064' WHEN 1065 THEN 'TOY-1065' WHEN 1066 THEN 'TOY-1066' WHEN 1067 THEN 'TOY-1067' WHEN 1068 THEN 'TOY-1068' WHEN 1069 THEN 'TOY-1069' WHEN 1070 THEN 'TOY-1070' WHEN 1071 THEN 'TOY-1071' WHEN 1072 THEN 'TOY-1072' WHEN 1073 THEN 'TOY-1073' WHEN 1074 THEN 'TOY-1074' WHEN 1075 THEN 'TOY-1075' WHEN 1076 THEN 'TOY-1076' WHEN 1077 THEN 'TOY-1077' WHEN 1090 THEN 'BUNDLE-1090' WHEN 1091 THEN 'BUNDLE-1091' WHEN 1092 THEN 'BUNDLE-1092' END))
 + (SELECT COUNT(*) FROM `nnop3_minimarket_attributes` WHERE `id` IN (201,202,203,204,205,206,207,208,209,210,211,212) AND NOT (`code` <=> CASE `id` WHEN 201 THEN 'age' WHEN 202 THEN 'material' WHEN 203 THEN 'color' WHEN 204 THEN 'skill' WHEN 205 THEN 'pieces' WHEN 206 THEN 'players' WHEN 207 THEN 'weight' WHEN 208 THEN 'length' WHEN 209 THEN 'battery' WHEN 210 THEN 'gift' WHEN 211 THEN 'difficulty' WHEN 212 THEN 'playtype' END))
 + (SELECT COUNT(*) FROM `nnop3_minimarket_attribute_values` WHERE `id` IN (301,302,303,304,305,306,307,308,309,310,311,312,313) AND NOT (`attribute_id` <=> CASE `id` WHEN 301 THEN 203 WHEN 302 THEN 203 WHEN 303 THEN 203 WHEN 304 THEN 203 WHEN 305 THEN 203 WHEN 306 THEN 203 WHEN 307 THEN 203 WHEN 308 THEN 203 WHEN 309 THEN 203 WHEN 310 THEN 203 WHEN 311 THEN 203 WHEN 312 THEN 203 WHEN 313 THEN 203 END AND `code` <=> CASE `id` WHEN 301 THEN 'beige' WHEN 302 THEN 'white' WHEN 303 THEN 'brown' WHEN 304 THEN 'pink' WHEN 305 THEN 'blue' WHEN 306 THEN 'red' WHEN 307 THEN 'orange' WHEN 308 THEN 'gray' WHEN 309 THEN 'green' WHEN 310 THEN 'black' WHEN 311 THEN 'yellow' WHEN 312 THEN 'purple' WHEN 313 THEN 'multicolor' END))
 + (SELECT COUNT(*) FROM `nnop3_minimarket_order_options` WHERE `id` IN (401,402,403,404,405,406) AND NOT (`code` <=> CASE `id` WHEN 401 THEN 'size' WHEN 402 THEN 'execution-color' WHEN 403 THEN 'kit' WHEN 404 THEN 'gift-wrap' WHEN 405 THEN 'set-type' WHEN 406 THEN 'track-length' END))
 + (SELECT COUNT(*) FROM `nnop3_minimarket_order_option_values` WHERE `id` IN (501,502,503,504,505,506,507,508,509,510,511,512,513,514,515,516,517,518) AND NOT (`option_id` <=> CASE `id` WHEN 501 THEN 401 WHEN 502 THEN 401 WHEN 503 THEN 401 WHEN 504 THEN 402 WHEN 505 THEN 402 WHEN 506 THEN 402 WHEN 507 THEN 402 WHEN 508 THEN 403 WHEN 509 THEN 403 WHEN 510 THEN 403 WHEN 511 THEN 404 WHEN 512 THEN 404 WHEN 513 THEN 404 WHEN 514 THEN 405 WHEN 515 THEN 405 WHEN 516 THEN 406 WHEN 517 THEN 406 WHEN 518 THEN 406 END AND `code` <=> CASE `id` WHEN 501 THEN 'small' WHEN 502 THEN 'medium' WHEN 503 THEN 'large' WHEN 504 THEN 'beige' WHEN 505 THEN 'pink' WHEN 506 THEN 'sky' WHEN 507 THEN 'red' WHEN 508 THEN 'basic' WHEN 509 THEN 'extended' WHEN 510 THEN 'pro' WHEN 511 THEN 'none' WHEN 512 THEN 'kraft' WHEN 513 THEN 'bag' WHEN 514 THEN 'single' WHEN 515 THEN 'bundle' WHEN 516 THEN '60cm' WHEN 517 THEN '90cm' WHEN 518 THEN '120cm' END))
 + (SELECT COUNT(*) FROM `nnop3_minimarket_variant_dimensions` WHERE `id` IN (5001,5002,5003,5004,5005,5006,5007,5008) AND NOT (`product_id` <=> CASE `id` WHEN 5001 THEN 1001 WHEN 5002 THEN 1012 WHEN 5003 THEN 1012 WHEN 5004 THEN 1016 WHEN 5005 THEN 1048 WHEN 5006 THEN 1049 WHEN 5007 THEN 1054 WHEN 5008 THEN 1072 END AND `code` <=> CASE `id` WHEN 5001 THEN 'size' WHEN 5002 THEN 'set' WHEN 5003 THEN 'color' WHEN 5004 THEN 'set' WHEN 5005 THEN 'set' WHEN 5006 THEN 'set' WHEN 5007 THEN 'dress' WHEN 5008 THEN 'set' END))
 + (SELECT COUNT(*) FROM `nnop3_minimarket_variant_dimension_values` WHERE `id` IN (6001,6002,6003,6004,6005,6006,6007,6008,6009,6010,6011,6012,6013,6014,6015,6016,6017) AND NOT (`dimension_id` <=> CASE `id` WHEN 6001 THEN 5001 WHEN 6002 THEN 5001 WHEN 6003 THEN 5001 WHEN 6004 THEN 5002 WHEN 6005 THEN 5002 WHEN 6006 THEN 5003 WHEN 6007 THEN 5003 WHEN 6008 THEN 5004 WHEN 6009 THEN 5004 WHEN 6010 THEN 5005 WHEN 6011 THEN 5005 WHEN 6012 THEN 5006 WHEN 6013 THEN 5006 WHEN 6014 THEN 5007 WHEN 6015 THEN 5007 WHEN 6016 THEN 5008 WHEN 6017 THEN 5008 END AND `code` <=> CASE `id` WHEN 6001 THEN 'small' WHEN 6002 THEN 'medium' WHEN 6003 THEN 'large' WHEN 6004 THEN 'set60' WHEN 6005 THEN 'set120' WHEN 6006 THEN 'blue' WHEN 6007 THEN 'red' WHEN 6008 THEN 'set32' WHEN 6009 THEN 'set64' WHEN 6010 THEN 'light' WHEN 6011 THEN 'pro' WHEN 6012 THEN 'six' WHEN 6013 THEN 'twelve' WHEN 6014 THEN 'pink' WHEN 6015 THEN 'blue' WHEN 6016 THEN 'start' WHEN 6017 THEN 'plus' END))
 + (SELECT COUNT(*) FROM `nnop3_minimarket_product_variants` WHERE `id` IN (7001,7002,7003,7004,7005,7006,7007,7008,7009,7010,7011,7012,7013,7014,7015,7016) AND NOT (`product_id` <=> CASE `id` WHEN 7001 THEN 1001 WHEN 7002 THEN 1001 WHEN 7003 THEN 1001 WHEN 7004 THEN 1012 WHEN 7005 THEN 1012 WHEN 7006 THEN 1012 WHEN 7007 THEN 1016 WHEN 7008 THEN 1016 WHEN 7009 THEN 1048 WHEN 7010 THEN 1048 WHEN 7011 THEN 1049 WHEN 7012 THEN 1049 WHEN 7013 THEN 1054 WHEN 7014 THEN 1054 WHEN 7015 THEN 1072 WHEN 7016 THEN 1072 END AND `sku` <=> CASE `id` WHEN 7001 THEN 'SKU-1001-SMALL' WHEN 7002 THEN 'SKU-1001-MEDIUM' WHEN 7003 THEN 'SKU-1001-LARGE' WHEN 7004 THEN 'SKU-1012-SET60-BLUE' WHEN 7005 THEN 'SKU-1012-SET120-BLUE' WHEN 7006 THEN 'SKU-1012-SET60-RED' WHEN 7007 THEN 'SKU-1016-SET32' WHEN 7008 THEN 'SKU-1016-SET64' WHEN 7009 THEN 'SKU-1048-LIGHT' WHEN 7010 THEN 'SKU-1048-PRO' WHEN 7011 THEN 'SKU-1049-SIX' WHEN 7012 THEN 'SKU-1049-TWELVE' WHEN 7013 THEN 'SKU-1054-PINK' WHEN 7014 THEN 'SKU-1054-BLUE' WHEN 7015 THEN 'SKU-1072-START' WHEN 7016 THEN 'SKU-1072-PLUS' END))
 + (SELECT COUNT(*) FROM `nnop3_minimarket_product_images` WHERE `id` IN (10001,10002,10003,10004,10005,10006,10007,10008,10009,10010,10011,10012,10013,10014,10015,10016,10017,10018,10019,10020,10021,10022,10023,10024,10025,10026,10027,10028,10029,10030,10031,10032,10033,10034,10035,10036,10037,10038,10039,10040,10041,10042,10043,10044,10045,10046,10047,10048,10049,10050,10051,10052,10053,10054,10055,10056,10057,10058,10059,10060,10061,10062,10063,10064,10065,10066,10067,10068,10069,10070,10071,10072,10073,10074,10075,10076,10077) AND NOT (`product_id` <=> CASE `id` WHEN 10001 THEN 1001 WHEN 10002 THEN 1002 WHEN 10003 THEN 1003 WHEN 10004 THEN 1004 WHEN 10005 THEN 1005 WHEN 10006 THEN 1006 WHEN 10007 THEN 1007 WHEN 10008 THEN 1008 WHEN 10009 THEN 1009 WHEN 10010 THEN 1010 WHEN 10011 THEN 1011 WHEN 10012 THEN 1012 WHEN 10013 THEN 1013 WHEN 10014 THEN 1014 WHEN 10015 THEN 1015 WHEN 10016 THEN 1016 WHEN 10017 THEN 1017 WHEN 10018 THEN 1018 WHEN 10019 THEN 1019 WHEN 10020 THEN 1020 WHEN 10021 THEN 1021 WHEN 10022 THEN 1022 WHEN 10023 THEN 1023 WHEN 10024 THEN 1024 WHEN 10025 THEN 1025 WHEN 10026 THEN 1026 WHEN 10027 THEN 1027 WHEN 10028 THEN 1028 WHEN 10029 THEN 1029 WHEN 10030 THEN 1030 WHEN 10031 THEN 1031 WHEN 10032 THEN 1032 WHEN 10033 THEN 1033 WHEN 10034 THEN 1034 WHEN 10035 THEN 1035 WHEN 10036 THEN 1036 WHEN 10037 THEN 1037 WHEN 10038 THEN 1038 WHEN 10039 THEN 1039 WHEN 10040 THEN 1040 WHEN 10041 THEN 1041 WHEN 10042 THEN 1042 WHEN 10043 THEN 1043 WHEN 10044 THEN 1044 WHEN 10045 THEN 1045 WHEN 10046 THEN 1046 WHEN 10047 THEN 1047 WHEN 10048 THEN 1048 WHEN 10049 THEN 1049 WHEN 10050 THEN 1050 WHEN 10051 THEN 1051 WHEN 10052 THEN 1052 WHEN 10053 THEN 1053 WHEN 10054 THEN 1054 WHEN 10055 THEN 1055 WHEN 10056 THEN 1056 WHEN 10057 THEN 1057 WHEN 10058 THEN 1058 WHEN 10059 THEN 1059 WHEN 10060 THEN 1060 WHEN 10061 THEN 1061 WHEN 10062 THEN 1062 WHEN 10063 THEN 1063 WHEN 10064 THEN 1064 WHEN 10065 THEN 1065 WHEN 10066 THEN 1066 WHEN 10067 THEN 1067 WHEN 10068 THEN 1068 WHEN 10069 THEN 1069 WHEN 10070 THEN 1070 WHEN 10071 THEN 1071 WHEN 10072 THEN 1072 WHEN 10073 THEN 1073 WHEN 10074 THEN 1074 WHEN 10075 THEN 1075 WHEN 10076 THEN 1076 WHEN 10077 THEN 1077 END))
 + (SELECT COUNT(*) FROM `nnop3_minimarket_variant_value_images` WHERE `id` IN (8501,8502,8503,8504,8505,8506,8507,8508,8509,8510,8511,8512,8513,8514,8515,8516,8517) AND NOT (`value_id` <=> CASE `id` WHEN 8501 THEN 6001 WHEN 8502 THEN 6002 WHEN 8503 THEN 6003 WHEN 8504 THEN 6004 WHEN 8505 THEN 6005 WHEN 8506 THEN 6006 WHEN 8507 THEN 6007 WHEN 8508 THEN 6008 WHEN 8509 THEN 6009 WHEN 8510 THEN 6010 WHEN 8511 THEN 6011 WHEN 8512 THEN 6012 WHEN 8513 THEN 6013 WHEN 8514 THEN 6014 WHEN 8515 THEN 6015 WHEN 8516 THEN 6016 WHEN 8517 THEN 6017 END))
 + (SELECT COUNT(*) FROM `nnop3_minimarket_product_variant_images` WHERE `id` IN (8001,8002,8003,8004,8005,8006,8007,8008,8009,8010,8011,8012,8013,8014,8015,8016) AND NOT (`variant_id` <=> CASE `id` WHEN 8001 THEN 7001 WHEN 8002 THEN 7002 WHEN 8003 THEN 7003 WHEN 8004 THEN 7004 WHEN 8005 THEN 7005 WHEN 8006 THEN 7006 WHEN 8007 THEN 7007 WHEN 8008 THEN 7008 WHEN 8009 THEN 7009 WHEN 8010 THEN 7010 WHEN 8011 THEN 7011 WHEN 8012 THEN 7012 WHEN 8013 THEN 7013 WHEN 8014 THEN 7014 WHEN 8015 THEN 7015 WHEN 8016 THEN 7016 END))
 + (SELECT COUNT(*) FROM `nnop3_minimarket_product_groups` WHERE `id` IN (901,902,903,904,905,906,907,908,909,910) AND NOT (`title` <=> CASE `id` WHEN 901 THEN 'Плюшевые друзья' WHEN 902 THEN 'Подарки для малыша' WHEN 903 THEN 'Строим космический город' WHEN 904 THEN 'Для семейного вечера' WHEN 905 THEN 'Учимся считать и читать' WHEN 906 THEN 'Юный художник' WHEN 907 THEN 'Мир куклы Насти' WHEN 908 THEN 'Транспортный парк' WHEN 909 THEN 'Настенные механизмы' WHEN 910 THEN 'Подарки для любознательных' END))
 + (SELECT COUNT(*) FROM `nnop3_minimarket_categories` WHERE (`alias` <=> 'myagkie-igrushki' AND `id` <> 101) OR (`alias` <=> 'plyushevye-medvedi' AND `id` <> 102) OR (`alias` <=> 'zveryata-iz-plyusha' AND `id` <> 103) OR (`alias` <=> 'konstruktory' AND `id` <> 104) OR (`alias` <=> 'blochnye-konstruktory' AND `id` <> 105) OR (`alias` <=> 'magnitnye-konstruktory' AND `id` <> 106) OR (`alias` <=> 'nastolnye-igry' AND `id` <> 107) OR (`alias` <=> 'semejnye-igry' AND `id` <> 108) OR (`alias` <=> 'obuchayushchie-igry' AND `id` <> 109) OR (`alias` <=> 'igrushki-dlya-malyshej' AND `id` <> 110) OR (`alias` <=> 'sortery-i-piramidki' AND `id` <> 111) OR (`alias` <=> 'muzykalnye-igrushki' AND `id` <> 112) OR (`alias` <=> 'mashinki-i-transport' AND `id` <> 113) OR (`alias` <=> 'legkovye-mashinki' AND `id` <> 114) OR (`alias` <=> 'spetstekhnika' AND `id` <> 115) OR (`alias` <=> 'tvorchestvo-i-hobbi' AND `id` <> 116) OR (`alias` <=> 'risovanie' AND `id` <> 117) OR (`alias` <=> 'lepka-i-modelirovanie' AND `id` <> 118) OR (`alias` <=> 'kukly-i-aksessuary' AND `id` <> 119) OR (`alias` <=> 'kukly' AND `id` <> 120) OR (`alias` <=> 'kukolnye-domiki' AND `id` <> 121) OR (`alias` <=> 'pazly' AND `id` <> 122) OR (`alias` <=> 'derevyannye-pazly' AND `id` <> 123) OR (`alias` <=> 'kartonnye-pazly' AND `id` <> 124) OR (`alias` <=> 'nastennye-razvivayushchie-paneli' AND `id` <> 125) OR (`alias` <=> 'magnitnye-steny' AND `id` <> 126) OR (`alias` <=> 'trassy-dlya-sharikov' AND `id` <> 127))
 + (SELECT COUNT(*) FROM `nnop3_minimarket_products` WHERE (`alias` <=> 'bear-tyoma' AND `id` <> 1001) OR (`alias` <=> 'bear-polar' AND `id` <> 1002) OR (`alias` <=> 'bear-bruno' AND `id` <> 1003) OR (`alias` <=> 'bear-honey' AND `id` <> 1004) OR (`alias` <=> 'bear-sailor' AND `id` <> 1005) OR (`alias` <=> 'rabbit-ushastik' AND `id` <> 1006) OR (`alias` <=> 'fox-ryzhik' AND `id` <> 1007) OR (`alias` <=> 'cat-murchik' AND `id` <> 1008) OR (`alias` <=> 'hedgehog-shurshik' AND `id` <> 1009) OR (`alias` <=> 'panda-sonya' AND `id` <> 1010) OR (`alias` <=> 'city-blocks' AND `id` <> 1011) OR (`alias` <=> 'cosmo-block' AND `id` <> 1012) OR (`alias` <=> 'castle-bricks' AND `id` <> 1013) OR (`alias` <=> 'train-bricks' AND `id` <> 1014) OR (`alias` <=> 'dino-park' AND `id` <> 1015) OR (`alias` <=> 'mag-geomir' AND `id` <> 1016) OR (`alias` <=> 'mag-farm' AND `id` <> 1017) OR (`alias` <=> 'mag-orbit' AND `id` <> 1018) OR (`alias` <=> 'mag-mosaic' AND `id` <> 1019) OR (`alias` <=> 'game-forest-race' AND `id` <> 1020) OR (`alias` <=> 'game-fastest' AND `id` <> 1021) OR (`alias` <=> 'game-color-stories' AND `id` <> 1022) OR (`alias` <=> 'game-travel' AND `id` <> 1023) OR (`alias` <=> 'game-questions' AND `id` <> 1024) OR (`alias` <=> 'edu-memory' AND `id` <> 1025) OR (`alias` <=> 'edu-count' AND `id` <> 1026) OR (`alias` <=> 'edu-letters' AND `id` <> 1027) OR (`alias` <=> 'edu-clock' AND `id` <> 1028) OR (`alias` <=> 'sorter-house' AND `id` <> 1029) OR (`alias` <=> 'rings-rainbow' AND `id` <> 1030) OR (`alias` <=> 'busy-cube' AND `id` <> 1031) OR (`alias` <=> 'rainbow-arch' AND `id` <> 1032) OR (`alias` <=> 'music-drum' AND `id` <> 1033) OR (`alias` <=> 'music-xylophone' AND `id` <> 1034) OR (`alias` <=> 'music-rattle' AND `id` <> 1035) OR (`alias` <=> 'music-piano' AND `id` <> 1036) OR (`alias` <=> 'car-city' AND `id` <> 1037) OR (`alias` <=> 'car-bolid' AND `id` <> 1038) OR (`alias` <=> 'car-bus' AND `id` <> 1039) OR (`alias` <=> 'car-offroad' AND `id` <> 1040) OR (`alias` <=> 'truck-fire' AND `id` <> 1041) OR (`alias` <=> 'truck-excavator' AND `id` <> 1042) OR (`alias` <=> 'truck-mixer' AND `id` <> 1043) OR (`alias` <=> 'truck-crane' AND `id` <> 1044) OR (`alias` <=> 'art-first-album' AND `id` <> 1045) OR (`alias` <=> 'art-markers' AND `id` <> 1046) OR (`alias` <=> 'art-watercolor' AND `id` <> 1047) OR (`alias` <=> 'art-artist-kit' AND `id` <> 1048) OR (`alias` <=> 'clay-dough' AND `id` <> 1049) OR (`alias` <=> 'clay-sand' AND `id` <> 1050) OR (`alias` <=> 'clay-soft' AND `id` <> 1051) OR (`alias` <=> 'clay-molds' AND `id` <> 1052) OR (`alias` <=> 'doll-anya' AND `id` <> 1053) OR (`alias` <=> 'doll-nastya' AND `id` <> 1054) OR (`alias` <=> 'doll-liza' AND `id` <> 1055) OR (`alias` <=> 'doll-sonya' AND `id` <> 1056) OR (`alias` <=> 'dollhouse-sunny' AND `id` <> 1057) OR (`alias` <=> 'dollhouse-furniture' AND `id` <> 1058) OR (`alias` <=> 'dollhouse-kitchen' AND `id` <> 1059) OR (`alias` <=> 'dollhouse-bedroom' AND `id` <> 1060) OR (`alias` <=> 'puzzle-africa' AND `id` <> 1061) OR (`alias` <=> 'puzzle-alphabet' AND `id` <> 1062) OR (`alias` <=> 'puzzle-map' AND `id` <> 1063) OR (`alias` <=> 'puzzle-cars' AND `id` <> 1064) OR (`alias` <=> 'puzzle-space' AND `id` <> 1065) OR (`alias` <=> 'puzzle-ocean' AND `id` <> 1066) OR (`alias` <=> 'puzzle-castle' AND `id` <> 1067) OR (`alias` <=> 'puzzle-kittens' AND `id` <> 1068) OR (`alias` <=> 'wall-magnetic' AND `id` <> 1069) OR (`alias` <=> 'wall-gears' AND `id` <> 1070) OR (`alias` <=> 'wall-draw' AND `id` <> 1071) OR (`alias` <=> 'track-marble' AND `id` <> 1072) OR (`alias` <=> 'track-zigzag' AND `id` <> 1073) OR (`alias` <=> 'track-waterfall' AND `id` <> 1074) OR (`alias` <=> 'track-turn' AND `id` <> 1075) OR (`alias` <=> 'mag-spiral-prototype' AND `id` <> 1076) OR (`alias` <=> 'track-loop-prototype' AND `id` <> 1077) OR (`alias` <=> 'demo-bundle-bears' AND `id` <> 1090) OR (`alias` <=> 'demo-bundle-engineer' AND `id` <> 1091) OR (`alias` <=> 'demo-bundle-friends' AND `id` <> 1092))
 + (SELECT COUNT(*) FROM `nnop3_minimarket_products` WHERE (`sku` <=> 'TOY-1001' AND `id` <> 1001) OR (`sku` <=> 'TOY-1002' AND `id` <> 1002) OR (`sku` <=> 'TOY-1003' AND `id` <> 1003) OR (`sku` <=> 'TOY-1004' AND `id` <> 1004) OR (`sku` <=> 'TOY-1005' AND `id` <> 1005) OR (`sku` <=> 'TOY-1006' AND `id` <> 1006) OR (`sku` <=> 'TOY-1007' AND `id` <> 1007) OR (`sku` <=> 'TOY-1008' AND `id` <> 1008) OR (`sku` <=> 'TOY-1009' AND `id` <> 1009) OR (`sku` <=> 'TOY-1010' AND `id` <> 1010) OR (`sku` <=> 'TOY-1011' AND `id` <> 1011) OR (`sku` <=> 'TOY-1012' AND `id` <> 1012) OR (`sku` <=> 'TOY-1013' AND `id` <> 1013) OR (`sku` <=> 'TOY-1014' AND `id` <> 1014) OR (`sku` <=> 'TOY-1015' AND `id` <> 1015) OR (`sku` <=> 'TOY-1016' AND `id` <> 1016) OR (`sku` <=> 'TOY-1017' AND `id` <> 1017) OR (`sku` <=> 'TOY-1018' AND `id` <> 1018) OR (`sku` <=> 'TOY-1019' AND `id` <> 1019) OR (`sku` <=> 'TOY-1020' AND `id` <> 1020) OR (`sku` <=> 'TOY-1021' AND `id` <> 1021) OR (`sku` <=> 'TOY-1022' AND `id` <> 1022) OR (`sku` <=> 'TOY-1023' AND `id` <> 1023) OR (`sku` <=> 'TOY-1024' AND `id` <> 1024) OR (`sku` <=> 'TOY-1025' AND `id` <> 1025) OR (`sku` <=> 'TOY-1026' AND `id` <> 1026) OR (`sku` <=> 'TOY-1027' AND `id` <> 1027) OR (`sku` <=> 'TOY-1028' AND `id` <> 1028) OR (`sku` <=> 'TOY-1029' AND `id` <> 1029) OR (`sku` <=> 'TOY-1030' AND `id` <> 1030) OR (`sku` <=> 'TOY-1031' AND `id` <> 1031) OR (`sku` <=> 'TOY-1032' AND `id` <> 1032) OR (`sku` <=> 'TOY-1033' AND `id` <> 1033) OR (`sku` <=> 'TOY-1034' AND `id` <> 1034) OR (`sku` <=> 'TOY-1035' AND `id` <> 1035) OR (`sku` <=> 'TOY-1036' AND `id` <> 1036) OR (`sku` <=> 'TOY-1037' AND `id` <> 1037) OR (`sku` <=> 'TOY-1038' AND `id` <> 1038) OR (`sku` <=> 'TOY-1039' AND `id` <> 1039) OR (`sku` <=> 'TOY-1040' AND `id` <> 1040) OR (`sku` <=> 'TOY-1041' AND `id` <> 1041) OR (`sku` <=> 'TOY-1042' AND `id` <> 1042) OR (`sku` <=> 'TOY-1043' AND `id` <> 1043) OR (`sku` <=> 'TOY-1044' AND `id` <> 1044) OR (`sku` <=> 'TOY-1045' AND `id` <> 1045) OR (`sku` <=> 'TOY-1046' AND `id` <> 1046) OR (`sku` <=> 'TOY-1047' AND `id` <> 1047) OR (`sku` <=> 'TOY-1048' AND `id` <> 1048) OR (`sku` <=> 'TOY-1049' AND `id` <> 1049) OR (`sku` <=> 'TOY-1050' AND `id` <> 1050) OR (`sku` <=> 'TOY-1051' AND `id` <> 1051) OR (`sku` <=> 'TOY-1052' AND `id` <> 1052) OR (`sku` <=> 'TOY-1053' AND `id` <> 1053) OR (`sku` <=> 'TOY-1054' AND `id` <> 1054) OR (`sku` <=> 'TOY-1055' AND `id` <> 1055) OR (`sku` <=> 'TOY-1056' AND `id` <> 1056) OR (`sku` <=> 'TOY-1057' AND `id` <> 1057) OR (`sku` <=> 'TOY-1058' AND `id` <> 1058) OR (`sku` <=> 'TOY-1059' AND `id` <> 1059) OR (`sku` <=> 'TOY-1060' AND `id` <> 1060) OR (`sku` <=> 'TOY-1061' AND `id` <> 1061) OR (`sku` <=> 'TOY-1062' AND `id` <> 1062) OR (`sku` <=> 'TOY-1063' AND `id` <> 1063) OR (`sku` <=> 'TOY-1064' AND `id` <> 1064) OR (`sku` <=> 'TOY-1065' AND `id` <> 1065) OR (`sku` <=> 'TOY-1066' AND `id` <> 1066) OR (`sku` <=> 'TOY-1067' AND `id` <> 1067) OR (`sku` <=> 'TOY-1068' AND `id` <> 1068) OR (`sku` <=> 'TOY-1069' AND `id` <> 1069) OR (`sku` <=> 'TOY-1070' AND `id` <> 1070) OR (`sku` <=> 'TOY-1071' AND `id` <> 1071) OR (`sku` <=> 'TOY-1072' AND `id` <> 1072) OR (`sku` <=> 'TOY-1073' AND `id` <> 1073) OR (`sku` <=> 'TOY-1074' AND `id` <> 1074) OR (`sku` <=> 'TOY-1075' AND `id` <> 1075) OR (`sku` <=> 'TOY-1076' AND `id` <> 1076) OR (`sku` <=> 'TOY-1077' AND `id` <> 1077) OR (`sku` <=> 'BUNDLE-1090' AND `id` <> 1090) OR (`sku` <=> 'BUNDLE-1091' AND `id` <> 1091) OR (`sku` <=> 'BUNDLE-1092' AND `id` <> 1092))
 + (SELECT COUNT(*) FROM `nnop3_minimarket_attributes` WHERE (`code` <=> 'age' AND `id` <> 201) OR (`code` <=> 'material' AND `id` <> 202) OR (`code` <=> 'color' AND `id` <> 203) OR (`code` <=> 'skill' AND `id` <> 204) OR (`code` <=> 'pieces' AND `id` <> 205) OR (`code` <=> 'players' AND `id` <> 206) OR (`code` <=> 'weight' AND `id` <> 207) OR (`code` <=> 'length' AND `id` <> 208) OR (`code` <=> 'battery' AND `id` <> 209) OR (`code` <=> 'gift' AND `id` <> 210) OR (`code` <=> 'difficulty' AND `id` <> 211) OR (`code` <=> 'playtype' AND `id` <> 212))
 + (SELECT COUNT(*) FROM `nnop3_minimarket_attribute_values` WHERE (`attribute_id` <=> 203 AND `code` <=> 'beige' AND `id` <> 301) OR (`attribute_id` <=> 203 AND `code` <=> 'white' AND `id` <> 302) OR (`attribute_id` <=> 203 AND `code` <=> 'brown' AND `id` <> 303) OR (`attribute_id` <=> 203 AND `code` <=> 'pink' AND `id` <> 304) OR (`attribute_id` <=> 203 AND `code` <=> 'blue' AND `id` <> 305) OR (`attribute_id` <=> 203 AND `code` <=> 'red' AND `id` <> 306) OR (`attribute_id` <=> 203 AND `code` <=> 'orange' AND `id` <> 307) OR (`attribute_id` <=> 203 AND `code` <=> 'gray' AND `id` <> 308) OR (`attribute_id` <=> 203 AND `code` <=> 'green' AND `id` <> 309) OR (`attribute_id` <=> 203 AND `code` <=> 'black' AND `id` <> 310) OR (`attribute_id` <=> 203 AND `code` <=> 'yellow' AND `id` <> 311) OR (`attribute_id` <=> 203 AND `code` <=> 'purple' AND `id` <> 312) OR (`attribute_id` <=> 203 AND `code` <=> 'multicolor' AND `id` <> 313))
 + (SELECT COUNT(*) FROM `nnop3_minimarket_order_options` WHERE (`code` <=> 'size' AND `id` <> 401) OR (`code` <=> 'execution-color' AND `id` <> 402) OR (`code` <=> 'kit' AND `id` <> 403) OR (`code` <=> 'gift-wrap' AND `id` <> 404) OR (`code` <=> 'set-type' AND `id` <> 405) OR (`code` <=> 'track-length' AND `id` <> 406))
 + (SELECT COUNT(*) FROM `nnop3_minimarket_order_option_values` WHERE (`option_id` <=> 401 AND `code` <=> 'small' AND `id` <> 501) OR (`option_id` <=> 401 AND `code` <=> 'medium' AND `id` <> 502) OR (`option_id` <=> 401 AND `code` <=> 'large' AND `id` <> 503) OR (`option_id` <=> 402 AND `code` <=> 'beige' AND `id` <> 504) OR (`option_id` <=> 402 AND `code` <=> 'pink' AND `id` <> 505) OR (`option_id` <=> 402 AND `code` <=> 'sky' AND `id` <> 506) OR (`option_id` <=> 402 AND `code` <=> 'red' AND `id` <> 507) OR (`option_id` <=> 403 AND `code` <=> 'basic' AND `id` <> 508) OR (`option_id` <=> 403 AND `code` <=> 'extended' AND `id` <> 509) OR (`option_id` <=> 403 AND `code` <=> 'pro' AND `id` <> 510) OR (`option_id` <=> 404 AND `code` <=> 'none' AND `id` <> 511) OR (`option_id` <=> 404 AND `code` <=> 'kraft' AND `id` <> 512) OR (`option_id` <=> 404 AND `code` <=> 'bag' AND `id` <> 513) OR (`option_id` <=> 405 AND `code` <=> 'single' AND `id` <> 514) OR (`option_id` <=> 405 AND `code` <=> 'bundle' AND `id` <> 515) OR (`option_id` <=> 406 AND `code` <=> '60cm' AND `id` <> 516) OR (`option_id` <=> 406 AND `code` <=> '90cm' AND `id` <> 517) OR (`option_id` <=> 406 AND `code` <=> '120cm' AND `id` <> 518))
 + (SELECT COUNT(*) FROM `nnop3_minimarket_variant_dimensions` WHERE (`product_id` <=> 1001 AND `code` <=> 'size' AND `id` <> 5001) OR (`product_id` <=> 1012 AND `code` <=> 'set' AND `id` <> 5002) OR (`product_id` <=> 1012 AND `code` <=> 'color' AND `id` <> 5003) OR (`product_id` <=> 1016 AND `code` <=> 'set' AND `id` <> 5004) OR (`product_id` <=> 1048 AND `code` <=> 'set' AND `id` <> 5005) OR (`product_id` <=> 1049 AND `code` <=> 'set' AND `id` <> 5006) OR (`product_id` <=> 1054 AND `code` <=> 'dress' AND `id` <> 5007) OR (`product_id` <=> 1072 AND `code` <=> 'set' AND `id` <> 5008))
 + (SELECT COUNT(*) FROM `nnop3_minimarket_variant_dimension_values` WHERE (`dimension_id` <=> 5001 AND `code` <=> 'small' AND `id` <> 6001) OR (`dimension_id` <=> 5001 AND `code` <=> 'medium' AND `id` <> 6002) OR (`dimension_id` <=> 5001 AND `code` <=> 'large' AND `id` <> 6003) OR (`dimension_id` <=> 5002 AND `code` <=> 'set60' AND `id` <> 6004) OR (`dimension_id` <=> 5002 AND `code` <=> 'set120' AND `id` <> 6005) OR (`dimension_id` <=> 5003 AND `code` <=> 'blue' AND `id` <> 6006) OR (`dimension_id` <=> 5003 AND `code` <=> 'red' AND `id` <> 6007) OR (`dimension_id` <=> 5004 AND `code` <=> 'set32' AND `id` <> 6008) OR (`dimension_id` <=> 5004 AND `code` <=> 'set64' AND `id` <> 6009) OR (`dimension_id` <=> 5005 AND `code` <=> 'light' AND `id` <> 6010) OR (`dimension_id` <=> 5005 AND `code` <=> 'pro' AND `id` <> 6011) OR (`dimension_id` <=> 5006 AND `code` <=> 'six' AND `id` <> 6012) OR (`dimension_id` <=> 5006 AND `code` <=> 'twelve' AND `id` <> 6013) OR (`dimension_id` <=> 5007 AND `code` <=> 'pink' AND `id` <> 6014) OR (`dimension_id` <=> 5007 AND `code` <=> 'blue' AND `id` <> 6015) OR (`dimension_id` <=> 5008 AND `code` <=> 'start' AND `id` <> 6016) OR (`dimension_id` <=> 5008 AND `code` <=> 'plus' AND `id` <> 6017))
 + (SELECT COUNT(*) FROM `nnop3_minimarket_product_variants` WHERE (`sku` <=> 'SKU-1001-SMALL' AND `id` <> 7001) OR (`sku` <=> 'SKU-1001-MEDIUM' AND `id` <> 7002) OR (`sku` <=> 'SKU-1001-LARGE' AND `id` <> 7003) OR (`sku` <=> 'SKU-1012-SET60-BLUE' AND `id` <> 7004) OR (`sku` <=> 'SKU-1012-SET120-BLUE' AND `id` <> 7005) OR (`sku` <=> 'SKU-1012-SET60-RED' AND `id` <> 7006) OR (`sku` <=> 'SKU-1016-SET32' AND `id` <> 7007) OR (`sku` <=> 'SKU-1016-SET64' AND `id` <> 7008) OR (`sku` <=> 'SKU-1048-LIGHT' AND `id` <> 7009) OR (`sku` <=> 'SKU-1048-PRO' AND `id` <> 7010) OR (`sku` <=> 'SKU-1049-SIX' AND `id` <> 7011) OR (`sku` <=> 'SKU-1049-TWELVE' AND `id` <> 7012) OR (`sku` <=> 'SKU-1054-PINK' AND `id` <> 7013) OR (`sku` <=> 'SKU-1054-BLUE' AND `id` <> 7014) OR (`sku` <=> 'SKU-1072-START' AND `id` <> 7015) OR (`sku` <=> 'SKU-1072-PLUS' AND `id` <> 7016))
 + (SELECT COUNT(*) FROM `nnop3_minimarket_product_variants` WHERE (`product_id` <=> 1001 AND `signature` <=> '5483c0564b74508effab03f50444849e5fd8c9f1c3ac90bba6cfd039eec6f5c1' AND `id` <> 7001) OR (`product_id` <=> 1001 AND `signature` <=> '4bd71b27d762b51ada738ac76b0cde9703cf6e5ec22ecc033424cbe707528efb' AND `id` <> 7002) OR (`product_id` <=> 1001 AND `signature` <=> 'db684f042c6f0156615bf277ef9ccaa6c61e71e72c3878459806d00d08417d7c' AND `id` <> 7003) OR (`product_id` <=> 1012 AND `signature` <=> '6f19b7bbb113f9a6cc3eac90cff6aeb69956ff61c65268dc5440ac975c23c3a2' AND `id` <> 7004) OR (`product_id` <=> 1012 AND `signature` <=> '5ec8bac531293f4f7150a799b2f2fb23e7800d528d5f2cf33e8e627de7b10174' AND `id` <> 7005) OR (`product_id` <=> 1012 AND `signature` <=> '075ed2211524ada59c5bfd431da8d21629911459c330817a7a59a3bd037f6ad3' AND `id` <> 7006) OR (`product_id` <=> 1016 AND `signature` <=> 'd1e5a7f81129e92f81da3ff964a19e4557c8069ce6b6585ce3321f6db6631474' AND `id` <> 7007) OR (`product_id` <=> 1016 AND `signature` <=> '845573350ff546d0d907d307f4ae25d5e030a99a44d3b03914be786dd98d5608' AND `id` <> 7008) OR (`product_id` <=> 1048 AND `signature` <=> 'c3993266c2612a441d48a270616dae1bc7f2bd00d3f72681fa5c98d1ce828089' AND `id` <> 7009) OR (`product_id` <=> 1048 AND `signature` <=> '0dbc618d32a30381753e5d9b97698bacda0429b4cf44fa8512d03e66d254e333' AND `id` <> 7010) OR (`product_id` <=> 1049 AND `signature` <=> '6e45d7cc3a28c0621a194ead51fe74dce54bf951001ff92ff4cd0dc18e2729bd' AND `id` <> 7011) OR (`product_id` <=> 1049 AND `signature` <=> 'fe48d22c7975192bd3e63a0eeabba68cd106eb0ebf23e3a5219d1d2c1abb17ac' AND `id` <> 7012) OR (`product_id` <=> 1054 AND `signature` <=> 'bba72db1ee2732b8eb9c25adaf471d963af3dcc11c65f682b7f68c70e0152ec2' AND `id` <> 7013) OR (`product_id` <=> 1054 AND `signature` <=> '45005bdb8d8d93cbf1c1984262a795e9f207aa773a93c682414ea4c653695f26' AND `id` <> 7014) OR (`product_id` <=> 1072 AND `signature` <=> 'b6d3468e0f1510bbbb5f469ac8c91e12396ee5bb7079cfb283a3626c929d4947' AND `id` <> 7015) OR (`product_id` <=> 1072 AND `signature` <=> 'b6181972974474c658e255da6e6bb447e01e944602775855502b2420e95f7b61' AND `id` <> 7016))
 + (SELECT COUNT(*) FROM `nnop3_minimarket_product_images` WHERE (`product_id` <=> 1001 AND `path` <=> 'images/minimarket/sample-01.png' AND `id` <> 10001) OR (`product_id` <=> 1002 AND `path` <=> 'images/minimarket/sample-02.png' AND `id` <> 10002) OR (`product_id` <=> 1003 AND `path` <=> 'images/minimarket/sample-03.png' AND `id` <> 10003) OR (`product_id` <=> 1004 AND `path` <=> 'images/minimarket/sample-04.png' AND `id` <> 10004) OR (`product_id` <=> 1005 AND `path` <=> 'images/minimarket/sample-05.png' AND `id` <> 10005) OR (`product_id` <=> 1006 AND `path` <=> 'images/minimarket/sample-06.png' AND `id` <> 10006) OR (`product_id` <=> 1007 AND `path` <=> 'images/minimarket/sample-07.png' AND `id` <> 10007) OR (`product_id` <=> 1008 AND `path` <=> 'images/minimarket/sample-08.png' AND `id` <> 10008) OR (`product_id` <=> 1009 AND `path` <=> 'images/minimarket/sample-09.png' AND `id` <> 10009) OR (`product_id` <=> 1010 AND `path` <=> 'images/minimarket/sample-10.png' AND `id` <> 10010) OR (`product_id` <=> 1011 AND `path` <=> 'images/minimarket/sample-01.png' AND `id` <> 10011) OR (`product_id` <=> 1012 AND `path` <=> 'images/minimarket/sample-02.png' AND `id` <> 10012) OR (`product_id` <=> 1013 AND `path` <=> 'images/minimarket/sample-03.png' AND `id` <> 10013) OR (`product_id` <=> 1014 AND `path` <=> 'images/minimarket/sample-04.png' AND `id` <> 10014) OR (`product_id` <=> 1015 AND `path` <=> 'images/minimarket/sample-05.png' AND `id` <> 10015) OR (`product_id` <=> 1016 AND `path` <=> 'images/minimarket/sample-06.png' AND `id` <> 10016) OR (`product_id` <=> 1017 AND `path` <=> 'images/minimarket/sample-07.png' AND `id` <> 10017) OR (`product_id` <=> 1018 AND `path` <=> 'images/minimarket/sample-08.png' AND `id` <> 10018) OR (`product_id` <=> 1019 AND `path` <=> 'images/minimarket/sample-09.png' AND `id` <> 10019) OR (`product_id` <=> 1020 AND `path` <=> 'images/minimarket/sample-10.png' AND `id` <> 10020) OR (`product_id` <=> 1021 AND `path` <=> 'images/minimarket/sample-01.png' AND `id` <> 10021) OR (`product_id` <=> 1022 AND `path` <=> 'images/minimarket/sample-02.png' AND `id` <> 10022) OR (`product_id` <=> 1023 AND `path` <=> 'images/minimarket/sample-03.png' AND `id` <> 10023) OR (`product_id` <=> 1024 AND `path` <=> 'images/minimarket/sample-04.png' AND `id` <> 10024) OR (`product_id` <=> 1025 AND `path` <=> 'images/minimarket/sample-05.png' AND `id` <> 10025) OR (`product_id` <=> 1026 AND `path` <=> 'images/minimarket/sample-06.png' AND `id` <> 10026) OR (`product_id` <=> 1027 AND `path` <=> 'images/minimarket/sample-07.png' AND `id` <> 10027) OR (`product_id` <=> 1028 AND `path` <=> 'images/minimarket/sample-08.png' AND `id` <> 10028) OR (`product_id` <=> 1029 AND `path` <=> 'images/minimarket/sample-09.png' AND `id` <> 10029) OR (`product_id` <=> 1030 AND `path` <=> 'images/minimarket/sample-10.png' AND `id` <> 10030) OR (`product_id` <=> 1031 AND `path` <=> 'images/minimarket/sample-01.png' AND `id` <> 10031) OR (`product_id` <=> 1032 AND `path` <=> 'images/minimarket/sample-02.png' AND `id` <> 10032) OR (`product_id` <=> 1033 AND `path` <=> 'images/minimarket/sample-03.png' AND `id` <> 10033) OR (`product_id` <=> 1034 AND `path` <=> 'images/minimarket/sample-04.png' AND `id` <> 10034) OR (`product_id` <=> 1035 AND `path` <=> 'images/minimarket/sample-05.png' AND `id` <> 10035) OR (`product_id` <=> 1036 AND `path` <=> 'images/minimarket/sample-06.png' AND `id` <> 10036) OR (`product_id` <=> 1037 AND `path` <=> 'images/minimarket/sample-07.png' AND `id` <> 10037) OR (`product_id` <=> 1038 AND `path` <=> 'images/minimarket/sample-08.png' AND `id` <> 10038) OR (`product_id` <=> 1039 AND `path` <=> 'images/minimarket/sample-09.png' AND `id` <> 10039) OR (`product_id` <=> 1040 AND `path` <=> 'images/minimarket/sample-10.png' AND `id` <> 10040) OR (`product_id` <=> 1041 AND `path` <=> 'images/minimarket/sample-01.png' AND `id` <> 10041) OR (`product_id` <=> 1042 AND `path` <=> 'images/minimarket/sample-02.png' AND `id` <> 10042) OR (`product_id` <=> 1043 AND `path` <=> 'images/minimarket/sample-03.png' AND `id` <> 10043) OR (`product_id` <=> 1044 AND `path` <=> 'images/minimarket/sample-04.png' AND `id` <> 10044) OR (`product_id` <=> 1045 AND `path` <=> 'images/minimarket/sample-05.png' AND `id` <> 10045) OR (`product_id` <=> 1046 AND `path` <=> 'images/minimarket/sample-06.png' AND `id` <> 10046) OR (`product_id` <=> 1047 AND `path` <=> 'images/minimarket/sample-07.png' AND `id` <> 10047) OR (`product_id` <=> 1048 AND `path` <=> 'images/minimarket/sample-08.png' AND `id` <> 10048) OR (`product_id` <=> 1049 AND `path` <=> 'images/minimarket/sample-09.png' AND `id` <> 10049) OR (`product_id` <=> 1050 AND `path` <=> 'images/minimarket/sample-10.png' AND `id` <> 10050) OR (`product_id` <=> 1051 AND `path` <=> 'images/minimarket/sample-01.png' AND `id` <> 10051) OR (`product_id` <=> 1052 AND `path` <=> 'images/minimarket/sample-02.png' AND `id` <> 10052) OR (`product_id` <=> 1053 AND `path` <=> 'images/minimarket/sample-03.png' AND `id` <> 10053) OR (`product_id` <=> 1054 AND `path` <=> 'images/minimarket/sample-04.png' AND `id` <> 10054) OR (`product_id` <=> 1055 AND `path` <=> 'images/minimarket/sample-05.png' AND `id` <> 10055) OR (`product_id` <=> 1056 AND `path` <=> 'images/minimarket/sample-06.png' AND `id` <> 10056) OR (`product_id` <=> 1057 AND `path` <=> 'images/minimarket/sample-07.png' AND `id` <> 10057) OR (`product_id` <=> 1058 AND `path` <=> 'images/minimarket/sample-08.png' AND `id` <> 10058) OR (`product_id` <=> 1059 AND `path` <=> 'images/minimarket/sample-09.png' AND `id` <> 10059) OR (`product_id` <=> 1060 AND `path` <=> 'images/minimarket/sample-10.png' AND `id` <> 10060) OR (`product_id` <=> 1061 AND `path` <=> 'images/minimarket/sample-01.png' AND `id` <> 10061) OR (`product_id` <=> 1062 AND `path` <=> 'images/minimarket/sample-02.png' AND `id` <> 10062) OR (`product_id` <=> 1063 AND `path` <=> 'images/minimarket/sample-03.png' AND `id` <> 10063) OR (`product_id` <=> 1064 AND `path` <=> 'images/minimarket/sample-04.png' AND `id` <> 10064) OR (`product_id` <=> 1065 AND `path` <=> 'images/minimarket/sample-05.png' AND `id` <> 10065) OR (`product_id` <=> 1066 AND `path` <=> 'images/minimarket/sample-06.png' AND `id` <> 10066) OR (`product_id` <=> 1067 AND `path` <=> 'images/minimarket/sample-07.png' AND `id` <> 10067) OR (`product_id` <=> 1068 AND `path` <=> 'images/minimarket/sample-08.png' AND `id` <> 10068) OR (`product_id` <=> 1069 AND `path` <=> 'images/minimarket/sample-09.png' AND `id` <> 10069) OR (`product_id` <=> 1070 AND `path` <=> 'images/minimarket/sample-10.png' AND `id` <> 10070) OR (`product_id` <=> 1071 AND `path` <=> 'images/minimarket/sample-01.png' AND `id` <> 10071) OR (`product_id` <=> 1072 AND `path` <=> 'images/minimarket/sample-02.png' AND `id` <> 10072) OR (`product_id` <=> 1073 AND `path` <=> 'images/minimarket/sample-03.png' AND `id` <> 10073) OR (`product_id` <=> 1074 AND `path` <=> 'images/minimarket/sample-04.png' AND `id` <> 10074) OR (`product_id` <=> 1075 AND `path` <=> 'images/minimarket/sample-05.png' AND `id` <> 10075) OR (`product_id` <=> 1076 AND `path` <=> 'images/minimarket/sample-06.png' AND `id` <> 10076) OR (`product_id` <=> 1077 AND `path` <=> 'images/minimarket/sample-07.png' AND `id` <> 10077))
 + (SELECT COUNT(*) FROM `nnop3_minimarket_variant_value_images` WHERE (`value_id` <=> 6001 AND `path` <=> 'images/minimarket/sample-01.png' AND `id` <> 8501) OR (`value_id` <=> 6002 AND `path` <=> 'images/minimarket/sample-02.png' AND `id` <> 8502) OR (`value_id` <=> 6003 AND `path` <=> 'images/minimarket/sample-03.png' AND `id` <> 8503) OR (`value_id` <=> 6004 AND `path` <=> 'images/minimarket/sample-04.png' AND `id` <> 8504) OR (`value_id` <=> 6005 AND `path` <=> 'images/minimarket/sample-05.png' AND `id` <> 8505) OR (`value_id` <=> 6006 AND `path` <=> 'images/minimarket/sample-06.png' AND `id` <> 8506) OR (`value_id` <=> 6007 AND `path` <=> 'images/minimarket/sample-07.png' AND `id` <> 8507) OR (`value_id` <=> 6008 AND `path` <=> 'images/minimarket/sample-08.png' AND `id` <> 8508) OR (`value_id` <=> 6009 AND `path` <=> 'images/minimarket/sample-09.png' AND `id` <> 8509) OR (`value_id` <=> 6010 AND `path` <=> 'images/minimarket/sample-10.png' AND `id` <> 8510) OR (`value_id` <=> 6011 AND `path` <=> 'images/minimarket/sample-01.png' AND `id` <> 8511) OR (`value_id` <=> 6012 AND `path` <=> 'images/minimarket/sample-02.png' AND `id` <> 8512) OR (`value_id` <=> 6013 AND `path` <=> 'images/minimarket/sample-03.png' AND `id` <> 8513) OR (`value_id` <=> 6014 AND `path` <=> 'images/minimarket/sample-04.png' AND `id` <> 8514) OR (`value_id` <=> 6015 AND `path` <=> 'images/minimarket/sample-05.png' AND `id` <> 8515) OR (`value_id` <=> 6016 AND `path` <=> 'images/minimarket/sample-06.png' AND `id` <> 8516) OR (`value_id` <=> 6017 AND `path` <=> 'images/minimarket/sample-07.png' AND `id` <> 8517))
 + (SELECT COUNT(*) FROM `nnop3_minimarket_product_variant_images` WHERE (`variant_id` <=> 7001 AND `path` <=> 'images/minimarket/sample-01.png' AND `id` <> 8001) OR (`variant_id` <=> 7002 AND `path` <=> 'images/minimarket/sample-02.png' AND `id` <> 8002) OR (`variant_id` <=> 7003 AND `path` <=> 'images/minimarket/sample-03.png' AND `id` <> 8003) OR (`variant_id` <=> 7004 AND `path` <=> 'images/minimarket/sample-04.png' AND `id` <> 8004) OR (`variant_id` <=> 7005 AND `path` <=> 'images/minimarket/sample-05.png' AND `id` <> 8005) OR (`variant_id` <=> 7006 AND `path` <=> 'images/minimarket/sample-06.png' AND `id` <> 8006) OR (`variant_id` <=> 7007 AND `path` <=> 'images/minimarket/sample-07.png' AND `id` <> 8007) OR (`variant_id` <=> 7008 AND `path` <=> 'images/minimarket/sample-08.png' AND `id` <> 8008) OR (`variant_id` <=> 7009 AND `path` <=> 'images/minimarket/sample-09.png' AND `id` <> 8009) OR (`variant_id` <=> 7010 AND `path` <=> 'images/minimarket/sample-10.png' AND `id` <> 8010) OR (`variant_id` <=> 7011 AND `path` <=> 'images/minimarket/sample-01.png' AND `id` <> 8011) OR (`variant_id` <=> 7012 AND `path` <=> 'images/minimarket/sample-02.png' AND `id` <> 8012) OR (`variant_id` <=> 7013 AND `path` <=> 'images/minimarket/sample-03.png' AND `id` <> 8013) OR (`variant_id` <=> 7014 AND `path` <=> 'images/minimarket/sample-04.png' AND `id` <> 8014) OR (`variant_id` <=> 7015 AND `path` <=> 'images/minimarket/sample-05.png' AND `id` <> 8015) OR (`variant_id` <=> 7016 AND `path` <=> 'images/minimarket/sample-06.png' AND `id` <> 8016))
 + (SELECT COUNT(*) FROM `nnop3_minimarket_product_groups` WHERE (`title` <=> 'Плюшевые друзья' AND `id` <> 901) OR (`title` <=> 'Подарки для малыша' AND `id` <> 902) OR (`title` <=> 'Строим космический город' AND `id` <> 903) OR (`title` <=> 'Для семейного вечера' AND `id` <> 904) OR (`title` <=> 'Учимся считать и читать' AND `id` <> 905) OR (`title` <=> 'Юный художник' AND `id` <> 906) OR (`title` <=> 'Мир куклы Насти' AND `id` <> 907) OR (`title` <=> 'Транспортный парк' AND `id` <> 908) OR (`title` <=> 'Настенные механизмы' AND `id` <> 909) OR (`title` <=> 'Подарки для любознательных' AND `id` <> 910));
DROP TEMPORARY TABLE `_mm_catalog_identity_guard`;

-- 1. Category tree, attributes and order-option dictionaries.

INSERT INTO `nnop3_minimarket_categories`
  (`id`, `parent_id`, `title`, `alias`, `description`, `state`, `introtext`, `sort_order`, `image`, `image_alt`, `show_children`, `show_products`, `meta_title`, `meta_description`, `meta_robots`, `og_title`, `og_description`, `og_image`, `og_image_alt`) VALUES
  (101, 0, 'Мягкие игрушки', 'myagkie-igrushki', '<p>Плюшевые друзья для игр, объятий и подарков.</p>', 1, '', 1, 'images/minimarket/sample-01.png', 'Мягкие игрушки', -1, -1, 'Мягкие игрушки — игрушки и игровые наборы', 'Плюшевые друзья для игр, объятий и подарков.', 'index,follow', 'Мягкие игрушки', 'Плюшевые друзья для игр, объятий и подарков.', 'images/minimarket/sample-01.png', 'Мягкие игрушки'),
  (102, 101, 'Плюшевые медведи', 'plyushevye-medvedi', '<p>Медвежата разных размеров и оттенков.</p>', 1, '', 2, 'images/minimarket/sample-02.png', 'Плюшевые медведи', -1, -1, 'Плюшевые медведи — игрушки и игровые наборы', 'Медвежата разных размеров и оттенков.', 'index,follow', 'Плюшевые медведи', 'Медвежата разных размеров и оттенков.', 'images/minimarket/sample-02.png', 'Плюшевые медведи'),
  (103, 101, 'Зверята из плюша', 'zveryata-iz-plyusha', '<p>Зайцы, лисы, котики и другие мягкие персонажи.</p>', 1, '', 3, 'images/minimarket/sample-03.png', 'Зверята из плюша', -1, -1, 'Зверята из плюша — игрушки и игровые наборы', 'Зайцы, лисы, котики и другие мягкие персонажи.', 'index,follow', 'Зверята из плюша', 'Зайцы, лисы, котики и другие мягкие персонажи.', 'images/minimarket/sample-03.png', 'Зверята из плюша'),
  (104, 0, 'Конструкторы', 'konstruktory', '<p>Наборы для сборки и первых инженерных открытий.</p>', 1, '', 4, 'images/minimarket/sample-04.png', 'Конструкторы', -1, -1, 'Конструкторы — игрушки и игровые наборы', 'Наборы для сборки и первых инженерных открытий.', 'index,follow', 'Конструкторы', 'Наборы для сборки и первых инженерных открытий.', 'images/minimarket/sample-04.png', 'Конструкторы'),
  (105, 104, 'Блочные конструкторы', 'blochnye-konstruktory', '<p>Разноцветные детали, города, транспорт и фантазийные миры.</p>', 1, '', 5, 'images/minimarket/sample-05.png', 'Блочные конструкторы', -1, -1, 'Блочные конструкторы — игрушки и игровые наборы', 'Разноцветные детали, города, транспорт и фантазийные миры.', 'index,follow', 'Блочные конструкторы', 'Разноцветные детали, города, транспорт и фантазийные миры.', 'images/minimarket/sample-05.png', 'Блочные конструкторы'),
  (106, 104, 'Магнитные конструкторы', 'magnitnye-konstruktory', '<p>Игры с магнитными элементами, формами и симметрией.</p>', 1, '', 6, 'images/minimarket/sample-06.png', 'Магнитные конструкторы', -1, -1, 'Магнитные конструкторы — игрушки и игровые наборы', 'Игры с магнитными элементами, формами и симметрией.', 'index,follow', 'Магнитные конструкторы', 'Игры с магнитными элементами, формами и симметрией.', 'images/minimarket/sample-06.png', 'Магнитные конструкторы'),
  (107, 0, 'Настольные игры', 'nastolnye-igry', '<p>Игры для семьи и компании, где важны воображение и логика.</p>', 1, '', 7, 'images/minimarket/sample-07.png', 'Настольные игры', -1, -1, 'Настольные игры — игрушки и игровые наборы', 'Игры для семьи и компании, где важны воображение и логика.', 'index,follow', 'Настольные игры', 'Игры для семьи и компании, где важны воображение и логика.', 'images/minimarket/sample-07.png', 'Настольные игры'),
  (108, 107, 'Семейные игры', 'semejnye-igry', '<p>Простые правила, весёлые партии и совместное время.</p>', 1, '', 8, 'images/minimarket/sample-08.png', 'Семейные игры', -1, -1, 'Семейные игры — игрушки и игровые наборы', 'Простые правила, весёлые партии и совместное время.', 'index,follow', 'Семейные игры', 'Простые правила, весёлые партии и совместное время.', 'images/minimarket/sample-08.png', 'Семейные игры'),
  (109, 107, 'Обучающие игры', 'obuchayushchie-igry', '<p>Счёт, буквы, внимание и память в игровом формате.</p>', 1, '', 9, 'images/minimarket/sample-09.png', 'Обучающие игры', -1, -1, 'Обучающие игры — игрушки и игровые наборы', 'Счёт, буквы, внимание и память в игровом формате.', 'index,follow', 'Обучающие игры', 'Счёт, буквы, внимание и память в игровом формате.', 'images/minimarket/sample-09.png', 'Обучающие игры'),
  (110, 0, 'Игрушки для малышей', 'igrushki-dlya-malyshej', '<p>Развивающие игрушки для самых маленьких.</p>', 1, '', 10, 'images/minimarket/sample-10.png', 'Игрушки для малышей', -1, -1, 'Игрушки для малышей — игрушки и игровые наборы', 'Развивающие игрушки для самых маленьких.', 'index,follow', 'Игрушки для малышей', 'Развивающие игрушки для самых маленьких.', 'images/minimarket/sample-10.png', 'Игрушки для малышей'),
  (111, 110, 'Сортеры и пирамидки', 'sortery-i-piramidki', '<p>Знакомство с формами, цветами и размерами.</p>', 1, '', 11, 'images/minimarket/sample-01.png', 'Сортеры и пирамидки', -1, -1, 'Сортеры и пирамидки — игрушки и игровые наборы', 'Знакомство с формами, цветами и размерами.', 'index,follow', 'Сортеры и пирамидки', 'Знакомство с формами, цветами и размерами.', 'images/minimarket/sample-01.png', 'Сортеры и пирамидки'),
  (112, 110, 'Музыкальные игрушки', 'muzykalnye-igrushki', '<p>Первые ритмы, звуки и мелодии.</p>', 1, '', 12, 'images/minimarket/sample-02.png', 'Музыкальные игрушки', -1, -1, 'Музыкальные игрушки — игрушки и игровые наборы', 'Первые ритмы, звуки и мелодии.', 'index,follow', 'Музыкальные игрушки', 'Первые ритмы, звуки и мелодии.', 'images/minimarket/sample-02.png', 'Музыкальные игрушки'),
  (113, 0, 'Машинки и транспорт', 'mashinki-i-transport', '<p>Маленькие машины для больших приключений.</p>', 1, '', 13, 'images/minimarket/sample-03.png', 'Машинки и транспорт', -1, -1, 'Машинки и транспорт — игрушки и игровые наборы', 'Маленькие машины для больших приключений.', 'index,follow', 'Машинки и транспорт', 'Маленькие машины для больших приключений.', 'images/minimarket/sample-03.png', 'Машинки и транспорт'),
  (114, 113, 'Легковые машинки', 'legkovye-mashinki', '<p>Городские, спортивные и внедорожные модели.</p>', 1, '', 14, 'images/minimarket/sample-04.png', 'Легковые машинки', -1, -1, 'Легковые машинки — игрушки и игровые наборы', 'Городские, спортивные и внедорожные модели.', 'index,follow', 'Легковые машинки', 'Городские, спортивные и внедорожные модели.', 'images/minimarket/sample-04.png', 'Легковые машинки'),
  (115, 113, 'Спецтехника', 'spetstekhnika', '<p>Пожарные автомобили, краны и строительная техника.</p>', 1, '', 15, 'images/minimarket/sample-05.png', 'Спецтехника', -1, -1, 'Спецтехника — игрушки и игровые наборы', 'Пожарные автомобили, краны и строительная техника.', 'index,follow', 'Спецтехника', 'Пожарные автомобили, краны и строительная техника.', 'images/minimarket/sample-05.png', 'Спецтехника'),
  (116, 0, 'Творчество и хобби', 'tvorchestvo-i-hobbi', '<p>Всё для первых творческих опытов.</p>', 1, '', 16, 'images/minimarket/sample-06.png', 'Творчество и хобби', -1, -1, 'Творчество и хобби — игрушки и игровые наборы', 'Всё для первых творческих опытов.', 'index,follow', 'Творчество и хобби', 'Всё для первых творческих опытов.', 'images/minimarket/sample-06.png', 'Творчество и хобби'),
  (117, 116, 'Рисование', 'risovanie', '<p>Краски, карандаши и художественные наборы.</p>', 1, '', 17, 'images/minimarket/sample-07.png', 'Рисование', -1, -1, 'Рисование — игрушки и игровые наборы', 'Краски, карандаши и художественные наборы.', 'index,follow', 'Рисование', 'Краски, карандаши и художественные наборы.', 'images/minimarket/sample-07.png', 'Рисование'),
  (118, 116, 'Лепка и моделирование', 'lepka-i-modelirovanie', '<p>Тесто, пластилин и формочки для поделок.</p>', 1, '', 18, 'images/minimarket/sample-08.png', 'Лепка и моделирование', -1, -1, 'Лепка и моделирование — игрушки и игровые наборы', 'Тесто, пластилин и формочки для поделок.', 'index,follow', 'Лепка и моделирование', 'Тесто, пластилин и формочки для поделок.', 'images/minimarket/sample-08.png', 'Лепка и моделирование'),
  (119, 0, 'Куклы и аксессуары', 'kukly-i-aksessuary', '<p>Куклы, игровые пространства и миниатюрные вещи.</p>', 1, '', 19, 'images/minimarket/sample-09.png', 'Куклы и аксессуары', -1, -1, 'Куклы и аксессуары — игрушки и игровые наборы', 'Куклы, игровые пространства и миниатюрные вещи.', 'index,follow', 'Куклы и аксессуары', 'Куклы, игровые пространства и миниатюрные вещи.', 'images/minimarket/sample-09.png', 'Куклы и аксессуары'),
  (120, 119, 'Куклы', 'kukly', '<p>Игровые куклы разных образов.</p>', 1, '', 20, 'images/minimarket/sample-10.png', 'Куклы', -1, -1, 'Куклы — игрушки и игровые наборы', 'Игровые куклы разных образов.', 'index,follow', 'Куклы', 'Игровые куклы разных образов.', 'images/minimarket/sample-10.png', 'Куклы'),
  (121, 119, 'Кукольные домики', 'kukolnye-domiki', '<p>Домики, комнаты и мебель для ролевых игр.</p>', 1, '', 21, 'images/minimarket/sample-01.png', 'Кукольные домики', -1, -1, 'Кукольные домики — игрушки и игровые наборы', 'Домики, комнаты и мебель для ролевых игр.', 'index,follow', 'Кукольные домики', 'Домики, комнаты и мебель для ролевых игр.', 'images/minimarket/sample-01.png', 'Кукольные домики'),
  (122, 0, 'Пазлы', 'pazly', '<p>Картинки по частям — от первых форм до сложных сюжетов.</p>', 1, '', 22, 'images/minimarket/sample-02.png', 'Пазлы', -1, -1, 'Пазлы — игрушки и игровые наборы', 'Картинки по частям — от первых форм до сложных сюжетов.', 'index,follow', 'Пазлы', 'Картинки по частям — от первых форм до сложных сюжетов.', 'images/minimarket/sample-02.png', 'Пазлы'),
  (123, 122, 'Деревянные пазлы', 'derevyannye-pazly', '<p>Тактильные пазлы из дерева.</p>', 1, '', 23, 'images/minimarket/sample-03.png', 'Деревянные пазлы', -1, -1, 'Деревянные пазлы — игрушки и игровые наборы', 'Тактильные пазлы из дерева.', 'index,follow', 'Деревянные пазлы', 'Тактильные пазлы из дерева.', 'images/minimarket/sample-03.png', 'Деревянные пазлы'),
  (124, 122, 'Картонные пазлы', 'kartonnye-pazly', '<p>Яркие сюжеты и разное количество элементов.</p>', 1, '', 24, 'images/minimarket/sample-04.png', 'Картонные пазлы', -1, -1, 'Картонные пазлы — игрушки и игровые наборы', 'Яркие сюжеты и разное количество элементов.', 'index,follow', 'Картонные пазлы', 'Яркие сюжеты и разное количество элементов.', 'images/minimarket/sample-04.png', 'Картонные пазлы'),
  (125, 0, 'Настенные развивающие панели', 'nastennye-razvivayushchie-paneli', '<p>Игровые панели для обучения через движение и эксперимент.</p>', 1, '', 25, 'images/minimarket/sample-05.png', 'Настенные развивающие панели', -1, -1, 'Настенные развивающие панели — игрушки и игровые наборы', 'Игровые панели для обучения через движение и эксперимент.', 'index,follow', 'Настенные развивающие панели', 'Игровые панели для обучения через движение и эксперимент.', 'images/minimarket/sample-05.png', 'Настенные развивающие панели'),
  (126, 125, 'Магнитные стены', 'magnitnye-steny', '<p>Магнитные игровые поверхности и модули.</p>', 1, '', 26, 'images/minimarket/sample-06.png', 'Магнитные стены', -1, -1, 'Магнитные стены — игрушки и игровые наборы', 'Магнитные игровые поверхности и модули.', 'index,follow', 'Магнитные стены', 'Магнитные игровые поверхности и модули.', 'images/minimarket/sample-06.png', 'Магнитные стены'),
  (127, 125, 'Трассы для шариков', 'trassy-dlya-sharikov', '<p>Желоба, повороты и маршруты для шариков.</p>', 1, '', 27, 'images/minimarket/sample-07.png', 'Трассы для шариков', -1, -1, 'Трассы для шариков — игрушки и игровые наборы', 'Желоба, повороты и маршруты для шариков.', 'index,follow', 'Трассы для шариков', 'Желоба, повороты и маршруты для шариков.', 'images/minimarket/sample-07.png', 'Трассы для шариков') ON DUPLICATE KEY UPDATE `id`=`id`;

INSERT INTO `nnop3_minimarket_attributes`
 (`id`, `title`, `code`, `input_type`, `value_source`, `number_unit`, `number_min`, `number_max`, `number_step`, `is_multiple`, `is_filterable`, `filter_mode`, `filter_multiple`, `sort_order`, `state`) VALUES
 (201, 'Возраст', 'age', 'text', 'preset', '', NULL, NULL, NULL, 0, 1, 'dropdown', 0, 1, 1),
 (202, 'Материал', 'material', 'text', 'preset', '', NULL, NULL, NULL, 0, 1, 'dropdown', 0, 2, 1),
 (203, 'Цвет', 'color', 'color', 'preset', '', NULL, NULL, NULL, 0, 1, 'checkboxes', 1, 3, 1),
 (204, 'Развиваемые навыки', 'skill', 'text', 'preset', '', NULL, NULL, NULL, 0, 1, 'checkboxes', 1, 4, 1),
 (205, 'Количество деталей', 'pieces', 'number', 'manual', 'шт.', 0, 1000, 1, 0, 1, 'range', 0, 5, 1),
 (206, 'Количество игроков', 'players', 'number', 'manual', 'чел.', 1, 12, 1, 0, 1, 'range', 0, 6, 1),
 (207, 'Вес', 'weight', 'number', 'manual', 'г', 0, 100000, 1, 0, 1, 'range', 0, 7, 1),
 (208, 'Длина', 'length', 'number', 'manual', 'см', 0, 300, 1, 0, 1, 'range', 0, 8, 1),
 (209, 'Нужны батарейки', 'battery', 'boolean', 'manual', '', NULL, NULL, NULL, 0, 1, 'boolean', 0, 9, 1),
 (210, 'Для подарка', 'gift', 'boolean', 'manual', '', NULL, NULL, NULL, 0, 1, 'boolean', 0, 10, 1),
 (211, 'Сложность', 'difficulty', 'text', 'preset', '', NULL, NULL, NULL, 0, 1, 'dropdown', 0, 11, 1),
 (212, 'Тип игры', 'playtype', 'text', 'preset', '', NULL, NULL, NULL, 0, 1, 'dropdown', 0, 12, 1) ON DUPLICATE KEY UPDATE `id`=`id`;

INSERT INTO `nnop3_minimarket_attribute_values`
  (`id`, `attribute_id`, `title`, `code`, `color_hex`, `sample_image`, `sort_order`, `state`) VALUES
  (301, 203, 'Бежевый', 'beige', '#D8C3A5', 'images/minimarket/sample-01.png', 0, 1),
  (302, 203, 'Белый', 'white', '#FFFFFF', 'images/minimarket/sample-02.png', 1, 1),
  (303, 203, 'Коричневый', 'brown', '#845C47', 'images/minimarket/sample-03.png', 2, 1),
  (304, 203, 'Розовый', 'pink', '#F6B5C6', 'images/minimarket/sample-04.png', 3, 1),
  (305, 203, 'Синий', 'blue', '#3675E8', 'images/minimarket/sample-05.png', 4, 1),
  (306, 203, 'Красный', 'red', '#E34545', 'images/minimarket/sample-06.png', 5, 1),
  (307, 203, 'Оранжевый', 'orange', '#F29D38', 'images/minimarket/sample-07.png', 6, 1),
  (308, 203, 'Серый', 'gray', '#9AA0A6', 'images/minimarket/sample-08.png', 7, 1),
  (309, 203, 'Зелёный', 'green', '#4EAE70', 'images/minimarket/sample-09.png', 8, 1),
  (310, 203, 'Чёрный', 'black', '#242424', 'images/minimarket/sample-10.png', 9, 1),
  (311, 203, 'Жёлтый', 'yellow', '#F7D64A', 'images/minimarket/sample-01.png', 10, 1),
  (312, 203, 'Фиолетовый', 'purple', '#9968CD', 'images/minimarket/sample-02.png', 11, 1),
  (313, 203, 'Разноцветный', 'multicolor', '#F2AC26', 'images/minimarket/sample-03.png', 12, 1) ON DUPLICATE KEY UPDATE `id`=`id`;

INSERT INTO `nnop3_minimarket_attribute_values`
 (`attribute_id`,`title`,`code`,`value_payload`,`sort_order`,`state`) VALUES
 (201,'1+','mm-preset-00001','1+',1,1),
 (201,'2+','mm-preset-00002','2+',2,1),
 (201,'3+','mm-preset-00003','3+',3,1),
 (201,'4+','mm-preset-00004','4+',4,1),
 (201,'5+','mm-preset-00005','5+',5,1),
 (201,'6+','mm-preset-00006','6+',6,1),
 (201,'7+','mm-preset-00007','7+',7,1),
 (202,'Плюш','mm-preset-00001','Плюш',1,1),
 (202,'Пластик','mm-preset-00002','Пластик',2,1),
 (202,'Дерево','mm-preset-00003','Дерево',3,1),
 (202,'Картон','mm-preset-00004','Картон',4,1),
 (202,'Металл','mm-preset-00005','Металл',5,1),
 (202,'Бумага','mm-preset-00006','Бумага',6,1),
 (202,'Краски','mm-preset-00007','Краски',7,1),
 (202,'Тесто','mm-preset-00008','Тесто',8,1),
 (202,'Песок','mm-preset-00009','Песок',9,1),
 (202,'Масса для лепки','mm-preset-00010','Масса для лепки',10,1),
 (202,'Смешанный','mm-preset-00011','Смешанный',11,1),
 (204,'Логика','mm-preset-00001','Логика',1,1),
 (204,'Моторика','mm-preset-00002','Моторика',2,1),
 (204,'Воображение','mm-preset-00003','Воображение',3,1),
 (204,'Эмоции','mm-preset-00004','Эмоции',4,1),
 (204,'Внимание','mm-preset-00005','Внимание',5,1),
 (204,'Реакция','mm-preset-00006','Реакция',6,1),
 (204,'Память','mm-preset-00007','Память',7,1),
 (204,'Счёт','mm-preset-00008','Счёт',8,1),
 (204,'Чтение','mm-preset-00009','Чтение',9,1),
 (204,'Слух','mm-preset-00010','Слух',10,1),
 (204,'Творчество','mm-preset-00011','Творчество',11,1),
 (204,'Общение','mm-preset-00012','Общение',12,1),
 (211,'Начальная','mm-preset-00001','Начальная',1,1),
 (211,'Средняя','mm-preset-00002','Средняя',2,1),
 (211,'Продвинутая','mm-preset-00003','Продвинутая',3,1),
 (212,'Сюжетная','mm-preset-00001','Сюжетная',1,1),
 (212,'Обучающая','mm-preset-00002','Обучающая',2,1),
 (212,'Творческая','mm-preset-00003','Творческая',3,1),
 (212,'Соревновательная','mm-preset-00004','Соревновательная',4,1),
 (212,'Конструирование','mm-preset-00005','Конструирование',5,1),
 (212,'Сенсорная','mm-preset-00006','Сенсорная',6,1) ON DUPLICATE KEY UPDATE `id`=`id`;


INSERT INTO `nnop3_minimarket_category_attributes`
  (`category_id`, `attribute_id`, `enabled`, `required`) VALUES
  (101, 201, 1, 1),
  (101, 202, 1, 0),
  (101, 203, 1, 0),
  (101, 204, 1, 0),
  (101, 207, 1, 0),
  (101, 208, 1, 0),
  (101, 210, 1, 0),
  (101, 211, 1, 0),
  (101, 212, 1, 0),
  (104, 201, 1, 1),
  (104, 202, 1, 0),
  (104, 203, 1, 0),
  (104, 204, 1, 0),
  (104, 207, 1, 0),
  (104, 208, 1, 0),
  (104, 210, 1, 0),
  (104, 211, 1, 0),
  (104, 212, 1, 0),
  (107, 201, 1, 1),
  (107, 202, 1, 0),
  (107, 203, 1, 0),
  (107, 204, 1, 0),
  (107, 207, 1, 0),
  (107, 208, 1, 0),
  (107, 210, 1, 0),
  (107, 211, 1, 0),
  (107, 212, 1, 0),
  (110, 201, 1, 1),
  (110, 202, 1, 0),
  (110, 203, 1, 0),
  (110, 204, 1, 0),
  (110, 207, 1, 0),
  (110, 208, 1, 0),
  (110, 210, 1, 0),
  (110, 211, 1, 0),
  (110, 212, 1, 0),
  (113, 201, 1, 1),
  (113, 202, 1, 0),
  (113, 203, 1, 0),
  (113, 204, 1, 0),
  (113, 207, 1, 0),
  (113, 208, 1, 0),
  (113, 210, 1, 0),
  (113, 211, 1, 0),
  (113, 212, 1, 0),
  (116, 201, 1, 1),
  (116, 202, 1, 0),
  (116, 203, 1, 0),
  (116, 204, 1, 0),
  (116, 207, 1, 0),
  (116, 208, 1, 0),
  (116, 210, 1, 0),
  (116, 211, 1, 0),
  (116, 212, 1, 0),
  (119, 201, 1, 1),
  (119, 202, 1, 0),
  (119, 203, 1, 0),
  (119, 204, 1, 0),
  (119, 207, 1, 0),
  (119, 208, 1, 0),
  (119, 210, 1, 0),
  (119, 211, 1, 0),
  (119, 212, 1, 0),
  (122, 201, 1, 1),
  (122, 202, 1, 0),
  (122, 203, 1, 0),
  (122, 204, 1, 0),
  (122, 207, 1, 0),
  (122, 208, 1, 0),
  (122, 210, 1, 0),
  (122, 211, 1, 0),
  (122, 212, 1, 0),
  (125, 201, 1, 1),
  (125, 202, 1, 0),
  (125, 203, 1, 0) ON DUPLICATE KEY UPDATE `category_id`=`category_id`;
INSERT INTO `nnop3_minimarket_category_attributes`
  (`category_id`, `attribute_id`, `enabled`, `required`) VALUES
  (125, 204, 1, 0),
  (125, 207, 1, 0),
  (125, 208, 1, 0),
  (125, 210, 1, 0),
  (125, 211, 1, 0),
  (125, 212, 1, 0),
  (104, 205, 1, 0),
  (107, 205, 1, 0),
  (122, 205, 1, 0),
  (125, 205, 1, 0),
  (107, 206, 1, 0),
  (112, 209, 1, 0),
  (116, 209, 1, 0) ON DUPLICATE KEY UPDATE `category_id`=`category_id`;

INSERT INTO `nnop3_minimarket_order_options`
  (`id`, `title`, `code`, `state`, `sort_order`) VALUES
  (401, 'Размер', 'size', 1, 0),
  (402, 'Цвет исполнения', 'execution-color', 1, 1),
  (403, 'Комплектация', 'kit', 1, 2),
  (404, 'Подарочная упаковка', 'gift-wrap', 1, 3),
  (405, 'Тип набора', 'set-type', 1, 4),
  (406, 'Длина трассы', 'track-length', 1, 5) ON DUPLICATE KEY UPDATE `id`=`id`;

INSERT INTO `nnop3_minimarket_order_option_values`
  (`id`, `option_id`, `title`, `code`, `color_hex`, `state`, `sort_order`) VALUES
  (501, 401, 'Маленький', 'small', '', 1, 0),
  (502, 401, 'Средний', 'medium', '', 1, 1),
  (503, 401, 'Большой', 'large', '', 1, 2),
  (504, 402, 'Бежевый', 'beige', '#D8C3A5', 1, 3),
  (505, 402, 'Розовый', 'pink', '#F6B5C6', 1, 4),
  (506, 402, 'Голубой', 'sky', '#79BBED', 1, 5),
  (507, 402, 'Красный', 'red', '#E34545', 1, 6),
  (508, 403, 'Базовая', 'basic', '', 1, 7),
  (509, 403, 'Расширенная', 'extended', '', 1, 8),
  (510, 403, 'Профессиональная', 'pro', '', 1, 9),
  (511, 404, 'Без упаковки', 'none', '', 1, 10),
  (512, 404, 'Крафтовая коробка', 'kraft', '', 1, 11),
  (513, 404, 'Подарочный пакет', 'bag', '', 1, 12),
  (514, 405, 'Один предмет', 'single', '', 1, 13),
  (515, 405, 'Набор', 'bundle', '', 1, 14),
  (516, 406, '60 см', '60cm', '', 1, 15),
  (517, 406, '90 см', '90cm', '', 1, 16),
  (518, 406, '120 см', '120cm', '', 1, 17) ON DUPLICATE KEY UPDATE `id`=`id`;

INSERT INTO `nnop3_minimarket_category_order_options`
  (`category_id`, `option_id`, `mode`) VALUES
  (101, 401, 1),
  (101, 402, 1),
  (101, 404, 1),
  (104, 403, 2),
  (104, 404, 1),
  (105, 402, 1),
  (106, 402, 1),
  (107, 404, 1),
  (110, 404, 1),
  (113, 402, 1),
  (116, 403, 1),
  (119, 402, 1),
  (119, 404, 1),
  (125, 403, 1),
  (125, 404, 1),
  (127, 406, 2) ON DUPLICATE KEY UPDATE `category_id`=`category_id`;


-- 2. Products with descriptions, SEO and one shared sample image.

INSERT INTO `nnop3_minimarket_products`
  (`id`, `category_id`, `title`, `alias`, `sku`, `price`, `stock`, `state`, `introtext`, `description`, `sort_order`, `image`, `image_alt`, `meta_title`, `meta_description`, `meta_robots`, `og_title`, `og_description`, `og_image`, `og_image_alt`, `option_stock_mode`) VALUES
  (1001, 102, 'Плюшевый мишка «Тёма»', 'bear-tyoma', 'TOY-1001', 1490.00, 0, 1, '<p>Плюшевый мишка «Тёма» — игрушка для детей от 3 лет.</p>', '<p>Плюшевый мишка «Тёма». Демонстрационный товар из категории «Плюшевые медведи». Материал: Плюш. Рекомендуемый возраст: 3+. Предназначен для игр и развития навыков.</p><p>Характеристики, цены и остатки созданы для тестирования MiniMarket, не являются предложением реального магазина.</p>', 1, 'images/minimarket/sample-01.png', 'Плюшевый мишка «Тёма»', 'Плюшевый мишка «Тёма»', 'Плюшевый мишка «Тёма»: описание, характеристики, наличие и варианты исполнения в MiniMarket.', 'index,follow', 'Плюшевый мишка «Тёма»', 'Плюшевый мишка «Тёма»: описание, характеристики, наличие и варианты исполнения в MiniMarket.', 'images/minimarket/sample-01.png', 'Плюшевый мишка «Тёма»', 1),
  (1002, 102, 'Мишка «Полярник» белый', 'bear-polar', 'TOY-1002', 2890.00, 14, 1, '<p>Мишка «Полярник» белый — игрушка для детей от 3 лет.</p>', '<p>Мишка «Полярник» белый. Демонстрационный товар из категории «Плюшевые медведи». Материал: Плюш. Рекомендуемый возраст: 3+. Предназначен для игр и развития навыков.</p><p>Характеристики, цены и остатки созданы для тестирования MiniMarket, не являются предложением реального магазина.</p>', 2, 'images/minimarket/sample-02.png', 'Мишка «Полярник» белый', 'Мишка «Полярник» белый', 'Мишка «Полярник» белый: описание, характеристики, наличие и варианты исполнения в MiniMarket.', 'index,follow', 'Мишка «Полярник» белый', 'Мишка «Полярник» белый: описание, характеристики, наличие и варианты исполнения в MiniMarket.', 'images/minimarket/sample-02.png', 'Мишка «Полярник» белый', 0),
  (1003, 102, 'Медвежонок «Бруно»', 'bear-bruno', 'TOY-1003', 1690.00, 28, 1, '<p>Медвежонок «Бруно» — игрушка для детей от 3 лет.</p>', '<p>Медвежонок «Бруно». Демонстрационный товар из категории «Плюшевые медведи». Материал: Плюш. Рекомендуемый возраст: 3+. Предназначен для игр и развития навыков.</p><p>Характеристики, цены и остатки созданы для тестирования MiniMarket, не являются предложением реального магазина.</p>', 3, 'images/minimarket/sample-03.png', 'Медвежонок «Бруно»', 'Медвежонок «Бруно»', 'Медвежонок «Бруно»: описание, характеристики, наличие и варианты исполнения в MiniMarket.', 'index,follow', 'Медвежонок «Бруно»', 'Медвежонок «Бруно»: описание, характеристики, наличие и варианты исполнения в MiniMarket.', 'images/minimarket/sample-03.png', 'Медвежонок «Бруно»', 0),
  (1004, 102, 'Мишка «Медовый сон»', 'bear-honey', 'TOY-1004', 3290.00, 9, 1, '<p>Мишка «Медовый сон» — игрушка для детей от 3 лет.</p>', '<p>Мишка «Медовый сон». Демонстрационный товар из категории «Плюшевые медведи». Материал: Плюш. Рекомендуемый возраст: 3+. Предназначен для игр и развития навыков.</p><p>Характеристики, цены и остатки созданы для тестирования MiniMarket, не являются предложением реального магазина.</p>', 4, 'images/minimarket/sample-04.png', 'Мишка «Медовый сон»', 'Мишка «Медовый сон»', 'Мишка «Медовый сон»: описание, характеристики, наличие и варианты исполнения в MiniMarket.', 'index,follow', 'Мишка «Медовый сон»', 'Мишка «Медовый сон»: описание, характеристики, наличие и варианты исполнения в MiniMarket.', 'images/minimarket/sample-04.png', 'Мишка «Медовый сон»', 0),
  (1005, 102, 'Мишка «Морячок»', 'bear-sailor', 'TOY-1005', 2190.00, 11, 1, '<p>Мишка «Морячок» — игрушка для детей от 3 лет.</p>', '<p>Мишка «Морячок». Демонстрационный товар из категории «Плюшевые медведи». Материал: Плюш. Рекомендуемый возраст: 3+. Предназначен для игр и развития навыков.</p><p>Характеристики, цены и остатки созданы для тестирования MiniMarket, не являются предложением реального магазина.</p>', 5, 'images/minimarket/sample-05.png', 'Мишка «Морячок»', 'Мишка «Морячок»', 'Мишка «Морячок»: описание, характеристики, наличие и варианты исполнения в MiniMarket.', 'index,follow', 'Мишка «Морячок»', 'Мишка «Морячок»: описание, характеристики, наличие и варианты исполнения в MiniMarket.', 'images/minimarket/sample-05.png', 'Мишка «Морячок»', 0),
  (1006, 103, 'Зайчик «Ушастик»', 'rabbit-ushastik', 'TOY-1006', 2190.00, 18, 1, '<p>Зайчик «Ушастик» — игрушка для детей от 3 лет.</p>', '<p>Зайчик «Ушастик». Демонстрационный товар из категории «Зверята из плюша». Материал: Плюш. Рекомендуемый возраст: 3+. Предназначен для игр и развития навыков.</p><p>Характеристики, цены и остатки созданы для тестирования MiniMarket, не являются предложением реального магазина.</p>', 6, 'images/minimarket/sample-06.png', 'Зайчик «Ушастик»', 'Зайчик «Ушастик»', 'Зайчик «Ушастик»: описание, характеристики, наличие и варианты исполнения в MiniMarket.', 'index,follow', 'Зайчик «Ушастик»', 'Зайчик «Ушастик»: описание, характеристики, наличие и варианты исполнения в MiniMarket.', 'images/minimarket/sample-06.png', 'Зайчик «Ушастик»', 0),
  (1007, 103, 'Лисёнок «Рыжик»', 'fox-ryzhik', 'TOY-1007', 1890.00, 20, 1, '<p>Лисёнок «Рыжик» — игрушка для детей от 3 лет.</p>', '<p>Лисёнок «Рыжик». Демонстрационный товар из категории «Зверята из плюша». Материал: Плюш. Рекомендуемый возраст: 3+. Предназначен для игр и развития навыков.</p><p>Характеристики, цены и остатки созданы для тестирования MiniMarket, не являются предложением реального магазина.</p>', 7, 'images/minimarket/sample-07.png', 'Лисёнок «Рыжик»', 'Лисёнок «Рыжик»', 'Лисёнок «Рыжик»: описание, характеристики, наличие и варианты исполнения в MiniMarket.', 'index,follow', 'Лисёнок «Рыжик»', 'Лисёнок «Рыжик»: описание, характеристики, наличие и варианты исполнения в MiniMarket.', 'images/minimarket/sample-07.png', 'Лисёнок «Рыжик»', 0),
  (1008, 103, 'Котик «Мурчик»', 'cat-murchik', 'TOY-1008', 1690.00, 0, 1, '<p>Котик «Мурчик» — игрушка для детей от 3 лет.</p>', '<p>Котик «Мурчик». Демонстрационный товар из категории «Зверята из плюша». Материал: Плюш. Рекомендуемый возраст: 3+. Предназначен для игр и развития навыков.</p><p>Характеристики, цены и остатки созданы для тестирования MiniMarket, не являются предложением реального магазина.</p>', 8, 'images/minimarket/sample-08.png', 'Котик «Мурчик»', 'Котик «Мурчик»', 'Котик «Мурчик»: описание, характеристики, наличие и варианты исполнения в MiniMarket.', 'index,follow', 'Котик «Мурчик»', 'Котик «Мурчик»: описание, характеристики, наличие и варианты исполнения в MiniMarket.', 'images/minimarket/sample-08.png', 'Котик «Мурчик»', 0),
  (1009, 103, 'Ёжик «Шуршик»', 'hedgehog-shurshik', 'TOY-1009', 1790.00, 12, 1, '<p>Ёжик «Шуршик» — игрушка для детей от 3 лет.</p>', '<p>Ёжик «Шуршик». Демонстрационный товар из категории «Зверята из плюша». Материал: Плюш. Рекомендуемый возраст: 3+. Предназначен для игр и развития навыков.</p><p>Характеристики, цены и остатки созданы для тестирования MiniMarket, не являются предложением реального магазина.</p>', 9, 'images/minimarket/sample-09.png', 'Ёжик «Шуршик»', 'Ёжик «Шуршик»', 'Ёжик «Шуршик»: описание, характеристики, наличие и варианты исполнения в MiniMarket.', 'index,follow', 'Ёжик «Шуршик»', 'Ёжик «Шуршик»: описание, характеристики, наличие и варианты исполнения в MiniMarket.', 'images/minimarket/sample-09.png', 'Ёжик «Шуршик»', 0),
  (1010, 103, 'Панда «Соня»', 'panda-sonya', 'TOY-1010', 2590.00, 15, 1, '<p>Панда «Соня» — игрушка для детей от 3 лет.</p>', '<p>Панда «Соня». Демонстрационный товар из категории «Зверята из плюша». Материал: Плюш. Рекомендуемый возраст: 3+. Предназначен для игр и развития навыков.</p><p>Характеристики, цены и остатки созданы для тестирования MiniMarket, не являются предложением реального магазина.</p>', 10, 'images/minimarket/sample-10.png', 'Панда «Соня»', 'Панда «Соня»', 'Панда «Соня»: описание, характеристики, наличие и варианты исполнения в MiniMarket.', 'index,follow', 'Панда «Соня»', 'Панда «Соня»: описание, характеристики, наличие и варианты исполнения в MiniMarket.', 'images/minimarket/sample-10.png', 'Панда «Соня»', 0),
  (1011, 105, 'Конструктор «Город из кубиков»', 'city-blocks', 'TOY-1011', 2490.00, 35, 1, '<p>Конструктор «Город из кубиков» — игрушка для детей от 4 лет.</p>', '<p>Конструктор «Город из кубиков». Демонстрационный товар из категории «Блочные конструкторы». Материал: Пластик. Рекомендуемый возраст: 4+. Предназначен для игр и развития навыков.</p><p>Характеристики, цены и остатки созданы для тестирования MiniMarket, не являются предложением реального магазина.</p>', 11, 'images/minimarket/sample-01.png', 'Конструктор «Город из кубиков»', 'Конструктор «Город из кубиков»', 'Конструктор «Город из кубиков»: описание, характеристики, наличие и варианты исполнения в MiniMarket.', 'index,follow', 'Конструктор «Город из кубиков»', 'Конструктор «Город из кубиков»: описание, характеристики, наличие и варианты исполнения в MiniMarket.', 'images/minimarket/sample-01.png', 'Конструктор «Город из кубиков»', 0),
  (1012, 105, 'Конструктор «КосмоБлок»', 'cosmo-block', 'TOY-1012', 3190.00, 0, 1, '<p>Конструктор «КосмоБлок» — игрушка для детей от 6 лет.</p>', '<p>Конструктор «КосмоБлок». Демонстрационный товар из категории «Блочные конструкторы». Материал: Пластик. Рекомендуемый возраст: 6+. Предназначен для игр и развития навыков.</p><p>Характеристики, цены и остатки созданы для тестирования MiniMarket, не являются предложением реального магазина.</p>', 12, 'images/minimarket/sample-02.png', 'Конструктор «КосмоБлок»', 'Конструктор «КосмоБлок»', 'Конструктор «КосмоБлок»: описание, характеристики, наличие и варианты исполнения в MiniMarket.', 'index,follow', 'Конструктор «КосмоБлок»', 'Конструктор «КосмоБлок»: описание, характеристики, наличие и варианты исполнения в MiniMarket.', 'images/minimarket/sample-02.png', 'Конструктор «КосмоБлок»', 1),
  (1013, 105, 'Конструктор «Средневековый замок»', 'castle-bricks', 'TOY-1013', 5790.00, 8, 1, '<p>Конструктор «Средневековый замок» — игрушка для детей от 6 лет.</p>', '<p>Конструктор «Средневековый замок». Демонстрационный товар из категории «Блочные конструкторы». Материал: Пластик. Рекомендуемый возраст: 6+. Предназначен для игр и развития навыков.</p><p>Характеристики, цены и остатки созданы для тестирования MiniMarket, не являются предложением реального магазина.</p>', 13, 'images/minimarket/sample-03.png', 'Конструктор «Средневековый замок»', 'Конструктор «Средневековый замок»', 'Конструктор «Средневековый замок»: описание, характеристики, наличие и варианты исполнения в MiniMarket.', 'index,follow', 'Конструктор «Средневековый замок»', 'Конструктор «Средневековый замок»: описание, характеристики, наличие и варианты исполнения в MiniMarket.', 'images/minimarket/sample-03.png', 'Конструктор «Средневековый замок»', 0),
  (1014, 105, 'Конструктор «Паровозик»', 'train-bricks', 'TOY-1014', 2790.00, 13, 1, '<p>Конструктор «Паровозик» — игрушка для детей от 4 лет.</p>', '<p>Конструктор «Паровозик». Демонстрационный товар из категории «Блочные конструкторы». Материал: Пластик. Рекомендуемый возраст: 4+. Предназначен для игр и развития навыков.</p><p>Характеристики, цены и остатки созданы для тестирования MiniMarket, не являются предложением реального магазина.</p>', 14, 'images/minimarket/sample-04.png', 'Конструктор «Паровозик»', 'Конструктор «Паровозик»', 'Конструктор «Паровозик»: описание, характеристики, наличие и варианты исполнения в MiniMarket.', 'index,follow', 'Конструктор «Паровозик»', 'Конструктор «Паровозик»: описание, характеристики, наличие и варианты исполнения в MiniMarket.', 'images/minimarket/sample-04.png', 'Конструктор «Паровозик»', 0),
  (1015, 105, 'Конструктор «Динопарк»', 'dino-park', 'TOY-1015', 3690.00, 4, 1, '<p>Конструктор «Динопарк» — игрушка для детей от 6 лет.</p>', '<p>Конструктор «Динопарк». Демонстрационный товар из категории «Блочные конструкторы». Материал: Пластик. Рекомендуемый возраст: 6+. Предназначен для игр и развития навыков.</p><p>Характеристики, цены и остатки созданы для тестирования MiniMarket, не являются предложением реального магазина.</p>', 15, 'images/minimarket/sample-05.png', 'Конструктор «Динопарк»', 'Конструктор «Динопарк»', 'Конструктор «Динопарк»: описание, характеристики, наличие и варианты исполнения в MiniMarket.', 'index,follow', 'Конструктор «Динопарк»', 'Конструктор «Динопарк»: описание, характеристики, наличие и варианты исполнения в MiniMarket.', 'images/minimarket/sample-05.png', 'Конструктор «Динопарк»', 0),
  (1016, 106, 'Магнитный конструктор «ГеоМир»', 'mag-geomir', 'TOY-1016', 2990.00, 0, 1, '<p>Магнитный конструктор «ГеоМир» — игрушка для детей от 5 лет.</p>', '<p>Магнитный конструктор «ГеоМир». Демонстрационный товар из категории «Магнитные конструкторы». Материал: Пластик. Рекомендуемый возраст: 5+. Предназначен для игр и развития навыков.</p><p>Характеристики, цены и остатки созданы для тестирования MiniMarket, не являются предложением реального магазина.</p>', 16, 'images/minimarket/sample-06.png', 'Магнитный конструктор «ГеоМир»', 'Магнитный конструктор «ГеоМир»', 'Магнитный конструктор «ГеоМир»: описание, характеристики, наличие и варианты исполнения в MiniMarket.', 'index,follow', 'Магнитный конструктор «ГеоМир»', 'Магнитный конструктор «ГеоМир»: описание, характеристики, наличие и варианты исполнения в MiniMarket.', 'images/minimarket/sample-06.png', 'Магнитный конструктор «ГеоМир»', 1),
  (1017, 106, 'Магнитная ферма', 'mag-farm', 'TOY-1017', 3690.00, 7, 1, '<p>Магнитная ферма — игрушка для детей от 5 лет.</p>', '<p>Магнитная ферма. Демонстрационный товар из категории «Магнитные конструкторы». Материал: Пластик. Рекомендуемый возраст: 5+. Предназначен для игр и развития навыков.</p><p>Характеристики, цены и остатки созданы для тестирования MiniMarket, не являются предложением реального магазина.</p>', 17, 'images/minimarket/sample-07.png', 'Магнитная ферма', 'Магнитная ферма', 'Магнитная ферма: описание, характеристики, наличие и варианты исполнения в MiniMarket.', 'index,follow', 'Магнитная ферма', 'Магнитная ферма: описание, характеристики, наличие и варианты исполнения в MiniMarket.', 'images/minimarket/sample-07.png', 'Магнитная ферма', 0),
  (1018, 106, 'Магнитная орбита', 'mag-orbit', 'TOY-1018', 4590.00, 6, 1, '<p>Магнитная орбита — игрушка для детей от 6 лет.</p>', '<p>Магнитная орбита. Демонстрационный товар из категории «Магнитные конструкторы». Материал: Пластик. Рекомендуемый возраст: 6+. Предназначен для игр и развития навыков.</p><p>Характеристики, цены и остатки созданы для тестирования MiniMarket, не являются предложением реального магазина.</p>', 18, 'images/minimarket/sample-08.png', 'Магнитная орбита', 'Магнитная орбита', 'Магнитная орбита: описание, характеристики, наличие и варианты исполнения в MiniMarket.', 'index,follow', 'Магнитная орбита', 'Магнитная орбита: описание, характеристики, наличие и варианты исполнения в MiniMarket.', 'images/minimarket/sample-08.png', 'Магнитная орбита', 0),
  (1019, 106, 'Магнитная мозаика «Фигуры»', 'mag-mosaic', 'TOY-1019', 1790.00, 17, 1, '<p>Магнитная мозаика «Фигуры» — игрушка для детей от 4 лет.</p>', '<p>Магнитная мозаика «Фигуры». Демонстрационный товар из категории «Магнитные конструкторы». Материал: Пластик. Рекомендуемый возраст: 4+. Предназначен для игр и развития навыков.</p><p>Характеристики, цены и остатки созданы для тестирования MiniMarket, не являются предложением реального магазина.</p>', 19, 'images/minimarket/sample-09.png', 'Магнитная мозаика «Фигуры»', 'Магнитная мозаика «Фигуры»', 'Магнитная мозаика «Фигуры»: описание, характеристики, наличие и варианты исполнения в MiniMarket.', 'index,follow', 'Магнитная мозаика «Фигуры»', 'Магнитная мозаика «Фигуры»: описание, характеристики, наличие и варианты исполнения в MiniMarket.', 'images/minimarket/sample-09.png', 'Магнитная мозаика «Фигуры»', 0),
  (1020, 108, 'Настольная игра «Лесная гонка»', 'game-forest-race', 'TOY-1020', 1890.00, 16, 1, '<p>Настольная игра «Лесная гонка» — игрушка для детей от 5 лет.</p>', '<p>Настольная игра «Лесная гонка». Демонстрационный товар из категории «Семейные игры». Материал: Картон. Рекомендуемый возраст: 5+. Предназначен для игр и развития навыков.</p><p>Характеристики, цены и остатки созданы для тестирования MiniMarket, не являются предложением реального магазина.</p>', 20, 'images/minimarket/sample-10.png', 'Настольная игра «Лесная гонка»', 'Настольная игра «Лесная гонка»', 'Настольная игра «Лесная гонка»: описание, характеристики, наличие и варианты исполнения в MiniMarket.', 'index,follow', 'Настольная игра «Лесная гонка»', 'Настольная игра «Лесная гонка»: описание, характеристики, наличие и варианты исполнения в MiniMarket.', 'images/minimarket/sample-10.png', 'Настольная игра «Лесная гонка»', 0),
  (1021, 108, 'Игра «Кто быстрее?»', 'game-fastest', 'TOY-1021', 2290.00, 9, 1, '<p>Игра «Кто быстрее?» — игрушка для детей от 6 лет.</p>', '<p>Игра «Кто быстрее?». Демонстрационный товар из категории «Семейные игры». Материал: Картон. Рекомендуемый возраст: 6+. Предназначен для игр и развития навыков.</p><p>Характеристики, цены и остатки созданы для тестирования MiniMarket, не являются предложением реального магазина.</p>', 21, 'images/minimarket/sample-01.png', 'Игра «Кто быстрее?»', 'Игра «Кто быстрее?»', 'Игра «Кто быстрее?»: описание, характеристики, наличие и варианты исполнения в MiniMarket.', 'index,follow', 'Игра «Кто быстрее?»', 'Игра «Кто быстрее?»: описание, характеристики, наличие и варианты исполнения в MiniMarket.', 'images/minimarket/sample-01.png', 'Игра «Кто быстрее?»', 0),
  (1022, 108, 'Игра «Цветные истории»', 'game-color-stories', 'TOY-1022', 1590.00, 23, 1, '<p>Игра «Цветные истории» — игрушка для детей от 4 лет.</p>', '<p>Игра «Цветные истории». Демонстрационный товар из категории «Семейные игры». Материал: Картон. Рекомендуемый возраст: 4+. Предназначен для игр и развития навыков.</p><p>Характеристики, цены и остатки созданы для тестирования MiniMarket, не являются предложением реального магазина.</p>', 22, 'images/minimarket/sample-02.png', 'Игра «Цветные истории»', 'Игра «Цветные истории»', 'Игра «Цветные истории»: описание, характеристики, наличие и варианты исполнения в MiniMarket.', 'index,follow', 'Игра «Цветные истории»', 'Игра «Цветные истории»: описание, характеристики, наличие и варианты исполнения в MiniMarket.', 'images/minimarket/sample-02.png', 'Игра «Цветные истории»', 0),
  (1023, 108, 'Игра «Путешествие по карте»', 'game-travel', 'TOY-1023', 2890.00, 10, 1, '<p>Игра «Путешествие по карте» — игрушка для детей от 7 лет.</p>', '<p>Игра «Путешествие по карте». Демонстрационный товар из категории «Семейные игры». Материал: Картон. Рекомендуемый возраст: 7+. Предназначен для игр и развития навыков.</p><p>Характеристики, цены и остатки созданы для тестирования MiniMarket, не являются предложением реального магазина.</p>', 23, 'images/minimarket/sample-03.png', 'Игра «Путешествие по карте»', 'Игра «Путешествие по карте»', 'Игра «Путешествие по карте»: описание, характеристики, наличие и варианты исполнения в MiniMarket.', 'index,follow', 'Игра «Путешествие по карте»', 'Игра «Путешествие по карте»: описание, характеристики, наличие и варианты исполнения в MiniMarket.', 'images/minimarket/sample-03.png', 'Игра «Путешествие по карте»', 0),
  (1024, 108, 'Игра «Весёлые вопросы»', 'game-questions', 'TOY-1024', 1290.00, 26, 1, '<p>Игра «Весёлые вопросы» — игрушка для детей от 5 лет.</p>', '<p>Игра «Весёлые вопросы». Демонстрационный товар из категории «Семейные игры». Материал: Картон. Рекомендуемый возраст: 5+. Предназначен для игр и развития навыков.</p><p>Характеристики, цены и остатки созданы для тестирования MiniMarket, не являются предложением реального магазина.</p>', 24, 'images/minimarket/sample-04.png', 'Игра «Весёлые вопросы»', 'Игра «Весёлые вопросы»', 'Игра «Весёлые вопросы»: описание, характеристики, наличие и варианты исполнения в MiniMarket.', 'index,follow', 'Игра «Весёлые вопросы»', 'Игра «Весёлые вопросы»: описание, характеристики, наличие и варианты исполнения в MiniMarket.', 'images/minimarket/sample-04.png', 'Игра «Весёлые вопросы»', 0),
  (1025, 109, 'Мемо «Найди пару»', 'edu-memory', 'TOY-1025', 990.00, 31, 1, '<p>Мемо «Найди пару» — игрушка для детей от 3 лет.</p>', '<p>Мемо «Найди пару». Демонстрационный товар из категории «Обучающие игры». Материал: Картон. Рекомендуемый возраст: 3+. Предназначен для игр и развития навыков.</p><p>Характеристики, цены и остатки созданы для тестирования MiniMarket, не являются предложением реального магазина.</p>', 25, 'images/minimarket/sample-05.png', 'Мемо «Найди пару»', 'Мемо «Найди пару»', 'Мемо «Найди пару»: описание, характеристики, наличие и варианты исполнения в MiniMarket.', 'index,follow', 'Мемо «Найди пару»', 'Мемо «Найди пару»: описание, характеристики, наличие и варианты исполнения в MiniMarket.', 'images/minimarket/sample-05.png', 'Мемо «Найди пару»', 0),
  (1026, 109, 'Набор «Счёт до 20»', 'edu-count', 'TOY-1026', 1390.00, 18, 1, '<p>Набор «Счёт до 20» — игрушка для детей от 5 лет.</p>', '<p>Набор «Счёт до 20». Демонстрационный товар из категории «Обучающие игры». Материал: Дерево. Рекомендуемый возраст: 5+. Предназначен для игр и развития навыков.</p><p>Характеристики, цены и остатки созданы для тестирования MiniMarket, не являются предложением реального магазина.</p>', 26, 'images/minimarket/sample-06.png', 'Набор «Счёт до 20»', 'Набор «Счёт до 20»', 'Набор «Счёт до 20»: описание, характеристики, наличие и варианты исполнения в MiniMarket.', 'index,follow', 'Набор «Счёт до 20»', 'Набор «Счёт до 20»: описание, характеристики, наличие и варианты исполнения в MiniMarket.', 'images/minimarket/sample-06.png', 'Набор «Счёт до 20»', 0),
  (1027, 109, 'Игра «Буквенный экспресс»', 'edu-letters', 'TOY-1027', 1990.00, 11, 1, '<p>Игра «Буквенный экспресс» — игрушка для детей от 5 лет.</p>', '<p>Игра «Буквенный экспресс». Демонстрационный товар из категории «Обучающие игры». Материал: Картон. Рекомендуемый возраст: 5+. Предназначен для игр и развития навыков.</p><p>Характеристики, цены и остатки созданы для тестирования MiniMarket, не являются предложением реального магазина.</p>', 27, 'images/minimarket/sample-07.png', 'Игра «Буквенный экспресс»', 'Игра «Буквенный экспресс»', 'Игра «Буквенный экспресс»: описание, характеристики, наличие и варианты исполнения в MiniMarket.', 'index,follow', 'Игра «Буквенный экспресс»', 'Игра «Буквенный экспресс»: описание, характеристики, наличие и варианты исполнения в MiniMarket.', 'images/minimarket/sample-07.png', 'Игра «Буквенный экспресс»', 0),
  (1028, 109, 'Обучающая игра «Учим часы»', 'edu-clock', 'TOY-1028', 1690.00, 14, 1, '<p>Обучающая игра «Учим часы» — игрушка для детей от 5 лет.</p>', '<p>Обучающая игра «Учим часы». Демонстрационный товар из категории «Обучающие игры». Материал: Дерево. Рекомендуемый возраст: 5+. Предназначен для игр и развития навыков.</p><p>Характеристики, цены и остатки созданы для тестирования MiniMarket, не являются предложением реального магазина.</p>', 28, 'images/minimarket/sample-08.png', 'Обучающая игра «Учим часы»', 'Обучающая игра «Учим часы»', 'Обучающая игра «Учим часы»: описание, характеристики, наличие и варианты исполнения в MiniMarket.', 'index,follow', 'Обучающая игра «Учим часы»', 'Обучающая игра «Учим часы»: описание, характеристики, наличие и варианты исполнения в MiniMarket.', 'images/minimarket/sample-08.png', 'Обучающая игра «Учим часы»', 0),
  (1029, 111, 'Сортер «Умный домик»', 'sorter-house', 'TOY-1029', 2790.00, 15, 1, '<p>Сортер «Умный домик» — игрушка для детей от 1 лет.</p>', '<p>Сортер «Умный домик». Демонстрационный товар из категории «Сортеры и пирамидки». Материал: Дерево. Рекомендуемый возраст: 1+. Предназначен для игр и развития навыков.</p><p>Характеристики, цены и остатки созданы для тестирования MiniMarket, не являются предложением реального магазина.</p>', 29, 'images/minimarket/sample-09.png', 'Сортер «Умный домик»', 'Сортер «Умный домик»', 'Сортер «Умный домик»: описание, характеристики, наличие и варианты исполнения в MiniMarket.', 'index,follow', 'Сортер «Умный домик»', 'Сортер «Умный домик»: описание, характеристики, наличие и варианты исполнения в MiniMarket.', 'images/minimarket/sample-09.png', 'Сортер «Умный домик»', 0),
  (1030, 111, 'Пирамидка «Цветные кольца»', 'rings-rainbow', 'TOY-1030', 1190.00, 25, 1, '<p>Пирамидка «Цветные кольца» — игрушка для детей от 1 лет.</p>', '<p>Пирамидка «Цветные кольца». Демонстрационный товар из категории «Сортеры и пирамидки». Материал: Дерево. Рекомендуемый возраст: 1+. Предназначен для игр и развития навыков.</p><p>Характеристики, цены и остатки созданы для тестирования MiniMarket, не являются предложением реального магазина.</p>', 30, 'images/minimarket/sample-10.png', 'Пирамидка «Цветные кольца»', 'Пирамидка «Цветные кольца»', 'Пирамидка «Цветные кольца»: описание, характеристики, наличие и варианты исполнения в MiniMarket.', 'index,follow', 'Пирамидка «Цветные кольца»', 'Пирамидка «Цветные кольца»: описание, характеристики, наличие и варианты исполнения в MiniMarket.', 'images/minimarket/sample-10.png', 'Пирамидка «Цветные кольца»', 0),
  (1031, 111, 'Кубик «Первые открытия»', 'busy-cube', 'TOY-1031', 3490.00, 8, 1, '<p>Кубик «Первые открытия» — игрушка для детей от 1 лет.</p>', '<p>Кубик «Первые открытия». Демонстрационный товар из категории «Сортеры и пирамидки». Материал: Дерево. Рекомендуемый возраст: 1+. Предназначен для игр и развития навыков.</p><p>Характеристики, цены и остатки созданы для тестирования MiniMarket, не являются предложением реального магазина.</p>', 31, 'images/minimarket/sample-01.png', 'Кубик «Первые открытия»', 'Кубик «Первые открытия»', 'Кубик «Первые открытия»: описание, характеристики, наличие и варианты исполнения в MiniMarket.', 'index,follow', 'Кубик «Первые открытия»', 'Кубик «Первые открытия»: описание, характеристики, наличие и варианты исполнения в MiniMarket.', 'images/minimarket/sample-01.png', 'Кубик «Первые открытия»', 0),
  (1032, 111, 'Деревянная радуга', 'rainbow-arch', 'TOY-1032', 2990.00, 9, 1, '<p>Деревянная радуга — игрушка для детей от 1 лет.</p>', '<p>Деревянная радуга. Демонстрационный товар из категории «Сортеры и пирамидки». Материал: Дерево. Рекомендуемый возраст: 1+. Предназначен для игр и развития навыков.</p><p>Характеристики, цены и остатки созданы для тестирования MiniMarket, не являются предложением реального магазина.</p>', 32, 'images/minimarket/sample-02.png', 'Деревянная радуга', 'Деревянная радуга', 'Деревянная радуга: описание, характеристики, наличие и варианты исполнения в MiniMarket.', 'index,follow', 'Деревянная радуга', 'Деревянная радуга: описание, характеристики, наличие и варианты исполнения в MiniMarket.', 'images/minimarket/sample-02.png', 'Деревянная радуга', 0),
  (1033, 112, 'Детский барабан «Первый ритм»', 'music-drum', 'TOY-1033', 1690.00, 12, 1, '<p>Детский барабан «Первый ритм» — игрушка для детей от 2 лет.</p>', '<p>Детский барабан «Первый ритм». Демонстрационный товар из категории «Музыкальные игрушки». Материал: Дерево. Рекомендуемый возраст: 2+. Предназначен для игр и развития навыков.</p><p>Характеристики, цены и остатки созданы для тестирования MiniMarket, не являются предложением реального магазина.</p>', 33, 'images/minimarket/sample-03.png', 'Детский барабан «Первый ритм»', 'Детский барабан «Первый ритм»', 'Детский барабан «Первый ритм»: описание, характеристики, наличие и варианты исполнения в MiniMarket.', 'index,follow', 'Детский барабан «Первый ритм»', 'Детский барабан «Первый ритм»: описание, характеристики, наличие и варианты исполнения в MiniMarket.', 'images/minimarket/sample-03.png', 'Детский барабан «Первый ритм»', 0),
  (1034, 112, 'Ксилофон «Радуга»', 'music-xylophone', 'TOY-1034', 2190.00, 15, 1, '<p>Ксилофон «Радуга» — игрушка для детей от 2 лет.</p>', '<p>Ксилофон «Радуга». Демонстрационный товар из категории «Музыкальные игрушки». Материал: Дерево. Рекомендуемый возраст: 2+. Предназначен для игр и развития навыков.</p><p>Характеристики, цены и остатки созданы для тестирования MiniMarket, не являются предложением реального магазина.</p>', 34, 'images/minimarket/sample-04.png', 'Ксилофон «Радуга»', 'Ксилофон «Радуга»', 'Ксилофон «Радуга»: описание, характеристики, наличие и варианты исполнения в MiniMarket.', 'index,follow', 'Ксилофон «Радуга»', 'Ксилофон «Радуга»: описание, характеристики, наличие и варианты исполнения в MiniMarket.', 'images/minimarket/sample-04.png', 'Ксилофон «Радуга»', 0),
  (1035, 112, 'Набор погремушек «Мелодия»', 'music-rattle', 'TOY-1035', 1190.00, 19, 1, '<p>Набор погремушек «Мелодия» — игрушка для детей от 1 лет.</p>', '<p>Набор погремушек «Мелодия». Демонстрационный товар из категории «Музыкальные игрушки». Материал: Пластик. Рекомендуемый возраст: 1+. Предназначен для игр и развития навыков.</p><p>Характеристики, цены и остатки созданы для тестирования MiniMarket, не являются предложением реального магазина.</p>', 35, 'images/minimarket/sample-05.png', 'Набор погремушек «Мелодия»', 'Набор погремушек «Мелодия»', 'Набор погремушек «Мелодия»: описание, характеристики, наличие и варианты исполнения в MiniMarket.', 'index,follow', 'Набор погремушек «Мелодия»', 'Набор погремушек «Мелодия»: описание, характеристики, наличие и варианты исполнения в MiniMarket.', 'images/minimarket/sample-05.png', 'Набор погремушек «Мелодия»', 0),
  (1036, 112, 'Детское пианино «Мини»', 'music-piano', 'TOY-1036', 3890.00, 6, 1, '<p>Детское пианино «Мини» — игрушка для детей от 3 лет.</p>', '<p>Детское пианино «Мини». Демонстрационный товар из категории «Музыкальные игрушки». Материал: Пластик. Рекомендуемый возраст: 3+. Предназначен для игр и развития навыков.</p><p>Характеристики, цены и остатки созданы для тестирования MiniMarket, не являются предложением реального магазина.</p>', 36, 'images/minimarket/sample-06.png', 'Детское пианино «Мини»', 'Детское пианино «Мини»', 'Детское пианино «Мини»: описание, характеристики, наличие и варианты исполнения в MiniMarket.', 'index,follow', 'Детское пианино «Мини»', 'Детское пианино «Мини»: описание, характеристики, наличие и варианты исполнения в MiniMarket.', 'images/minimarket/sample-06.png', 'Детское пианино «Мини»', 0),
  (1037, 114, 'Машинка «Сити»', 'car-city', 'TOY-1037', 990.00, 36, 1, '<p>Машинка «Сити» — игрушка для детей от 3 лет.</p>', '<p>Машинка «Сити». Демонстрационный товар из категории «Легковые машинки». Материал: Металл. Рекомендуемый возраст: 3+. Предназначен для игр и развития навыков.</p><p>Характеристики, цены и остатки созданы для тестирования MiniMarket, не являются предложением реального магазина.</p>', 37, 'images/minimarket/sample-07.png', 'Машинка «Сити»', 'Машинка «Сити»', 'Машинка «Сити»: описание, характеристики, наличие и варианты исполнения в MiniMarket.', 'index,follow', 'Машинка «Сити»', 'Машинка «Сити»: описание, характеристики, наличие и варианты исполнения в MiniMarket.', 'images/minimarket/sample-07.png', 'Машинка «Сити»', 0),
  (1038, 114, 'Гоночный болид «Турбо»', 'car-bolid', 'TOY-1038', 1490.00, 16, 1, '<p>Гоночный болид «Турбо» — игрушка для детей от 4 лет.</p>', '<p>Гоночный болид «Турбо». Демонстрационный товар из категории «Легковые машинки». Материал: Металл. Рекомендуемый возраст: 4+. Предназначен для игр и развития навыков.</p><p>Характеристики, цены и остатки созданы для тестирования MiniMarket, не являются предложением реального магазина.</p>', 38, 'images/minimarket/sample-08.png', 'Гоночный болид «Турбо»', 'Гоночный болид «Турбо»', 'Гоночный болид «Турбо»: описание, характеристики, наличие и варианты исполнения в MiniMarket.', 'index,follow', 'Гоночный болид «Турбо»', 'Гоночный болид «Турбо»: описание, характеристики, наличие и варианты исполнения в MiniMarket.', 'images/minimarket/sample-08.png', 'Гоночный болид «Турбо»', 0),
  (1039, 114, 'Игрушечный автобус «Школьный»', 'car-bus', 'TOY-1039', 1690.00, 13, 1, '<p>Игрушечный автобус «Школьный» — игрушка для детей от 3 лет.</p>', '<p>Игрушечный автобус «Школьный». Демонстрационный товар из категории «Легковые машинки». Материал: Пластик. Рекомендуемый возраст: 3+. Предназначен для игр и развития навыков.</p><p>Характеристики, цены и остатки созданы для тестирования MiniMarket, не являются предложением реального магазина.</p>', 39, 'images/minimarket/sample-09.png', 'Игрушечный автобус «Школьный»', 'Игрушечный автобус «Школьный»', 'Игрушечный автобус «Школьный»: описание, характеристики, наличие и варианты исполнения в MiniMarket.', 'index,follow', 'Игрушечный автобус «Школьный»', 'Игрушечный автобус «Школьный»: описание, характеристики, наличие и варианты исполнения в MiniMarket.', 'images/minimarket/sample-09.png', 'Игрушечный автобус «Школьный»', 0),
  (1040, 114, 'Внедорожник «Следопыт»', 'car-offroad', 'TOY-1040', 2190.00, 0, 1, '<p>Внедорожник «Следопыт» — игрушка для детей от 4 лет.</p>', '<p>Внедорожник «Следопыт». Демонстрационный товар из категории «Легковые машинки». Материал: Металл. Рекомендуемый возраст: 4+. Предназначен для игр и развития навыков.</p><p>Характеристики, цены и остатки созданы для тестирования MiniMarket, не являются предложением реального магазина.</p>', 40, 'images/minimarket/sample-10.png', 'Внедорожник «Следопыт»', 'Внедорожник «Следопыт»', 'Внедорожник «Следопыт»: описание, характеристики, наличие и варианты исполнения в MiniMarket.', 'index,follow', 'Внедорожник «Следопыт»', 'Внедорожник «Следопыт»: описание, характеристики, наличие и варианты исполнения в MiniMarket.', 'images/minimarket/sample-10.png', 'Внедорожник «Следопыт»', 0),
  (1041, 115, 'Пожарная машина с лестницей', 'truck-fire', 'TOY-1041', 2890.00, 10, 1, '<p>Пожарная машина с лестницей — игрушка для детей от 3 лет.</p>', '<p>Пожарная машина с лестницей. Демонстрационный товар из категории «Спецтехника». Материал: Пластик. Рекомендуемый возраст: 3+. Предназначен для игр и развития навыков.</p><p>Характеристики, цены и остатки созданы для тестирования MiniMarket, не являются предложением реального магазина.</p>', 41, 'images/minimarket/sample-01.png', 'Пожарная машина с лестницей', 'Пожарная машина с лестницей', 'Пожарная машина с лестницей: описание, характеристики, наличие и варианты исполнения в MiniMarket.', 'index,follow', 'Пожарная машина с лестницей', 'Пожарная машина с лестницей: описание, характеристики, наличие и варианты исполнения в MiniMarket.', 'images/minimarket/sample-01.png', 'Пожарная машина с лестницей', 0),
  (1042, 115, 'Экскаватор «Стройка»', 'truck-excavator', 'TOY-1042', 2590.00, 12, 1, '<p>Экскаватор «Стройка» — игрушка для детей от 3 лет.</p>', '<p>Экскаватор «Стройка». Демонстрационный товар из категории «Спецтехника». Материал: Пластик. Рекомендуемый возраст: 3+. Предназначен для игр и развития навыков.</p><p>Характеристики, цены и остатки созданы для тестирования MiniMarket, не являются предложением реального магазина.</p>', 42, 'images/minimarket/sample-02.png', 'Экскаватор «Стройка»', 'Экскаватор «Стройка»', 'Экскаватор «Стройка»: описание, характеристики, наличие и варианты исполнения в MiniMarket.', 'index,follow', 'Экскаватор «Стройка»', 'Экскаватор «Стройка»: описание, характеристики, наличие и варианты исполнения в MiniMarket.', 'images/minimarket/sample-02.png', 'Экскаватор «Стройка»', 0),
  (1043, 115, 'Бетономешалка «Мастер»', 'truck-mixer', 'TOY-1043', 2190.00, 7, 1, '<p>Бетономешалка «Мастер» — игрушка для детей от 3 лет.</p>', '<p>Бетономешалка «Мастер». Демонстрационный товар из категории «Спецтехника». Материал: Пластик. Рекомендуемый возраст: 3+. Предназначен для игр и развития навыков.</p><p>Характеристики, цены и остатки созданы для тестирования MiniMarket, не являются предложением реального магазина.</p>', 43, 'images/minimarket/sample-03.png', 'Бетономешалка «Мастер»', 'Бетономешалка «Мастер»', 'Бетономешалка «Мастер»: описание, характеристики, наличие и варианты исполнения в MiniMarket.', 'index,follow', 'Бетономешалка «Мастер»', 'Бетономешалка «Мастер»: описание, характеристики, наличие и варианты исполнения в MiniMarket.', 'images/minimarket/sample-03.png', 'Бетономешалка «Мастер»', 0),
  (1044, 115, 'Подъёмный кран', 'truck-crane', 'TOY-1044', 3490.00, 5, 1, '<p>Подъёмный кран — игрушка для детей от 4 лет.</p>', '<p>Подъёмный кран. Демонстрационный товар из категории «Спецтехника». Материал: Пластик. Рекомендуемый возраст: 4+. Предназначен для игр и развития навыков.</p><p>Характеристики, цены и остатки созданы для тестирования MiniMarket, не являются предложением реального магазина.</p>', 44, 'images/minimarket/sample-04.png', 'Подъёмный кран', 'Подъёмный кран', 'Подъёмный кран: описание, характеристики, наличие и варианты исполнения в MiniMarket.', 'index,follow', 'Подъёмный кран', 'Подъёмный кран: описание, характеристики, наличие и варианты исполнения в MiniMarket.', 'images/minimarket/sample-04.png', 'Подъёмный кран', 0),
  (1045, 117, 'Альбом «Мои первые рисунки»', 'art-first-album', 'TOY-1045', 590.00, 45, 1, '<p>Альбом «Мои первые рисунки» — игрушка для детей от 3 лет.</p>', '<p>Альбом «Мои первые рисунки». Демонстрационный товар из категории «Рисование». Материал: Бумага. Рекомендуемый возраст: 3+. Предназначен для игр и развития навыков.</p><p>Характеристики, цены и остатки созданы для тестирования MiniMarket, не являются предложением реального магазина.</p>', 45, 'images/minimarket/sample-05.png', 'Альбом «Мои первые рисунки»', 'Альбом «Мои первые рисунки»', 'Альбом «Мои первые рисунки»: описание, характеристики, наличие и варианты исполнения в MiniMarket.', 'index,follow', 'Альбом «Мои первые рисунки»', 'Альбом «Мои первые рисунки»: описание, характеристики, наличие и варианты исполнения в MiniMarket.', 'images/minimarket/sample-05.png', 'Альбом «Мои первые рисунки»', 0),
  (1046, 117, 'Набор фломастеров «Яркий мир»', 'art-markers', 'TOY-1046', 990.00, 21, 1, '<p>Набор фломастеров «Яркий мир» — игрушка для детей от 3 лет.</p>', '<p>Набор фломастеров «Яркий мир». Демонстрационный товар из категории «Рисование». Материал: Пластик. Рекомендуемый возраст: 3+. Предназначен для игр и развития навыков.</p><p>Характеристики, цены и остатки созданы для тестирования MiniMarket, не являются предложением реального магазина.</p>', 46, 'images/minimarket/sample-06.png', 'Набор фломастеров «Яркий мир»', 'Набор фломастеров «Яркий мир»', 'Набор фломастеров «Яркий мир»: описание, характеристики, наличие и варианты исполнения в MiniMarket.', 'index,follow', 'Набор фломастеров «Яркий мир»', 'Набор фломастеров «Яркий мир»: описание, характеристики, наличие и варианты исполнения в MiniMarket.', 'images/minimarket/sample-06.png', 'Набор фломастеров «Яркий мир»', 0),
  (1047, 117, 'Акварель «12 оттенков»', 'art-watercolor', 'TOY-1047', 790.00, 27, 1, '<p>Акварель «12 оттенков» — игрушка для детей от 4 лет.</p>', '<p>Акварель «12 оттенков». Демонстрационный товар из категории «Рисование». Материал: Краски. Рекомендуемый возраст: 4+. Предназначен для игр и развития навыков.</p><p>Характеристики, цены и остатки созданы для тестирования MiniMarket, не являются предложением реального магазина.</p>', 47, 'images/minimarket/sample-07.png', 'Акварель «12 оттенков»', 'Акварель «12 оттенков»', 'Акварель «12 оттенков»: описание, характеристики, наличие и варианты исполнения в MiniMarket.', 'index,follow', 'Акварель «12 оттенков»', 'Акварель «12 оттенков»: описание, характеристики, наличие и варианты исполнения в MiniMarket.', 'images/minimarket/sample-07.png', 'Акварель «12 оттенков»', 0),
  (1048, 117, 'Набор художника «Мастерская»', 'art-artist-kit', 'TOY-1048', 3190.00, 0, 1, '<p>Набор художника «Мастерская» — игрушка для детей от 5 лет.</p>', '<p>Набор художника «Мастерская». Демонстрационный товар из категории «Рисование». Материал: Смешанный. Рекомендуемый возраст: 5+. Предназначен для игр и развития навыков.</p><p>Характеристики, цены и остатки созданы для тестирования MiniMarket, не являются предложением реального магазина.</p>', 48, 'images/minimarket/sample-08.png', 'Набор художника «Мастерская»', 'Набор художника «Мастерская»', 'Набор художника «Мастерская»: описание, характеристики, наличие и варианты исполнения в MiniMarket.', 'index,follow', 'Набор художника «Мастерская»', 'Набор художника «Мастерская»: описание, характеристики, наличие и варианты исполнения в MiniMarket.', 'images/minimarket/sample-08.png', 'Набор художника «Мастерская»', 1),
  (1049, 118, 'Набор теста для лепки «Пластилинка»', 'clay-dough', 'TOY-1049', 1590.00, 0, 1, '<p>Набор теста для лепки «Пластилинка» — игрушка для детей от 3 лет.</p>', '<p>Набор теста для лепки «Пластилинка». Демонстрационный товар из категории «Лепка и моделирование». Материал: Тесто. Рекомендуемый возраст: 3+. Предназначен для игр и развития навыков.</p><p>Характеристики, цены и остатки созданы для тестирования MiniMarket, не являются предложением реального магазина.</p>', 49, 'images/minimarket/sample-09.png', 'Набор теста для лепки «Пластилинка»', 'Набор теста для лепки «Пластилинка»', 'Набор теста для лепки «Пластилинка»: описание, характеристики, наличие и варианты исполнения в MiniMarket.', 'index,follow', 'Набор теста для лепки «Пластилинка»', 'Набор теста для лепки «Пластилинка»: описание, характеристики, наличие и варианты исполнения в MiniMarket.', 'images/minimarket/sample-09.png', 'Набор теста для лепки «Пластилинка»', 1),
  (1050, 118, 'Кинетический песок «Замки»', 'clay-sand', 'TOY-1050', 1890.00, 10, 1, '<p>Кинетический песок «Замки» — игрушка для детей от 3 лет.</p>', '<p>Кинетический песок «Замки». Демонстрационный товар из категории «Лепка и моделирование». Материал: Песок. Рекомендуемый возраст: 3+. Предназначен для игр и развития навыков.</p><p>Характеристики, цены и остатки созданы для тестирования MiniMarket, не являются предложением реального магазина.</p>', 50, 'images/minimarket/sample-10.png', 'Кинетический песок «Замки»', 'Кинетический песок «Замки»', 'Кинетический песок «Замки»: описание, характеристики, наличие и варианты исполнения в MiniMarket.', 'index,follow', 'Кинетический песок «Замки»', 'Кинетический песок «Замки»: описание, характеристики, наличие и варианты исполнения в MiniMarket.', 'images/minimarket/sample-10.png', 'Кинетический песок «Замки»', 0),
  (1051, 118, 'Мягкая масса для лепки «Облако»', 'clay-soft', 'TOY-1051', 1390.00, 17, 1, '<p>Мягкая масса для лепки «Облако» — игрушка для детей от 3 лет.</p>', '<p>Мягкая масса для лепки «Облако». Демонстрационный товар из категории «Лепка и моделирование». Материал: Масса для лепки. Рекомендуемый возраст: 3+. Предназначен для игр и развития навыков.</p><p>Характеристики, цены и остатки созданы для тестирования MiniMarket, не являются предложением реального магазина.</p>', 51, 'images/minimarket/sample-01.png', 'Мягкая масса для лепки «Облако»', 'Мягкая масса для лепки «Облако»', 'Мягкая масса для лепки «Облако»: описание, характеристики, наличие и варианты исполнения в MiniMarket.', 'index,follow', 'Мягкая масса для лепки «Облако»', 'Мягкая масса для лепки «Облако»: описание, характеристики, наличие и варианты исполнения в MiniMarket.', 'images/minimarket/sample-01.png', 'Мягкая масса для лепки «Облако»', 0),
  (1052, 118, 'Формочки «Зверята»', 'clay-molds', 'TOY-1052', 690.00, 22, 1, '<p>Формочки «Зверята» — игрушка для детей от 3 лет.</p>', '<p>Формочки «Зверята». Демонстрационный товар из категории «Лепка и моделирование». Материал: Пластик. Рекомендуемый возраст: 3+. Предназначен для игр и развития навыков.</p><p>Характеристики, цены и остатки созданы для тестирования MiniMarket, не являются предложением реального магазина.</p>', 52, 'images/minimarket/sample-02.png', 'Формочки «Зверята»', 'Формочки «Зверята»', 'Формочки «Зверята»: описание, характеристики, наличие и варианты исполнения в MiniMarket.', 'index,follow', 'Формочки «Зверята»', 'Формочки «Зверята»: описание, характеристики, наличие и варианты исполнения в MiniMarket.', 'images/minimarket/sample-02.png', 'Формочки «Зверята»', 0),
  (1053, 120, 'Кукла «Малышка Аня»', 'doll-anya', 'TOY-1053', 2790.00, 14, 1, '<p>Кукла «Малышка Аня» — игрушка для детей от 3 лет.</p>', '<p>Кукла «Малышка Аня». Демонстрационный товар из категории «Куклы». Материал: Пластик. Рекомендуемый возраст: 3+. Предназначен для игр и развития навыков.</p><p>Характеристики, цены и остатки созданы для тестирования MiniMarket, не являются предложением реального магазина.</p>', 53, 'images/minimarket/sample-03.png', 'Кукла «Малышка Аня»', 'Кукла «Малышка Аня»', 'Кукла «Малышка Аня»: описание, характеристики, наличие и варианты исполнения в MiniMarket.', 'index,follow', 'Кукла «Малышка Аня»', 'Кукла «Малышка Аня»: описание, характеристики, наличие и варианты исполнения в MiniMarket.', 'images/minimarket/sample-03.png', 'Кукла «Малышка Аня»', 0),
  (1054, 120, 'Кукла «Настя»', 'doll-nastya', 'TOY-1054', 3290.00, 0, 1, '<p>Кукла «Настя» — игрушка для детей от 3 лет.</p>', '<p>Кукла «Настя». Демонстрационный товар из категории «Куклы». Материал: Пластик. Рекомендуемый возраст: 3+. Предназначен для игр и развития навыков.</p><p>Характеристики, цены и остатки созданы для тестирования MiniMarket, не являются предложением реального магазина.</p>', 54, 'images/minimarket/sample-04.png', 'Кукла «Настя»', 'Кукла «Настя»', 'Кукла «Настя»: описание, характеристики, наличие и варианты исполнения в MiniMarket.', 'index,follow', 'Кукла «Настя»', 'Кукла «Настя»: описание, характеристики, наличие и варианты исполнения в MiniMarket.', 'images/minimarket/sample-04.png', 'Кукла «Настя»', 1),
  (1055, 120, 'Кукла «Лиза»', 'doll-liza', 'TOY-1055', 2890.00, 6, 1, '<p>Кукла «Лиза» — игрушка для детей от 3 лет.</p>', '<p>Кукла «Лиза». Демонстрационный товар из категории «Куклы». Материал: Пластик. Рекомендуемый возраст: 3+. Предназначен для игр и развития навыков.</p><p>Характеристики, цены и остатки созданы для тестирования MiniMarket, не являются предложением реального магазина.</p>', 55, 'images/minimarket/sample-05.png', 'Кукла «Лиза»', 'Кукла «Лиза»', 'Кукла «Лиза»: описание, характеристики, наличие и варианты исполнения в MiniMarket.', 'index,follow', 'Кукла «Лиза»', 'Кукла «Лиза»: описание, характеристики, наличие и варианты исполнения в MiniMarket.', 'images/minimarket/sample-05.png', 'Кукла «Лиза»', 0),
  (1056, 120, 'Пупс «Соня»', 'doll-sonya', 'TOY-1056', 2290.00, 11, 1, '<p>Пупс «Соня» — игрушка для детей от 3 лет.</p>', '<p>Пупс «Соня». Демонстрационный товар из категории «Куклы». Материал: Пластик. Рекомендуемый возраст: 3+. Предназначен для игр и развития навыков.</p><p>Характеристики, цены и остатки созданы для тестирования MiniMarket, не являются предложением реального магазина.</p>', 56, 'images/minimarket/sample-06.png', 'Пупс «Соня»', 'Пупс «Соня»', 'Пупс «Соня»: описание, характеристики, наличие и варианты исполнения в MiniMarket.', 'index,follow', 'Пупс «Соня»', 'Пупс «Соня»: описание, характеристики, наличие и варианты исполнения в MiniMarket.', 'images/minimarket/sample-06.png', 'Пупс «Соня»', 0),
  (1057, 121, 'Кукольный домик «Солнечный»', 'dollhouse-sunny', 'TOY-1057', 8490.00, 4, 1, '<p>Кукольный домик «Солнечный» — игрушка для детей от 5 лет.</p>', '<p>Кукольный домик «Солнечный». Демонстрационный товар из категории «Кукольные домики». Материал: Дерево. Рекомендуемый возраст: 5+. Предназначен для игр и развития навыков.</p><p>Характеристики, цены и остатки созданы для тестирования MiniMarket, не являются предложением реального магазина.</p>', 57, 'images/minimarket/sample-07.png', 'Кукольный домик «Солнечный»', 'Кукольный домик «Солнечный»', 'Кукольный домик «Солнечный»: описание, характеристики, наличие и варианты исполнения в MiniMarket.', 'index,follow', 'Кукольный домик «Солнечный»', 'Кукольный домик «Солнечный»: описание, характеристики, наличие и варианты исполнения в MiniMarket.', 'images/minimarket/sample-07.png', 'Кукольный домик «Солнечный»', 0),
  (1058, 121, 'Набор мебели для кукол', 'dollhouse-furniture', 'TOY-1058', 2290.00, 12, 1, '<p>Набор мебели для кукол — игрушка для детей от 5 лет.</p>', '<p>Набор мебели для кукол. Демонстрационный товар из категории «Кукольные домики». Материал: Дерево. Рекомендуемый возраст: 5+. Предназначен для игр и развития навыков.</p><p>Характеристики, цены и остатки созданы для тестирования MiniMarket, не являются предложением реального магазина.</p>', 58, 'images/minimarket/sample-08.png', 'Набор мебели для кукол', 'Набор мебели для кукол', 'Набор мебели для кукол: описание, характеристики, наличие и варианты исполнения в MiniMarket.', 'index,follow', 'Набор мебели для кукол', 'Набор мебели для кукол: описание, характеристики, наличие и варианты исполнения в MiniMarket.', 'images/minimarket/sample-08.png', 'Набор мебели для кукол', 0),
  (1059, 121, 'Кукольная кухня «Маленький повар»', 'dollhouse-kitchen', 'TOY-1059', 3890.00, 6, 1, '<p>Кукольная кухня «Маленький повар» — игрушка для детей от 5 лет.</p>', '<p>Кукольная кухня «Маленький повар». Демонстрационный товар из категории «Кукольные домики». Материал: Дерево. Рекомендуемый возраст: 5+. Предназначен для игр и развития навыков.</p><p>Характеристики, цены и остатки созданы для тестирования MiniMarket, не являются предложением реального магазина.</p>', 59, 'images/minimarket/sample-09.png', 'Кукольная кухня «Маленький повар»', 'Кукольная кухня «Маленький повар»', 'Кукольная кухня «Маленький повар»: описание, характеристики, наличие и варианты исполнения в MiniMarket.', 'index,follow', 'Кукольная кухня «Маленький повар»', 'Кукольная кухня «Маленький повар»: описание, характеристики, наличие и варианты исполнения в MiniMarket.', 'images/minimarket/sample-09.png', 'Кукольная кухня «Маленький повар»', 0),
  (1060, 121, 'Кукольная спальня', 'dollhouse-bedroom', 'TOY-1060', 1990.00, 9, 1, '<p>Кукольная спальня — игрушка для детей от 5 лет.</p>', '<p>Кукольная спальня. Демонстрационный товар из категории «Кукольные домики». Материал: Дерево. Рекомендуемый возраст: 5+. Предназначен для игр и развития навыков.</p><p>Характеристики, цены и остатки созданы для тестирования MiniMarket, не являются предложением реального магазина.</p>', 60, 'images/minimarket/sample-10.png', 'Кукольная спальня', 'Кукольная спальня', 'Кукольная спальня: описание, характеристики, наличие и варианты исполнения в MiniMarket.', 'index,follow', 'Кукольная спальня', 'Кукольная спальня: описание, характеристики, наличие и варианты исполнения в MiniMarket.', 'images/minimarket/sample-10.png', 'Кукольная спальня', 0),
  (1061, 123, 'Деревянный пазл «Африка»', 'puzzle-africa', 'TOY-1061', 1690.00, 13, 1, '<p>Деревянный пазл «Африка» — игрушка для детей от 3 лет.</p>', '<p>Деревянный пазл «Африка». Демонстрационный товар из категории «Деревянные пазлы». Материал: Дерево. Рекомендуемый возраст: 3+. Предназначен для игр и развития навыков.</p><p>Характеристики, цены и остатки созданы для тестирования MiniMarket, не являются предложением реального магазина.</p>', 61, 'images/minimarket/sample-01.png', 'Деревянный пазл «Африка»', 'Деревянный пазл «Африка»', 'Деревянный пазл «Африка»: описание, характеристики, наличие и варианты исполнения в MiniMarket.', 'index,follow', 'Деревянный пазл «Африка»', 'Деревянный пазл «Африка»: описание, характеристики, наличие и варианты исполнения в MiniMarket.', 'images/minimarket/sample-01.png', 'Деревянный пазл «Африка»', 0),
  (1062, 123, 'Алфавит из дерева', 'puzzle-alphabet', 'TOY-1062', 2190.00, 20, 1, '<p>Алфавит из дерева — игрушка для детей от 3 лет.</p>', '<p>Алфавит из дерева. Демонстрационный товар из категории «Деревянные пазлы». Материал: Дерево. Рекомендуемый возраст: 3+. Предназначен для игр и развития навыков.</p><p>Характеристики, цены и остатки созданы для тестирования MiniMarket, не являются предложением реального магазина.</p>', 62, 'images/minimarket/sample-02.png', 'Алфавит из дерева', 'Алфавит из дерева', 'Алфавит из дерева: описание, характеристики, наличие и варианты исполнения в MiniMarket.', 'index,follow', 'Алфавит из дерева', 'Алфавит из дерева: описание, характеристики, наличие и варианты исполнения в MiniMarket.', 'images/minimarket/sample-02.png', 'Алфавит из дерева', 0),
  (1063, 123, 'Пазл «Карта мира»', 'puzzle-map', 'TOY-1063', 2790.00, 8, 1, '<p>Пазл «Карта мира» — игрушка для детей от 6 лет.</p>', '<p>Пазл «Карта мира». Демонстрационный товар из категории «Деревянные пазлы». Материал: Дерево. Рекомендуемый возраст: 6+. Предназначен для игр и развития навыков.</p><p>Характеристики, цены и остатки созданы для тестирования MiniMarket, не являются предложением реального магазина.</p>', 63, 'images/minimarket/sample-03.png', 'Пазл «Карта мира»', 'Пазл «Карта мира»', 'Пазл «Карта мира»: описание, характеристики, наличие и варианты исполнения в MiniMarket.', 'index,follow', 'Пазл «Карта мира»', 'Пазл «Карта мира»: описание, характеристики, наличие и варианты исполнения в MiniMarket.', 'images/minimarket/sample-03.png', 'Пазл «Карта мира»', 0),
  (1064, 123, 'Деревянный пазл «Машинки»', 'puzzle-cars', 'TOY-1064', 1190.00, 21, 1, '<p>Деревянный пазл «Машинки» — игрушка для детей от 2 лет.</p>', '<p>Деревянный пазл «Машинки». Демонстрационный товар из категории «Деревянные пазлы». Материал: Дерево. Рекомендуемый возраст: 2+. Предназначен для игр и развития навыков.</p><p>Характеристики, цены и остатки созданы для тестирования MiniMarket, не являются предложением реального магазина.</p>', 64, 'images/minimarket/sample-04.png', 'Деревянный пазл «Машинки»', 'Деревянный пазл «Машинки»', 'Деревянный пазл «Машинки»: описание, характеристики, наличие и варианты исполнения в MiniMarket.', 'index,follow', 'Деревянный пазл «Машинки»', 'Деревянный пазл «Машинки»: описание, характеристики, наличие и варианты исполнения в MiniMarket.', 'images/minimarket/sample-04.png', 'Деревянный пазл «Машинки»', 0),
  (1065, 124, 'Пазл «Космос», 100 деталей', 'puzzle-space', 'TOY-1065', 890.00, 24, 1, '<p>Пазл «Космос», 100 деталей — игрушка для детей от 6 лет.</p>', '<p>Пазл «Космос», 100 деталей. Демонстрационный товар из категории «Картонные пазлы». Материал: Картон. Рекомендуемый возраст: 6+. Предназначен для игр и развития навыков.</p><p>Характеристики, цены и остатки созданы для тестирования MiniMarket, не являются предложением реального магазина.</p>', 65, 'images/minimarket/sample-05.png', 'Пазл «Космос», 100 деталей', 'Пазл «Космос», 100 деталей', 'Пазл «Космос», 100 деталей: описание, характеристики, наличие и варианты исполнения в MiniMarket.', 'index,follow', 'Пазл «Космос», 100 деталей', 'Пазл «Космос», 100 деталей: описание, характеристики, наличие и варианты исполнения в MiniMarket.', 'images/minimarket/sample-05.png', 'Пазл «Космос», 100 деталей', 0),
  (1066, 124, 'Пазл «Океан», 48 деталей', 'puzzle-ocean', 'TOY-1066', 690.00, 33, 1, '<p>Пазл «Океан», 48 деталей — игрушка для детей от 4 лет.</p>', '<p>Пазл «Океан», 48 деталей. Демонстрационный товар из категории «Картонные пазлы». Материал: Картон. Рекомендуемый возраст: 4+. Предназначен для игр и развития навыков.</p><p>Характеристики, цены и остатки созданы для тестирования MiniMarket, не являются предложением реального магазина.</p>', 66, 'images/minimarket/sample-06.png', 'Пазл «Океан», 48 деталей', 'Пазл «Океан», 48 деталей', 'Пазл «Океан», 48 деталей: описание, характеристики, наличие и варианты исполнения в MiniMarket.', 'index,follow', 'Пазл «Океан», 48 деталей', 'Пазл «Океан», 48 деталей: описание, характеристики, наличие и варианты исполнения в MiniMarket.', 'images/minimarket/sample-06.png', 'Пазл «Океан», 48 деталей', 0),
  (1067, 124, 'Пазл «Замок», 200 деталей', 'puzzle-castle', 'TOY-1067', 1190.00, 14, 1, '<p>Пазл «Замок», 200 деталей — игрушка для детей от 7 лет.</p>', '<p>Пазл «Замок», 200 деталей. Демонстрационный товар из категории «Картонные пазлы». Материал: Картон. Рекомендуемый возраст: 7+. Предназначен для игр и развития навыков.</p><p>Характеристики, цены и остатки созданы для тестирования MiniMarket, не являются предложением реального магазина.</p>', 67, 'images/minimarket/sample-07.png', 'Пазл «Замок», 200 деталей', 'Пазл «Замок», 200 деталей', 'Пазл «Замок», 200 деталей: описание, характеристики, наличие и варианты исполнения в MiniMarket.', 'index,follow', 'Пазл «Замок», 200 деталей', 'Пазл «Замок», 200 деталей: описание, характеристики, наличие и варианты исполнения в MiniMarket.', 'images/minimarket/sample-07.png', 'Пазл «Замок», 200 деталей', 0),
  (1068, 124, 'Пазл «Котики», 24 детали', 'puzzle-kittens', 'TOY-1068', 490.00, 41, 1, '<p>Пазл «Котики», 24 детали — игрушка для детей от 3 лет.</p>', '<p>Пазл «Котики», 24 детали. Демонстрационный товар из категории «Картонные пазлы». Материал: Картон. Рекомендуемый возраст: 3+. Предназначен для игр и развития навыков.</p><p>Характеристики, цены и остатки созданы для тестирования MiniMarket, не являются предложением реального магазина.</p>', 68, 'images/minimarket/sample-08.png', 'Пазл «Котики», 24 детали', 'Пазл «Котики», 24 детали', 'Пазл «Котики», 24 детали: описание, характеристики, наличие и варианты исполнения в MiniMarket.', 'index,follow', 'Пазл «Котики», 24 детали', 'Пазл «Котики», 24 детали: описание, характеристики, наличие и варианты исполнения в MiniMarket.', 'images/minimarket/sample-08.png', 'Пазл «Котики», 24 детали', 0),
  (1069, 126, 'Игровая магнитная стена «Мир»', 'wall-magnetic', 'TOY-1069', 18990.00, 3, 1, '<p>Игровая магнитная стена «Мир» — игрушка для детей от 3 лет.</p>', '<p>Игровая магнитная стена «Мир». Демонстрационный товар из категории «Магнитные стены». Материал: Дерево. Рекомендуемый возраст: 3+. Предназначен для игр и развития навыков.</p><p>Характеристики, цены и остатки созданы для тестирования MiniMarket, не являются предложением реального магазина.</p>', 69, 'images/minimarket/sample-09.png', 'Игровая магнитная стена «Мир»', 'Игровая магнитная стена «Мир»', 'Игровая магнитная стена «Мир»: описание, характеристики, наличие и варианты исполнения в MiniMarket.', 'index,follow', 'Игровая магнитная стена «Мир»', 'Игровая магнитная стена «Мир»: описание, характеристики, наличие и варианты исполнения в MiniMarket.', 'images/minimarket/sample-09.png', 'Игровая магнитная стена «Мир»', 0),
  (1070, 126, 'Магнитный модуль «Шестерёнки»', 'wall-gears', 'TOY-1070', 4490.00, 8, 1, '<p>Магнитный модуль «Шестерёнки» — игрушка для детей от 3 лет.</p>', '<p>Магнитный модуль «Шестерёнки». Демонстрационный товар из категории «Магнитные стены». Материал: Дерево. Рекомендуемый возраст: 3+. Предназначен для игр и развития навыков.</p><p>Характеристики, цены и остатки созданы для тестирования MiniMarket, не являются предложением реального магазина.</p>', 70, 'images/minimarket/sample-10.png', 'Магнитный модуль «Шестерёнки»', 'Магнитный модуль «Шестерёнки»', 'Магнитный модуль «Шестерёнки»: описание, характеристики, наличие и варианты исполнения в MiniMarket.', 'index,follow', 'Магнитный модуль «Шестерёнки»', 'Магнитный модуль «Шестерёнки»: описание, характеристики, наличие и варианты исполнения в MiniMarket.', 'images/minimarket/sample-10.png', 'Магнитный модуль «Шестерёнки»', 0),
  (1071, 126, 'Магнитная доска «Рисуй и играй»', 'wall-draw', 'TOY-1071', 6490.00, 7, 1, '<p>Магнитная доска «Рисуй и играй» — игрушка для детей от 3 лет.</p>', '<p>Магнитная доска «Рисуй и играй». Демонстрационный товар из категории «Магнитные стены». Материал: Дерево. Рекомендуемый возраст: 3+. Предназначен для игр и развития навыков.</p><p>Характеристики, цены и остатки созданы для тестирования MiniMarket, не являются предложением реального магазина.</p>', 71, 'images/minimarket/sample-01.png', 'Магнитная доска «Рисуй и играй»', 'Магнитная доска «Рисуй и играй»', 'Магнитная доска «Рисуй и играй»: описание, характеристики, наличие и варианты исполнения в MiniMarket.', 'index,follow', 'Магнитная доска «Рисуй и играй»', 'Магнитная доска «Рисуй и играй»: описание, характеристики, наличие и варианты исполнения в MiniMarket.', 'images/minimarket/sample-01.png', 'Магнитная доска «Рисуй и играй»', 0),
  (1072, 127, 'Настенная трасса «Шарикопад»', 'track-marble', 'TOY-1072', 12990.00, 0, 1, '<p>Настенная трасса «Шарикопад» — игрушка для детей от 4 лет.</p>', '<p>Настенная трасса «Шарикопад». Демонстрационный товар из категории «Трассы для шариков». Материал: Дерево. Рекомендуемый возраст: 4+. Предназначен для игр и развития навыков.</p><p>Характеристики, цены и остатки созданы для тестирования MiniMarket, не являются предложением реального магазина.</p>', 72, 'images/minimarket/sample-02.png', 'Настенная трасса «Шарикопад»', 'Настенная трасса «Шарикопад»', 'Настенная трасса «Шарикопад»: описание, характеристики, наличие и варианты исполнения в MiniMarket.', 'index,follow', 'Настенная трасса «Шарикопад»', 'Настенная трасса «Шарикопад»: описание, характеристики, наличие и варианты исполнения в MiniMarket.', 'images/minimarket/sample-02.png', 'Настенная трасса «Шарикопад»', 1),
  (1073, 127, 'Модуль трассы «Зигзаг»', 'track-zigzag', 'TOY-1073', 3790.00, 17, 1, '<p>Модуль трассы «Зигзаг» — игрушка для детей от 4 лет.</p>', '<p>Модуль трассы «Зигзаг». Демонстрационный товар из категории «Трассы для шариков». Материал: Дерево. Рекомендуемый возраст: 4+. Предназначен для игр и развития навыков.</p><p>Характеристики, цены и остатки созданы для тестирования MiniMarket, не являются предложением реального магазина.</p>', 73, 'images/minimarket/sample-03.png', 'Модуль трассы «Зигзаг»', 'Модуль трассы «Зигзаг»', 'Модуль трассы «Зигзаг»: описание, характеристики, наличие и варианты исполнения в MiniMarket.', 'index,follow', 'Модуль трассы «Зигзаг»', 'Модуль трассы «Зигзаг»: описание, характеристики, наличие и варианты исполнения в MiniMarket.', 'images/minimarket/sample-03.png', 'Модуль трассы «Зигзаг»', 0),
  (1074, 127, 'Модуль трассы «Водопад»', 'track-waterfall', 'TOY-1074', 4990.00, 9, 1, '<p>Модуль трассы «Водопад» — игрушка для детей от 4 лет.</p>', '<p>Модуль трассы «Водопад». Демонстрационный товар из категории «Трассы для шариков». Материал: Дерево. Рекомендуемый возраст: 4+. Предназначен для игр и развития навыков.</p><p>Характеристики, цены и остатки созданы для тестирования MiniMarket, не являются предложением реального магазина.</p>', 74, 'images/minimarket/sample-04.png', 'Модуль трассы «Водопад»', 'Модуль трассы «Водопад»', 'Модуль трассы «Водопад»: описание, характеристики, наличие и варианты исполнения в MiniMarket.', 'index,follow', 'Модуль трассы «Водопад»', 'Модуль трассы «Водопад»: описание, характеристики, наличие и варианты исполнения в MiniMarket.', 'images/minimarket/sample-04.png', 'Модуль трассы «Водопад»', 0),
  (1075, 127, 'Набор поворотов для шариков', 'track-turn', 'TOY-1075', 2590.00, 22, 1, '<p>Набор поворотов для шариков — игрушка для детей от 4 лет.</p>', '<p>Набор поворотов для шариков. Демонстрационный товар из категории «Трассы для шариков». Материал: Дерево. Рекомендуемый возраст: 4+. Предназначен для игр и развития навыков.</p><p>Характеристики, цены и остатки созданы для тестирования MiniMarket, не являются предложением реального магазина.</p>', 75, 'images/minimarket/sample-05.png', 'Набор поворотов для шариков', 'Набор поворотов для шариков', 'Набор поворотов для шариков: описание, характеристики, наличие и варианты исполнения в MiniMarket.', 'index,follow', 'Набор поворотов для шариков', 'Набор поворотов для шариков: описание, характеристики, наличие и варианты исполнения в MiniMarket.', 'images/minimarket/sample-05.png', 'Набор поворотов для шариков', 0) ON DUPLICATE KEY UPDATE `id`=`id`;
INSERT INTO `nnop3_minimarket_products`
  (`id`, `category_id`, `title`, `alias`, `sku`, `price`, `stock`, `state`, `introtext`, `description`, `sort_order`, `image`, `image_alt`, `meta_title`, `meta_description`, `meta_robots`, `og_title`, `og_description`, `og_image`, `og_image_alt`, `option_stock_mode`) VALUES
  (1076, 106, 'Магнитная спираль (прототип)', 'mag-spiral-prototype', 'TOY-1076', 5990.00, 0, 0, '<p>Магнитная спираль (прототип) — игрушка для детей от 6 лет.</p>', '<p>Магнитная спираль (прототип). Демонстрационный товар из категории «Магнитные конструкторы». Материал: Пластик. Рекомендуемый возраст: 6+. Предназначен для игр и развития навыков.</p><p>Характеристики, цены и остатки созданы для тестирования MiniMarket, не являются предложением реального магазина.</p>', 76, 'images/minimarket/sample-06.png', 'Магнитная спираль (прототип)', 'Магнитная спираль (прототип)', 'Магнитная спираль (прототип): описание, характеристики, наличие и варианты исполнения в MiniMarket.', 'index,follow', 'Магнитная спираль (прототип)', 'Магнитная спираль (прототип): описание, характеристики, наличие и варианты исполнения в MiniMarket.', 'images/minimarket/sample-06.png', 'Магнитная спираль (прототип)', 0),
  (1077, 127, 'Модуль трассы «Петля» (прототип)', 'track-loop-prototype', 'TOY-1077', 5890.00, 0, 0, '<p>Модуль трассы «Петля» (прототип) — игрушка для детей от 4 лет.</p>', '<p>Модуль трассы «Петля» (прототип). Демонстрационный товар из категории «Трассы для шариков». Материал: Дерево. Рекомендуемый возраст: 4+. Предназначен для игр и развития навыков.</p><p>Характеристики, цены и остатки созданы для тестирования MiniMarket, не являются предложением реального магазина.</p>', 77, 'images/minimarket/sample-07.png', 'Модуль трассы «Петля» (прототип)', 'Модуль трассы «Петля» (прототип)', 'Модуль трассы «Петля» (прототип): описание, характеристики, наличие и варианты исполнения в MiniMarket.', 'index,follow', 'Модуль трассы «Петля» (прототип)', 'Модуль трассы «Петля» (прототип): описание, характеристики, наличие и варианты исполнения в MiniMarket.', 'images/minimarket/sample-07.png', 'Модуль трассы «Петля» (прототип)', 0) ON DUPLICATE KEY UPDATE `id`=`id`;

INSERT INTO `nnop3_minimarket_product_images`
  (`id`, `product_id`, `path`, `alt`, `sort_order`) VALUES
  (10001, 1001, 'images/minimarket/sample-01.png', 'Плюшевый мишка «Тёма»', 0),
  (10002, 1002, 'images/minimarket/sample-02.png', 'Мишка «Полярник» белый', 0),
  (10003, 1003, 'images/minimarket/sample-03.png', 'Медвежонок «Бруно»', 0),
  (10004, 1004, 'images/minimarket/sample-04.png', 'Мишка «Медовый сон»', 0),
  (10005, 1005, 'images/minimarket/sample-05.png', 'Мишка «Морячок»', 0),
  (10006, 1006, 'images/minimarket/sample-06.png', 'Зайчик «Ушастик»', 0),
  (10007, 1007, 'images/minimarket/sample-07.png', 'Лисёнок «Рыжик»', 0),
  (10008, 1008, 'images/minimarket/sample-08.png', 'Котик «Мурчик»', 0),
  (10009, 1009, 'images/minimarket/sample-09.png', 'Ёжик «Шуршик»', 0),
  (10010, 1010, 'images/minimarket/sample-10.png', 'Панда «Соня»', 0),
  (10011, 1011, 'images/minimarket/sample-01.png', 'Конструктор «Город из кубиков»', 0),
  (10012, 1012, 'images/minimarket/sample-02.png', 'Конструктор «КосмоБлок»', 0),
  (10013, 1013, 'images/minimarket/sample-03.png', 'Конструктор «Средневековый замок»', 0),
  (10014, 1014, 'images/minimarket/sample-04.png', 'Конструктор «Паровозик»', 0),
  (10015, 1015, 'images/minimarket/sample-05.png', 'Конструктор «Динопарк»', 0),
  (10016, 1016, 'images/minimarket/sample-06.png', 'Магнитный конструктор «ГеоМир»', 0),
  (10017, 1017, 'images/minimarket/sample-07.png', 'Магнитная ферма', 0),
  (10018, 1018, 'images/minimarket/sample-08.png', 'Магнитная орбита', 0),
  (10019, 1019, 'images/minimarket/sample-09.png', 'Магнитная мозаика «Фигуры»', 0),
  (10020, 1020, 'images/minimarket/sample-10.png', 'Настольная игра «Лесная гонка»', 0),
  (10021, 1021, 'images/minimarket/sample-01.png', 'Игра «Кто быстрее?»', 0),
  (10022, 1022, 'images/minimarket/sample-02.png', 'Игра «Цветные истории»', 0),
  (10023, 1023, 'images/minimarket/sample-03.png', 'Игра «Путешествие по карте»', 0),
  (10024, 1024, 'images/minimarket/sample-04.png', 'Игра «Весёлые вопросы»', 0),
  (10025, 1025, 'images/minimarket/sample-05.png', 'Мемо «Найди пару»', 0),
  (10026, 1026, 'images/minimarket/sample-06.png', 'Набор «Счёт до 20»', 0),
  (10027, 1027, 'images/minimarket/sample-07.png', 'Игра «Буквенный экспресс»', 0),
  (10028, 1028, 'images/minimarket/sample-08.png', 'Обучающая игра «Учим часы»', 0),
  (10029, 1029, 'images/minimarket/sample-09.png', 'Сортер «Умный домик»', 0),
  (10030, 1030, 'images/minimarket/sample-10.png', 'Пирамидка «Цветные кольца»', 0),
  (10031, 1031, 'images/minimarket/sample-01.png', 'Кубик «Первые открытия»', 0),
  (10032, 1032, 'images/minimarket/sample-02.png', 'Деревянная радуга', 0),
  (10033, 1033, 'images/minimarket/sample-03.png', 'Детский барабан «Первый ритм»', 0),
  (10034, 1034, 'images/minimarket/sample-04.png', 'Ксилофон «Радуга»', 0),
  (10035, 1035, 'images/minimarket/sample-05.png', 'Набор погремушек «Мелодия»', 0),
  (10036, 1036, 'images/minimarket/sample-06.png', 'Детское пианино «Мини»', 0),
  (10037, 1037, 'images/minimarket/sample-07.png', 'Машинка «Сити»', 0),
  (10038, 1038, 'images/minimarket/sample-08.png', 'Гоночный болид «Турбо»', 0),
  (10039, 1039, 'images/minimarket/sample-09.png', 'Игрушечный автобус «Школьный»', 0),
  (10040, 1040, 'images/minimarket/sample-10.png', 'Внедорожник «Следопыт»', 0),
  (10041, 1041, 'images/minimarket/sample-01.png', 'Пожарная машина с лестницей', 0),
  (10042, 1042, 'images/minimarket/sample-02.png', 'Экскаватор «Стройка»', 0),
  (10043, 1043, 'images/minimarket/sample-03.png', 'Бетономешалка «Мастер»', 0),
  (10044, 1044, 'images/minimarket/sample-04.png', 'Подъёмный кран', 0),
  (10045, 1045, 'images/minimarket/sample-05.png', 'Альбом «Мои первые рисунки»', 0),
  (10046, 1046, 'images/minimarket/sample-06.png', 'Набор фломастеров «Яркий мир»', 0),
  (10047, 1047, 'images/minimarket/sample-07.png', 'Акварель «12 оттенков»', 0),
  (10048, 1048, 'images/minimarket/sample-08.png', 'Набор художника «Мастерская»', 0),
  (10049, 1049, 'images/minimarket/sample-09.png', 'Набор теста для лепки «Пластилинка»', 0),
  (10050, 1050, 'images/minimarket/sample-10.png', 'Кинетический песок «Замки»', 0),
  (10051, 1051, 'images/minimarket/sample-01.png', 'Мягкая масса для лепки «Облако»', 0),
  (10052, 1052, 'images/minimarket/sample-02.png', 'Формочки «Зверята»', 0),
  (10053, 1053, 'images/minimarket/sample-03.png', 'Кукла «Малышка Аня»', 0),
  (10054, 1054, 'images/minimarket/sample-04.png', 'Кукла «Настя»', 0),
  (10055, 1055, 'images/minimarket/sample-05.png', 'Кукла «Лиза»', 0),
  (10056, 1056, 'images/minimarket/sample-06.png', 'Пупс «Соня»', 0),
  (10057, 1057, 'images/minimarket/sample-07.png', 'Кукольный домик «Солнечный»', 0),
  (10058, 1058, 'images/minimarket/sample-08.png', 'Набор мебели для кукол', 0),
  (10059, 1059, 'images/minimarket/sample-09.png', 'Кукольная кухня «Маленький повар»', 0),
  (10060, 1060, 'images/minimarket/sample-10.png', 'Кукольная спальня', 0),
  (10061, 1061, 'images/minimarket/sample-01.png', 'Деревянный пазл «Африка»', 0),
  (10062, 1062, 'images/minimarket/sample-02.png', 'Алфавит из дерева', 0),
  (10063, 1063, 'images/minimarket/sample-03.png', 'Пазл «Карта мира»', 0),
  (10064, 1064, 'images/minimarket/sample-04.png', 'Деревянный пазл «Машинки»', 0),
  (10065, 1065, 'images/minimarket/sample-05.png', 'Пазл «Космос», 100 деталей', 0),
  (10066, 1066, 'images/minimarket/sample-06.png', 'Пазл «Океан», 48 деталей', 0),
  (10067, 1067, 'images/minimarket/sample-07.png', 'Пазл «Замок», 200 деталей', 0),
  (10068, 1068, 'images/minimarket/sample-08.png', 'Пазл «Котики», 24 детали', 0),
  (10069, 1069, 'images/minimarket/sample-09.png', 'Игровая магнитная стена «Мир»', 0),
  (10070, 1070, 'images/minimarket/sample-10.png', 'Магнитный модуль «Шестерёнки»', 0),
  (10071, 1071, 'images/minimarket/sample-01.png', 'Магнитная доска «Рисуй и играй»', 0),
  (10072, 1072, 'images/minimarket/sample-02.png', 'Настенная трасса «Шарикопад»', 0),
  (10073, 1073, 'images/minimarket/sample-03.png', 'Модуль трассы «Зигзаг»', 0),
  (10074, 1074, 'images/minimarket/sample-04.png', 'Модуль трассы «Водопад»', 0),
  (10075, 1075, 'images/minimarket/sample-05.png', 'Набор поворотов для шариков', 0) ON DUPLICATE KEY UPDATE `id`=`id`;
INSERT INTO `nnop3_minimarket_product_images`
  (`id`, `product_id`, `path`, `alt`, `sort_order`) VALUES
  (10076, 1076, 'images/minimarket/sample-06.png', 'Магнитная спираль (прототип)', 0),
  (10077, 1077, 'images/minimarket/sample-07.png', 'Модуль трассы «Петля» (прототип)', 0) ON DUPLICATE KEY UPDATE `id`=`id`;

INSERT INTO `nnop3_minimarket_product_attribute_scalars`
 (`product_id`, `attribute_id`, `value_text`, `value_number`, `value_boolean`) VALUES
 (1001, 205, NULL, 0, NULL),
 (1001, 206, NULL, 1, NULL),
 (1001, 207, NULL, 350, NULL),
 (1001, 208, NULL, 35, NULL),
 (1001, 209, NULL, NULL, 0),
 (1001, 210, NULL, NULL, 0),
 (1002, 205, NULL, 0, NULL),
 (1002, 206, NULL, 1, NULL),
 (1002, 207, NULL, 420, NULL),
 (1002, 208, NULL, 40, NULL),
 (1002, 209, NULL, NULL, 0),
 (1002, 210, NULL, NULL, 1),
 (1003, 205, NULL, 0, NULL),
 (1003, 206, NULL, 1, NULL),
 (1003, 207, NULL, 220, NULL),
 (1003, 208, NULL, 25, NULL),
 (1003, 209, NULL, NULL, 0),
 (1003, 210, NULL, NULL, 0),
 (1004, 205, NULL, 0, NULL),
 (1004, 206, NULL, 1, NULL),
 (1004, 207, NULL, 480, NULL),
 (1004, 208, NULL, 45, NULL),
 (1004, 209, NULL, NULL, 0),
 (1004, 210, NULL, NULL, 1),
 (1005, 205, NULL, 0, NULL),
 (1005, 206, NULL, 1, NULL),
 (1005, 207, NULL, 280, NULL),
 (1005, 208, NULL, 30, NULL),
 (1005, 209, NULL, NULL, 0),
 (1005, 210, NULL, NULL, 1),
 (1006, 205, NULL, 0, NULL),
 (1006, 206, NULL, 1, NULL),
 (1006, 207, NULL, 180, NULL),
 (1006, 208, NULL, 30, NULL),
 (1006, 209, NULL, NULL, 0),
 (1006, 210, NULL, NULL, 1),
 (1007, 205, NULL, 0, NULL),
 (1007, 206, NULL, 1, NULL),
 (1007, 207, NULL, 200, NULL),
 (1007, 208, NULL, 28, NULL),
 (1007, 209, NULL, NULL, 0),
 (1007, 210, NULL, NULL, 0),
 (1008, 205, NULL, 0, NULL),
 (1008, 206, NULL, 1, NULL),
 (1008, 207, NULL, 190, NULL) ON DUPLICATE KEY UPDATE `product_id`=`product_id`;
INSERT INTO `nnop3_minimarket_product_attribute_scalars`
 (`product_id`, `attribute_id`, `value_text`, `value_number`, `value_boolean`) VALUES
 (1008, 208, NULL, 25, NULL),
 (1008, 209, NULL, NULL, 0),
 (1008, 210, NULL, NULL, 0),
 (1009, 205, NULL, 0, NULL),
 (1009, 206, NULL, 1, NULL),
 (1009, 207, NULL, 220, NULL),
 (1009, 208, NULL, 23, NULL),
 (1009, 209, NULL, NULL, 0),
 (1009, 210, NULL, NULL, 0),
 (1010, 205, NULL, 0, NULL),
 (1010, 206, NULL, 1, NULL),
 (1010, 207, NULL, 310, NULL),
 (1010, 208, NULL, 32, NULL),
 (1010, 209, NULL, NULL, 0),
 (1010, 210, NULL, NULL, 1),
 (1011, 205, NULL, 80, NULL),
 (1011, 206, NULL, 1, NULL),
 (1011, 207, NULL, 650, NULL),
 (1011, 208, NULL, 32, NULL),
 (1011, 209, NULL, NULL, 0),
 (1011, 210, NULL, NULL, 1),
 (1012, 205, NULL, 60, NULL),
 (1012, 206, NULL, 1, NULL),
 (1012, 207, NULL, 800, NULL),
 (1012, 208, NULL, 35, NULL),
 (1012, 209, NULL, NULL, 0),
 (1012, 210, NULL, NULL, 1),
 (1013, 205, NULL, 230, NULL),
 (1013, 206, NULL, 1, NULL),
 (1013, 207, NULL, 1380, NULL),
 (1013, 208, NULL, 42, NULL),
 (1013, 209, NULL, NULL, 0),
 (1013, 210, NULL, NULL, 1),
 (1014, 205, NULL, 90, NULL),
 (1014, 206, NULL, 1, NULL),
 (1014, 207, NULL, 730, NULL),
 (1014, 208, NULL, 30, NULL),
 (1014, 209, NULL, NULL, 0),
 (1014, 210, NULL, NULL, 1),
 (1015, 205, NULL, 150, NULL),
 (1015, 206, NULL, 1, NULL),
 (1015, 207, NULL, 920, NULL),
 (1015, 208, NULL, 35, NULL),
 (1015, 209, NULL, NULL, 0),
 (1015, 210, NULL, NULL, 1) ON DUPLICATE KEY UPDATE `product_id`=`product_id`;
INSERT INTO `nnop3_minimarket_product_attribute_scalars`
 (`product_id`, `attribute_id`, `value_text`, `value_number`, `value_boolean`) VALUES
 (1016, 205, NULL, 32, NULL),
 (1016, 206, NULL, 1, NULL),
 (1016, 207, NULL, 740, NULL),
 (1016, 208, NULL, 28, NULL),
 (1016, 209, NULL, NULL, 0),
 (1016, 210, NULL, NULL, 1),
 (1017, 205, NULL, 48, NULL),
 (1017, 206, NULL, 1, NULL),
 (1017, 207, NULL, 880, NULL),
 (1017, 208, NULL, 32, NULL),
 (1017, 209, NULL, NULL, 0),
 (1017, 210, NULL, NULL, 1),
 (1018, 205, NULL, 60, NULL),
 (1018, 206, NULL, 1, NULL),
 (1018, 207, NULL, 1040, NULL),
 (1018, 208, NULL, 36, NULL),
 (1018, 209, NULL, NULL, 0),
 (1018, 210, NULL, NULL, 1),
 (1019, 205, NULL, 42, NULL),
 (1019, 206, NULL, 1, NULL),
 (1019, 207, NULL, 430, NULL),
 (1019, 208, NULL, 25, NULL),
 (1019, 209, NULL, NULL, 0),
 (1019, 210, NULL, NULL, 0),
 (1020, 205, NULL, 60, NULL),
 (1020, 206, NULL, 4, NULL),
 (1020, 207, NULL, 460, NULL),
 (1020, 208, NULL, 28, NULL),
 (1020, 209, NULL, NULL, 0),
 (1020, 210, NULL, NULL, 0),
 (1021, 205, NULL, 80, NULL),
 (1021, 206, NULL, 6, NULL),
 (1021, 207, NULL, 510, NULL),
 (1021, 208, NULL, 30, NULL),
 (1021, 209, NULL, NULL, 0),
 (1021, 210, NULL, NULL, 1),
 (1022, 205, NULL, 54, NULL),
 (1022, 206, NULL, 4, NULL),
 (1022, 207, NULL, 390, NULL),
 (1022, 208, NULL, 24, NULL),
 (1022, 209, NULL, NULL, 0),
 (1022, 210, NULL, NULL, 0),
 (1023, 205, NULL, 120, NULL),
 (1023, 206, NULL, 5, NULL),
 (1023, 207, NULL, 670, NULL) ON DUPLICATE KEY UPDATE `product_id`=`product_id`;
INSERT INTO `nnop3_minimarket_product_attribute_scalars`
 (`product_id`, `attribute_id`, `value_text`, `value_number`, `value_boolean`) VALUES
 (1023, 208, NULL, 34, NULL),
 (1023, 209, NULL, NULL, 0),
 (1023, 210, NULL, NULL, 1),
 (1024, 205, NULL, 100, NULL),
 (1024, 206, NULL, 8, NULL),
 (1024, 207, NULL, 300, NULL),
 (1024, 208, NULL, 18, NULL),
 (1024, 209, NULL, NULL, 0),
 (1024, 210, NULL, NULL, 0),
 (1025, 205, NULL, 48, NULL),
 (1025, 206, NULL, 2, NULL),
 (1025, 207, NULL, 230, NULL),
 (1025, 208, NULL, 19, NULL),
 (1025, 209, NULL, NULL, 0),
 (1025, 210, NULL, NULL, 0),
 (1026, 205, NULL, 45, NULL),
 (1026, 206, NULL, 1, NULL),
 (1026, 207, NULL, 390, NULL),
 (1026, 208, NULL, 25, NULL),
 (1026, 209, NULL, NULL, 0),
 (1026, 210, NULL, NULL, 0),
 (1027, 205, NULL, 80, NULL),
 (1027, 206, NULL, 4, NULL),
 (1027, 207, NULL, 450, NULL),
 (1027, 208, NULL, 28, NULL),
 (1027, 209, NULL, NULL, 0),
 (1027, 210, NULL, NULL, 0),
 (1028, 205, NULL, 24, NULL),
 (1028, 206, NULL, 1, NULL),
 (1028, 207, NULL, 380, NULL),
 (1028, 208, NULL, 24, NULL),
 (1028, 209, NULL, NULL, 0),
 (1028, 210, NULL, NULL, 0),
 (1029, 205, NULL, 12, NULL),
 (1029, 206, NULL, 1, NULL),
 (1029, 207, NULL, 900, NULL),
 (1029, 208, NULL, 25, NULL),
 (1029, 209, NULL, NULL, 0),
 (1029, 210, NULL, NULL, 1),
 (1030, 205, NULL, 7, NULL),
 (1030, 206, NULL, 1, NULL),
 (1030, 207, NULL, 340, NULL),
 (1030, 208, NULL, 18, NULL),
 (1030, 209, NULL, NULL, 0),
 (1030, 210, NULL, NULL, 0) ON DUPLICATE KEY UPDATE `product_id`=`product_id`;
INSERT INTO `nnop3_minimarket_product_attribute_scalars`
 (`product_id`, `attribute_id`, `value_text`, `value_number`, `value_boolean`) VALUES
 (1031, 205, NULL, 8, NULL),
 (1031, 206, NULL, 1, NULL),
 (1031, 207, NULL, 1250, NULL),
 (1031, 208, NULL, 24, NULL),
 (1031, 209, NULL, NULL, 0),
 (1031, 210, NULL, NULL, 1),
 (1032, 205, NULL, 10, NULL),
 (1032, 206, NULL, 1, NULL),
 (1032, 207, NULL, 970, NULL),
 (1032, 208, NULL, 32, NULL),
 (1032, 209, NULL, NULL, 0),
 (1032, 210, NULL, NULL, 1),
 (1033, 205, NULL, 3, NULL),
 (1033, 206, NULL, 1, NULL),
 (1033, 207, NULL, 430, NULL),
 (1033, 208, NULL, 20, NULL),
 (1033, 209, NULL, NULL, 0),
 (1033, 210, NULL, NULL, 0),
 (1034, 205, NULL, 10, NULL),
 (1034, 206, NULL, 1, NULL),
 (1034, 207, NULL, 570, NULL),
 (1034, 208, NULL, 29, NULL),
 (1034, 209, NULL, NULL, 0),
 (1034, 210, NULL, NULL, 1),
 (1035, 205, NULL, 4, NULL),
 (1035, 206, NULL, 1, NULL),
 (1035, 207, NULL, 170, NULL),
 (1035, 208, NULL, 21, NULL),
 (1035, 209, NULL, NULL, 0),
 (1035, 210, NULL, NULL, 0),
 (1036, 205, NULL, 1, NULL),
 (1036, 206, NULL, 1, NULL),
 (1036, 207, NULL, 900, NULL),
 (1036, 208, NULL, 38, NULL),
 (1036, 209, NULL, NULL, 1),
 (1036, 210, NULL, NULL, 1),
 (1037, 205, NULL, 1, NULL),
 (1037, 206, NULL, 1, NULL),
 (1037, 207, NULL, 110, NULL),
 (1037, 208, NULL, 12, NULL),
 (1037, 209, NULL, NULL, 0),
 (1037, 210, NULL, NULL, 0),
 (1038, 205, NULL, 1, NULL),
 (1038, 206, NULL, 1, NULL),
 (1038, 207, NULL, 190, NULL) ON DUPLICATE KEY UPDATE `product_id`=`product_id`;
INSERT INTO `nnop3_minimarket_product_attribute_scalars`
 (`product_id`, `attribute_id`, `value_text`, `value_number`, `value_boolean`) VALUES
 (1038, 208, NULL, 19, NULL),
 (1038, 209, NULL, NULL, 0),
 (1038, 210, NULL, NULL, 0),
 (1039, 205, NULL, 1, NULL),
 (1039, 206, NULL, 1, NULL),
 (1039, 207, NULL, 310, NULL),
 (1039, 208, NULL, 23, NULL),
 (1039, 209, NULL, NULL, 0),
 (1039, 210, NULL, NULL, 0),
 (1040, 205, NULL, 1, NULL),
 (1040, 206, NULL, 1, NULL),
 (1040, 207, NULL, 370, NULL),
 (1040, 208, NULL, 26, NULL),
 (1040, 209, NULL, NULL, 0),
 (1040, 210, NULL, NULL, 1),
 (1041, 205, NULL, 1, NULL),
 (1041, 206, NULL, 1, NULL),
 (1041, 207, NULL, 620, NULL),
 (1041, 208, NULL, 37, NULL),
 (1041, 209, NULL, NULL, 0),
 (1041, 210, NULL, NULL, 1),
 (1042, 205, NULL, 1, NULL),
 (1042, 206, NULL, 1, NULL),
 (1042, 207, NULL, 550, NULL),
 (1042, 208, NULL, 35, NULL),
 (1042, 209, NULL, NULL, 0),
 (1042, 210, NULL, NULL, 1),
 (1043, 205, NULL, 1, NULL),
 (1043, 206, NULL, 1, NULL),
 (1043, 207, NULL, 500, NULL),
 (1043, 208, NULL, 30, NULL),
 (1043, 209, NULL, NULL, 0),
 (1043, 210, NULL, NULL, 1),
 (1044, 205, NULL, 1, NULL),
 (1044, 206, NULL, 1, NULL),
 (1044, 207, NULL, 720, NULL),
 (1044, 208, NULL, 48, NULL),
 (1044, 209, NULL, NULL, 0),
 (1044, 210, NULL, NULL, 1),
 (1045, 205, NULL, 40, NULL),
 (1045, 206, NULL, 1, NULL),
 (1045, 207, NULL, 170, NULL),
 (1045, 208, NULL, 30, NULL),
 (1045, 209, NULL, NULL, 0),
 (1045, 210, NULL, NULL, 0) ON DUPLICATE KEY UPDATE `product_id`=`product_id`;
INSERT INTO `nnop3_minimarket_product_attribute_scalars`
 (`product_id`, `attribute_id`, `value_text`, `value_number`, `value_boolean`) VALUES
 (1046, 205, NULL, 24, NULL),
 (1046, 206, NULL, 1, NULL),
 (1046, 207, NULL, 260, NULL),
 (1046, 208, NULL, 24, NULL),
 (1046, 209, NULL, NULL, 0),
 (1046, 210, NULL, NULL, 0),
 (1047, 205, NULL, 12, NULL),
 (1047, 206, NULL, 1, NULL),
 (1047, 207, NULL, 240, NULL),
 (1047, 208, NULL, 18, NULL),
 (1047, 209, NULL, NULL, 0),
 (1047, 210, NULL, NULL, 0),
 (1048, 205, NULL, 72, NULL),
 (1048, 206, NULL, 1, NULL),
 (1048, 207, NULL, 960, NULL),
 (1048, 208, NULL, 36, NULL),
 (1048, 209, NULL, NULL, 0),
 (1048, 210, NULL, NULL, 1),
 (1049, 205, NULL, 12, NULL),
 (1049, 206, NULL, 1, NULL),
 (1049, 207, NULL, 750, NULL),
 (1049, 208, NULL, 28, NULL),
 (1049, 209, NULL, NULL, 0),
 (1049, 210, NULL, NULL, 0),
 (1050, 205, NULL, 8, NULL),
 (1050, 206, NULL, 1, NULL),
 (1050, 207, NULL, 1500, NULL),
 (1050, 208, NULL, 31, NULL),
 (1050, 209, NULL, NULL, 0),
 (1050, 210, NULL, NULL, 0),
 (1051, 205, NULL, 8, NULL),
 (1051, 206, NULL, 1, NULL),
 (1051, 207, NULL, 650, NULL),
 (1051, 208, NULL, 24, NULL),
 (1051, 209, NULL, NULL, 0),
 (1051, 210, NULL, NULL, 0),
 (1052, 205, NULL, 10, NULL),
 (1052, 206, NULL, 1, NULL),
 (1052, 207, NULL, 180, NULL),
 (1052, 208, NULL, 17, NULL),
 (1052, 209, NULL, NULL, 0),
 (1052, 210, NULL, NULL, 0),
 (1053, 205, NULL, 1, NULL),
 (1053, 206, NULL, 1, NULL),
 (1053, 207, NULL, 520, NULL) ON DUPLICATE KEY UPDATE `product_id`=`product_id`;
INSERT INTO `nnop3_minimarket_product_attribute_scalars`
 (`product_id`, `attribute_id`, `value_text`, `value_number`, `value_boolean`) VALUES
 (1053, 208, NULL, 34, NULL),
 (1053, 209, NULL, NULL, 0),
 (1053, 210, NULL, NULL, 1),
 (1054, 205, NULL, 1, NULL),
 (1054, 206, NULL, 1, NULL),
 (1054, 207, NULL, 570, NULL),
 (1054, 208, NULL, 40, NULL),
 (1054, 209, NULL, NULL, 0),
 (1054, 210, NULL, NULL, 1),
 (1055, 205, NULL, 1, NULL),
 (1055, 206, NULL, 1, NULL),
 (1055, 207, NULL, 560, NULL),
 (1055, 208, NULL, 38, NULL),
 (1055, 209, NULL, NULL, 0),
 (1055, 210, NULL, NULL, 1),
 (1056, 205, NULL, 1, NULL),
 (1056, 206, NULL, 1, NULL),
 (1056, 207, NULL, 480, NULL),
 (1056, 208, NULL, 30, NULL),
 (1056, 209, NULL, NULL, 0),
 (1056, 210, NULL, NULL, 1),
 (1057, 205, NULL, 75, NULL),
 (1057, 206, NULL, 1, NULL),
 (1057, 207, NULL, 5200, NULL),
 (1057, 208, NULL, 68, NULL),
 (1057, 209, NULL, NULL, 0),
 (1057, 210, NULL, NULL, 1),
 (1058, 205, NULL, 12, NULL),
 (1058, 206, NULL, 1, NULL),
 (1058, 207, NULL, 850, NULL),
 (1058, 208, NULL, 31, NULL),
 (1058, 209, NULL, NULL, 0),
 (1058, 210, NULL, NULL, 1),
 (1059, 205, NULL, 18, NULL),
 (1059, 206, NULL, 1, NULL),
 (1059, 207, NULL, 1730, NULL),
 (1059, 208, NULL, 40, NULL),
 (1059, 209, NULL, NULL, 0),
 (1059, 210, NULL, NULL, 1),
 (1060, 205, NULL, 9, NULL),
 (1060, 206, NULL, 1, NULL),
 (1060, 207, NULL, 790, NULL),
 (1060, 208, NULL, 28, NULL),
 (1060, 209, NULL, NULL, 0),
 (1060, 210, NULL, NULL, 0) ON DUPLICATE KEY UPDATE `product_id`=`product_id`;
INSERT INTO `nnop3_minimarket_product_attribute_scalars`
 (`product_id`, `attribute_id`, `value_text`, `value_number`, `value_boolean`) VALUES
 (1061, 205, NULL, 24, NULL),
 (1061, 206, NULL, 1, NULL),
 (1061, 207, NULL, 500, NULL),
 (1061, 208, NULL, 28, NULL),
 (1061, 209, NULL, NULL, 0),
 (1061, 210, NULL, NULL, 0),
 (1062, 205, NULL, 33, NULL),
 (1062, 206, NULL, 1, NULL),
 (1062, 207, NULL, 670, NULL),
 (1062, 208, NULL, 35, NULL),
 (1062, 209, NULL, NULL, 0),
 (1062, 210, NULL, NULL, 1),
 (1063, 205, NULL, 58, NULL),
 (1063, 206, NULL, 1, NULL),
 (1063, 207, NULL, 910, NULL),
 (1063, 208, NULL, 43, NULL),
 (1063, 209, NULL, NULL, 0),
 (1063, 210, NULL, NULL, 1),
 (1064, 205, NULL, 8, NULL),
 (1064, 206, NULL, 1, NULL),
 (1064, 207, NULL, 330, NULL),
 (1064, 208, NULL, 22, NULL),
 (1064, 209, NULL, NULL, 0),
 (1064, 210, NULL, NULL, 0),
 (1065, 205, NULL, 100, NULL),
 (1065, 206, NULL, 1, NULL),
 (1065, 207, NULL, 250, NULL),
 (1065, 208, NULL, 29, NULL),
 (1065, 209, NULL, NULL, 0),
 (1065, 210, NULL, NULL, 0),
 (1066, 205, NULL, 48, NULL),
 (1066, 206, NULL, 1, NULL),
 (1066, 207, NULL, 210, NULL),
 (1066, 208, NULL, 27, NULL),
 (1066, 209, NULL, NULL, 0),
 (1066, 210, NULL, NULL, 0),
 (1067, 205, NULL, 200, NULL),
 (1067, 206, NULL, 1, NULL),
 (1067, 207, NULL, 340, NULL),
 (1067, 208, NULL, 38, NULL),
 (1067, 209, NULL, NULL, 0),
 (1067, 210, NULL, NULL, 0),
 (1068, 205, NULL, 24, NULL),
 (1068, 206, NULL, 1, NULL),
 (1068, 207, NULL, 140, NULL) ON DUPLICATE KEY UPDATE `product_id`=`product_id`;
INSERT INTO `nnop3_minimarket_product_attribute_scalars`
 (`product_id`, `attribute_id`, `value_text`, `value_number`, `value_boolean`) VALUES
 (1068, 208, NULL, 22, NULL),
 (1068, 209, NULL, NULL, 0),
 (1068, 210, NULL, NULL, 0),
 (1069, 205, NULL, 12, NULL),
 (1069, 206, NULL, 1, NULL),
 (1069, 207, NULL, 7500, NULL),
 (1069, 208, NULL, 90, NULL),
 (1069, 209, NULL, NULL, 0),
 (1069, 210, NULL, NULL, 1),
 (1070, 205, NULL, 14, NULL),
 (1070, 206, NULL, 1, NULL),
 (1070, 207, NULL, 1250, NULL),
 (1070, 208, NULL, 38, NULL),
 (1070, 209, NULL, NULL, 0),
 (1070, 210, NULL, NULL, 1),
 (1071, 205, NULL, 7, NULL),
 (1071, 206, NULL, 1, NULL),
 (1071, 207, NULL, 2800, NULL),
 (1071, 208, NULL, 60, NULL),
 (1071, 209, NULL, NULL, 0),
 (1071, 210, NULL, NULL, 1),
 (1072, 205, NULL, 20, NULL),
 (1072, 206, NULL, 1, NULL),
 (1072, 207, NULL, 5300, NULL),
 (1072, 208, NULL, 75, NULL),
 (1072, 209, NULL, NULL, 0),
 (1072, 210, NULL, NULL, 1),
 (1073, 205, NULL, 4, NULL),
 (1073, 206, NULL, 1, NULL),
 (1073, 207, NULL, 950, NULL),
 (1073, 208, NULL, 36, NULL),
 (1073, 209, NULL, NULL, 0),
 (1073, 210, NULL, NULL, 1),
 (1074, 205, NULL, 6, NULL),
 (1074, 206, NULL, 1, NULL),
 (1074, 207, NULL, 1420, NULL),
 (1074, 208, NULL, 42, NULL),
 (1074, 209, NULL, NULL, 0),
 (1074, 210, NULL, NULL, 1),
 (1075, 205, NULL, 8, NULL),
 (1075, 206, NULL, 1, NULL),
 (1075, 207, NULL, 670, NULL),
 (1075, 208, NULL, 28, NULL),
 (1075, 209, NULL, NULL, 0),
 (1075, 210, NULL, NULL, 1) ON DUPLICATE KEY UPDATE `product_id`=`product_id`;
INSERT INTO `nnop3_minimarket_product_attribute_scalars`
 (`product_id`, `attribute_id`, `value_text`, `value_number`, `value_boolean`) VALUES
 (1076, 205, NULL, 80, NULL),
 (1076, 206, NULL, 1, NULL),
 (1076, 207, NULL, 1040, NULL),
 (1076, 208, NULL, 36, NULL),
 (1076, 209, NULL, NULL, 0),
 (1076, 210, NULL, NULL, 0),
 (1077, 205, NULL, 5, NULL),
 (1077, 206, NULL, 1, NULL),
 (1077, 207, NULL, 970, NULL),
 (1077, 208, NULL, 37, NULL),
 (1077, 209, NULL, NULL, 0),
 (1077, 210, NULL, NULL, 0) ON DUPLICATE KEY UPDATE `product_id`=`product_id`;




INSERT INTO `nnop3_minimarket_product_attribute_options`
  (`product_id`, `attribute_id`, `value_id`) VALUES
  (1001, 203, 301),
  (1002, 203, 302),
  (1003, 203, 303),
  (1004, 203, 301),
  (1005, 203, 305),
  (1006, 203, 304),
  (1007, 203, 307),
  (1008, 203, 308),
  (1009, 203, 303),
  (1010, 203, 310),
  (1011, 203, 313),
  (1012, 203, 305),
  (1013, 203, 308),
  (1014, 203, 306),
  (1015, 203, 309),
  (1016, 203, 313),
  (1017, 203, 309),
  (1018, 203, 305),
  (1019, 203, 313),
  (1020, 203, 309),
  (1021, 203, 313),
  (1022, 203, 313),
  (1023, 203, 305),
  (1024, 203, 311),
  (1025, 203, 313),
  (1026, 203, 313),
  (1027, 203, 313),
  (1028, 203, 301),
  (1029, 203, 313),
  (1030, 203, 313),
  (1031, 203, 301),
  (1032, 203, 313),
  (1033, 203, 306),
  (1034, 203, 313),
  (1035, 203, 313),
  (1036, 203, 305),
  (1037, 203, 306),
  (1038, 203, 305),
  (1039, 203, 311),
  (1040, 203, 309),
  (1041, 203, 306),
  (1042, 203, 311),
  (1043, 203, 307),
  (1044, 203, 311),
  (1045, 203, 313),
  (1046, 203, 313),
  (1047, 203, 313),
  (1048, 203, 313),
  (1049, 203, 313),
  (1050, 203, 301),
  (1051, 203, 313),
  (1052, 203, 313),
  (1053, 203, 304),
  (1054, 203, 304),
  (1055, 203, 312),
  (1056, 203, 301),
  (1057, 203, 304),
  (1058, 203, 301),
  (1059, 203, 302),
  (1060, 203, 304),
  (1061, 203, 313),
  (1062, 203, 313),
  (1063, 203, 313),
  (1064, 203, 313),
  (1065, 203, 305),
  (1066, 203, 305),
  (1067, 203, 313),
  (1068, 203, 304),
  (1069, 203, 301),
  (1070, 203, 313),
  (1071, 203, 302),
  (1072, 203, 301),
  (1073, 203, 301),
  (1074, 203, 301),
  (1075, 203, 301) ON DUPLICATE KEY UPDATE `product_id`=`product_id`;
INSERT INTO `nnop3_minimarket_product_attribute_options`
  (`product_id`, `attribute_id`, `value_id`) VALUES
  (1076, 203, 312),
  (1077, 203, 301) ON DUPLICATE KEY UPDATE `product_id`=`product_id`;

-- Resolve new choices by source label, never by assumed numeric IDs.
INSERT INTO nnop3_minimarket_product_attribute_options (product_id,attribute_id,value_id)
SELECT t.pid,t.aid,MIN(v.id)
FROM JSON_TABLE('[[1001,201,"3+"],[1001,202,"Плюш"],[1001,211,"Начальная"],[1001,212,"Сюжетная"],[1002,201,"3+"],[1002,202,"Плюш"],[1002,211,"Начальная"],[1002,212,"Сюжетная"],[1003,201,"3+"],[1003,202,"Плюш"],[1003,211,"Начальная"],[1003,212,"Сюжетная"],[1004,201,"3+"],[1004,202,"Плюш"],[1004,211,"Начальная"],[1004,212,"Сюжетная"],[1005,201,"3+"],[1005,202,"Плюш"],[1005,211,"Начальная"],[1005,212,"Сюжетная"],[1006,201,"3+"],[1006,202,"Плюш"],[1006,211,"Начальная"],[1006,212,"Сюжетная"],[1007,201,"3+"],[1007,202,"Плюш"],[1007,211,"Начальная"],[1007,212,"Сюжетная"],[1008,201,"3+"],[1008,202,"Плюш"],[1008,211,"Начальная"],[1008,212,"Сюжетная"],[1009,201,"3+"],[1009,202,"Плюш"],[1009,211,"Начальная"],[1009,212,"Сюжетная"],[1010,201,"3+"],[1010,202,"Плюш"],[1010,211,"Начальная"],[1010,212,"Сюжетная"],[1011,201,"4+"],[1011,202,"Пластик"],[1011,211,"Средняя"],[1011,212,"Конструирование"],[1012,201,"6+"],[1012,202,"Пластик"],[1012,211,"Продвинутая"],[1012,212,"Конструирование"],[1013,201,"6+"],[1013,202,"Пластик"],[1013,211,"Продвинутая"],[1013,212,"Конструирование"],[1014,201,"4+"],[1014,202,"Пластик"],[1014,211,"Средняя"],[1014,212,"Конструирование"],[1015,201,"6+"],[1015,202,"Пластик"],[1015,211,"Продвинутая"],[1015,212,"Конструирование"],[1016,201,"5+"],[1016,202,"Пластик"],[1016,211,"Средняя"],[1016,212,"Конструирование"],[1017,201,"5+"],[1017,202,"Пластик"],[1017,211,"Средняя"],[1017,212,"Конструирование"],[1018,201,"6+"],[1018,202,"Пластик"],[1018,211,"Продвинутая"],[1018,212,"Конструирование"],[1019,201,"4+"],[1019,202,"Пластик"],[1019,211,"Средняя"],[1019,212,"Конструирование"],[1020,201,"5+"],[1020,202,"Картон"],[1020,211,"Средняя"],[1020,212,"Соревновательная"],[1021,201,"6+"],[1021,202,"Картон"],[1021,211,"Продвинутая"],[1021,212,"Соревновательная"],[1022,201,"4+"],[1022,202,"Картон"],[1022,211,"Средняя"],[1022,212,"Соревновательная"],[1023,201,"7+"],[1023,202,"Картон"],[1023,211,"Продвинутая"],[1023,212,"Соревновательная"],[1024,201,"5+"],[1024,202,"Картон"],[1024,211,"Средняя"],[1024,212,"Соревновательная"],[1025,201,"3+"],[1025,202,"Картон"],[1025,211,"Начальная"],[1025,212,"Обучающая"],[1026,201,"5+"],[1026,202,"Дерево"],[1026,211,"Средняя"],[1026,212,"Обучающая"],[1027,201,"5+"],[1027,202,"Картон"],[1027,211,"Средняя"],[1027,212,"Обучающая"],[1028,201,"5+"],[1028,202,"Дерево"],[1028,211,"Средняя"],[1028,212,"Обучающая"],[1029,201,"1+"],[1029,202,"Дерево"],[1029,211,"Начальная"],[1029,212,"Обучающая"],[1030,201,"1+"],[1030,202,"Дерево"],[1030,211,"Начальная"],[1030,212,"Обучающая"],[1031,201,"1+"],[1031,202,"Дерево"],[1031,211,"Начальная"],[1031,212,"Обучающая"],[1032,201,"1+"],[1032,202,"Дерево"],[1032,211,"Начальная"],[1032,212,"Обучающая"],[1033,201,"2+"],[1033,202,"Дерево"],[1033,211,"Начальная"],[1033,212,"Сюжетная"],[1034,201,"2+"],[1034,202,"Дерево"],[1034,211,"Начальная"],[1034,212,"Сюжетная"],[1035,201,"1+"],[1035,202,"Пластик"],[1035,211,"Начальная"],[1035,212,"Сюжетная"],[1036,201,"3+"],[1036,202,"Пластик"],[1036,211,"Начальная"],[1036,212,"Сюжетная"],[1037,201,"3+"],[1037,202,"Металл"],[1037,211,"Начальная"],[1037,212,"Сюжетная"],[1038,201,"4+"],[1038,202,"Металл"],[1038,211,"Средняя"],[1038,212,"Сюжетная"],[1039,201,"3+"],[1039,202,"Пластик"],[1039,211,"Начальная"],[1039,212,"Сюжетная"],[1040,201,"4+"],[1040,202,"Металл"],[1040,211,"Средняя"],[1040,212,"Сюжетная"],[1041,201,"3+"],[1041,202,"Пластик"],[1041,211,"Начальная"],[1041,212,"Сюжетная"],[1042,201,"3+"],[1042,202,"Пластик"],[1042,211,"Начальная"],[1042,212,"Сюжетная"],[1043,201,"3+"],[1043,202,"Пластик"],[1043,211,"Начальная"],[1043,212,"Сюжетная"],[1044,201,"4+"],[1044,202,"Пластик"],[1044,211,"Средняя"],[1044,212,"Сюжетная"],[1045,201,"3+"],[1045,202,"Бумага"],[1045,211,"Начальная"],[1045,212,"Творческая"],[1046,201,"3+"],[1046,202,"Пластик"],[1046,211,"Начальная"],[1046,212,"Творческая"],[1047,201,"4+"],[1047,202,"Краски"],[1047,211,"Средняя"],[1047,212,"Творческая"],[1048,201,"5+"],[1048,202,"Смешанный"],[1048,211,"Средняя"],[1048,212,"Творческая"],[1049,201,"3+"],[1049,202,"Тесто"],[1049,211,"Начальная"],[1049,212,"Творческая"],[1050,201,"3+"],[1050,202,"Песок"],[1050,211,"Начальная"],[1050,212,"Творческая"],[1051,201,"3+"],[1051,202,"Масса для лепки"],[1051,211,"Начальная"],[1051,212,"Творческая"],[1052,201,"3+"],[1052,202,"Пластик"],[1052,211,"Начальная"],[1052,212,"Творческая"],[1053,201,"3+"],[1053,202,"Пластик"],[1053,211,"Начальная"],[1053,212,"Сюжетная"],[1054,201,"3+"],[1054,202,"Пластик"],[1054,211,"Начальная"],[1054,212,"Сюжетная"],[1055,201,"3+"],[1055,202,"Пластик"],[1055,211,"Начальная"],[1055,212,"Сюжетная"],[1056,201,"3+"],[1056,202,"Пластик"],[1056,211,"Начальная"],[1056,212,"Сюжетная"],[1057,201,"5+"],[1057,202,"Дерево"],[1057,211,"Средняя"],[1057,212,"Сюжетная"],[1058,201,"5+"],[1058,202,"Дерево"],[1058,211,"Средняя"],[1058,212,"Сюжетная"],[1059,201,"5+"],[1059,202,"Дерево"],[1059,211,"Средняя"],[1059,212,"Сюжетная"],[1060,201,"5+"],[1060,202,"Дерево"],[1060,211,"Средняя"],[1060,212,"Сюжетная"],[1061,201,"3+"],[1061,202,"Дерево"],[1061,211,"Начальная"],[1061,212,"Обучающая"],[1062,201,"3+"],[1062,202,"Дерево"],[1062,211,"Начальная"],[1062,212,"Обучающая"],[1063,201,"6+"],[1063,202,"Дерево"],[1063,211,"Продвинутая"],[1063,212,"Обучающая"],[1064,201,"2+"],[1064,202,"Дерево"],[1064,211,"Начальная"],[1064,212,"Обучающая"],[1065,201,"6+"],[1065,202,"Картон"],[1065,211,"Продвинутая"],[1065,212,"Обучающая"],[1066,201,"4+"],[1066,202,"Картон"],[1066,211,"Средняя"],[1066,212,"Обучающая"],[1067,201,"7+"],[1067,202,"Картон"],[1067,211,"Продвинутая"],[1067,212,"Обучающая"],[1068,201,"3+"],[1068,202,"Картон"],[1068,211,"Начальная"],[1068,212,"Обучающая"],[1069,201,"3+"],[1069,202,"Дерево"],[1069,211,"Начальная"],[1069,212,"Конструирование"],[1070,201,"3+"],[1070,202,"Дерево"],[1070,211,"Начальная"],[1070,212,"Конструирование"],[1071,201,"3+"],[1071,202,"Дерево"],[1071,211,"Начальная"],[1071,212,"Конструирование"],[1072,201,"4+"],[1072,202,"Дерево"],[1072,211,"Средняя"],[1072,212,"Конструирование"],[1073,201,"4+"],[1073,202,"Дерево"],[1073,211,"Средняя"],[1073,212,"Конструирование"],[1074,201,"4+"],[1074,202,"Дерево"],[1074,211,"Средняя"],[1074,212,"Конструирование"],[1075,201,"4+"],[1075,202,"Дерево"],[1075,211,"Средняя"],[1075,212,"Конструирование"],[1076,201,"6+"],[1076,202,"Пластик"],[1076,211,"Продвинутая"],[1076,212,"Конструирование"],[1077,201,"4+"],[1077,202,"Дерево"],[1077,211,"Средняя"],[1077,212,"Конструирование"],[1001,204,"Воображение"],[1002,204,"Эмоции"],[1003,204,"Воображение"],[1004,204,"Эмоции"],[1005,204,"Воображение"],[1006,204,"Эмоции"],[1007,204,"Воображение"],[1008,204,"Эмоции"],[1009,204,"Моторика"],[1010,204,"Воображение"],[1011,204,"Логика"],[1011,204,"Моторика"],[1012,204,"Логика"],[1012,204,"Моторика"],[1013,204,"Воображение"],[1013,204,"Моторика"],[1014,204,"Логика"],[1014,204,"Моторика"],[1015,204,"Воображение"],[1015,204,"Моторика"],[1016,204,"Логика"],[1016,204,"Моторика"],[1017,204,"Воображение"],[1017,204,"Моторика"],[1018,204,"Логика"],[1018,204,"Моторика"],[1019,204,"Моторика"],[1020,204,"Внимание"],[1020,204,"Моторика"],[1021,204,"Реакция"],[1021,204,"Моторика"],[1022,204,"Воображение"],[1022,204,"Моторика"],[1023,204,"Память"],[1023,204,"Моторика"],[1024,204,"Общение"],[1024,204,"Моторика"],[1025,204,"Память"],[1025,204,"Моторика"],[1026,204,"Счёт"],[1026,204,"Моторика"],[1027,204,"Чтение"],[1027,204,"Моторика"],[1028,204,"Логика"],[1028,204,"Моторика"],[1029,204,"Моторика"],[1030,204,"Моторика"],[1031,204,"Логика"],[1032,204,"Воображение"],[1033,204,"Слух"],[1034,204,"Слух"],[1035,204,"Слух"],[1036,204,"Слух"],[1037,204,"Воображение"],[1038,204,"Реакция"],[1039,204,"Воображение"],[1040,204,"Воображение"],[1041,204,"Воображение"],[1042,204,"Моторика"],[1043,204,"Воображение"],[1044,204,"Моторика"],[1045,204,"Творчество"],[1045,204,"Моторика"],[1046,204,"Творчество"],[1046,204,"Моторика"],[1047,204,"Творчество"],[1047,204,"Моторика"],[1048,204,"Творчество"],[1048,204,"Моторика"],[1049,204,"Творчество"],[1049,204,"Моторика"],[1050,204,"Моторика"],[1051,204,"Творчество"],[1052,204,"Моторика"],[1053,204,"Воображение"],[1054,204,"Воображение"],[1055,204,"Эмоции"],[1056,204,"Воображение"],[1057,204,"Воображение"],[1057,204,"Моторика"],[1058,204,"Воображение"],[1058,204,"Моторика"],[1059,204,"Воображение"],[1059,204,"Моторика"],[1060,204,"Воображение"],[1061,204,"Логика"],[1061,204,"Моторика"],[1062,204,"Чтение"],[1062,204,"Моторика"],[1063,204,"Память"],[1063,204,"Моторика"],[1064,204,"Моторика"],[1065,204,"Логика"],[1065,204,"Моторика"],[1066,204,"Логика"],[1066,204,"Моторика"],[1067,204,"Внимание"],[1067,204,"Моторика"],[1068,204,"Моторика"],[1069,204,"Моторика"],[1070,204,"Логика"],[1070,204,"Моторика"],[1071,204,"Творчество"],[1072,204,"Логика"],[1072,204,"Моторика"],[1073,204,"Моторика"],[1074,204,"Внимание"],[1075,204,"Логика"],[1076,204,"Логика"],[1076,204,"Моторика"],[1077,204,"Логика"]]', '$[*]' COLUMNS (
 pid INT PATH '$[0]', aid INT PATH '$[1]', label VARCHAR(255) PATH '$[2]'
)) AS t
JOIN nnop3_minimarket_attribute_values v ON v.attribute_id=t.aid AND v.title=t.label COLLATE utf8mb4_unicode_ci
GROUP BY t.pid,t.aid
ON DUPLICATE KEY UPDATE product_id=product_id;



-- 3. Real per-product variant dimensions and full valid combinations.

INSERT INTO `nnop3_minimarket_variant_dimensions`
  (`id`, `product_id`, `code`, `title`, `sort_order`) VALUES
  (5001, 1001, 'size', 'Размер', 0),
  (5002, 1012, 'set', 'Комплектация', 0),
  (5003, 1012, 'color', 'Цвет', 1),
  (5004, 1016, 'set', 'Комплектация', 0),
  (5005, 1048, 'set', 'Комплектация', 0),
  (5006, 1049, 'set', 'Комплектация', 0),
  (5007, 1054, 'dress', 'Цвет платья', 0),
  (5008, 1072, 'set', 'Комплектация', 0) ON DUPLICATE KEY UPDATE `id`=`id`;

INSERT INTO `nnop3_minimarket_variant_dimension_values`
  (`id`, `dimension_id`, `code`, `title`, `image`, `color_hex`, `sort_order`) VALUES
  (6001, 5001, 'small', '25 см', 'images/minimarket/sample-01.png', '', 0),
  (6002, 5001, 'medium', '35 см', 'images/minimarket/sample-02.png', '', 1),
  (6003, 5001, 'large', '50 см', 'images/minimarket/sample-03.png', '', 2),
  (6004, 5002, 'set60', '60 деталей', 'images/minimarket/sample-04.png', '', 0),
  (6005, 5002, 'set120', '120 деталей', 'images/minimarket/sample-05.png', '', 1),
  (6006, 5003, 'blue', 'Синий', 'images/minimarket/sample-06.png', '', 0),
  (6007, 5003, 'red', 'Красный', 'images/minimarket/sample-07.png', '', 1),
  (6008, 5004, 'set32', '32 детали', 'images/minimarket/sample-08.png', '', 0),
  (6009, 5004, 'set64', '64 детали', 'images/minimarket/sample-09.png', '', 1),
  (6010, 5005, 'light', 'Базовая', 'images/minimarket/sample-10.png', '', 0),
  (6011, 5005, 'pro', 'Расширенная', 'images/minimarket/sample-01.png', '', 1),
  (6012, 5006, 'six', '6 баночек', 'images/minimarket/sample-02.png', '', 0),
  (6013, 5006, 'twelve', '12 баночек', 'images/minimarket/sample-03.png', '', 1),
  (6014, 5007, 'pink', 'Розовое', 'images/minimarket/sample-04.png', '', 0),
  (6015, 5007, 'blue', 'Голубое', 'images/minimarket/sample-05.png', '', 1),
  (6016, 5008, 'start', 'Стартовая', 'images/minimarket/sample-06.png', '', 0),
  (6017, 5008, 'plus', 'Расширенная', 'images/minimarket/sample-07.png', '', 1) ON DUPLICATE KEY UPDATE `id`=`id`;

INSERT INTO `nnop3_minimarket_variant_value_images`
  (`id`, `value_id`, `path`, `alt`, `sort_order`) VALUES
  (8501, 6001, 'images/minimarket/sample-01.png', '25 см', 0),
  (8502, 6002, 'images/minimarket/sample-02.png', '35 см', 0),
  (8503, 6003, 'images/minimarket/sample-03.png', '50 см', 0),
  (8504, 6004, 'images/minimarket/sample-04.png', '60 деталей', 0),
  (8505, 6005, 'images/minimarket/sample-05.png', '120 деталей', 0),
  (8506, 6006, 'images/minimarket/sample-06.png', 'Синий', 0),
  (8507, 6007, 'images/minimarket/sample-07.png', 'Красный', 0),
  (8508, 6008, 'images/minimarket/sample-08.png', '32 детали', 0),
  (8509, 6009, 'images/minimarket/sample-09.png', '64 детали', 0),
  (8510, 6010, 'images/minimarket/sample-10.png', 'Базовая', 0),
  (8511, 6011, 'images/minimarket/sample-01.png', 'Расширенная', 0),
  (8512, 6012, 'images/minimarket/sample-02.png', '6 баночек', 0),
  (8513, 6013, 'images/minimarket/sample-03.png', '12 баночек', 0),
  (8514, 6014, 'images/minimarket/sample-04.png', 'Розовое', 0),
  (8515, 6015, 'images/minimarket/sample-05.png', 'Голубое', 0),
  (8516, 6016, 'images/minimarket/sample-06.png', 'Стартовая', 0),
  (8517, 6017, 'images/minimarket/sample-07.png', 'Расширенная', 0) ON DUPLICATE KEY UPDATE `id`=`id`;

INSERT INTO `nnop3_minimarket_product_variants`
  (`id`, `product_id`, `sku`, `selection_json`, `signature`, `price`, `stock`, `image`, `image_alt`, `state`, `sort_order`, `description_mode`, `introtext_override`, `description_override`, `video_mode`) VALUES
  (7001, 1001, 'SKU-1001-SMALL', '{"size":"v:small"}', '5483c0564b74508effab03f50444849e5fd8c9f1c3ac90bba6cfd039eec6f5c1', 1490.00, 13, 'images/minimarket/sample-01.png', 'Плюшевый мишка «Тёма» — small', 1, 0, 0, NULL, NULL, 0),
  (7002, 1001, 'SKU-1001-MEDIUM', '{"size":"v:medium"}', '4bd71b27d762b51ada738ac76b0cde9703cf6e5ec22ecc033424cbe707528efb', 2490.00, 11, 'images/minimarket/sample-02.png', 'Плюшевый мишка «Тёма» — medium', 1, 1, 0, NULL, NULL, 0),
  (7003, 1001, 'SKU-1001-LARGE', '{"size":"v:large"}', 'db684f042c6f0156615bf277ef9ccaa6c61e71e72c3878459806d00d08417d7c', 3790.00, 4, 'images/minimarket/sample-03.png', 'Плюшевый мишка «Тёма» — large', 1, 2, 0, NULL, NULL, 0),
  (7004, 1012, 'SKU-1012-SET60-BLUE', '{"set":"v:set60","color":"v:blue"}', '6f19b7bbb113f9a6cc3eac90cff6aeb69956ff61c65268dc5440ac975c23c3a2', 3190.00, 9, 'images/minimarket/sample-04.png', 'Конструктор «КосмоБлок» — set60, blue', 1, 0, 0, NULL, NULL, 0),
  (7005, 1012, 'SKU-1012-SET120-BLUE', '{"set":"v:set120","color":"v:blue"}', '5ec8bac531293f4f7150a799b2f2fb23e7800d528d5f2cf33e8e627de7b10174', 4990.00, 5, 'images/minimarket/sample-05.png', 'Конструктор «КосмоБлок» — set120, blue', 1, 1, 0, NULL, NULL, 0),
  (7006, 1012, 'SKU-1012-SET60-RED', '{"set":"v:set60","color":"v:red"}', '075ed2211524ada59c5bfd431da8d21629911459c330817a7a59a3bd037f6ad3', 3190.00, 0, 'images/minimarket/sample-06.png', 'Конструктор «КосмоБлок» — set60, red', 0, 2, 0, NULL, NULL, 0),
  (7007, 1016, 'SKU-1016-SET32', '{"set":"v:set32"}', 'd1e5a7f81129e92f81da3ff964a19e4557c8069ce6b6585ce3321f6db6631474', 2990.00, 10, 'images/minimarket/sample-07.png', 'Магнитный конструктор «ГеоМир» — set32', 1, 0, 0, NULL, NULL, 0),
  (7008, 1016, 'SKU-1016-SET64', '{"set":"v:set64"}', '845573350ff546d0d907d307f4ae25d5e030a99a44d3b03914be786dd98d5608', 5290.00, 6, 'images/minimarket/sample-08.png', 'Магнитный конструктор «ГеоМир» — set64', 1, 1, 0, NULL, NULL, 0),
  (7009, 1048, 'SKU-1048-LIGHT', '{"set":"v:light"}', 'c3993266c2612a441d48a270616dae1bc7f2bd00d3f72681fa5c98d1ce828089', 3190.00, 5, 'images/minimarket/sample-09.png', 'Набор художника «Мастерская» — light', 1, 0, 0, NULL, NULL, 0),
  (7010, 1048, 'SKU-1048-PRO', '{"set":"v:pro"}', '0dbc618d32a30381753e5d9b97698bacda0429b4cf44fa8512d03e66d254e333', 4990.00, 2, 'images/minimarket/sample-10.png', 'Набор художника «Мастерская» — pro', 1, 1, 0, NULL, NULL, 0),
  (7011, 1049, 'SKU-1049-SIX', '{"set":"v:six"}', '6e45d7cc3a28c0621a194ead51fe74dce54bf951001ff92ff4cd0dc18e2729bd', 1590.00, 18, 'images/minimarket/sample-01.png', 'Набор теста для лепки «Пластилинка» — six', 1, 0, 0, NULL, NULL, 0),
  (7012, 1049, 'SKU-1049-TWELVE', '{"set":"v:twelve"}', 'fe48d22c7975192bd3e63a0eeabba68cd106eb0ebf23e3a5219d1d2c1abb17ac', 2490.00, 9, 'images/minimarket/sample-02.png', 'Набор теста для лепки «Пластилинка» — twelve', 1, 1, 0, NULL, NULL, 0),
  (7013, 1054, 'SKU-1054-PINK', '{"dress":"v:pink"}', 'bba72db1ee2732b8eb9c25adaf471d963af3dcc11c65f682b7f68c70e0152ec2', 3290.00, 7, 'images/minimarket/sample-03.png', 'Кукла «Настя» — pink', 1, 0, 0, NULL, NULL, 0),
  (7014, 1054, 'SKU-1054-BLUE', '{"dress":"v:blue"}', '45005bdb8d8d93cbf1c1984262a795e9f207aa773a93c682414ea4c653695f26', 3290.00, 4, 'images/minimarket/sample-04.png', 'Кукла «Настя» — blue', 1, 1, 0, NULL, NULL, 0),
  (7015, 1072, 'SKU-1072-START', '{"set":"v:start"}', 'b6d3468e0f1510bbbb5f469ac8c91e12396ee5bb7079cfb283a3626c929d4947', 12990.00, 5, 'images/minimarket/sample-05.png', 'Настенная трасса «Шарикопад» — start', 1, 0, 0, NULL, NULL, 0),
  (7016, 1072, 'SKU-1072-PLUS', '{"set":"v:plus"}', 'b6181972974474c658e255da6e6bb447e01e944602775855502b2420e95f7b61', 18990.00, 2, 'images/minimarket/sample-06.png', 'Настенная трасса «Шарикопад» — plus', 1, 1, 0, NULL, NULL, 0) ON DUPLICATE KEY UPDATE `id`=`id`;

INSERT INTO `nnop3_minimarket_variant_value_assignments`
  (`variant_id`, `dimension_id`, `value_id`) VALUES
  (7001, 5001, 6001),
  (7002, 5001, 6002),
  (7003, 5001, 6003),
  (7004, 5002, 6004),
  (7004, 5003, 6006),
  (7005, 5002, 6005),
  (7005, 5003, 6006),
  (7006, 5002, 6004),
  (7006, 5003, 6007),
  (7007, 5004, 6008),
  (7008, 5004, 6009),
  (7009, 5005, 6010),
  (7010, 5005, 6011),
  (7011, 5006, 6012),
  (7012, 5006, 6013),
  (7013, 5007, 6014),
  (7014, 5007, 6015),
  (7015, 5008, 6016),
  (7016, 5008, 6017) ON DUPLICATE KEY UPDATE `variant_id`=`variant_id`;

INSERT INTO `nnop3_minimarket_product_variant_images`
  (`id`, `variant_id`, `path`, `alt`, `sort_order`) VALUES
  (8001, 7001, 'images/minimarket/sample-01.png', 'Плюшевый мишка «Тёма» — small', 0),
  (8002, 7002, 'images/minimarket/sample-02.png', 'Плюшевый мишка «Тёма» — medium', 0),
  (8003, 7003, 'images/minimarket/sample-03.png', 'Плюшевый мишка «Тёма» — large', 0),
  (8004, 7004, 'images/minimarket/sample-04.png', 'Конструктор «КосмоБлок» — set60, blue', 0),
  (8005, 7005, 'images/minimarket/sample-05.png', 'Конструктор «КосмоБлок» — set120, blue', 0),
  (8006, 7006, 'images/minimarket/sample-06.png', 'Конструктор «КосмоБлок» — set60, red', 0),
  (8007, 7007, 'images/minimarket/sample-07.png', 'Магнитный конструктор «ГеоМир» — set32', 0),
  (8008, 7008, 'images/minimarket/sample-08.png', 'Магнитный конструктор «ГеоМир» — set64', 0),
  (8009, 7009, 'images/minimarket/sample-09.png', 'Набор художника «Мастерская» — light', 0),
  (8010, 7010, 'images/minimarket/sample-10.png', 'Набор художника «Мастерская» — pro', 0),
  (8011, 7011, 'images/minimarket/sample-01.png', 'Набор теста для лепки «Пластилинка» — six', 0),
  (8012, 7012, 'images/minimarket/sample-02.png', 'Набор теста для лепки «Пластилинка» — twelve', 0),
  (8013, 7013, 'images/minimarket/sample-03.png', 'Кукла «Настя» — pink', 0),
  (8014, 7014, 'images/minimarket/sample-04.png', 'Кукла «Настя» — blue', 0),
  (8015, 7015, 'images/minimarket/sample-05.png', 'Настенная трасса «Шарикопад» — start', 0),
  (8016, 7016, 'images/minimarket/sample-06.png', 'Настенная трасса «Шарикопад» — plus', 0) ON DUPLICATE KEY UPDATE `id`=`id`;


-- 4. Manual many-to-many product links; legacy type is technical only.

INSERT INTO `nnop3_minimarket_product_groups`
  (`id`, `title`, `type`, `state`, `sort_order`) VALUES
  (901, 'Плюшевые друзья', 'similar', 1, 1),
  (902, 'Подарки для малыша', 'similar', 1, 2),
  (903, 'Строим космический город', 'similar', 1, 3),
  (904, 'Для семейного вечера', 'similar', 1, 4),
  (905, 'Учимся считать и читать', 'similar', 1, 5),
  (906, 'Юный художник', 'similar', 1, 6),
  (907, 'Мир куклы Насти', 'similar', 1, 7),
  (908, 'Транспортный парк', 'similar', 1, 8),
  (909, 'Настенные механизмы', 'similar', 1, 9),
  (910, 'Подарки для любознательных', 'similar', 1, 10) ON DUPLICATE KEY UPDATE `id`=`id`;

INSERT INTO `nnop3_minimarket_product_group_members`
  (`product_id`, `group_id`, `sort_order`) VALUES
  (1001, 901, 1),
  (1003, 901, 2),
  (1006, 901, 3),
  (1007, 901, 4),
  (1010, 901, 5),
  (1029, 902, 1),
  (1030, 902, 2),
  (1031, 902, 3),
  (1034, 902, 4),
  (1006, 902, 5),
  (1012, 903, 1),
  (1016, 903, 2),
  (1018, 903, 3),
  (1011, 903, 4),
  (1015, 903, 5),
  (1020, 904, 1),
  (1021, 904, 2),
  (1022, 904, 3),
  (1024, 904, 4),
  (1065, 904, 5),
  (1026, 905, 1),
  (1027, 905, 2),
  (1028, 905, 3),
  (1062, 905, 4),
  (1025, 905, 5),
  (1045, 906, 1),
  (1046, 906, 2),
  (1047, 906, 3),
  (1048, 906, 4),
  (1049, 906, 5),
  (1054, 907, 1),
  (1057, 907, 2),
  (1058, 907, 3),
  (1060, 907, 4),
  (1059, 907, 5),
  (1037, 908, 1),
  (1038, 908, 2),
  (1039, 908, 3),
  (1041, 908, 4),
  (1042, 908, 5),
  (1069, 909, 1),
  (1070, 909, 2),
  (1071, 909, 3),
  (1072, 909, 4),
  (1073, 909, 5),
  (1074, 909, 6),
  (1019, 910, 1),
  (1063, 910, 2),
  (1072, 910, 3),
  (1031, 910, 4),
  (1015, 910, 5),
  (1028, 910, 6) ON DUPLICATE KEY UPDATE `product_id`=`product_id`;


-- 5. Fictional brands, collections and assignments for existing demo products.
INSERT INTO `nnop3_minimarket_brands`
 (title,alias,introtext,description,image,image_alt,meta_title,meta_description,meta_robots,og_title,og_description,og_image,og_image_alt,sort_order,state)
 VALUES ('Лесные игрушки','demo-forest-toys','<p>Плюшевые друзья, персонажи и мягкие игрушки.</p>','<p>Плюшевые друзья, персонажи и мягкие игрушки. Это вымышленные демонстрационные данные для тестирования магазина MiniMarket.</p>','images/minimarket/sample-01.png','Лесные игрушки','Лесные игрушки — MiniMarket','Плюшевые друзья, персонажи и мягкие игрушки. Тестовые товары и цены.','index,follow','Лесные игрушки','Плюшевые друзья, персонажи и мягкие игрушки','images/minimarket/sample-01.png','Лесные игрушки',1,1) ON DUPLICATE KEY UPDATE `title`=`title`;
INSERT INTO `nnop3_minimarket_brands`
 (title,alias,introtext,description,image,image_alt,meta_title,meta_description,meta_robots,og_title,og_description,og_image,og_image_alt,sort_order,state)
 VALUES ('ГеоЛаб','demo-geo-lab','<p>Конструкторы, магниты, механизмы и STEM-игры.</p>','<p>Конструкторы, магниты, механизмы и STEM-игры. Это вымышленные демонстрационные данные для тестирования магазина MiniMarket.</p>','images/minimarket/sample-02.png','ГеоЛаб','ГеоЛаб — MiniMarket','Конструкторы, магниты, механизмы и STEM-игры. Тестовые товары и цены.','index,follow','ГеоЛаб','Конструкторы, магниты, механизмы и STEM-игры','images/minimarket/sample-02.png','ГеоЛаб',2,1) ON DUPLICATE KEY UPDATE `title`=`title`;
INSERT INTO `nnop3_minimarket_brands`
 (title,alias,introtext,description,image,image_alt,meta_title,meta_description,meta_robots,og_title,og_description,og_image,og_image_alt,sort_order,state)
 VALUES ('Игра вместе','demo-play-together','<p>Настольные и семейные игры.</p>','<p>Настольные и семейные игры. Это вымышленные демонстрационные данные для тестирования магазина MiniMarket.</p>','images/minimarket/sample-03.png','Игра вместе','Игра вместе — MiniMarket','Настольные и семейные игры. Тестовые товары и цены.','index,follow','Игра вместе','Настольные и семейные игры','images/minimarket/sample-03.png','Игра вместе',3,1) ON DUPLICATE KEY UPDATE `title`=`title`;
INSERT INTO `nnop3_minimarket_brands`
 (title,alias,introtext,description,image,image_alt,meta_title,meta_description,meta_robots,og_title,og_description,og_image,og_image_alt,sort_order,state)
 VALUES ('Первые открытия','demo-first-discoveries','<p>Развивающие игрушки для малышей.</p>','<p>Развивающие игрушки для малышей. Это вымышленные демонстрационные данные для тестирования магазина MiniMarket.</p>','images/minimarket/sample-04.png','Первые открытия','Первые открытия — MiniMarket','Развивающие игрушки для малышей. Тестовые товары и цены.','index,follow','Первые открытия','Развивающие игрушки для малышей','images/minimarket/sample-04.png','Первые открытия',4,1) ON DUPLICATE KEY UPDATE `title`=`title`;
INSERT INTO `nnop3_minimarket_brands`
 (title,alias,introtext,description,image,image_alt,meta_title,meta_description,meta_robots,og_title,og_description,og_image,og_image_alt,sort_order,state)
 VALUES ('Мастерская игры','demo-toy-workshop','<p>Творческие наборы, машинки и игрушки для исследования.</p>','<p>Творческие наборы, машинки и игрушки для исследования. Это вымышленные демонстрационные данные для тестирования магазина MiniMarket.</p>','images/minimarket/sample-05.png','Мастерская игры','Мастерская игры — MiniMarket','Творческие наборы, машинки и игрушки для исследования. Тестовые товары и цены.','index,follow','Мастерская игры','Творческие наборы, машинки и игрушки для исследования','images/minimarket/sample-05.png','Мастерская игры',5,1) ON DUPLICATE KEY UPDATE `title`=`title`;
INSERT INTO `nnop3_minimarket_collections`
 (title,alias,introtext,description,image,image_alt,meta_title,meta_description,meta_robots,og_title,og_description,og_image,og_image_alt,sort_order,state)
 VALUES ('Мягкие друзья','demo-soft-friends','<p>Плюшевые зверята для уютной игры.</p>','<p>Плюшевые зверята для уютной игры. Это вымышленные демонстрационные данные для тестирования магазина MiniMarket.</p>','images/minimarket/sample-06.png','Мягкие друзья','Мягкие друзья — MiniMarket','Плюшевые зверята для уютной игры. Тестовые товары и цены.','index,follow','Мягкие друзья','Плюшевые зверята для уютной игры','images/minimarket/sample-06.png','Мягкие друзья',1,1) ON DUPLICATE KEY UPDATE `title`=`title`;
INSERT INTO `nnop3_minimarket_collections`
 (title,alias,introtext,description,image,image_alt,meta_title,meta_description,meta_robots,og_title,og_description,og_image,og_image_alt,sort_order,state)
 VALUES ('STEM и механика','demo-stem','<p>Игровые механизмы, трассы, инженерные конструкторы.</p>','<p>Игровые механизмы, трассы, инженерные конструкторы. Это вымышленные демонстрационные данные для тестирования магазина MiniMarket.</p>','images/minimarket/sample-07.png','STEM и механика','STEM и механика — MiniMarket','Игровые механизмы, трассы, инженерные конструкторы. Тестовые товары и цены.','index,follow','STEM и механика','Игровые механизмы, трассы, инженерные конструкторы','images/minimarket/sample-07.png','STEM и механика',2,1) ON DUPLICATE KEY UPDATE `title`=`title`;
INSERT INTO `nnop3_minimarket_collections`
 (title,alias,introtext,description,image,image_alt,meta_title,meta_description,meta_robots,og_title,og_description,og_image,og_image_alt,sort_order,state)
 VALUES ('Семейный вечер','demo-family-evening','<p>Игры для всей семьи.</p>','<p>Игры для всей семьи. Это вымышленные демонстрационные данные для тестирования магазина MiniMarket.</p>','images/minimarket/sample-08.png','Семейный вечер','Семейный вечер — MiniMarket','Игры для всей семьи. Тестовые товары и цены.','index,follow','Семейный вечер','Игры для всей семьи','images/minimarket/sample-08.png','Семейный вечер',3,1) ON DUPLICATE KEY UPDATE `title`=`title`;
INSERT INTO `nnop3_minimarket_collections`
 (title,alias,introtext,description,image,image_alt,meta_title,meta_description,meta_robots,og_title,og_description,og_image,og_image_alt,sort_order,state)
 VALUES ('Маленькие исследователи','demo-little-explorers','<p>Игрушки для первых открытий.</p>','<p>Игрушки для первых открытий. Это вымышленные демонстрационные данные для тестирования магазина MiniMarket.</p>','images/minimarket/sample-09.png','Маленькие исследователи','Маленькие исследователи — MiniMarket','Игрушки для первых открытий. Тестовые товары и цены.','index,follow','Маленькие исследователи','Игрушки для первых открытий','images/minimarket/sample-09.png','Маленькие исследователи',4,1) ON DUPLICATE KEY UPDATE `title`=`title`;
INSERT INTO `nnop3_minimarket_collections`
 (title,alias,introtext,description,image,image_alt,meta_title,meta_description,meta_robots,og_title,og_description,og_image,og_image_alt,sort_order,state)
 VALUES ('Творческая мастерская','demo-creativity','<p>Материалы и наборы для творчества.</p>','<p>Материалы и наборы для творчества. Это вымышленные демонстрационные данные для тестирования магазина MiniMarket.</p>','images/minimarket/sample-10.png','Творческая мастерская','Творческая мастерская — MiniMarket','Материалы и наборы для творчества. Тестовые товары и цены.','index,follow','Творческая мастерская','Материалы и наборы для творчества','images/minimarket/sample-10.png','Творческая мастерская',5,1) ON DUPLICATE KEY UPDATE `title`=`title`;
INSERT INTO `nnop3_minimarket_collections`
 (title,alias,introtext,description,image,image_alt,meta_title,meta_description,meta_robots,og_title,og_description,og_image,og_image_alt,sort_order,state)
 VALUES ('Идеи для подарка','demo-gifts','<p>Подборка подарков из разных категорий.</p>','<p>Подборка подарков из разных категорий. Это вымышленные демонстрационные данные для тестирования магазина MiniMarket.</p>','images/minimarket/sample-01.png','Идеи для подарка','Идеи для подарка — MiniMarket','Подборка подарков из разных категорий. Тестовые товары и цены.','index,follow','Идеи для подарка','Подборка подарков из разных категорий','images/minimarket/sample-01.png','Идеи для подарка',6,1) ON DUPLICATE KEY UPDATE `title`=`title`;

UPDATE `nnop3_minimarket_products` p
JOIN `nnop3_minimarket_brands` b ON b.alias = CASE
 WHEN p.category_id IN (101,102,103) THEN 'demo-forest-toys'
 WHEN p.category_id IN (104,105,106) THEN 'demo-geo-lab'
 WHEN p.category_id IN (107,108,109) THEN 'demo-play-together'
 WHEN p.category_id IN (110,111,112) THEN 'demo-first-discoveries'
 ELSE 'demo-toy-workshop' END
SET p.brand_id=b.id WHERE p.sku=CONCAT('TOY-',p.id);

INSERT INTO `nnop3_minimarket_product_collections` (product_id,collection_id,sort_order)
SELECT p.id,c.id,0 FROM `nnop3_minimarket_products` p
JOIN `nnop3_minimarket_collections` c ON c.alias = CASE
 WHEN p.category_id IN (101,102,103) THEN 'demo-soft-friends'
 WHEN p.category_id IN (104,105,106) THEN 'demo-stem'
 WHEN p.category_id IN (107,108,109) THEN 'demo-family-evening'
 WHEN p.category_id IN (110,111,112) THEN 'demo-little-explorers'
 ELSE 'demo-creativity' END
WHERE p.sku=CONCAT('TOY-',p.id)
ON DUPLICATE KEY UPDATE `product_id`=`product_id`;

INSERT INTO `nnop3_minimarket_product_collections` (product_id,collection_id,sort_order)
SELECT p.id,c.id,1 FROM `nnop3_minimarket_products` p
JOIN `nnop3_minimarket_collections` c ON c.alias='demo-gifts'
WHERE p.sku=CONCAT('TOY-',p.id) AND MOD(p.id,3)=0
ON DUPLICATE KEY UPDATE `product_id`=`product_id`;

-- Assign an existing gallery image as the main photo for selected SKU variants.
INSERT INTO `nnop3_minimarket_product_image_variants` (image_id,variant_id,is_cover)
SELECT pi.id,pv.id,1 FROM `nnop3_minimarket_product_images` pi
JOIN `nnop3_minimarket_product_variants` pv ON pv.product_id=pi.product_id
WHERE pv.id IN (7001,7004,7007,7009,7011,7013,7015)
  AND pi.sort_order=0
  AND NOT EXISTS (SELECT 1 FROM `nnop3_minimarket_product_image_variants` x
    WHERE x.variant_id=pv.id AND x.is_cover=1);

COMMIT;

-- Read-only verification.
SELECT 'Категории' AS section, COUNT(*) AS total FROM `nnop3_minimarket_categories`
UNION ALL SELECT 'Товары',COUNT(*) FROM `nnop3_minimarket_products`
UNION ALL SELECT 'Характеристики',COUNT(*) FROM `nnop3_minimarket_attributes`
UNION ALL SELECT 'Значения характеристик',COUNT(*) FROM `nnop3_minimarket_attribute_values`
UNION ALL SELECT 'Опции',COUNT(*) FROM `nnop3_minimarket_order_options`
UNION ALL SELECT 'Значения опций',COUNT(*) FROM `nnop3_minimarket_order_option_values`
UNION ALL SELECT 'Бренды',COUNT(*) FROM `nnop3_minimarket_brands`
UNION ALL SELECT 'Коллекции',COUNT(*) FROM `nnop3_minimarket_collections`
UNION ALL SELECT 'Связи коллекций',COUNT(*) FROM `nnop3_minimarket_product_collections`
UNION ALL SELECT 'Варианты SKU',COUNT(*) FROM `nnop3_minimarket_product_variants`
UNION ALL SELECT 'Связи товаров',COUNT(*) FROM `nnop3_minimarket_product_groups`
UNION ALL SELECT 'Дополнительные фотографии',COUNT(*) FROM `nnop3_minimarket_product_images`;

SELECT image,COUNT(*) AS references_count FROM
(SELECT image FROM `nnop3_minimarket_categories`
 UNION ALL SELECT image FROM `nnop3_minimarket_products`
 UNION ALL SELECT image FROM `nnop3_minimarket_brands`
 UNION ALL SELECT image FROM `nnop3_minimarket_collections`) imgs
GROUP BY image ORDER BY image;

-- === BUNDLES / PRODUCT & SKU MEMBERS ===
-- MiniMarket 0.5.51: DEMONSTRATION BUNDLES for the user's MySQL 8.4 site.
-- Database: minimarket, prefix: nnop3_. Requires 0.5.51 schema and
-- minimarket_mysql84_nnop3_full_demo.sql already imported.
-- Additive only: NO DROP/DELETE/ALTER/TRUNCATE. Existing data never overwritten.
-- Images already exist: images/minimarket/sample-01.png..sample-10.png.
-- Can be rerun safely; all bundle/sku links validated against demo SKU values.
-- Products and prices are fictitious.
USE `minimarket`;
SET NAMES utf8mb4;
START TRANSACTION;

INSERT INTO `nnop3_minimarket_products`
(id,category_id,title,alias,sku,product_type,price,stock,option_stock_mode,
 introtext,description,sort_order,image,image_alt,meta_title,meta_description,
 meta_robots,og_title,og_description,og_image,og_image_alt,state)
SELECT 1090,101,'Подарочный комплект «Медвежата»','demo-bundle-bears','BUNDLE-1090','bundle',4990.00,0,0,
 '<p>Подарочный комплект из двух видов плюшевых медведей.</p>','<p>Подарочный комплект из двух видов плюшевых медведей. Состав комплекта и доступность рассчитываются по товарам и SKU, а цена задаётся у комплекта. Тестовые данные.</p>',1,
 'images/minimarket/sample-02.png','Подарочный комплект «Медвежата»','Подарочный комплект «Медвежата» — MiniMarket','Подарочный комплект из двух видов плюшевых медведей.',
 'index,follow','Подарочный комплект «Медвежата»','Подарочный комплект из двух видов плюшевых медведей.','images/minimarket/sample-02.png','Подарочный комплект «Медвежата»',1
WHERE EXISTS (SELECT 1 FROM `nnop3_minimarket_categories` WHERE id=101)
  AND NOT EXISTS (SELECT 1 FROM `nnop3_minimarket_products`
   WHERE id=1090 OR alias='demo-bundle-bears' OR sku='BUNDLE-1090');
UPDATE `nnop3_minimarket_products` p
JOIN `nnop3_minimarket_brands` b ON b.alias='demo-forest-toys'
SET p.brand_id=b.id
WHERE p.id=1090 AND p.sku='BUNDLE-1090' AND p.brand_id IS NULL;
INSERT INTO `nnop3_minimarket_product_collections` (product_id,collection_id,sort_order)
SELECT p.id,c.id,0 FROM `nnop3_minimarket_products` p
JOIN `nnop3_minimarket_collections` c ON c.alias='demo-soft-friends'
WHERE p.id=1090 AND p.sku='BUNDLE-1090'
AND NOT EXISTS (SELECT 1 FROM `nnop3_minimarket_product_collections` pc
 WHERE pc.product_id=p.id AND pc.collection_id=c.id);

INSERT INTO `nnop3_minimarket_bundle_items`
(bundle_product_id,component_product_id,component_variant_id,quantity,sort_order)
SELECT b.id,1002,NULL,1,0
FROM `nnop3_minimarket_products` b
JOIN `nnop3_minimarket_products` c ON c.id=1002 AND c.sku='TOY-1002'
WHERE b.id=1090 AND b.sku='BUNDLE-1090' AND b.product_type='bundle'
AND NOT EXISTS (
 SELECT 1 FROM `nnop3_minimarket_bundle_items` i
 WHERE i.bundle_product_id=b.id AND i.component_product_id=1002
 AND i.component_variant_id IS NULL
);
INSERT INTO `nnop3_minimarket_bundle_items`
(bundle_product_id,component_product_id,component_variant_id,quantity,sort_order)
SELECT b.id,1003,NULL,2,1
FROM `nnop3_minimarket_products` b
JOIN `nnop3_minimarket_products` c ON c.id=1003 AND c.sku='TOY-1003'
WHERE b.id=1090 AND b.sku='BUNDLE-1090' AND b.product_type='bundle'
AND NOT EXISTS (
 SELECT 1 FROM `nnop3_minimarket_bundle_items` i
 WHERE i.bundle_product_id=b.id AND i.component_product_id=1003
 AND i.component_variant_id IS NULL
);

INSERT INTO `nnop3_minimarket_products`
(id,category_id,title,alias,sku,product_type,price,stock,option_stock_mode,
 introtext,description,sort_order,image,image_alt,meta_title,meta_description,
 meta_robots,og_title,og_description,og_image,og_image_alt,state)
SELECT 1091,104,'Набор юного инженера','demo-bundle-engineer','BUNDLE-1091','bundle',6290.00,0,0,
 '<p>Комплект двух конструкторов в конкретных SKU.</p>','<p>Комплект двух конструкторов в конкретных SKU. Состав комплекта и доступность рассчитываются по товарам и SKU, а цена задаётся у комплекта. Тестовые данные.</p>',2,
 'images/minimarket/sample-07.png','Набор юного инженера','Набор юного инженера — MiniMarket','Комплект двух конструкторов в конкретных SKU.',
 'index,follow','Набор юного инженера','Комплект двух конструкторов в конкретных SKU.','images/minimarket/sample-07.png','Набор юного инженера',1
WHERE EXISTS (SELECT 1 FROM `nnop3_minimarket_categories` WHERE id=104)
  AND NOT EXISTS (SELECT 1 FROM `nnop3_minimarket_products`
   WHERE id=1091 OR alias='demo-bundle-engineer' OR sku='BUNDLE-1091');
UPDATE `nnop3_minimarket_products` p
JOIN `nnop3_minimarket_brands` b ON b.alias='demo-geo-lab'
SET p.brand_id=b.id
WHERE p.id=1091 AND p.sku='BUNDLE-1091' AND p.brand_id IS NULL;
INSERT INTO `nnop3_minimarket_product_collections` (product_id,collection_id,sort_order)
SELECT p.id,c.id,0 FROM `nnop3_minimarket_products` p
JOIN `nnop3_minimarket_collections` c ON c.alias='demo-stem'
WHERE p.id=1091 AND p.sku='BUNDLE-1091'
AND NOT EXISTS (SELECT 1 FROM `nnop3_minimarket_product_collections` pc
 WHERE pc.product_id=p.id AND pc.collection_id=c.id);

INSERT INTO `nnop3_minimarket_bundle_items`
(bundle_product_id,component_product_id,component_variant_id,quantity,sort_order)
SELECT b.id,1012,7004,1,0
FROM `nnop3_minimarket_products` b
JOIN `nnop3_minimarket_product_variants` v ON v.id=7004 AND v.product_id=1012 AND v.sku='SKU-1012-SET60-BLUE'
WHERE b.id=1091 AND b.sku='BUNDLE-1091' AND b.product_type='bundle'
AND NOT EXISTS (
 SELECT 1 FROM `nnop3_minimarket_bundle_items` i
 WHERE i.bundle_product_id=b.id AND i.component_product_id=1012
 AND i.component_variant_id=7004
);
INSERT INTO `nnop3_minimarket_bundle_items`
(bundle_product_id,component_product_id,component_variant_id,quantity,sort_order)
SELECT b.id,1016,7007,1,1
FROM `nnop3_minimarket_products` b
JOIN `nnop3_minimarket_product_variants` v ON v.id=7007 AND v.product_id=1016 AND v.sku='SKU-1016-SET32'
WHERE b.id=1091 AND b.sku='BUNDLE-1091' AND b.product_type='bundle'
AND NOT EXISTS (
 SELECT 1 FROM `nnop3_minimarket_bundle_items` i
 WHERE i.bundle_product_id=b.id AND i.component_product_id=1016
 AND i.component_variant_id=7007
);

INSERT INTO `nnop3_minimarket_products`
(id,category_id,title,alias,sku,product_type,price,stock,option_stock_mode,
 introtext,description,sort_order,image,image_alt,meta_title,meta_description,
 meta_robots,og_title,og_description,og_image,og_image_alt,state)
SELECT 1092,101,'Большой комплект мягких друзей','demo-bundle-friends','BUNDLE-1092','bundle',7390.00,0,0,
 '<p>Вложенный комплект: подарочный набор медведей плюс ещё один медвежонок.</p>','<p>Вложенный комплект: подарочный набор медведей плюс ещё один медвежонок. Состав комплекта и доступность рассчитываются по товарам и SKU, а цена задаётся у комплекта. Тестовые данные.</p>',3,
 'images/minimarket/sample-03.png','Большой комплект мягких друзей','Большой комплект мягких друзей — MiniMarket','Вложенный комплект: подарочный набор медведей плюс ещё один медвежонок.',
 'index,follow','Большой комплект мягких друзей','Вложенный комплект: подарочный набор медведей плюс ещё один медвежонок.','images/minimarket/sample-03.png','Большой комплект мягких друзей',1
WHERE EXISTS (SELECT 1 FROM `nnop3_minimarket_categories` WHERE id=101)
  AND NOT EXISTS (SELECT 1 FROM `nnop3_minimarket_products`
   WHERE id=1092 OR alias='demo-bundle-friends' OR sku='BUNDLE-1092');
UPDATE `nnop3_minimarket_products` p
JOIN `nnop3_minimarket_brands` b ON b.alias='demo-forest-toys'
SET p.brand_id=b.id
WHERE p.id=1092 AND p.sku='BUNDLE-1092' AND p.brand_id IS NULL;
INSERT INTO `nnop3_minimarket_product_collections` (product_id,collection_id,sort_order)
SELECT p.id,c.id,0 FROM `nnop3_minimarket_products` p
JOIN `nnop3_minimarket_collections` c ON c.alias='demo-gifts'
WHERE p.id=1092 AND p.sku='BUNDLE-1092'
AND NOT EXISTS (SELECT 1 FROM `nnop3_minimarket_product_collections` pc
 WHERE pc.product_id=p.id AND pc.collection_id=c.id);

INSERT INTO `nnop3_minimarket_bundle_items`
(bundle_product_id,component_product_id,component_variant_id,quantity,sort_order)
SELECT b.id,1090,NULL,1,0
FROM `nnop3_minimarket_products` b
JOIN `nnop3_minimarket_products` c ON c.id=1090 AND c.product_type='bundle'
WHERE b.id=1092 AND b.sku='BUNDLE-1092' AND b.product_type='bundle'
AND NOT EXISTS (
 SELECT 1 FROM `nnop3_minimarket_bundle_items` i
 WHERE i.bundle_product_id=b.id AND i.component_product_id=1090
 AND i.component_variant_id IS NULL
);
INSERT INTO `nnop3_minimarket_bundle_items`
(bundle_product_id,component_product_id,component_variant_id,quantity,sort_order)
SELECT b.id,1003,NULL,1,1
FROM `nnop3_minimarket_products` b
JOIN `nnop3_minimarket_products` c ON c.id=1003 AND c.sku='TOY-1003'
WHERE b.id=1092 AND b.sku='BUNDLE-1092' AND b.product_type='bundle'
AND NOT EXISTS (
 SELECT 1 FROM `nnop3_minimarket_bundle_items` i
 WHERE i.bundle_product_id=b.id AND i.component_product_id=1003
 AND i.component_variant_id IS NULL
);

COMMIT;
SELECT p.id,p.title,p.sku,COUNT(i.id) AS component_rows
FROM `nnop3_minimarket_products` p
LEFT JOIN `nnop3_minimarket_bundle_items` i ON i.bundle_product_id=p.id
WHERE p.product_type='bundle'
GROUP BY p.id,p.title,p.sku ORDER BY p.id;

-- === BUNDLE PRICING ===
-- MiniMarket 0.5.52, optional pricing examples on existing demo bundles.
-- Target: minimarket database, nnop3_ table prefix, MySQL 8.4.
-- Requires com_minimarket 0.5.52 and the 0.5.51 bundle demo already loaded.
-- One fixed price, one 10% discount, one 15% nested-kit discount.
-- Never changes unknown products or overrides an already configured discount.
USE `minimarket`;
START TRANSACTION;
UPDATE `nnop3_minimarket_products`
SET bundle_price_mode='fixed',bundle_discount_pct=0
WHERE id=1090 AND sku='BUNDLE-1090' AND product_type='bundle'
  AND bundle_price_mode='fixed' AND bundle_discount_pct=0;
UPDATE `nnop3_minimarket_products`
SET bundle_price_mode='discount',bundle_discount_pct=10.00
WHERE id=1091 AND sku='BUNDLE-1091' AND product_type='bundle'
  AND bundle_price_mode='fixed' AND bundle_discount_pct=0;
UPDATE `nnop3_minimarket_products`
SET bundle_price_mode='discount',bundle_discount_pct=15.00
WHERE id=1092 AND sku='BUNDLE-1092' AND product_type='bundle'
  AND bundle_price_mode='fixed' AND bundle_discount_pct=0;
COMMIT;
SELECT id,title,sku,bundle_price_mode,bundle_discount_pct,price
FROM `nnop3_minimarket_products`
WHERE id IN (1090,1091,1092) AND product_type='bundle' ORDER BY id;

-- === SALE / NEW / POPULAR FLAGS ===
-- MiniMarket 0.5.53: OPTIONAL demonstration of New, Popular, and Sale flags.
-- MySQL 8.4, database minimarket, prefix nnop3_. Run ONLY after upgrading
-- MiniMarket to 0.5.53. Does not need a new catalog import.
-- Changes only untouched demo products with exact TOY-/BUNDLE- SKU.
-- Re-running is harmless; existing user-edited badges are left alone.
-- All test promotions begin at SQL import time (UTC), not at 0.5.53 build time.
-- No DROP / DELETE / TRUNCATE / ALTER and no changes to sale prices or inventory.
USE `minimarket`;
START TRANSACTION;

-- New for 7 days + sale discount calculated from old price of 1990.
UPDATE `nnop3_minimarket_products`
SET old_price=1990.00,badge_sale=1,
    badge_new=1,badge_new_days=7,badge_new_since=UTC_TIMESTAMP()
WHERE id=1001 AND sku='TOY-1001' AND price=1490.00
  AND old_price IS NULL AND badge_sale=0 AND badge_new=0 AND badge_popular=0;

-- Popular for 30 days. Leave existing product price 2890 unchanged.
UPDATE `nnop3_minimarket_products`
SET badge_popular=1,badge_popular_days=30,badge_popular_since=UTC_TIMESTAMP()
WHERE id=1002 AND sku='TOY-1002'
  AND old_price IS NULL AND badge_sale=0 AND badge_new=0 AND badge_popular=0;

-- Indefinite New (days = NULL).
UPDATE `nnop3_minimarket_products`
SET badge_new=1,badge_new_days=NULL,badge_new_since=UTC_TIMESTAMP()
WHERE id=1003 AND sku='TOY-1003'
  AND old_price IS NULL AND badge_sale=0 AND badge_new=0 AND badge_popular=0;

-- Indefinite Popular.
UPDATE `nnop3_minimarket_products`
SET badge_popular=1,badge_popular_days=NULL,badge_popular_since=UTC_TIMESTAMP()
WHERE id=1004 AND sku='TOY-1004'
  AND old_price IS NULL AND badge_sale=0 AND badge_new=0 AND badge_popular=0;

-- Fixed-price bundle: own price 4990, old price 6990 => ceil(28.61...) = 29%.
UPDATE `nnop3_minimarket_products`
SET old_price=6990.00,badge_sale=1
WHERE id=1090 AND sku='BUNDLE-1090' AND product_type='bundle'
  AND bundle_price_mode='fixed' AND price=4990.00
  AND old_price IS NULL AND badge_sale=0 AND badge_new=0 AND badge_popular=0;

COMMIT;
SELECT id,title,sku,price,old_price,badge_sale,
       badge_new,badge_new_days,badge_new_since,
       badge_popular,badge_popular_days,badge_popular_since
FROM `nnop3_minimarket_products`
WHERE id IN (1001,1002,1003,1004,1090) ORDER BY id;

-- === BENEFIT AND CATEGORY VISIBILITY ===
-- MiniMarket 0.5.54 — OPTIONAL, additive examples for the already populated
-- demo catalog on MySQL 8.4, database minimarket, prefix nnop3_.
-- Install com_minimarket >= 0.5.54 first. This is NOT a schema migration.
-- Requires previous demo bundles and (for the savings examples) 0.5.52
-- percentage pricing mode or manually configured discounts.
-- Existing user-selected fields are never changed if already customized.
USE `minimarket`;
START TRANSACTION;
-- Demonstrate a category explicitly showing bundles (default inherit=-1).
UPDATE `nnop3_minimarket_categories`
SET show_bundles=1
WHERE id=101 AND alias='myagkie-igrushki' AND show_bundles=-1;
-- Show money saved on demo bundles only when percentage pricing was chosen.
UPDATE `nnop3_minimarket_products`
SET badge_benefit=1
WHERE id IN (1091,1092) AND sku=CONCAT('BUNDLE-',id)
  AND product_type='bundle' AND bundle_price_mode='discount'
  AND bundle_discount_pct>0 AND badge_benefit=0;
COMMIT;
SELECT id,title,sku,bundle_price_mode,bundle_discount_pct,badge_benefit
FROM `nnop3_minimarket_products` WHERE id IN (1090,1091,1092)
ORDER BY id;
SELECT id,title,show_products,show_bundles
FROM `nnop3_minimarket_categories` WHERE id=101;

-- === FINAL DEMO COUNTS ===
SELECT product_type,COUNT(*) AS entries
FROM `nnop3_minimarket_products` GROUP BY product_type;
SELECT b.sku,b.bundle_price_mode,b.bundle_discount_pct,b.badge_sale,b.badge_benefit,
  COUNT(i.id) AS components
FROM `nnop3_minimarket_products` b
LEFT JOIN `nnop3_minimarket_bundle_items` i ON i.bundle_product_id=b.id
WHERE b.product_type='bundle'
GROUP BY b.id,b.sku,b.bundle_price_mode,b.bundle_discount_pct,b.badge_sale,b.badge_benefit
ORDER BY b.id;

-- === 0.5.59 ADDITIONAL DEMO MEDIA ===
-- Import only after 0.5.59 schema upgrade. Media files must be extracted separately.
USE `minimarket`;
START TRANSACTION;
INSERT INTO `nnop3_minimarket_product_images`
 (product_id,path,alt,sort_order)
SELECT p.id,x.path,x.alt,x.sort_order FROM (
SELECT 1001 AS product_id, 'TOY-1001' AS expected_sku, 'images/minimarket/sample-01.png' AS path, 'TOY-1001 / angle 1' AS alt, 1 AS sort_order
UNION ALL
SELECT 1001 AS product_id, 'TOY-1001' AS expected_sku, 'images/minimarket/sample-02.png' AS path, 'TOY-1001 / angle 2' AS alt, 2 AS sort_order
UNION ALL
SELECT 1012 AS product_id, 'TOY-1012' AS expected_sku, 'images/minimarket/sample-03.png' AS path, 'TOY-1012 / angle 1' AS alt, 1 AS sort_order
UNION ALL
SELECT 1012 AS product_id, 'TOY-1012' AS expected_sku, 'images/minimarket/sample-04.png' AS path, 'TOY-1012 / angle 2' AS alt, 2 AS sort_order
UNION ALL
SELECT 1016 AS product_id, 'TOY-1016' AS expected_sku, 'images/minimarket/sample-05.png' AS path, 'TOY-1016 / angle 1' AS alt, 1 AS sort_order
UNION ALL
SELECT 1016 AS product_id, 'TOY-1016' AS expected_sku, 'images/minimarket/sample-06.png' AS path, 'TOY-1016 / angle 2' AS alt, 2 AS sort_order
UNION ALL
SELECT 1048 AS product_id, 'TOY-1048' AS expected_sku, 'images/minimarket/sample-07.png' AS path, 'TOY-1048 / angle 1' AS alt, 1 AS sort_order
UNION ALL
SELECT 1048 AS product_id, 'TOY-1048' AS expected_sku, 'images/minimarket/sample-08.png' AS path, 'TOY-1048 / angle 2' AS alt, 2 AS sort_order
UNION ALL
SELECT 1054 AS product_id, 'TOY-1054' AS expected_sku, 'images/minimarket/sample-09.png' AS path, 'TOY-1054 / angle 1' AS alt, 1 AS sort_order
UNION ALL
SELECT 1054 AS product_id, 'TOY-1054' AS expected_sku, 'images/minimarket/sample-10.png' AS path, 'TOY-1054 / angle 2' AS alt, 2 AS sort_order
UNION ALL
SELECT 1072 AS product_id, 'TOY-1072' AS expected_sku, 'images/minimarket/sample-01.png' AS path, 'TOY-1072 / angle 1' AS alt, 1 AS sort_order
UNION ALL
SELECT 1072 AS product_id, 'TOY-1072' AS expected_sku, 'images/minimarket/sample-03.png' AS path, 'TOY-1072 / angle 2' AS alt, 2 AS sort_order
UNION ALL
SELECT 1002 AS product_id, 'TOY-1002' AS expected_sku, 'images/minimarket/sample-02.png' AS path, 'TOY-1002 / angle 1' AS alt, 1 AS sort_order
UNION ALL
SELECT 1002 AS product_id, 'TOY-1002' AS expected_sku, 'images/minimarket/sample-04.png' AS path, 'TOY-1002 / angle 2' AS alt, 2 AS sort_order
UNION ALL
SELECT 1090 AS product_id, 'BUNDLE-1090' AS expected_sku, 'images/minimarket/sample-05.png' AS path, 'BUNDLE-1090 / angle 1' AS alt, 1 AS sort_order
UNION ALL
SELECT 1090 AS product_id, 'BUNDLE-1090' AS expected_sku, 'images/minimarket/sample-07.png' AS path, 'BUNDLE-1090 / angle 2' AS alt, 2 AS sort_order
UNION ALL
SELECT 1091 AS product_id, 'BUNDLE-1091' AS expected_sku, 'images/minimarket/sample-06.png' AS path, 'BUNDLE-1091 / angle 1' AS alt, 1 AS sort_order
UNION ALL
SELECT 1091 AS product_id, 'BUNDLE-1091' AS expected_sku, 'images/minimarket/sample-08.png' AS path, 'BUNDLE-1091 / angle 2' AS alt, 2 AS sort_order
UNION ALL
SELECT 1092 AS product_id, 'BUNDLE-1092' AS expected_sku, 'images/minimarket/sample-09.png' AS path, 'BUNDLE-1092 / angle 1' AS alt, 1 AS sort_order
UNION ALL
SELECT 1092 AS product_id, 'BUNDLE-1092' AS expected_sku, 'images/minimarket/sample-10.png' AS path, 'BUNDLE-1092 / angle 2' AS alt, 2 AS sort_order
) x
JOIN `nnop3_minimarket_products` p ON p.id=x.product_id AND p.sku=x.expected_sku
WHERE NOT EXISTS (SELECT 1 FROM `nnop3_minimarket_product_images` old
 WHERE old.product_id=p.id AND old.path=x.path);

INSERT INTO `nnop3_minimarket_product_videos`
 (product_id,title,source,poster,is_promo,sort_order)
SELECT p.id,'Промо «КосмоБлок» (тест)','images/minimarket/demo-promo.mp4',
 'images/minimarket/sample-03.png',1,0
FROM `nnop3_minimarket_products` p
WHERE p.id=1012 AND p.sku='TOY-1012' AND p.product_type='simple'
 AND NOT EXISTS (SELECT 1 FROM `nnop3_minimarket_product_videos` v
 WHERE v.product_id=p.id AND v.source='images/minimarket/demo-promo.mp4');

UPDATE `nnop3_minimarket_products`
SET badge_sale_display='money'
WHERE id=1090 AND sku='BUNDLE-1090' AND badge_sale=1 AND badge_sale_display='percent';
UPDATE `nnop3_minimarket_products`
SET badge_benefit_display='percent'
WHERE id=1091 AND sku='BUNDLE-1091' AND badge_benefit=1 AND badge_benefit_display='money';
COMMIT;
