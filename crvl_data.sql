-- ============================================================
-- CRVL (Central Region Volleyball League) 2026 Season Data
-- Source: crvleague.com  |  All passwords: "password"
-- Run against an empty volleytrack database, or after database.sql
-- ============================================================
USE volleytrack;

SET FOREIGN_KEY_CHECKS=0;
TRUNCATE activity_log; TRUNCATE stream_chat; TRUNCATE stream_cameras;
TRUNCATE news_articles; TRUNCATE league_standings; TRUNCATE player_actions;
TRUNCATE match_sets; TRUNCATE matches; TRUNCATE players;
TRUNCATE seasons; TRUNCATE teams; TRUNCATE users;
SET FOREIGN_KEY_CHECKS=1;

-- ============================================================
-- USERS  (password = "password" for all)
-- ============================================================
INSERT INTO users (id,username,email,password_hash,full_name,role,is_active,last_login) VALUES
(1,'admin',   'admin@crvl.mw',  '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','CRVL Administrator','Admin',  1,'2026-05-14 08:00:00'),
(2,'jphiri',  'j.phiri@crvl.mw','$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','James Phiri',       'Referee', 1,'2026-05-14 09:00:00'),
(3,'gbanda',  'g.banda@crvl.mw','$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','Grace Banda',       'Scorer',  1,'2026-05-14 10:00:00'),
(4,'pmwale',  'p.mwale@crvl.mw','$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','Peter Mwale',       'Scorer',  1,'2026-05-10 14:00:00'),
(5,'mgondwe', 'fan@crvl.mw',    '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','Mary Gondwe',       'Viewer',  1,'2026-05-14 11:30:00');

-- ============================================================
-- SEASONS
-- ============================================================
INSERT INTO seasons (id,name,type,category,start_date,end_date,status) VALUES
(1,'CRVL League A Men 2026',   'League','League A Men',  '2026-04-01','2026-11-30','Active'),
(2,'CRVL Senior Ladies 2026',  'League','League A Women','2026-04-01','2026-11-30','Active'),
(3,'CRVL Category B Men 2026', 'League','League B Men',  '2026-04-01','2026-11-30','Active'),
(4,'CRVL Junior Ladies 2026',  'League','League B Women','2026-04-01','2026-11-30','Active'),
(5,'CRVL League A Men 2025',   'League','League A Men',  '2025-04-01','2025-11-30','Completed');

-- ============================================================
-- TEAMS (1-12 League A Men, 13-19 Senior Ladies,
--        20-26 Cat B Men, 27-31 Junior Ladies)
-- ============================================================
INSERT INTO teams (id,name,short_name,home_court,city,coach,color_primary,is_active) VALUES
(1, 'Blue Eagles',         'BLE','KIT Sports Hall, Area 49',   'Lilongwe','Coach B. Chimwemwe','#1565C0',1),
(2, 'Mipuniro Spikers',    'MIP','Mipuniro Sports Complex',    'Lilongwe','Coach M. Nyirenda', '#C62828',1),
(3, 'NRC',                 'NRC','NRC Sports Hall',             'Lilongwe','Coach T. Banda',    '#2E7D32',1),
(4, 'Bunda',               'BUN','Bunda College Hall',          'Lilongwe','Coach F. Kachingwe','#E65100',1),
(5, 'Kamuzu Barracks',     'KAB','KB Gymnasium, Area 10',      'Lilongwe','Coach A. Msiska',   '#4A148C',1),
(6, 'Mafco',               'MAF','MAFCO Sports Hall',           'Lilongwe','Coach L. Mhone',    '#00695C',1),
(7, 'Midhala',             'MDH','Area 18 Sports Complex',      'Lilongwe','Coach K. Gondwe',   '#558B2F',1),
(8, 'Msunga',              'MSU','Msunga Sports Hall',          'Lilongwe','Coach N. Phiri',    '#F57F17',1),
(9, 'Parachute',           'PAR','Parachute Battalion Hall',    'Lilongwe','Coach P. Chirwa',   '#0277BD',1),
(10,'Wolves',              'WOL','Wolves Sports Ground',        'Lilongwe','Coach S. Mwale',    '#424242',1),
(11,'Lilongwe Spikers',    'LLS','City Assembly Hall',          'Lilongwe','Coach R. Tembo',    '#283593',1),
(12,'Snipers',             'SNI','Snipers Grounds, Area 25',   'Lilongwe','Coach G. Nkosi',    '#6D4C41',1),
(13,'Archeas',             'ARC','Area 47 Community Hall',      'Lilongwe','Coach H. Banda',    '#AD1457',1),
(14,'Blue Eagles Ladies',  'BEL','KIT Sports Hall, Area 49',   'Lilongwe','Coach C. Gondwe',   '#1565C0',1),
(15,'Kamuzu Barracks Ladies','KBL','KB Gymnasium, Area 10',    'Lilongwe','Coach A. Chirwa',   '#4A148C',1),
(16,'Lilongwe Gemz',       'GEM','Civic Offices Hall',          'Lilongwe','Coach J. Phiri',    '#00838F',1),
(17,'Mipuniro Red-Li',     'MRL','Mipuniro Sports Complex',    'Lilongwe','Coach M. Mwale',    '#C62828',1),
(18,'Vixens',              'VIX','Kamuzu Stadium Annex',        'Lilongwe','Coach R. Tembo',    '#BF360C',1),
(19,'Wolves Ladies',       'WLL','Wolves Sports Ground',        'Lilongwe','Coach F. Mkwanda',  '#424242',1),
(20,'Arrows',              'ARR','Arrows Ground, Kasungu Rd',   'Lilongwe','Coach C. Kamwendo', '#F9A825',1),
(21,'Kasiya Spartans',     'KSP','Kasiya Sports Hall',          'Kasiya',  'Coach D. Nyirenda', '#1B5E20',1),
(22,'Mchinji Trickers',    'MCT','Mchinji Boma Hall',           'Mchinji', 'Coach E. Mphande',  '#880E4F',1),
(23,'Minthopa',            'MIN','Minthopa Community Hall',     'Dowa',    'Coach F. Zilira',   '#01579B',1),
(24,'Mvera Armor',         'MVA','Mvera Sports Ground',         'Dowa',    'Coach G. Kachingwe','#3E2723',1),
(25,'Waliranji',           'WAL','Waliranji Hall, Salima Rd',   'Lilongwe','Coach H. Matewere', '#B71C1C',1),
(26,'Wolves Aces',         'WAC','Wolves Sports Ground',        'Lilongwe','Coach I. Mvula',    '#616161',1),
(27,'Bunda Spartans',      'BSP','Bunda College Hall',          'Lilongwe','Coach J. Gondwe',   '#E91E63',1),
(28,'Kasiya Spartanz',     'KSZ','Kasiya Sports Hall',          'Kasiya',  'Coach K. Phiri',    '#FF8F00',1),
(29,'Minthopa She',        'MSH','Minthopa Community Hall',     'Dowa',    'Coach L. Banda',    '#7B1FA2',1),
(30,'Spixens',             'SPX','Area 23 Sports Hall',         'Lilongwe','Coach M. Chirwa',   '#8E24AA',1),
(31,'Wolves Cubs',         'WCB','Wolves Sports Ground',        'Lilongwe','Coach N. Mwale',    '#757575',1);

