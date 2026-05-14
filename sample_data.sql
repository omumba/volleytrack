-- VolleyTrack Full Sample Data
-- Self-contained: creates all tables if missing, then loads full sample data
-- All user passwords are: password

USE volleytrack;

-- ============================================================
-- SCHEMA (CREATE IF NOT EXISTS — safe to run on existing DB)
-- ============================================================
CREATE TABLE IF NOT EXISTS teams (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  short_name VARCHAR(10) NOT NULL,
  home_court VARCHAR(150) DEFAULT NULL,
  city VARCHAR(100) DEFAULT NULL,
  coach VARCHAR(100) DEFAULT NULL,
  color_primary VARCHAR(7) DEFAULT '#555555',
  is_active TINYINT(1) DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS players (
  id INT AUTO_INCREMENT PRIMARY KEY,
  team_id INT NOT NULL,
  first_name VARCHAR(80) NOT NULL,
  last_name VARCHAR(80) NOT NULL,
  jersey_number INT NOT NULL,
  position ENUM('Setter','Outside Hitter','Middle Blocker','Opposite Hitter','Libero','Defensive Specialist') NOT NULL,
  height_cm INT DEFAULT NULL,
  weight_kg DECIMAL(5,2) DEFAULT NULL,
  date_of_birth DATE DEFAULT NULL,
  is_active TINYINT(1) DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (team_id) REFERENCES teams(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS seasons (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(150) NOT NULL,
  type ENUM('League','Cup','Friendly','Playoff') DEFAULT 'League',
  start_date DATE NOT NULL,
  end_date DATE DEFAULT NULL,
  status ENUM('Upcoming','Active','Completed') DEFAULT 'Upcoming',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS matches (
  id INT AUTO_INCREMENT PRIMARY KEY,
  season_id INT NOT NULL,
  home_team_id INT NOT NULL,
  away_team_id INT NOT NULL,
  match_date DATETIME NOT NULL,
  venue VARCHAR(150) DEFAULT NULL,
  round VARCHAR(50) DEFAULT NULL,
  status ENUM('Scheduled','Live','Completed','Postponed','Cancelled') DEFAULT 'Scheduled',
  home_sets_won INT DEFAULT 0,
  away_sets_won INT DEFAULT 0,
  set_scores JSON DEFAULT NULL,
  current_set INT DEFAULT 1,
  home_score_current_set INT DEFAULT 0,
  away_score_current_set INT DEFAULT 0,
  started_at TIMESTAMP NULL DEFAULT NULL,
  ended_at TIMESTAMP NULL DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (season_id) REFERENCES seasons(id),
  FOREIGN KEY (home_team_id) REFERENCES teams(id),
  FOREIGN KEY (away_team_id) REFERENCES teams(id)
);

CREATE TABLE IF NOT EXISTS match_sets (
  id INT AUTO_INCREMENT PRIMARY KEY,
  match_id INT NOT NULL,
  set_number INT NOT NULL,
  home_score INT DEFAULT 0,
  away_score INT DEFAULT 0,
  winner ENUM('home','away') DEFAULT NULL,
  started_at TIMESTAMP NULL DEFAULT NULL,
  ended_at TIMESTAMP NULL DEFAULT NULL,
  FOREIGN KEY (match_id) REFERENCES matches(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS player_actions (
  id INT AUTO_INCREMENT PRIMARY KEY,
  match_id INT NOT NULL,
  set_number INT NOT NULL DEFAULT 1,
  player_id INT NOT NULL,
  team_id INT NOT NULL,
  action_type ENUM('Ace','Service Error','Attack Kill','Attack Error','Attack Blocked','Block','Block Error','Dig','Dig Error','Set Assist','Reception','Reception Error','Timeout Called','Substitution In','Substitution Out') NOT NULL,
  action_result ENUM('Success','Error','Neutral') DEFAULT 'Neutral',
  home_score_after INT DEFAULT 0,
  away_score_after INT DEFAULT 0,
  recorded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (match_id) REFERENCES matches(id) ON DELETE CASCADE,
  FOREIGN KEY (player_id) REFERENCES players(id),
  FOREIGN KEY (team_id) REFERENCES teams(id)
);

CREATE TABLE IF NOT EXISTS league_standings (
  id INT AUTO_INCREMENT PRIMARY KEY,
  season_id INT NOT NULL,
  team_id INT NOT NULL,
  matches_played INT DEFAULT 0,
  matches_won INT DEFAULT 0,
  matches_lost INT DEFAULT 0,
  sets_won INT DEFAULT 0,
  sets_lost INT DEFAULT 0,
  points INT DEFAULT 0,
  set_ratio DECIMAL(6,3) DEFAULT 0.000,
  last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq(season_id, team_id),
  FOREIGN KEY (season_id) REFERENCES seasons(id),
  FOREIGN KEY (team_id) REFERENCES teams(id)
);

CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(50) NOT NULL UNIQUE,
  email VARCHAR(150) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  full_name VARCHAR(150) NOT NULL,
  role ENUM('Admin','Referee','Scorer','Viewer') DEFAULT 'Viewer',
  is_active TINYINT(1) DEFAULT 1,
  last_login TIMESTAMP NULL DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS stream_cameras (
  id INT AUTO_INCREMENT PRIMARY KEY,
  match_id INT NOT NULL,
  label VARCHAR(100) NOT NULL DEFAULT 'Main Camera',
  stream_key VARCHAR(120) NOT NULL UNIQUE,
  hls_url VARCHAR(512) DEFAULT NULL,
  embed_url VARCHAR(512) DEFAULT NULL,
  provider ENUM('RTMP_HLS','YouTube','Facebook','Custom') DEFAULT 'RTMP_HLS',
  status ENUM('Offline','Live','Ended') DEFAULT 'Offline',
  is_primary TINYINT(1) DEFAULT 0,
  is_enabled TINYINT(1) DEFAULT 1,
  viewer_count INT DEFAULT 0,
  bitrate_kbps INT DEFAULT NULL,
  resolution VARCHAR(20) DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (match_id) REFERENCES matches(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS stream_chat (
  id INT AUTO_INCREMENT PRIMARY KEY,
  camera_id INT NOT NULL,
  user_id INT DEFAULT NULL,
  guest_name VARCHAR(60) DEFAULT 'Guest',
  message VARCHAR(300) NOT NULL,
  is_deleted TINYINT(1) DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (camera_id) REFERENCES stream_cameras(id) ON DELETE CASCADE,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS news_articles (
  id INT AUTO_INCREMENT PRIMARY KEY,
  author_id INT DEFAULT NULL,
  title VARCHAR(255) NOT NULL,
  slug VARCHAR(191) NOT NULL UNIQUE,
  summary TEXT DEFAULT NULL,
  content LONGTEXT NOT NULL,
  featured_image VARCHAR(255) DEFAULT NULL,
  category ENUM('Match Report','Transfer','General','Tournament','Interview','Analysis') DEFAULT 'General',
  tags VARCHAR(255) DEFAULT NULL,
  status ENUM('Draft','Published','Archived') DEFAULT 'Draft',
  is_featured TINYINT(1) DEFAULT 0,
  views INT DEFAULT 0,
  published_at TIMESTAMP NULL DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (author_id) REFERENCES users(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS activity_log (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT DEFAULT NULL,
  action VARCHAR(255) NOT NULL,
  target_type VARCHAR(50) DEFAULT NULL,
  target_id INT DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

-- ============================================================
-- CLEAR EXISTING DATA
-- ============================================================
SET FOREIGN_KEY_CHECKS=0;
TRUNCATE activity_log;
TRUNCATE stream_chat;
TRUNCATE stream_cameras;
TRUNCATE news_articles;
TRUNCATE league_standings;
TRUNCATE player_actions;
TRUNCATE match_sets;
TRUNCATE matches;
TRUNCATE players;
TRUNCATE league_standings;
TRUNCATE seasons;
TRUNCATE teams;
TRUNCATE users;
SET FOREIGN_KEY_CHECKS=1;

-- ============================================================
-- USERS  (password = "password" for all accounts)
-- ============================================================
INSERT INTO users (id,username,email,password_hash,full_name,role,is_active,last_login) VALUES
(1,'admin','admin@volleytrack.mw','$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','System Administrator','Admin',1,'2025-09-07 08:30:00'),
(2,'jphiri','james.phiri@volleytrack.mw','$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','James Phiri','Referee',1,'2025-09-07 09:00:00'),
(3,'gbanda','grace.banda@volleytrack.mw','$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','Grace Banda','Scorer',1,'2025-09-07 13:50:00'),
(4,'pmwale','peter.mwale@volleytrack.mw','$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','Peter Mwale','Scorer',1,'2025-09-06 18:00:00'),
(5,'mgondwe','mary.gondwe@gmail.com','$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','Mary Gondwe','Viewer',1,'2025-09-07 14:10:00');

-- ============================================================
-- SEASONS
-- ============================================================
INSERT INTO seasons (id,name,type,start_date,end_date,status) VALUES
(1,'Malawi Volleyball League 2025','League','2025-01-15','2025-12-15','Active'),
(2,'MVL Cup 2025','Cup','2025-06-01','2025-07-31','Completed'),
(3,'Malawi Volleyball League 2024','League','2024-01-20','2024-11-30','Completed');

-- ============================================================
-- TEAMS
-- ============================================================
INSERT INTO teams (id,name,short_name,home_court,city,coach,color_primary,is_active) VALUES
(1,'Blantyre Spikers','BLS','Kamuzu Stadium Court','Blantyre','Coach Mkwanda','#C41E3A',1),
(2,'Lilongwe Thunder','LLT','Bingu Arena','Lilongwe','Coach Phiri','#003087',1),
(3,'Zomba Aces','ZBA','Zomba Gymkhana','Zomba','Coach Banda','#006400',1),
(4,'Mzuzu Warriors','MZW','Mzuzu Stadium','Mzuzu','Coach Tembo','#FF6600',1);

-- ============================================================
-- PLAYERS  (12 per team = 48 total)
-- ============================================================
-- Team 1: Blantyre Spikers (IDs 1–12)
INSERT INTO players (id,team_id,first_name,last_name,jersey_number,position,height_cm,weight_kg,date_of_birth) VALUES
(1, 1,'Chisomo','Phiri',      1,'Setter',           185,78.00,'1998-03-12'),
(2, 1,'Kondwani','Banda',     7,'Outside Hitter',   192,85.50,'1996-07-25'),
(3, 1,'Takondwa','Mwale',    10,'Middle Blocker',   198,90.00,'1997-11-08'),
(4, 1,'Blessings','Tembo',    3,'Opposite Hitter',  194,88.00,'1999-01-30'),
(5, 1,'Wongani','Chirwa',    15,'Libero',            175,68.00,'2000-05-17'),
(6, 1,'Limbani','Nkosi',      5,'Outside Hitter',   189,82.00,'1997-09-04'),
(7, 1,'Gracious','Mvula',     8,'Middle Blocker',   196,91.00,'1995-12-22'),
(8, 1,'Stain','Mhone',       11,'Setter',           183,76.50,'2001-06-14'),
(9, 1,'Francis','Kadzamira', 14,'Outside Hitter',   188,80.00,'1998-08-19'),
(10,1,'Memory','Gondwe',      2,'Defensive Specialist',174,65.00,'2002-02-28'),
(11,1,'Bright','Chalamanda',  6,'Opposite Hitter',  191,86.00,'1996-04-11'),
(12,1,'Trevor','Kamanga',    13,'Middle Blocker',   197,92.00,'1994-10-03'),

-- Team 2: Lilongwe Thunder (IDs 13–24)
(13,2,'Mtendere','Gondwe',    2,'Setter',           186,79.00,'1997-02-18'),
(14,2,'Chikondi','Mkwanda',   9,'Outside Hitter',   191,84.00,'1998-11-05'),
(15,2,'Dziko','Kapanda',     12,'Middle Blocker',   199,93.00,'1996-06-30'),
(16,2,'Yankho','Lungu',       4,'Opposite Hitter',  193,87.00,'1999-09-14'),
(17,2,'Mphatso','Nyirenda',  16,'Libero',            176,67.00,'2001-01-07'),
(18,2,'Raphael','Chikuse',    7,'Outside Hitter',   190,83.00,'1997-07-22'),
(19,2,'Kenneth','Nkhata',    11,'Middle Blocker',   197,90.50,'1995-03-15'),
(20,2,'Joseph','Chisale',     1,'Setter',           184,77.00,'2000-12-01'),
(21,2,'Daniel','Phiri',      14,'Outside Hitter',   187,81.00,'1999-04-20'),
(22,2,'Victor','Mwale',       3,'Defensive Specialist',173,64.50,'2002-08-09'),
(23,2,'Elias','Kumwenda',    10,'Middle Blocker',   196,89.00,'1996-11-27'),
(24,2,'Samuel','Kaunda',      8,'Opposite Hitter',  192,85.00,'1997-05-16'),

-- Team 3: Zomba Aces (IDs 25–36)
(25,3,'Dalitso','Mponda',     8,'Setter',           184,77.50,'1998-08-21'),
(26,3,'Chifundo','Mwenifumbo',11,'Outside Hitter',  190,83.50,'1997-04-09'),
(27,3,'Tayamika','Mbewe',     6,'Middle Blocker',   195,89.00,'1999-02-14'),
(28,3,'Oliver','Nyasulu',     4,'Opposite Hitter',  192,86.50,'1996-10-31'),
(29,3,'Harrison','Ntchisi',  15,'Libero',            176,66.00,'2001-07-03'),
(30,3,'Austin','Kachingwe',   3,'Outside Hitter',   188,81.50,'1998-01-25'),
(31,3,'Moses','Matewere',    12,'Middle Blocker',   196,90.00,'1995-09-18'),
(32,3,'Eric','Mphande',       1,'Setter',           182,75.00,'2001-11-11'),
(33,3,'Nathan','Zilira',      9,'Outside Hitter',   189,82.50,'1999-06-06'),
(34,3,'Patrick','Gama',      14,'Defensive Specialist',174,65.50,'2002-03-19'),
(35,3,'Andrew','Mkandawire',  7,'Middle Blocker',   197,91.50,'1994-12-07'),
(36,3,'Collins','Chisale',   10,'Opposite Hitter',  193,87.50,'1997-08-28'),

-- Team 4: Mzuzu Warriors (IDs 37–48)
(37,4,'Kondwani','Kamwendo',  3,'Setter',           185,78.50,'1999-05-12'),
(38,4,'Thandiwe','Msiska',    9,'Outside Hitter',   190,84.00,'1997-01-29'),
(39,4,'Lovemore','Kalua',     7,'Middle Blocker',   196,90.50,'1996-08-16'),
(40,4,'George','Phiri',       5,'Opposite Hitter',  193,87.00,'1998-12-04'),
(41,4,'Richard','Nkosi',     16,'Libero',            175,67.50,'2000-09-23'),
(42,4,'Simon','Gondwe',      11,'Outside Hitter',   188,81.00,'1998-03-08'),
(43,4,'Albert','Mwale',      12,'Middle Blocker',   197,92.00,'1994-06-25'),
(44,4,'Bernard','Chirwa',     2,'Setter',           183,76.00,'2001-04-17'),
(45,4,'Charles','Tembo',      8,'Outside Hitter',   189,82.00,'1999-10-30'),
(46,4,'David','Mkwanda',     14,'Defensive Specialist',173,64.00,'2002-01-13'),
(47,4,'Emmanuel','Banda',     6,'Middle Blocker',   195,89.50,'1996-07-05'),
(48,4,'Francis','Phiri',      4,'Opposite Hitter',  192,86.00,'1997-11-20');

-- ============================================================
-- MATCHES
-- ============================================================
INSERT INTO matches (id,season_id,home_team_id,away_team_id,match_date,venue,round,status,home_sets_won,away_sets_won,set_scores,current_set,home_score_current_set,away_score_current_set,started_at,ended_at) VALUES
-- Round 1
(1,1,1,2,'2025-08-10 14:00:00','Kamuzu Stadium Court','Round 1','Completed',3,1,'[{"home":25,"away":20},{"home":23,"away":25},{"home":25,"away":18},{"home":25,"away":22}]',4,0,0,'2025-08-10 14:05:00','2025-08-10 16:10:00'),
(2,1,3,4,'2025-08-10 16:00:00','Zomba Gymkhana','Round 1','Completed',3,0,'[{"home":25,"away":15},{"home":25,"away":19},{"home":25,"away":21}]',3,0,0,'2025-08-10 16:08:00','2025-08-10 17:40:00'),
-- Round 2
(3,1,2,3,'2025-08-17 14:00:00','Bingu Arena','Round 2','Completed',3,2,'[{"home":25,"away":22},{"home":20,"away":25},{"home":25,"away":18},{"home":22,"away":25},{"home":15,"away":12}]',5,0,0,'2025-08-17 14:06:00','2025-08-17 16:45:00'),
(4,1,4,1,'2025-08-17 16:00:00','Mzuzu Stadium','Round 2','Completed',1,3,'[{"home":18,"away":25},{"home":25,"away":20},{"home":20,"away":25},{"home":22,"away":25}]',4,0,0,'2025-08-17 16:10:00','2025-08-17 17:55:00'),
-- Round 3
(5,1,1,3,'2025-08-24 14:00:00','Kamuzu Stadium Court','Round 3','Scheduled',0,0,NULL,1,0,0,NULL,NULL),
(6,1,2,4,'2025-08-24 16:00:00','Bingu Arena','Round 3','Scheduled',0,0,NULL,1,0,0,NULL,NULL),
-- Round 4 (Live)
(7,1,2,1,'2025-09-07 14:00:00','Bingu Arena','Round 4','Live',1,0,'[{"home":25,"away":21}]',2,18,14,'2025-09-07 14:05:00',NULL),
-- Round 4 (Scheduled)
(8,1,4,3,'2025-09-07 16:00:00','Mzuzu Stadium','Round 4','Scheduled',0,0,NULL,1,0,0,NULL,NULL),
-- Round 5
(9,1,1,4,'2025-09-14 14:00:00','Kamuzu Stadium Court','Round 5','Scheduled',0,0,NULL,1,0,0,NULL,NULL),
(10,1,3,2,'2025-09-14 16:00:00','Zomba Gymkhana','Round 5','Scheduled',0,0,NULL,1,0,0,NULL,NULL),
-- Round 6
(11,1,4,2,'2025-09-21 14:00:00','Mzuzu Stadium','Round 6','Scheduled',0,0,NULL,1,0,0,NULL,NULL),
(12,1,3,1,'2025-09-21 16:00:00','Zomba Gymkhana','Round 6','Scheduled',0,0,NULL,1,0,0,NULL,NULL),
-- Cup Season matches (season 2)
(13,2,1,3,'2025-06-14 14:00:00','Kamuzu Stadium Court','Semi-final','Completed',3,1,'[{"home":25,"away":19},{"home":22,"away":25},{"home":25,"away":17},{"home":25,"away":23}]',4,0,0,'2025-06-14 14:04:00','2025-06-14 15:58:00'),
(14,2,2,4,'2025-06-14 16:00:00','Bingu Arena','Semi-final','Completed',3,0,'[{"home":25,"away":14},{"home":25,"away":18},{"home":25,"away":20}]',3,0,0,'2025-06-14 16:07:00','2025-06-14 17:30:00'),
(15,2,1,2,'2025-07-05 15:00:00','Bingu Arena','Final','Completed',3,2,'[{"home":25,"away":23},{"home":21,"away":25},{"home":25,"away":20},{"home":23,"away":25},{"home":16,"away":14}]',5,0,0,'2025-07-05 15:05:00','2025-07-05 17:50:00');

-- ============================================================
-- MATCH SETS  (for all completed matches)
-- ============================================================
INSERT INTO match_sets (match_id,set_number,home_score,away_score,winner,started_at,ended_at) VALUES
-- Match 1: BLS 3-1 LLT
(1,1,25,20,'home','2025-08-10 14:05:00','2025-08-10 14:30:00'),
(1,2,23,25,'away','2025-08-10 14:32:00','2025-08-10 14:58:00'),
(1,3,25,18,'home','2025-08-10 15:00:00','2025-08-10 15:23:00'),
(1,4,25,22,'home','2025-08-10 15:25:00','2025-08-10 15:52:00'),
-- Match 2: ZBA 3-0 MZW
(2,1,25,15,'home','2025-08-10 16:08:00','2025-08-10 16:28:00'),
(2,2,25,19,'home','2025-08-10 16:30:00','2025-08-10 16:52:00'),
(2,3,25,21,'home','2025-08-10 16:54:00','2025-08-10 17:18:00'),
-- Match 3: LLT 3-2 ZBA
(3,1,25,22,'home','2025-08-17 14:06:00','2025-08-17 14:33:00'),
(3,2,20,25,'away','2025-08-17 14:35:00','2025-08-17 15:02:00'),
(3,3,25,18,'home','2025-08-17 15:04:00','2025-08-17 15:27:00'),
(3,4,22,25,'away','2025-08-17 15:29:00','2025-08-17 15:58:00'),
(3,5,15,12,'home','2025-08-17 16:00:00','2025-08-17 16:18:00'),
-- Match 4: MZW 1-3 BLS
(4,1,18,25,'away','2025-08-17 16:10:00','2025-08-17 16:35:00'),
(4,2,25,20,'home','2025-08-17 16:37:00','2025-08-17 17:02:00'),
(4,3,20,25,'away','2025-08-17 17:04:00','2025-08-17 17:28:00'),
(4,4,22,25,'away','2025-08-17 17:30:00','2025-08-17 17:55:00'),
-- Match 7: LLT vs BLS (Set 1 completed, mid set 2)
(7,1,25,21,'home','2025-09-07 14:05:00','2025-09-07 14:31:00'),
-- Cup Match 13: BLS 3-1 ZBA
(13,1,25,19,'home','2025-06-14 14:04:00','2025-06-14 14:28:00'),
(13,2,22,25,'away','2025-06-14 14:30:00','2025-06-14 14:56:00'),
(13,3,25,17,'home','2025-06-14 14:58:00','2025-06-14 15:20:00'),
(13,4,25,23,'home','2025-06-14 15:22:00','2025-06-14 15:50:00'),
-- Cup Match 14: LLT 3-0 MZW
(14,1,25,14,'home','2025-06-14 16:07:00','2025-06-14 16:26:00'),
(14,2,25,18,'home','2025-06-14 16:28:00','2025-06-14 16:48:00'),
(14,3,25,20,'home','2025-06-14 16:50:00','2025-06-14 17:12:00'),
-- Cup Final Match 15: BLS 3-2 LLT
(15,1,25,23,'home','2025-07-05 15:05:00','2025-07-05 15:33:00'),
(15,2,21,25,'away','2025-07-05 15:35:00','2025-07-05 16:02:00'),
(15,3,25,20,'home','2025-07-05 16:04:00','2025-07-05 16:28:00'),
(15,4,23,25,'away','2025-07-05 16:30:00','2025-07-05 16:58:00'),
(15,5,16,14,'home','2025-07-05 17:00:00','2025-07-05 17:22:00');

-- ============================================================
-- PLAYER ACTIONS  (Match 1: BLS vs LLT — Set 1 sample)
-- ============================================================
INSERT INTO player_actions (match_id,set_number,player_id,team_id,action_type,action_result,home_score_after,away_score_after) VALUES
-- Set 1 highlights BLS vs LLT
(1,1, 1,1,'Set Assist','Success',0,0),
(1,1, 2,1,'Attack Kill','Success',1,0),
(1,1,13,2,'Set Assist','Success',1,0),
(1,1,14,2,'Attack Kill','Success',1,1),
(1,1, 1,1,'Set Assist','Success',1,1),
(1,1, 3,1,'Block','Success',2,1),
(1,1, 2,1,'Ace','Success',3,1),
(1,1,13,2,'Service Error','Error',4,1),
(1,1, 6,1,'Attack Kill','Success',5,1),
(1,1,15,2,'Dig','Success',5,1),
(1,1,16,2,'Attack Kill','Success',5,2),
(1,1, 5,1,'Reception','Success',5,2),
(1,1, 4,1,'Attack Kill','Success',6,2),
(1,1,14,2,'Attack Blocked','Error',7,2),
(1,1, 7,1,'Block','Success',7,2),
(1,1,13,2,'Ace','Success',7,3),
(1,1,18,2,'Attack Kill','Success',7,4),
(1,1, 2,1,'Attack Kill','Success',8,4),
(1,1, 3,1,'Block','Success',9,4),
(1,1, 1,1,'Set Assist','Success',9,4),
(1,1, 2,1,'Attack Kill','Success',10,4),
(1,1,16,2,'Attack Kill','Success',10,5),
(1,1, 6,1,'Attack Kill','Success',11,5),
(1,1,15,2,'Ace','Success',11,6),
(1,1, 5,1,'Reception Error','Error',11,7),
(1,1,14,2,'Attack Kill','Success',11,8),
(1,1, 1,1,'Set Assist','Success',11,8),
(1,1, 4,1,'Attack Kill','Success',12,8),
(1,1,13,2,'Service Error','Error',13,8),
(1,1, 2,1,'Ace','Success',14,8),
-- Set 2 highlights
(1,2,13,2,'Set Assist','Success',0,0),
(1,2,18,2,'Attack Kill','Success',0,1),
(1,2,16,2,'Ace','Success',0,2),
(1,2, 2,1,'Attack Kill','Success',1,2),
(1,2, 3,1,'Block','Success',2,2),
(1,2,14,2,'Attack Kill','Success',2,3),
(1,2,19,2,'Block','Success',2,4),
(1,2, 1,1,'Set Assist','Success',2,4),
(1,2, 6,1,'Attack Kill','Success',3,4),
(1,2,13,2,'Set Assist','Success',3,4),
(1,2,16,2,'Attack Kill','Success',3,5),
-- Set 3 highlights
(1,3, 1,1,'Set Assist','Success',0,0),
(1,3, 2,1,'Attack Kill','Success',1,0),
(1,3, 7,1,'Block','Success',2,0),
(1,3, 2,1,'Ace','Success',3,0),
(1,3,15,2,'Dig','Success',3,0),
(1,3,14,2,'Attack Error','Error',4,0),
(1,3, 4,1,'Attack Kill','Success',5,0),
-- Match 4: MZW vs BLS — select player actions
(4,1,37,4,'Set Assist','Success',0,0),
(4,1,38,4,'Attack Kill','Success',1,0),
(4,1, 2,1,'Attack Kill','Success',1,1),
(4,1, 3,1,'Block','Success',1,2),
(4,1, 1,1,'Ace','Success',1,3),
(4,1,39,4,'Block','Success',2,3),
(4,1,38,4,'Attack Kill','Success',3,3),
(4,1, 6,1,'Attack Kill','Success',3,4),
(4,1, 2,1,'Attack Kill','Success',3,5),
(4,2,37,4,'Set Assist','Success',0,0),
(4,2,40,4,'Attack Kill','Success',1,0),
(4,2,39,4,'Block','Success',2,0),
(4,2,38,4,'Ace','Success',3,0),
(4,2, 1,1,'Set Assist','Success',3,0),
(4,2, 4,1,'Attack Kill','Success',3,1),
-- Cup Final Match 15 highlights
(15,5, 1,1,'Set Assist','Success',0,0),
(15,5, 2,1,'Attack Kill','Success',1,0),
(15,5,13,2,'Set Assist','Success',1,0),
(15,5,16,2,'Attack Kill','Success',1,1),
(15,5, 3,1,'Block','Success',2,1),
(15,5, 1,1,'Ace','Success',3,1),
(15,5,14,2,'Attack Kill','Success',3,2),
(15,5, 2,1,'Attack Kill','Success',4,2),
(15,5,15,2,'Dig','Success',4,2),
(15,5,16,2,'Attack Kill','Success',4,3),
(15,5, 4,1,'Attack Kill','Success',5,3),
(15,5,13,2,'Service Error','Error',6,3),
(15,5, 2,1,'Ace','Success',7,3),
(15,5,18,2,'Attack Kill','Success',7,4),
(15,5, 6,1,'Attack Kill','Success',8,4),
(15,5,19,2,'Block','Success',8,5),
(15,5,16,2,'Ace','Success',8,6),
(15,5, 5,1,'Reception','Success',8,6),
(15,5, 2,1,'Attack Kill','Success',9,6),
(15,5, 7,1,'Block','Success',10,6),
(15,5,14,2,'Attack Kill','Success',10,7),
(15,5, 2,1,'Attack Kill','Success',11,7),
(15,5, 3,1,'Block','Success',12,7),
(15,5,13,2,'Ace','Success',12,8),
(15,5, 1,1,'Set Assist','Success',12,8),
(15,5, 4,1,'Attack Kill','Success',13,8),
(15,5,16,2,'Attack Kill','Success',13,9),
(15,5, 2,1,'Attack Kill','Success',14,9),
(15,5,14,2,'Attack Error','Error',15,9),
(15,5, 1,1,'Timeout Called','Neutral',15,9),
(15,5,13,2,'Timeout Called','Neutral',15,9),
(15,5, 2,1,'Attack Kill','Success',16,9);

-- ============================================================
-- LEAGUE STANDINGS (MVL 2025 — after Round 2)
-- ============================================================
INSERT INTO league_standings (season_id,team_id,matches_played,matches_won,matches_lost,sets_won,sets_lost,points,set_ratio) VALUES
(1,1,3,2,1,8,5,6,1.600),
(1,2,3,1,2,5,8,3,0.625),
(1,3,2,1,1,4,4,3,1.000),
(1,4,2,0,2,2,6,0,0.333);

-- ============================================================
-- NEWS ARTICLES
-- ============================================================
INSERT INTO news_articles (id,author_id,title,slug,summary,content,category,status,is_featured,views,published_at) VALUES
(1,1,'Blantyre Spikers Win Opening Round Thriller','blantyre-spikers-win-round-1',
'Spikers defeat Thunder 3-1 in a hard-fought opening fixture at Kamuzu Stadium.',
'<p>Blantyre Spikers put on a commanding display defeating Lilongwe Thunder 3-1 in an exciting Round 1 opener at Kamuzu Stadium Court. Kondwani Banda was outstanding with 14 kills and 3 aces across the four sets, earning the Man of the Match award.</p><p>The Spikers raced ahead in the first set, dominating from the service line and finishing 25-20. Thunder fought back to level in the second set 25-23, but Blantyre reasserted control in the third and fourth sets to seal the win.</p><p>"We trained very hard for this and the results show," said head coach Mkwanda. "The boys executed the game plan perfectly. Kondwani was exceptional tonight."</p>',
'Match Report','Published',1,347,'2025-08-10 18:30:00'),

(2,1,'MVL 2025 Season Preview — Who Will Lift the Trophy?','mvl-2025-season-preview',
'Four teams battle for the Malawi Volleyball League title. We break down each contender.',
'<p>The Malawi Volleyball League 2025 promises to be the most competitive season in recent memory with four teams all having genuine title aspirations.</p><h3>Blantyre Spikers</h3><p>The defending champions have retained their core roster. Coach Mkwanda has them organized with a fast offence and a tight block. Kondwani Banda and Blessings Tembo form one of the most dangerous attacking partnerships in the league. They are the favourites.</p><h3>Lilongwe Thunder</h3><p>Lilongwe have invested heavily in their squad. Setter Mtendere Gondwe is arguably the best in the league and their block led by Dziko Kapanda is formidable. They are genuine title contenders.</p><h3>Zomba Aces</h3><p>Zomba surprised many in 2024 and will look to go one better this season. A young squad led by the experienced Tayamika Mbewe could spring some surprises.</p><h3>Mzuzu Warriors</h3><p>Mzuzu are building a squad for the future. Coach Tembo has brought in several promising young players and will be targeting a top-three finish.</p>',
'Analysis','Published',0,521,'2025-08-01 09:00:00'),

(3,1,'VolleyTrack Launches Live Streaming for MVL 2025','volleytrack-live-streaming',
'Fans can now watch all league matches live from anywhere in the world.',
'<p>VolleyTrack is proud to announce the launch of live streaming for all MVL 2025 matches. Fans across Malawi and around the world can now watch every serve, spike, and block live from the comfort of their homes.</p><p>Each match will feature up to three camera angles — the main court view, an attack camera behind the end line, and a defence camera from the sideline — giving viewers the most comprehensive coverage ever offered for Malawi volleyball.</p><p>The service is free and available through the VolleyTrack Scoreboard module. No account is required to watch.</p>',
'General','Published',1,892,'2025-09-05 11:00:00'),

(4,1,'Zomba Aces Blank Mzuzu Warriors in Dominant Display','zomba-aces-blank-mzuzu-round-1',
'Zomba sweep the Warriors 3-0 with an impressive team performance in Round 1.',
'<p>Zomba Aces made a powerful statement in Round 1, sweeping Mzuzu Warriors 3-0 at Zomba Gymkhana. The home side dominated all three sets, never allowing Warriors to get comfortable on their own terms.</p><p>Middle blocker Tayamika Mbewe led the defence with 6 blocks while setter Dalitso Mponda orchestrated the attack beautifully, spreading the ball across all three hitters. Oliver Nyasulu added 11 kills as opposite hitter.</p><p>"This is the level we want to maintain every match," said Coach Banda. "The team chemistry is excellent right now and we want to keep building on this."</p>',
'Match Report','Published',0,213,'2025-08-10 19:00:00'),

(5,1,'Blantyre Spikers Crowned MVL Cup 2025 Champions','bls-win-mvl-cup-2025',
'Spikers defeat Thunder 3-2 in a breathtaking Cup Final to claim silverware.',
'<p>Blantyre Spikers are the MVL Cup 2025 champions after defeating Lilongwe Thunder in a five-set final classic at Bingu Arena. The match, which lasted nearly three hours, saw both sides produce some of the finest volleyball seen in Malawi.</p><p>Kondwani Banda was the hero again with 22 kills and 2 blocks in the final. He has now won the Player of the Tournament award for the second straight year.</p><p>"Words cannot describe what this means to the team and the fans," said captain Chisomo Phiri. "We fought until the very last point and the whole country should be proud."</p><p>Thunder coach Phiri was gracious in defeat: "Blantyre were the better team on the day. We will come back stronger in the league."</p>',
'Tournament','Published',1,1240,'2025-07-05 20:00:00'),

(6,1,'Thunder Comeback Falls Short Against Spikers','thunder-comeback-round-4',
'Lilongwe Thunder lead 1-0 in sets but Blantyre fighting back in Round 4 clash.',
'<p>The biggest match of the MVL 2025 season so far is currently underway at Bingu Arena as Lilongwe Thunder host Blantyre Spikers in Round 4. Thunder drew first blood taking Set 1 25-21 and lead Set 2 18-14 as we go to press.</p><p>Thunder setter Mtendere Gondwe has been exceptional, controlling the tempo perfectly. Yankho Lungu has been dominant as opposite with 8 kills already.</p><p>Blantyre coach Mkwanda has called a timeout to reorganise. "We need to tighten up our reception and get Kondwani more touches," he was heard saying during the break.</p><p>Check the live scoreboard for real-time updates.</p>',
'Match Report','Published',1,458,'2025-09-07 15:00:00'),

(7,1,'Interview: Kondwani Banda on Life as MVL Top Scorer','interview-kondwani-banda',
'We sat down with the Blantyre Spikers outside hitter ahead of Round 3.',
'<p>"I just want to help the team win. Individual awards are nice but the only thing that matters is that trophy at the end of the season," said Kondwani Banda when we caught up with him ahead of Blantyre Spikers'' Round 3 fixture against Zomba Aces.</p><p>The 29-year-old outside hitter leads the MVL 2025 scoring charts with 36 kills in just four sets. He credits his success to the service of setter Chisomo Phiri.</p><p>"Chisomo reads the game like nobody else. When he sets me the ball in the perfect position it makes my job easy. We have been playing together for three years and we understand each other perfectly."</p><p>On the Round 3 challenge against Zomba: "They are a good team and they have momentum. We are not taking them lightly at all. We have prepared well and we will be ready."</p>',
'Interview','Published',0,314,'2025-08-22 10:00:00'),

(8,1,'Mzuzu Warriors Announce Three New Signings','mzuzu-warriors-signings-2025',
'Warriors strengthen their squad ahead of the second half of the MVL 2025 season.',
'<p>Mzuzu Warriors have announced the signing of three players ahead of Rounds 5 and 6 of the MVL 2025 season. The club confirmed the additions of outside hitter Emmanuel Chirwa from Karonga Volleyball Club, middle blocker Alfred Ng''ambi from Dedza Spartans, and libero Joyce Nkhonjera from the women''s national programme.</p><p>"We are building something special here at Mzuzu," said coach Tembo. "These signings show our ambition. We want to compete for the title next season and the groundwork starts now."</p><p>Warriors currently sit fourth in the standings but are optimistic about their remaining fixtures. "Every game is winnable. We have nothing to lose and everything to gain," Tembo added.</p>',
'Transfer','Published',0,187,'2025-08-28 14:00:00');

-- ============================================================
-- STREAM CAMERAS
-- ============================================================
INSERT INTO stream_cameras (id,match_id,label,stream_key,provider,status,is_primary,is_enabled,viewer_count,bitrate_kbps,resolution) VALUES
-- Match 7 (Live)
(1,7,'Main Court','vt-m7-main','RTMP_HLS','Live',1,1,142,3200,'1920x1080'),
(2,7,'Attack Camera','vt-m7-attack','RTMP_HLS','Live',0,1,87,2500,'1280x720'),
(3,7,'Defense Camera','vt-m7-defense','RTMP_HLS','Offline',0,1,0,NULL,NULL),
-- Match 1 (Ended)
(4,1,'Main Court','vt-m1-main','RTMP_HLS','Ended',1,1,0,0,NULL),
(5,1,'Attack Camera','vt-m1-attack','RTMP_HLS','Ended',0,1,0,0,NULL),
-- Match 3 (Ended)
(6,3,'Main Court','vt-m3-main','RTMP_HLS','Ended',1,1,0,0,NULL),
-- Cup Final Match 15 (Ended)
(7,15,'Main Court','vt-m15-main','RTMP_HLS','Ended',1,1,0,0,NULL),
(8,15,'Attack Camera','vt-m15-attack','RTMP_HLS','Ended',0,1,0,0,NULL),
(9,15,'Defense Camera','vt-m15-defense','RTMP_HLS','Ended',0,1,0,0,NULL);

-- ============================================================
-- STREAM CHAT
-- ============================================================
INSERT INTO stream_chat (camera_id,user_id,guest_name,message,created_at) VALUES
-- Match 7 live chat (camera 1 — main)
(1,NULL,'ChikondiM',   'Great match so far!',                        '2025-09-07 14:10:00'),
(1,NULL,'TakondwaV',   'Banda is on fire tonight!',                  '2025-09-07 14:12:00'),
(1,NULL,'LimbaniF',    'Go Spikers!!',                               '2025-09-07 14:14:00'),
(1,5,   'Mary Gondwe', 'Thunder looking very organised today',        '2025-09-07 14:16:00'),
(1,NULL,'MvulaSports', 'Gondwe''s setting is absolutely world class', '2025-09-07 14:18:00'),
(1,NULL,'BlantyreNo1', 'Come on BLS let''s fight back!',             '2025-09-07 14:20:00'),
(1,NULL,'ZombaFan',    'This is the best MVL match I have seen',     '2025-09-07 14:22:00'),
(1,NULL,'ThunderNation','Yankho Lungu with another big kill!!',       '2025-09-07 14:24:00'),
(1,NULL,'ChikondiM',   'Timeout called — Mkwanda setting up the comeback', '2025-09-07 14:26:00'),
(1,NULL,'LilongweFan', 'Thunder all the way to the title this year', '2025-09-07 14:28:00'),
-- Camera 2 — attack camera
(2,NULL,'AttackCamFan','Love this angle — you can see everything',   '2025-09-07 14:15:00'),
(2,NULL,'CoachWatcher','Watch how Thunder are shifting their block',  '2025-09-07 14:22:00'),
-- Historical chat from Cup Final (camera 7)
(7,NULL,'VolleyMalawi','Unbelievable atmosphere at Bingu Arena today!','2025-07-05 15:30:00'),
(7,NULL,'SpikersForever','KONDWANI BANDA IS A LEGEND!',              '2025-07-05 17:20:00'),
(7,NULL,'ThunderFan99','Gutted but what a final — Thunder will be back','2025-07-05 17:25:00'),
(7,5,   'Mary Gondwe', 'What a match. Best final in MVL history',    '2025-07-05 17:26:00');

-- ============================================================
-- ACTIVITY LOG
-- ============================================================
INSERT INTO activity_log (user_id,action,target_type,target_id,created_at) VALUES
(1,'Created season: Malawi Volleyball League 2025',     'season', 1,'2025-01-10 09:00:00'),
(1,'Added team: Blantyre Spikers',                      'team',   1,'2025-01-10 09:05:00'),
(1,'Added team: Lilongwe Thunder',                      'team',   2,'2025-01-10 09:06:00'),
(1,'Added team: Zomba Aces',                            'team',   3,'2025-01-10 09:07:00'),
(1,'Added team: Mzuzu Warriors',                        'team',   4,'2025-01-10 09:08:00'),
(1,'Scheduled match: BLS vs LLT (Round 1)',             'match',  1,'2025-01-15 10:00:00'),
(1,'Scheduled match: ZBA vs MZW (Round 1)',             'match',  2,'2025-01-15 10:02:00'),
(1,'Scheduled match: LLT vs ZBA (Round 2)',             'match',  3,'2025-01-15 10:04:00'),
(1,'Scheduled match: MZW vs BLS (Round 2)',             'match',  4,'2025-01-15 10:06:00'),
(3,'Started live scoring: Match 1 BLS vs LLT',         'match',  1,'2025-08-10 14:05:00'),
(3,'Match completed: BLS 3-1 LLT',                     'match',  1,'2025-08-10 16:10:00'),
(4,'Started live scoring: Match 2 ZBA vs MZW',         'match',  2,'2025-08-10 16:08:00'),
(4,'Match completed: ZBA 3-0 MZW',                     'match',  2,'2025-08-10 17:40:00'),
(3,'Started live scoring: Match 3 LLT vs ZBA',         'match',  3,'2025-08-17 14:06:00'),
(3,'Match completed: LLT 3-2 ZBA',                     'match',  3,'2025-08-17 16:45:00'),
(4,'Started live scoring: Match 4 MZW vs BLS',         'match',  4,'2025-08-17 16:10:00'),
(4,'Match completed: MZW 1-3 BLS',                     'match',  4,'2025-08-17 17:55:00'),
(1,'Recalculated standings for season 1',              'season',  1,'2025-08-17 18:30:00'),
(1,'Published news article: MVL Cup 2025 Final',       'news',    5,'2025-07-05 20:00:00'),
(1,'Published news article: Round 1 report',           'news',    1,'2025-08-10 18:30:00'),
(1,'Published news article: Season Preview',           'news',    2,'2025-08-01 09:00:00'),
(3,'Started live scoring: Match 7 LLT vs BLS',        'match',   7,'2025-09-07 14:05:00'),
(1,'Published news article: Thunder v Spikers Round 4','news',    6,'2025-09-07 15:00:00'),
(2,'Registered as referee for match 7',               'match',    7,'2025-09-07 13:00:00');