-- ============================================================
-- PLAYERS  (8 per team = 248 total)
-- ============================================================
INSERT INTO players (id,team_id,first_name,last_name,jersey_number,position,height_cm,weight_kg,date_of_birth) VALUES
-- Blue Eagles (1-8)
(1,1,'Chisomo','Banda',1,'Setter',186,78.0,'1998-03-12'),
(2,1,'Kondwani','Phiri',7,'Outside Hitter',193,86.0,'1996-07-25'),
(3,1,'Takondwa','Mwale',10,'Middle Blocker',199,91.0,'1997-11-08'),
(4,1,'Blessings','Tembo',3,'Opposite Hitter',195,88.0,'1999-01-30'),
(5,1,'Wongani','Chirwa',15,'Libero',177,68.0,'2000-05-17'),
(6,1,'Limbani','Nkosi',5,'Outside Hitter',190,83.0,'1997-09-04'),
(7,1,'Gracious','Mvula',8,'Middle Blocker',197,92.0,'1995-12-22'),
(8,1,'Francis','Gondwe',11,'Setter',184,76.0,'2001-06-14'),
-- Mipuniro Spikers (9-16)
(9,2,'Mtendere','Kamwendo',2,'Setter',187,79.0,'1997-02-18'),
(10,2,'Innocent','Mkwanda',9,'Outside Hitter',192,85.0,'1998-11-05'),
(11,2,'Dziko','Kapanda',12,'Middle Blocker',200,93.0,'1996-06-30'),
(12,2,'Yankho','Lungu',4,'Opposite Hitter',194,87.0,'1999-09-14'),
(13,2,'Mphatso','Nyirenda',16,'Libero',176,67.0,'2001-01-07'),
(14,2,'Raphael','Chikuse',7,'Outside Hitter',191,83.0,'1997-07-22'),
(15,2,'Kenneth','Nkhata',11,'Middle Blocker',198,91.0,'1995-03-15'),
(16,2,'Joseph','Chisale',1,'Setter',185,77.0,'2000-12-01'),
-- NRC (17-24)
(17,3,'Daniel','Phiri',3,'Setter',185,78.0,'1999-05-12'),
(18,3,'Elias','Kumwenda',9,'Outside Hitter',191,84.0,'1997-01-29'),
(19,3,'Samuel','Kaunda',7,'Middle Blocker',197,91.0,'1996-08-16'),
(20,3,'George','Mbewe',5,'Opposite Hitter',194,87.0,'1998-12-04'),
(21,3,'Richard','Nkosi',16,'Libero',175,67.0,'2000-09-23'),
(22,3,'Simon','Gondwe',11,'Outside Hitter',189,82.0,'1998-03-08'),
(23,3,'Albert','Mwale',12,'Middle Blocker',198,92.0,'1994-06-25'),
(24,3,'Bernard','Chirwa',2,'Setter',184,76.0,'2001-04-17'),
-- Bunda (25-32)
(25,4,'Dalitso','Mponda',8,'Setter',185,77.0,'1998-08-21'),
(26,4,'Chifundo','Mwenifumbo',11,'Outside Hitter',191,84.0,'1997-04-09'),
(27,4,'Tayamika','Mbewe',6,'Middle Blocker',196,90.0,'1999-02-14'),
(28,4,'Oliver','Nyasulu',4,'Opposite Hitter',193,87.0,'1996-10-31'),
(29,4,'Harrison','Ntchisi',15,'Libero',176,66.0,'2001-07-03'),
(30,4,'Austin','Kachingwe',3,'Outside Hitter',189,82.0,'1998-01-25'),
(31,4,'Moses','Matewere',12,'Middle Blocker',197,91.0,'1995-09-18'),
(32,4,'Eric','Mphande',1,'Setter',183,75.0,'2001-11-11'),
-- Kamuzu Barracks (33-40)
(33,5,'Nathan','Zilira',9,'Setter',186,79.0,'1996-06-06'),
(34,5,'Patrick','Gama',14,'Outside Hitter',193,86.0,'1997-03-19'),
(35,5,'Andrew','Mkandawire',7,'Middle Blocker',200,93.0,'1994-12-07'),
(36,5,'Collins','Chisale',10,'Opposite Hitter',194,88.0,'1997-08-28'),
(37,5,'Kondwani','Kamwendo',3,'Libero',175,67.0,'2000-03-15'),
(38,5,'Thandiwe','Msiska',5,'Outside Hitter',190,83.0,'1998-01-20'),
(39,5,'Lovemore','Kalua',11,'Middle Blocker',199,92.0,'1996-07-09'),
(40,5,'Emmanuel','Banda',2,'Setter',185,78.0,'1999-11-30'),
-- Mafco (41-48)
(41,6,'Steven','Phiri',6,'Setter',184,77.0,'1999-02-14'),
(42,6,'Felix','Banda',8,'Outside Hitter',190,83.0,'1998-06-28'),
(43,6,'Golden','Tembo',4,'Middle Blocker',197,91.0,'1997-10-03'),
(44,6,'Grant','Gondwe',3,'Opposite Hitter',193,87.0,'1996-04-21'),
(45,6,'Happy','Chirwa',15,'Libero',176,66.0,'2001-08-14'),
(46,6,'James','Mwale',7,'Outside Hitter',189,82.0,'1998-12-11'),
(47,6,'John','Nkosi',12,'Middle Blocker',198,90.0,'1995-05-30'),
(48,6,'Lewis','Mvula',1,'Setter',186,78.0,'2000-09-07'),
-- Midhala (49-56)
(49,7,'Martin','Phiri',5,'Setter',185,77.0,'1999-03-22'),
(50,7,'Nelson','Banda',9,'Outside Hitter',192,84.0,'1997-11-04'),
(51,7,'Owen','Mwale',7,'Middle Blocker',197,91.0,'1996-09-18'),
(52,7,'Peter','Gondwe',4,'Opposite Hitter',193,87.0,'1998-07-27'),
(53,7,'Robert','Chirwa',16,'Libero',175,66.0,'2001-02-13'),
(54,7,'Thomas','Tembo',11,'Outside Hitter',190,83.0,'1998-04-06'),
(55,7,'Victor','Nkosi',6,'Middle Blocker',198,92.0,'1995-01-19'),
(56,7,'Walter','Mkwanda',2,'Setter',184,76.0,'2000-10-31'),
-- Msunga (57-64)
(57,8,'Yohane','Phiri',3,'Setter',186,78.0,'1998-07-08'),
(58,8,'Zikomo','Banda',8,'Outside Hitter',191,84.0,'1997-05-24'),
(59,8,'Isaac','Mwale',7,'Middle Blocker',198,91.0,'1996-02-15'),
(60,8,'Emmanuel','Gondwe',4,'Opposite Hitter',194,88.0,'1999-10-03'),
(61,8,'Chisomo','Chirwa',15,'Libero',176,67.0,'2001-04-27'),
(62,8,'Kondwani','Tembo',11,'Outside Hitter',190,83.0,'1998-08-16'),
(63,8,'Mphatso','Nkosi',12,'Middle Blocker',197,90.0,'1995-11-09'),
(64,8,'Innocent','Mvula',1,'Setter',185,77.0,'2000-06-22'),
-- Parachute (65-72)
(65,9,'Dziko','Phiri',6,'Setter',185,78.0,'1999-01-14'),
(66,9,'Yankho','Banda',9,'Outside Hitter',192,85.0,'1997-09-30'),
(67,9,'Mtendere','Mwale',7,'Middle Blocker',199,92.0,'1996-12-05'),
(68,9,'Raphael','Gondwe',4,'Opposite Hitter',193,87.0,'1998-05-21'),
(69,9,'Kenneth','Chirwa',16,'Libero',175,66.0,'2001-07-16'),
(70,9,'Joseph','Tembo',11,'Outside Hitter',190,83.0,'1998-02-04'),
(71,9,'Daniel','Nkosi',5,'Middle Blocker',198,91.0,'1995-08-23'),
(72,9,'Elias','Kamwendo',2,'Setter',184,76.0,'2000-03-10'),
-- Wolves (73-80)
(73,10,'Samuel','Phiri',3,'Setter',185,77.0,'1999-04-19'),
(74,10,'George','Banda',8,'Outside Hitter',191,84.0,'1997-12-07'),
(75,10,'Richard','Mwale',7,'Middle Blocker',197,91.0,'1996-10-23'),
(76,10,'Simon','Gondwe',4,'Opposite Hitter',193,87.0,'1998-06-11'),
(77,10,'Albert','Chirwa',15,'Libero',175,66.0,'2001-09-28'),
(78,10,'Bernard','Tembo',11,'Outside Hitter',190,82.0,'1998-03-16'),
(79,10,'Charles','Nkosi',6,'Middle Blocker',198,90.0,'1995-07-04'),
(80,10,'David','Mvula',1,'Setter',186,78.0,'2000-01-31'),
-- Lilongwe Spikers (81-88)
(81,11,'Takondwa','Phiri',5,'Setter',185,77.0,'1999-06-08'),
(82,11,'Blessings','Banda',9,'Outside Hitter',191,84.0,'1997-10-25'),
(83,11,'Wongani','Mwale',7,'Middle Blocker',197,90.0,'1996-04-12'),
(84,11,'Limbani','Gondwe',4,'Opposite Hitter',193,86.0,'1998-02-19'),
(85,11,'Gracious','Chirwa',16,'Libero',175,65.0,'2001-11-03'),
(86,11,'Francis','Tembo',11,'Outside Hitter',190,82.0,'1998-07-30'),
(87,11,'Chisomo','Nkosi',6,'Middle Blocker',198,91.0,'1995-03-18'),
(88,11,'Kondwani','Mkwanda',2,'Setter',184,76.0,'2000-08-14'),
-- Snipers (89-96)
(89,12,'Mphatso','Phiri',3,'Setter',184,77.0,'1999-08-27'),
(90,12,'Innocent','Banda',8,'Outside Hitter',191,84.0,'1997-06-14'),
(91,12,'Dziko','Mwale',7,'Middle Blocker',197,91.0,'1996-11-01'),
(92,12,'Yankho','Gondwe',4,'Opposite Hitter',193,87.0,'1998-09-18'),
(93,12,'Mtendere','Chirwa',16,'Libero',175,66.0,'2001-05-25'),
(94,12,'Raphael','Tembo',11,'Outside Hitter',190,83.0,'1998-01-12'),
(95,12,'Kenneth','Nkosi',6,'Middle Blocker',198,90.0,'1995-10-07'),
(96,12,'Joseph','Kamwendo',1,'Setter',185,77.0,'2000-04-23'),
-- Archeas (97-104)
(97,13,'Grace','Phiri',3,'Setter',178,62.0,'2000-02-14'),
(98,13,'Mary','Banda',7,'Outside Hitter',182,67.0,'1998-08-21'),
(99,13,'Faith','Mwale',9,'Middle Blocker',186,71.0,'1997-12-05'),
(100,13,'Hope','Gondwe',4,'Opposite Hitter',183,68.0,'1999-06-18'),
(101,13,'Mercy','Chirwa',14,'Libero',169,59.0,'2001-03-30'),
(102,13,'Joy','Tembo',6,'Outside Hitter',181,66.0,'2000-10-11'),
(103,13,'Peace','Nkosi',11,'Middle Blocker',185,70.0,'1998-04-27'),
(104,13,'Patience','Mvula',2,'Setter',177,61.0,'2002-07-08'),
-- Blue Eagles Ladies (105-112)
(105,14,'Chimwemwe','Phiri',1,'Setter',179,63.0,'1999-01-15'),
(106,14,'Lindiwe','Banda',8,'Outside Hitter',183,68.0,'1997-07-04'),
(107,14,'Thandiwe','Mwale',10,'Middle Blocker',187,72.0,'1998-11-22'),
(108,14,'Mphatso','Gondwe',5,'Opposite Hitter',184,69.0,'2000-04-08'),
(109,14,'Tiwonge','Chirwa',15,'Libero',170,60.0,'2001-09-16'),
(110,14,'Chifundo','Tembo',6,'Outside Hitter',182,67.0,'1999-02-28'),
(111,14,'Mayamiko','Nkosi',12,'Middle Blocker',186,71.0,'1997-06-13'),
(112,14,'Fyness','Kamwendo',3,'Setter',178,62.0,'2002-01-20'),
-- Kamuzu Barracks Ladies (113-120)
(113,15,'Doreen','Phiri',2,'Setter',178,62.0,'2000-03-07'),
(114,15,'Esther','Banda',7,'Outside Hitter',182,67.0,'1998-09-15'),
(115,15,'Florence','Mwale',9,'Middle Blocker',187,72.0,'1997-07-29'),
(116,15,'Gertrude','Gondwe',4,'Opposite Hitter',184,69.0,'1999-12-03'),
(117,15,'Irene','Chirwa',14,'Libero',169,59.0,'2001-05-19'),
(118,15,'Josephine','Tembo',6,'Outside Hitter',181,66.0,'2000-11-26'),
(119,15,'Karen','Nkosi',11,'Middle Blocker',186,71.0,'1998-01-14'),
(120,15,'Loness','Mvula',1,'Setter',177,61.0,'2002-08-30'),
-- Lilongwe Gemz (121-128)
(121,16,'Monica','Phiri',3,'Setter',179,63.0,'1999-04-24'),
(122,16,'Nelius','Banda',8,'Outside Hitter',183,68.0,'1997-10-11'),
(123,16,'Ophelia','Mwale',10,'Middle Blocker',187,72.0,'1998-06-05'),
(124,16,'Priscilla','Gondwe',5,'Opposite Hitter',184,69.0,'2000-01-17'),
(125,16,'Ruth','Chirwa',15,'Libero',170,60.0,'2001-08-22'),
(126,16,'Suzanne','Tembo',6,'Outside Hitter',182,67.0,'1999-03-10'),
(127,16,'Agnes','Nkosi',12,'Middle Blocker',186,71.0,'1997-11-28'),
(128,16,'Bertha','Kamwendo',2,'Setter',178,62.0,'2002-05-15'),
-- Mipuniro Red-Li (129-136)
(129,17,'Diana','Phiri',1,'Setter',178,62.0,'2000-06-03'),
(130,17,'Eleanor','Banda',7,'Outside Hitter',182,67.0,'1998-12-18'),
(131,17,'Gloria','Mwale',9,'Middle Blocker',187,72.0,'1997-08-07'),
(132,17,'Hannah','Gondwe',4,'Opposite Hitter',183,68.0,'1999-10-24'),
(133,17,'Jane','Chirwa',14,'Libero',169,59.0,'2001-04-09'),
(134,17,'Lucy','Tembo',6,'Outside Hitter',181,66.0,'2000-07-21'),
(135,17,'Martha','Nkosi',11,'Middle Blocker',185,70.0,'1998-02-16'),
(136,17,'Nancy','Mkwanda',3,'Setter',177,61.0,'2002-09-04'),
-- Vixens (137-144)
(137,18,'Rose','Phiri',2,'Setter',179,63.0,'1999-07-13'),
(138,18,'Sarah','Banda',8,'Outside Hitter',183,68.0,'1997-05-29'),
(139,18,'Tina','Mwale',10,'Middle Blocker',187,72.0,'1998-10-16'),
(140,18,'Violet','Gondwe',5,'Opposite Hitter',184,69.0,'2000-02-04'),
(141,18,'Christina','Chirwa',15,'Libero',170,60.0,'2001-10-20'),
(142,18,'Chisomo','Tembo',6,'Outside Hitter',182,67.0,'1999-04-07'),
(143,18,'Kondwani','Nkosi',12,'Middle Blocker',186,71.0,'1997-12-25'),
(144,18,'Mphatso','Kamwendo',3,'Setter',178,62.0,'2002-03-12'),
-- Wolves Ladies (145-152)
(145,19,'Wongani','Phiri',1,'Setter',178,62.0,'2000-08-19'),
(146,19,'Limbani','Banda',7,'Outside Hitter',182,67.0,'1998-04-06'),
(147,19,'Gracious','Mwale',9,'Middle Blocker',187,72.0,'1997-09-23'),
(148,19,'Francis','Gondwe',4,'Opposite Hitter',183,68.0,'1999-11-10'),
(149,19,'Takondwa','Chirwa',14,'Libero',169,59.0,'2001-06-28'),
(150,19,'Blessings','Tembo',6,'Outside Hitter',181,66.0,'2000-03-15'),
(151,19,'Dziko','Nkosi',11,'Middle Blocker',186,71.0,'1998-01-02'),
(152,19,'Yankho','Mvula',2,'Setter',177,61.0,'2002-10-17'),
-- Arrows (153-160)
(153,20,'Chisomo','Kamwendo',4,'Setter',184,77.0,'2000-01-09'),
(154,20,'Kondwani','Msiska',8,'Outside Hitter',190,83.0,'1999-07-26'),
(155,20,'Takondwa','Kalua',7,'Middle Blocker',196,90.0,'1998-03-14'),
(156,20,'Blessings','Banda',3,'Opposite Hitter',192,86.0,'2000-09-01'),
(157,20,'Wongani','Phiri',15,'Libero',175,65.0,'2002-05-18'),
(158,20,'Limbani','Gondwe',11,'Outside Hitter',189,82.0,'1999-11-07'),
(159,20,'Gracious','Chirwa',6,'Middle Blocker',196,90.0,'1997-08-24'),
(160,20,'Francis','Tembo',1,'Setter',184,76.0,'2001-02-11'),
-- Kasiya Spartans (161-168)
(161,21,'Mphatso','Kamwendo',3,'Setter',185,77.0,'2000-04-28'),
(162,21,'Innocent','Phiri',8,'Outside Hitter',191,84.0,'1999-10-15'),
(163,21,'Dziko','Banda',7,'Middle Blocker',197,91.0,'1998-06-02'),
(164,21,'Yankho','Mwale',4,'Opposite Hitter',193,87.0,'2000-12-19'),
(165,21,'Mtendere','Gondwe',16,'Libero',175,65.0,'2002-07-06'),
(166,21,'Raphael','Chirwa',11,'Outside Hitter',189,82.0,'1999-02-23'),
(167,21,'Kenneth','Tembo',6,'Middle Blocker',197,90.0,'1997-11-10'),
(168,21,'Joseph','Nkosi',1,'Setter',185,77.0,'2001-04-27'),
-- Mchinji Trickers (169-176)
(169,22,'Daniel','Kamwendo',5,'Setter',184,77.0,'2000-06-14'),
(170,22,'Elias','Phiri',9,'Outside Hitter',190,83.0,'1999-12-01'),
(171,22,'Samuel','Banda',7,'Middle Blocker',196,90.0,'1998-08-19'),
(172,22,'George','Mwale',3,'Opposite Hitter',192,86.0,'2000-03-06'),
(173,22,'Richard','Gondwe',15,'Libero',175,65.0,'2002-09-23'),
(174,22,'Simon','Chirwa',11,'Outside Hitter',189,82.0,'1999-05-10'),
(175,22,'Albert','Tembo',6,'Middle Blocker',196,90.0,'1997-04-28'),
(176,22,'Bernard','Nkosi',1,'Setter',184,76.0,'2001-07-15'),
-- Minthopa (177-184)
(177,23,'Charles','Kamwendo',4,'Setter',185,77.0,'2000-08-31'),
(178,23,'David','Phiri',8,'Outside Hitter',191,84.0,'1999-04-18'),
(179,23,'Emmanuel','Banda',7,'Middle Blocker',197,91.0,'1998-10-05'),
(180,23,'Steven','Mwale',3,'Opposite Hitter',193,87.0,'2000-06-22'),
(181,23,'Felix','Gondwe',16,'Libero',175,65.0,'2002-01-09'),
(182,23,'Golden','Chirwa',11,'Outside Hitter',189,82.0,'1999-08-26'),
(183,23,'Grant','Tembo',6,'Middle Blocker',197,90.0,'1997-07-13'),
(184,23,'Happy','Nkosi',1,'Setter',185,77.0,'2001-10-30'),
-- Mvera Armor (185-192)
(185,24,'James','Kamwendo',5,'Setter',184,77.0,'2000-11-16'),
(186,24,'John','Phiri',9,'Outside Hitter',190,83.0,'1999-07-03'),
(187,24,'Lewis','Banda',7,'Middle Blocker',196,90.0,'1998-12-21'),
(188,24,'Martin','Mwale',3,'Opposite Hitter',192,86.0,'2000-05-08'),
(189,24,'Nelson','Gondwe',15,'Libero',175,65.0,'2002-02-25'),
(190,24,'Owen','Chirwa',11,'Outside Hitter',189,82.0,'1999-10-12'),
(191,24,'Peter','Tembo',6,'Middle Blocker',196,90.0,'1997-09-29'),
(192,24,'Robert','Nkosi',1,'Setter',184,76.0,'2001-12-16'),
-- Waliranji (193-200)
(193,25,'Thomas','Kamwendo',4,'Setter',185,77.0,'2000-02-02'),
(194,25,'Victor','Phiri',8,'Outside Hitter',191,84.0,'1999-06-19'),
(195,25,'Walter','Banda',7,'Middle Blocker',197,91.0,'1998-04-07'),
(196,25,'Yohane','Mwale',3,'Opposite Hitter',193,87.0,'2000-10-24'),
(197,25,'Zikomo','Gondwe',16,'Libero',175,65.0,'2002-07-11'),
(198,25,'Isaac','Chirwa',11,'Outside Hitter',189,82.0,'1999-01-28'),
(199,25,'Chisomo','Tembo',6,'Middle Blocker',197,90.0,'1997-12-15'),
(200,25,'Kondwani','Nkosi',1,'Setter',185,77.0,'2001-03-02'),
-- Wolves Aces (201-208)
(201,26,'Takondwa','Kamwendo',5,'Setter',184,77.0,'2000-09-19'),
(202,26,'Blessings','Phiri',9,'Outside Hitter',190,83.0,'1999-05-06'),
(203,26,'Wongani','Banda',7,'Middle Blocker',196,90.0,'1998-07-24'),
(204,26,'Limbani','Mwale',3,'Opposite Hitter',192,86.0,'2000-04-11'),
(205,26,'Gracious','Gondwe',15,'Libero',175,65.0,'2002-11-28'),
(206,26,'Francis','Chirwa',11,'Outside Hitter',189,82.0,'1999-03-15'),
(207,26,'Mphatso','Tembo',6,'Middle Blocker',196,90.0,'1997-10-02'),
(208,26,'Innocent','Nkosi',1,'Setter',185,77.0,'2001-06-19'),
-- Bunda Spartans W (209-216)
(209,27,'Chisomo','Phiri',2,'Setter',177,61.0,'2002-01-26'),
(210,27,'Kondwani','Banda',7,'Outside Hitter',181,65.0,'2001-07-14'),
(211,27,'Takondwa','Mwale',9,'Middle Blocker',185,70.0,'2000-11-01'),
(212,27,'Blessings','Gondwe',4,'Opposite Hitter',182,67.0,'2001-03-19'),
(213,27,'Wongani','Chirwa',14,'Libero',168,57.0,'2003-08-06'),
(214,27,'Limbani','Tembo',6,'Outside Hitter',180,64.0,'2001-12-23'),
(215,27,'Gracious','Nkosi',11,'Middle Blocker',185,70.0,'2000-05-10'),
(216,27,'Francis','Mvula',1,'Setter',177,61.0,'2002-10-27'),
-- Kasiya Spartanz W (217-224)
(217,28,'Mphatso','Phiri',3,'Setter',178,62.0,'2002-04-13'),
(218,28,'Innocent','Banda',8,'Outside Hitter',182,66.0,'2001-10-30'),
(219,28,'Dziko','Mwale',10,'Middle Blocker',186,71.0,'2000-08-17'),
(220,28,'Yankho','Gondwe',5,'Opposite Hitter',183,68.0,'2001-06-04'),
(221,28,'Mtendere','Chirwa',15,'Libero',169,58.0,'2003-01-21'),
(222,28,'Raphael','Tembo',6,'Outside Hitter',181,65.0,'2001-02-08'),
(223,28,'Kenneth','Nkosi',12,'Middle Blocker',186,71.0,'2000-04-25'),
(224,28,'Joseph','Kamwendo',1,'Setter',177,61.0,'2002-12-12'),
-- Minthopa She (225-232)
(225,29,'Daniel','Phiri',2,'Setter',178,62.0,'2002-06-29'),
(226,29,'Elias','Banda',7,'Outside Hitter',182,66.0,'2001-12-16'),
(227,29,'Samuel','Mwale',9,'Middle Blocker',186,71.0,'2000-10-03'),
(228,29,'George','Gondwe',4,'Opposite Hitter',183,68.0,'2001-08-20'),
(229,29,'Richard','Chirwa',14,'Libero',169,58.0,'2003-03-07'),
(230,29,'Simon','Tembo',6,'Outside Hitter',181,65.0,'2001-04-24'),
(231,29,'Albert','Nkosi',11,'Middle Blocker',186,71.0,'2000-06-11'),
(232,29,'Bernard','Mvula',3,'Setter',177,61.0,'2002-08-28'),
-- Spixens (233-240)
(233,30,'Charles','Phiri',1,'Setter',178,62.0,'2002-03-15'),
(234,30,'David','Banda',8,'Outside Hitter',182,66.0,'2001-09-02'),
(235,30,'Emmanuel','Mwale',10,'Middle Blocker',186,71.0,'2000-12-19'),
(236,30,'Steven','Gondwe',5,'Opposite Hitter',183,68.0,'2001-07-06'),
(237,30,'Felix','Chirwa',15,'Libero',169,58.0,'2003-02-23'),
(238,30,'Golden','Tembo',6,'Outside Hitter',181,65.0,'2001-01-10'),
(239,30,'Grant','Nkosi',12,'Middle Blocker',186,71.0,'2000-05-28'),
(240,30,'Happy','Kamwendo',3,'Setter',177,61.0,'2002-11-14'),
-- Wolves Cubs (241-248)
(241,31,'James','Phiri',2,'Setter',178,62.0,'2002-07-01'),
(242,31,'John','Banda',7,'Outside Hitter',182,66.0,'2001-11-18'),
(243,31,'Lewis','Mwale',9,'Middle Blocker',186,71.0,'2000-09-05'),
(244,31,'Martin','Gondwe',4,'Opposite Hitter',183,68.0,'2001-05-22'),
(245,31,'Nelson','Chirwa',14,'Libero',169,58.0,'2003-04-09'),
(246,31,'Owen','Tembo',6,'Outside Hitter',181,65.0,'2001-02-26'),
(247,31,'Peter','Nkosi',11,'Middle Blocker',186,71.0,'2000-07-13'),
(248,31,'Robert','Mvula',3,'Setter',177,61.0,'2002-10-30');

-- ============================================================
-- MATCHES
-- Season 1 (League A Men 2026):
--   R1 m1-m6  (m1-m3 completed per crvleague.com, m4-m6 scheduled)
--   R2 m7-m12 (m7 completed, m8 LIVE, m9-m12 scheduled)
--   R3 m13-m18 (all scheduled)
-- Season 2 (Senior Ladies): m19-m22 scheduled
-- Season 3 (Cat B Men):     m23-m26 scheduled
-- Season 4 (Junior Ladies): m27-m28 scheduled
-- ============================================================
INSERT INTO matches (id,season_id,home_team_id,away_team_id,match_date,venue,round,status,
  home_sets_won,away_sets_won,set_scores,current_set,
  home_score_current_set,away_score_current_set,started_at,ended_at) VALUES
-- League A Men Round 1 (3 May 2026)
(1,1,1,11,'2026-05-03 09:00:00','KIT Sports Hall','Round 1','Completed',3,0,
  '[{"home":25,"away":20},{"home":25,"away":18},{"home":25,"away":22}]',3,0,0,
  '2026-05-03 09:05:00','2026-05-03 10:35:00'),
(2,1,2,3,'2026-05-03 11:00:00','Mipuniro Sports Complex','Round 1','Completed',3,0,
  '[{"home":25,"away":22},{"home":25,"away":18},{"home":25,"away":21}]',3,0,0,
  '2026-05-03 11:06:00','2026-05-03 12:30:00'),
(3,1,4,12,'2026-05-03 13:00:00','Bunda College Hall','Round 1','Completed',3,0,
  '[{"home":25,"away":17},{"home":25,"away":19},{"home":25,"away":23}]',3,0,0,
  '2026-05-03 13:04:00','2026-05-03 14:28:00'),
(4,1,5,6,'2026-05-03 09:00:00','KB Gymnasium, Area 10','Round 1','Scheduled',0,0,NULL,1,0,0,NULL,NULL),
(5,1,7,10,'2026-05-03 11:00:00','Area 18 Sports Complex','Round 1','Scheduled',0,0,NULL,1,0,0,NULL,NULL),
(6,1,8,9,'2026-05-03 13:00:00','Msunga Sports Hall','Round 1','Scheduled',0,0,NULL,1,0,0,NULL,NULL),
-- League A Men Round 2 (10 May 2026)
(7,1,3,4,'2026-05-10 09:00:00','NRC Sports Hall','Round 2','Completed',3,1,
  '[{"home":25,"away":22},{"home":21,"away":25},{"home":25,"away":20},{"home":25,"away":23}]',4,0,0,
  '2026-05-10 09:08:00','2026-05-10 10:55:00'),
(8,1,1,2,'2026-05-14 14:00:00','KIT Sports Hall','Round 2','Live',1,0,
  '[{"home":25,"away":21}]',2,14,11,
  '2026-05-14 14:05:00',NULL),
(9,1,11,12,'2026-05-17 09:00:00','City Assembly Hall','Round 2','Scheduled',0,0,NULL,1,0,0,NULL,NULL),
(10,1,6,7,'2026-05-17 11:00:00','MAFCO Sports Hall','Round 2','Scheduled',0,0,NULL,1,0,0,NULL,NULL),
(11,1,9,5,'2026-05-17 13:00:00','Parachute Battalion Hall','Round 2','Scheduled',0,0,NULL,1,0,0,NULL,NULL),
(12,1,10,8,'2026-05-17 15:00:00','Wolves Sports Ground','Round 2','Scheduled',0,0,NULL,1,0,0,NULL,NULL),
-- League A Men Round 3 (24 May 2026)
(13,1,2,4,'2026-05-24 09:00:00','Mipuniro Sports Complex','Round 3','Scheduled',0,0,NULL,1,0,0,NULL,NULL),
(14,1,1,3,'2026-05-24 11:00:00','KIT Sports Hall','Round 3','Scheduled',0,0,NULL,1,0,0,NULL,NULL),
(15,1,12,11,'2026-05-24 09:00:00','Snipers Grounds, Area 25','Round 3','Scheduled',0,0,NULL,1,0,0,NULL,NULL),
(16,1,5,7,'2026-05-24 11:00:00','KB Gymnasium, Area 10','Round 3','Scheduled',0,0,NULL,1,0,0,NULL,NULL),
(17,1,8,6,'2026-05-24 13:00:00','Msunga Sports Hall','Round 3','Scheduled',0,0,NULL,1,0,0,NULL,NULL),
(18,1,10,9,'2026-05-24 15:00:00','Wolves Sports Ground','Round 3','Scheduled',0,0,NULL,1,0,0,NULL,NULL),
-- Senior Ladies Round 1 (17 May 2026)
(19,2,14,13,'2026-05-17 09:00:00','KIT Sports Hall','Round 1','Scheduled',0,0,NULL,1,0,0,NULL,NULL),
(20,2,15,16,'2026-05-17 11:00:00','KB Gymnasium, Area 10','Round 1','Scheduled',0,0,NULL,1,0,0,NULL,NULL),
(21,2,17,18,'2026-05-17 09:00:00','Mipuniro Sports Complex','Round 1','Scheduled',0,0,NULL,1,0,0,NULL,NULL),
(22,2,19,13,'2026-05-24 09:00:00','Wolves Sports Ground','Round 1','Scheduled',0,0,NULL,1,0,0,NULL,NULL),
-- Category B Men Round 1 (17 May 2026)
(23,3,20,21,'2026-05-17 09:00:00','Arrows Ground, Kasungu Rd','Round 1','Scheduled',0,0,NULL,1,0,0,NULL,NULL),
(24,3,22,23,'2026-05-17 11:00:00','Mchinji Boma Hall','Round 1','Scheduled',0,0,NULL,1,0,0,NULL,NULL),
(25,3,24,25,'2026-05-17 09:00:00','Mvera Sports Ground','Round 1','Scheduled',0,0,NULL,1,0,0,NULL,NULL),
(26,3,26,20,'2026-05-24 09:00:00','Wolves Sports Ground','Round 1','Scheduled',0,0,NULL,1,0,0,NULL,NULL),
-- Junior Ladies Round 1 (17 May 2026)
(27,4,27,28,'2026-05-17 09:00:00','Bunda College Hall','Round 1','Scheduled',0,0,NULL,1,0,0,NULL,NULL),
(28,4,29,30,'2026-05-17 11:00:00','Minthopa Community Hall','Round 1','Scheduled',0,0,NULL,1,0,0,NULL,NULL);

-- ============================================================
-- MATCH SETS (completed matches)
-- ============================================================
INSERT INTO match_sets (match_id,set_number,home_score,away_score,winner,started_at,ended_at) VALUES
-- m1: Blue Eagles 3-0 Lilongwe Spikers
(1,1,25,20,'home','2026-05-03 09:05:00','2026-05-03 09:28:00'),
(1,2,25,18,'home','2026-05-03 09:30:00','2026-05-03 09:52:00'),
(1,3,25,22,'home','2026-05-03 09:54:00','2026-05-03 10:18:00'),
-- m2: Mipuniro 3-0 NRC
(2,1,25,22,'home','2026-05-03 11:06:00','2026-05-03 11:30:00'),
(2,2,25,18,'home','2026-05-03 11:32:00','2026-05-03 11:54:00'),
(2,3,25,21,'home','2026-05-03 11:56:00','2026-05-03 12:20:00'),
-- m3: Bunda 3-0 Snipers
(3,1,25,17,'home','2026-05-03 13:04:00','2026-05-03 13:24:00'),
(3,2,25,19,'home','2026-05-03 13:26:00','2026-05-03 13:48:00'),
(3,3,25,23,'home','2026-05-03 13:50:00','2026-05-03 14:14:00'),
-- m7: NRC 3-1 Bunda
(7,1,25,22,'home','2026-05-10 09:08:00','2026-05-10 09:33:00'),
(7,2,21,25,'away','2026-05-10 09:35:00','2026-05-10 10:00:00'),
(7,3,25,20,'home','2026-05-10 10:02:00','2026-05-10 10:25:00'),
(7,4,25,23,'home','2026-05-10 10:27:00','2026-05-10 10:52:00'),
-- m8: Blue Eagles vs Mipuniro (live — set 1 done)
(8,1,25,21,'home','2026-05-14 14:05:00','2026-05-14 14:29:00');

-- ============================================================
-- LEAGUE STANDINGS
-- Season 1 (League A Men 2026) — after Round 1 + NRC vs Bunda
-- Blue Eagles 3pts | Mipuniro 3pts | NRC 3pts | Bunda 3pts
-- LLS/Snipers 0pts | others 0pts
-- ============================================================
INSERT INTO league_standings (season_id,team_id,matches_played,matches_won,matches_lost,sets_won,sets_lost,points,set_ratio) VALUES
(1,1, 1,1,0,3,0, 3,3.000), -- Blue Eagles (3-0 record)
(1,2, 1,1,0,3,0, 3,3.000), -- Mipuniro Spikers
(1,3, 2,1,1,3,4, 3,0.750), -- NRC (beat Bunda 3-1, lost to Mipuniro 0-3)
(1,4, 2,1,1,4,3, 3,1.333), -- Bunda (beat Snipers 3-0, lost to NRC 1-3)
(1,5, 0,0,0,0,0, 0,0.000), -- Kamuzu Barracks
(1,6, 0,0,0,0,0, 0,0.000), -- Mafco
(1,7, 0,0,0,0,0, 0,0.000), -- Midhala
(1,8, 0,0,0,0,0, 0,0.000), -- Msunga
(1,9, 0,0,0,0,0, 0,0.000), -- Parachute
(1,10,0,0,0,0,0, 0,0.000), -- Wolves
(1,11,1,0,1,0,3, 0,0.000), -- Lilongwe Spikers
(1,12,1,0,1,0,3, 0,0.000), -- Snipers
-- Season 2 (Senior Ladies 2026) — no matches played yet
(2,13,0,0,0,0,0,0,0.000),(2,14,0,0,0,0,0,0,0.000),(2,15,0,0,0,0,0,0,0.000),
(2,16,0,0,0,0,0,0,0.000),(2,17,0,0,0,0,0,0,0.000),(2,18,0,0,0,0,0,0,0.000),
(2,19,0,0,0,0,0,0,0.000),
-- Season 3 (Category B Men 2026)
(3,20,0,0,0,0,0,0,0.000),(3,21,0,0,0,0,0,0,0.000),(3,22,0,0,0,0,0,0,0.000),
(3,23,0,0,0,0,0,0,0.000),(3,24,0,0,0,0,0,0,0.000),(3,25,0,0,0,0,0,0,0.000),
(3,26,0,0,0,0,0,0,0.000),
-- Season 4 (Junior Ladies 2026)
(4,27,0,0,0,0,0,0,0.000),(4,28,0,0,0,0,0,0,0.000),(4,29,0,0,0,0,0,0,0.000),
(4,30,0,0,0,0,0,0,0.000),(4,31,0,0,0,0,0,0,0.000),
-- Season 5 (League A Men 2025 — Kamuzu Barracks champions)
(5,5, 10,8,2,26,10,24,2.600), -- Kamuzu Barracks
(5,1, 10,7,3,23,13,21,1.769), -- Blue Eagles
(5,2, 10,6,4,20,16,18,1.250), -- Mipuniro Spikers
(5,4, 10,5,5,17,19,15,0.895), -- Bunda
(5,3, 10,4,6,15,20,12,0.750), -- NRC
(5,11,10,3,7,12,22, 9,0.545), -- Lilongwe Spikers
(5,6, 10,2,8, 9,24, 6,0.375), -- Mafco
(5,7, 10,1,9, 5,27, 3,0.185); -- Midhala

-- ============================================================
-- PLAYER ACTIONS (key highlights from completed matches)
-- ============================================================
INSERT INTO player_actions (match_id,set_number,player_id,team_id,action_type,action_result,home_score_after,away_score_after) VALUES
-- m1 Set 1: Blue Eagles vs Lilongwe Spikers
(1,1,1,1,'Set Assist','Success',0,0),
(1,1,2,1,'Attack Kill','Success',1,0),
(1,1,81,11,'Set Assist','Success',1,0),
(1,1,82,11,'Attack Kill','Success',1,1),
(1,1,3,1,'Block','Success',2,1),
(1,1,2,1,'Ace','Success',3,1),
(1,1,6,1,'Attack Kill','Success',4,1),
(1,1,85,11,'Reception','Success',4,1),
(1,1,84,11,'Attack Kill','Success',4,2),
(1,1,5,1,'Reception','Success',4,2),
(1,1,4,1,'Attack Kill','Success',5,2),
(1,1,82,11,'Attack Blocked','Error',6,2),
(1,1,7,1,'Block','Success',7,2),
(1,1,81,11,'Ace','Success',7,3),
(1,1,2,1,'Attack Kill','Success',8,3),
(1,1,3,1,'Block','Success',9,3),
(1,1,1,1,'Set Assist','Success',9,3),
(1,1,6,1,'Attack Kill','Success',10,3),
(1,1,86,11,'Attack Kill','Success',10,4),
(1,1,2,1,'Attack Kill','Success',11,4),
-- m1 Set 2
(1,2,1,1,'Set Assist','Success',0,0),
(1,2,7,1,'Block','Success',1,0),
(1,2,2,1,'Ace','Success',2,0),
(1,2,83,11,'Attack Kill','Success',2,1),
(1,2,4,1,'Attack Kill','Success',3,1),
(1,2,3,1,'Middle Blocker','Success',3,1),
(1,2,6,1,'Attack Kill','Success',4,1),
-- m2 Set 1: Mipuniro vs NRC
(2,1,9,2,'Set Assist','Success',0,0),
(2,1,10,2,'Attack Kill','Success',1,0),
(2,1,11,2,'Block','Success',2,0),
(2,1,17,3,'Set Assist','Success',2,0),
(2,1,18,3,'Attack Kill','Success',2,1),
(2,1,12,2,'Attack Kill','Success',3,1),
(2,1,9,2,'Ace','Success',4,1),
(2,1,19,3,'Block','Success',4,2),
(2,1,10,2,'Attack Kill','Success',5,2),
(2,1,11,2,'Block','Success',6,2),
(2,1,14,2,'Attack Kill','Success',7,2),
(2,1,21,3,'Dig','Success',7,2),
(2,1,18,3,'Attack Kill','Success',7,3),
(2,1,12,2,'Attack Kill','Success',8,3),
(2,1,10,2,'Ace','Success',9,3),
-- m3 Set 1: Bunda vs Snipers
(3,1,25,4,'Set Assist','Success',0,0),
(3,1,26,4,'Attack Kill','Success',1,0),
(3,1,27,4,'Block','Success',2,0),
(3,1,25,4,'Ace','Success',3,0),
(3,1,89,12,'Service Error','Error',4,0),
(3,1,28,4,'Attack Kill','Success',5,0),
(3,1,90,12,'Attack Kill','Success',5,1),
(3,1,27,4,'Block','Success',6,1),
(3,1,26,4,'Attack Kill','Success',7,1),
(3,1,30,4,'Attack Kill','Success',8,1),
(3,1,91,12,'Attack Blocked','Error',9,1),
(3,1,27,4,'Block','Success',10,1),
-- m7 Set 1: NRC vs Bunda
(7,1,17,3,'Set Assist','Success',0,0),
(7,1,18,3,'Attack Kill','Success',1,0),
(7,1,19,3,'Block','Success',2,0),
(7,1,25,4,'Set Assist','Success',2,0),
(7,1,26,4,'Attack Kill','Success',2,1),
(7,1,23,3,'Block','Success',3,1),
(7,1,17,3,'Ace','Success',4,1),
(7,1,27,4,'Block','Success',4,2),
(7,1,20,3,'Attack Kill','Success',5,2),
(7,1,28,4,'Attack Kill','Success',5,3),
(7,1,18,3,'Attack Kill','Success',6,3),
-- m7 Set 2 (Bunda wins)
(7,2,25,4,'Set Assist','Success',0,0),
(7,2,28,4,'Attack Kill','Success',0,1),
(7,2,27,4,'Block','Success',0,2),
(7,2,26,4,'Ace','Success',0,3),
(7,2,17,3,'Set Assist','Success',0,3),
(7,2,20,3,'Attack Kill','Success',1,3),
(7,2,29,4,'Dig','Success',1,3),
(7,2,26,4,'Attack Kill','Success',1,4),
-- m8 Set 1: Blue Eagles vs Mipuniro (completed set)
(8,1,1,1,'Set Assist','Success',0,0),
(8,1,2,1,'Attack Kill','Success',1,0),
(8,1,9,2,'Set Assist','Success',1,0),
(8,1,12,2,'Attack Kill','Success',1,1),
(8,1,3,1,'Block','Success',2,1),
(8,1,11,2,'Block','Success',2,2),
(8,1,2,1,'Ace','Success',3,2),
(8,1,10,2,'Attack Kill','Success',3,3),
(8,1,6,1,'Attack Kill','Success',4,3),
(8,1,14,2,'Attack Kill','Success',4,4),
(8,1,3,1,'Block','Success',5,4),
(8,1,2,1,'Attack Kill','Success',6,4),
(8,1,7,1,'Block','Success',7,4),
(8,1,12,2,'Attack Kill','Success',7,5),
(8,1,1,1,'Ace','Success',8,5),
(8,1,9,2,'Service Error','Error',9,5),
(8,1,2,1,'Attack Kill','Success',10,5),
(8,1,11,2,'Block','Success',10,6),
(8,1,6,1,'Attack Kill','Success',11,6);

-- ============================================================
-- STREAM CAMERAS (live match m8)
-- ============================================================
INSERT INTO stream_cameras (id,match_id,label,stream_key,provider,status,is_primary,is_enabled,viewer_count,bitrate_kbps,resolution) VALUES
(1,8,'Main Court','vt-m8-main','RTMP_HLS','Live',1,1,218,3200,'1920x1080'),
(2,8,'Attack Camera','vt-m8-attack','RTMP_HLS','Live',0,1,134,2500,'1280x720'),
(3,8,'Defence Camera','vt-m8-def','RTMP_HLS','Offline',0,1,0,NULL,NULL);

-- ============================================================
-- STREAM CHAT (live match m8 camera 1)
-- ============================================================
INSERT INTO stream_chat (camera_id,user_id,guest_name,message,created_at) VALUES
(1,NULL,'CRVLFan1',    'Massive match! Blue Eagles vs Mipuniro — this decides top spot!','2026-05-14 14:08:00'),
(1,NULL,'LilongweLive','Kondwani Phiri already with 4 kills in set 1!',                 '2026-05-14 14:12:00'),
(1,NULL,'MipuniroZiko','Dziko Kapanda is a WALL at the net today',                      '2026-05-14 14:15:00'),
(1,5,   'Mary Gondwe', 'Great atmosphere at KIT hall — packed to capacity',             '2026-05-14 14:17:00'),
(1,NULL,'BlueEaglesFC','BLE winning set 1 25-21!! Lets go!!',                           '2026-05-14 14:30:00'),
(1,NULL,'CRVLAnalyst', 'Mtendere Kamwendo setting very well for Mipuniro but BLE block is solid', '2026-05-14 14:35:00'),
(1,NULL,'LilongweLive','Set 2 in progress — Mipuniro fighting back already',            '2026-05-14 14:37:00'),
(1,NULL,'VolleyMalawi','This is the best match of the 2026 season so far',             '2026-05-14 14:40:00'),
(2,NULL,'AttackCamFan','Watch how BLE are mixing up their attack angles',               '2026-05-14 14:20:00'),
(2,NULL,'TacticsGuru', 'Mipuniro shifting their block to track #7 Kondwani',            '2026-05-14 14:33:00');

-- ============================================================
-- NEWS ARTICLES
-- ============================================================
INSERT INTO news_articles (id,author_id,title,slug,summary,content,category,status,is_featured,views,published_at) VALUES
(1,1,'Blue Eagles Open CRVL 2026 Season with Dominant 3-0 Win','blue-eagles-open-2026-3-0',
'Blue Eagles sweep Lilongwe Spikers 3-0 in their Round 1 opener at KIT Sports Hall.',
'<p>Blue Eagles made a powerful statement on the opening day of the CRVL 2026 season, sweeping Lilongwe Spikers 3-0 at KIT Sports Hall in Area 49. Outside hitter Kondwani Phiri was outstanding with 14 kills across the three sets, earning the man of the match award.</p><p>The Eagles raced to 25-20 in the first set, with middle blocker Takondwa Mwale providing a solid defensive wall. The second set was equally commanding at 25-18 before Eagles closed out a 25-22 third set.</p><p>"We have been preparing hard and this performance is the result of that work," said Coach Chimwemwe. "The boys executed our game plan from the first whistle."</p><p>Lilongwe Spikers struggled to find their rhythm against the Eagles block and will need to improve significantly ahead of their Round 2 clash.</p>',
'Match Report','Published',1,312,'2026-05-03 12:00:00'),

(2,1,'Mipuniro Spikers Silence NRC in Round 1 Clash','mipuniro-silence-nrc-round-1',
'Mipuniro Spikers defeat NRC 3-0 with an impressive team performance at Mipuniro Sports Complex.',
'<p>Mipuniro Spikers produced a composed display to defeat NRC 3-0 at Mipuniro Sports Complex in Round 1 of the CRVL 2026 season. Setter Mtendere Kamwendo was the architect of the win, orchestrating the attack beautifully while middle blocker Dziko Kapanda dominated at the net with 7 blocks.</p><p>The match scores of 25-22, 25-18 and 25-21 reflect a comfortable victory despite NRC putting up a fight in the first set. Opposite hitter Yankho Lungu contributed 11 kills to seal the win.</p><p>"NRC are a good team and they made us work in the first set," said Coach Nyirenda. "But once we settled into our rhythm there was only one winner."</p>',
'Match Report','Published',0,241,'2026-05-03 13:30:00'),

(3,1,'Bunda Cruise Past Snipers to Make Early Statement','bunda-cruise-snipers-round-1',
'Bunda beat Snipers 3-0 in a comfortable Round 1 victory at Bunda College Hall.',
'<p>Bunda made light work of Snipers in a one-sided Round 1 affair at Bunda College Hall, winning 3-0 with set scores of 25-17, 25-19 and 25-23. Middle blocker Tayamika Mbewe was the standout performer, registering 8 blocks to neutralise the Snipers attack completely.</p><p>Setter Dalitso Mponda and outside hitter Chifundo Mwenifumbo combined well throughout, giving the Snipers defence no time to settle. Austin Kachingwe added 9 kills from the outside.</p><p>"This is the standard we want to set from day one," said coach Kachingwe. "Tayamika was exceptional — that is the kind of defensive performance that wins titles."</p>',
'Match Report','Published',0,198,'2026-05-03 15:00:00'),

(4,1,'NRC Bounce Back to Beat Bunda in Tight Round 2 Thriller','nrc-beat-bunda-round-2',
'NRC recover from their Round 1 loss to defeat Bunda 3-1 at NRC Sports Hall on 10 May.',
'<p>NRC showed tremendous resilience to bounce back from their Round 1 loss and defeat Bunda 3-1 at NRC Sports Hall in Round 2. The match went to four sets with Bunda levelling at 1-1 before NRC reasserted control to win 25-22, 21-25, 25-20, 25-23.</p><p>George Mbewe was the key figure for NRC with 16 kills as opposite hitter, while setter Daniel Phiri controlled the tempo superbly in the deciding sets. Bunda had their chances — outside hitter Chifundo Mwenifumbo was again excellent — but NRC held their nerve.</p><p>"Losing in Round 1 hurt us. We came back with something to prove today and the whole squad delivered," said NRC coach Banda.</p><p>Both teams now sit on 3 points with identical records of 1 win and 1 loss heading into Round 3.</p>',
'Match Report','Published',0,287,'2026-05-10 12:00:00'),

(5,1,'CRVL 2026 Preview: Who Will Challenge for the Title?','crvl-2026-season-preview',
'The Central Region Volleyball League returns for 2026 with four competitive categories and over 30 teams.',
'<p>The Central Region Volleyball League kicks off its 2026 season with more teams and more categories than ever before. Across the four divisions — League A Men, Senior Ladies, Category B Men and Junior Ladies — over 30 clubs will compete for honours in Lilongwe and the surrounding Central Region.</p><h3>League A Men — Defending Champions Kamuzu Barracks Favourite Again</h3><p>Kamuzu Barracks lifted the 2025 League A Men title in dominant fashion and are the team to beat again. However, Blue Eagles and Mipuniro Spikers have both strengthened over the off-season and look capable of ending KB''s reign.</p><p>Bunda and NRC will be looking to push into the top three, while Wolves, Mafco and Parachute are genuine dark horses who could spring surprises.</p><h3>Senior Ladies — Blue Eagles Ladies Defending Champions</h3><p>Blue Eagles Ladies lifted the 2025 women''s title and have retained their core squad. Wolves Ladies, who won back-to-back titles in 2023 and 2024, will be hungry to reclaim the crown. Vixens and Archeas have both invested in their rosters and could challenge for the podium.</p><h3>Category B Men — Wide Open</h3><p>The second tier men''s competition is perhaps the most open of all four categories. Kasiya Spartans have a strong home record at their Kasiya base, while Waliranji and Arrows bring experience. Mchinji Trickers travel the furthest of any team but are well organised.</p><h3>Junior Ladies — A Platform for Young Talent</h3><p>The Junior Ladies division provides vital competitive experience for younger players. All five clubs — Bunda Spartans, Kasiya Spartanz, Minthopa She, Spixens and Wolves Cubs — will be looking to develop their squads for future Senior Ladies campaigns.</p>',
'Analysis','Published',1,634,'2026-04-28 10:00:00'),

(6,1,'Kamuzu Barracks Crowned CRVL League A Men 2025 Champions','kb-crvl-champions-2025',
'Kamuzu Barracks lift the CRVL League A Men 2025 title after a superb season-long campaign.',
'<p>Kamuzu Barracks were crowned CRVL League A Men 2025 champions after a dominant season that saw them finish eight points clear at the top of the standings. The club, backed by the discipline and fitness of their military training, were the best side in the league from round one to the last.</p><p>Outside hitter Patrick Gama was the standout player of the campaign with 87 kills across the season, while middle blocker Andrew Mkandawire anchored a formidable defensive unit.</p><p>Blue Eagles Ladies claimed the 2025 Senior Ladies title in similarly convincing fashion, defending a 2024 title that had itself seen off the challenge of Wolves Ladies — the 2023 and 2022 champions.</p><p>Both champions receive automatic entry into the 2026 All-Region Invitational series.</p>',
'Tournament','Published',0,521,'2025-11-30 18:00:00'),

(7,1,'Blue Eagles vs Mipuniro: The Round 2 Clash CRVL Cannot Wait For','blue-eagles-mipuniro-round-2-preview',
'The two perfect teams from Round 1 meet today in what promises to be the match of the season so far.',
'<p>It is the match that CRVL fans have been waiting for since the Round 1 draw was made. Blue Eagles and Mipuniro Spikers — the only two sides with a perfect record in League A Men 2026 — meet at KIT Sports Hall today at 14:00 in what is already the most anticipated fixture of the young season.</p><p>Blue Eagles claimed a confident 3-0 win over Lilongwe Spikers in Round 1, with Kondwani Phiri in devastating form. Mipuniro were equally ruthless, sweeping NRC 3-0 with Dziko Kapanda bossing the net.</p><p>The result will have significant implications at the top of the League A Men standings, and with Kamuzu Barracks yet to play their first match, the winner today sets the tone for the rest of the season.</p><p>Kick-off is at 14:00. Check the live scoreboard for real-time updates throughout the match.</p>',
'Match Report','Published',1,445,'2026-05-14 10:00:00');

-- ============================================================
-- ACTIVITY LOG
-- ============================================================
INSERT INTO activity_log (user_id,action,target_type,target_id,created_at) VALUES
(1,'Created season: CRVL League A Men 2026',    'season',1,'2026-04-01 08:00:00'),
(1,'Created season: CRVL Senior Ladies 2026',   'season',2,'2026-04-01 08:05:00'),
(1,'Created season: CRVL Category B Men 2026',  'season',3,'2026-04-01 08:10:00'),
(1,'Created season: CRVL Junior Ladies 2026',   'season',4,'2026-04-01 08:15:00'),
(3,'Started live scoring: m1 Blue Eagles vs LLS','match',1,'2026-05-03 09:05:00'),
(3,'Match completed: Blue Eagles 3-0 LLS',      'match',1,'2026-05-03 10:35:00'),
(4,'Started live scoring: m2 Mipuniro vs NRC',  'match',2,'2026-05-03 11:06:00'),
(4,'Match completed: Mipuniro 3-0 NRC',         'match',2,'2026-05-03 12:30:00'),
(3,'Started live scoring: m3 Bunda vs Snipers', 'match',3,'2026-05-03 13:04:00'),
(3,'Match completed: Bunda 3-0 Snipers',        'match',3,'2026-05-03 14:28:00'),
(4,'Started live scoring: m7 NRC vs Bunda',     'match',7,'2026-05-10 09:08:00'),
(4,'Match completed: NRC 3-1 Bunda',            'match',7,'2026-05-10 10:55:00'),
(3,'Started live scoring: m8 Blue Eagles vs Mipuniro','match',8,'2026-05-14 14:05:00'),
(1,'Published: Blue Eagles win Round 1',        'news', 1,'2026-05-03 12:00:00'),
(1,'Published: Mipuniro win Round 1',           'news', 2,'2026-05-03 13:30:00'),
(1,'Published: Bunda win Round 1',              'news', 3,'2026-05-03 15:00:00'),
(1,'Published: NRC beat Bunda Round 2',         'news', 4,'2026-05-10 12:00:00'),
(1,'Published: 2026 Season Preview',            'news', 5,'2026-04-28 10:00:00');
