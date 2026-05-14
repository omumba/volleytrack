-- VolleyTrack Complete Database
CREATE DATABASE IF NOT EXISTS volleytrack CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE volleytrack;

CREATE TABLE teams (
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

CREATE TABLE players (
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

CREATE TABLE seasons (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(150) NOT NULL,
  type ENUM('League','Cup','Friendly','Playoff') DEFAULT 'League',
  start_date DATE NOT NULL,
  end_date DATE DEFAULT NULL,
  status ENUM('Upcoming','Active','Completed') DEFAULT 'Upcoming',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE matches (
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

CREATE TABLE match_sets (
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

CREATE TABLE player_actions (
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

CREATE TABLE league_standings (
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

CREATE TABLE users (
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

CREATE TABLE stream_cameras (
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

CREATE TABLE stream_chat (
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

CREATE TABLE news_articles (
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

CREATE TABLE activity_log (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT DEFAULT NULL,
  action VARCHAR(255) NOT NULL,
  target_type VARCHAR(50) DEFAULT NULL,
  target_id INT DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

-- Seed data
INSERT INTO users (username,email,password_hash,full_name,role) VALUES
('admin','admin@volleytrack.mw','$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','System Administrator','Admin');

INSERT INTO seasons (name,type,start_date,end_date,status) VALUES
('Malawi Volleyball League 2025','League','2025-01-15','2025-12-15','Active');

INSERT INTO teams (name,short_name,home_court,city,coach,color_primary) VALUES
('Blantyre Spikers','BLS','Kamuzu Stadium Court','Blantyre','Coach Mkwanda','#C41E3A'),
('Lilongwe Thunder','LLT','Bingu Arena','Lilongwe','Coach Phiri','#003087'),
('Zomba Aces','ZBA','Zomba Gymkhana','Zomba','Coach Banda','#006400'),
('Mzuzu Warriors','MZW','Mzuzu Stadium','Mzuzu','Coach Tembo','#FF6600');

INSERT INTO players (team_id,first_name,last_name,jersey_number,position) VALUES
(1,'Chisomo','Phiri',1,'Setter'),(1,'Kondwani','Banda',7,'Outside Hitter'),(1,'Takondwa','Mwale',10,'Middle Blocker'),(1,'Blessings','Tembo',3,'Opposite Hitter'),(1,'Wongani','Chirwa',15,'Libero'),(1,'Limbani','Nkosi',5,'Outside Hitter'),
(2,'Mtendere','Gondwe',2,'Setter'),(2,'Chikondi','Mkwanda',9,'Outside Hitter'),(2,'Dziko','Kapanda',12,'Middle Blocker'),(2,'Yankho','Lungu',4,'Opposite Hitter'),(2,'Mphatso','Nyirenda',16,'Libero'),
(3,'Dalitso','Mponda',8,'Setter'),(3,'Chifundo','Mwenifumbo',11,'Outside Hitter'),(3,'Tayamika','Mbewe',6,'Middle Blocker'),
(4,'Kondwani','Kamwendo',3,'Setter'),(4,'Thandiwe','Msiska',9,'Outside Hitter'),(4,'Lovemore','Kalua',7,'Middle Blocker');

INSERT INTO matches (season_id,home_team_id,away_team_id,match_date,venue,round,status,home_sets_won,away_sets_won,set_scores) VALUES
(1,1,2,'2025-08-10 14:00:00','Kamuzu Stadium Court','Round 1','Completed',3,1,'[{"home":25,"away":20},{"home":23,"away":25},{"home":25,"away":18},{"home":25,"away":22}]'),
(1,3,4,'2025-08-10 16:00:00','Zomba Gymkhana','Round 1','Completed',3,0,'[{"home":25,"away":15},{"home":25,"away":19},{"home":25,"away":21}]'),
(1,2,3,'2025-08-17 14:00:00','Bingu Arena','Round 2','Completed',3,2,'[{"home":25,"away":22},{"home":20,"away":25},{"home":25,"away":18},{"home":22,"away":25},{"home":15,"away":12}]'),
(1,4,1,'2025-08-17 16:00:00','Mzuzu Stadium','Round 2','Completed',1,3,'[{"home":18,"away":25},{"home":25,"away":20},{"home":20,"away":25},{"home":22,"away":25}]'),
(1,1,3,'2025-08-24 14:00:00','Kamuzu Stadium Court','Round 3','Scheduled',0,0,NULL),
(1,2,4,'2025-08-24 16:00:00','Bingu Arena','Round 3','Scheduled',0,0,NULL),
(1,2,1,'2025-09-07 14:00:00','Bingu Arena','Round 4','Live',1,0,'[]');

UPDATE matches SET status='Live',current_set=2,home_score_current_set=18,away_score_current_set=14,started_at=NOW() WHERE id=7;

INSERT INTO league_standings (season_id,team_id,matches_played,matches_won,matches_lost,sets_won,sets_lost,points,set_ratio) VALUES
(1,1,3,2,1,8,5,6,1.600),(1,2,3,1,2,5,8,3,0.625),(1,3,2,1,1,4,4,3,1.000),(1,4,2,0,2,2,6,0,0.333);

INSERT INTO news_articles (author_id,title,slug,summary,content,category,status,is_featured,views,published_at) VALUES
(1,'Blantyre Spikers Win Opening Round Thriller','blantyre-spikers-win-round-1','Spikers defeat Thunder 3-1 in a hard-fought opening fixture at Kamuzu Stadium.','<p>Blantyre Spikers put on a commanding display defeating Lilongwe Thunder 3-1 in an exciting Round 1 opener. Kondwani Banda was outstanding with 14 kills and 3 aces across the four sets.</p><p>"We trained very hard for this and the results show," said head coach Mkwanda. The team is playing with great confidence heading into the rest of the season.</p>','Match Report','Published',1,347,NOW() - INTERVAL 10 DAY),
(1,'MVL 2025 Season Preview','mvl-2025-season-preview','Four teams battle for the Malawi Volleyball League title. We break down each team.','<p>The Malawi Volleyball League 2025 promises to be the most competitive season in recent memory with four teams all having genuine title aspirations.</p><h3>Blantyre Spikers</h3><p>The defending champions have retained their core roster. Coach Mkwanda has them organized with a fast offence and solid block. They are the favourites to retain the title.</p><h3>Lilongwe Thunder</h3><p>Lilongwe invested in two new outside hitters this season. Their setter Gondwe is one of the best in the league.</p>','Analysis','Published',0,521,NOW() - INTERVAL 14 DAY),
(1,'VolleyTrack Launches Live Streaming','volleytrack-live-streaming','Fans can now watch all league matches live from anywhere in the world.','<p>VolleyTrack is proud to announce the launch of live streaming for all MVL 2025 matches. Fans across Malawi and around the world can watch every serve, spike, and block live.</p><p>Each match features up to three camera angles giving viewers the most comprehensive experience ever offered for Malawi volleyball.</p>','General','Published',1,892,NOW() - INTERVAL 2 DAY);

INSERT INTO stream_cameras (match_id,label,stream_key,provider,status,is_primary,viewer_count,bitrate_kbps,resolution) VALUES
(7,'Main Court','vt-m7-main','RTMP_HLS','Live',1,142,3200,'1920x1080'),
(7,'Attack Camera','vt-m7-attack','RTMP_HLS','Live',0,87,2500,'1280x720'),
(7,'Defense Camera','vt-m7-defense','RTMP_HLS','Offline',0,0,NULL,NULL);

INSERT INTO stream_chat (camera_id,guest_name,message) VALUES
(1,'ChikondiM','Great match so far!'),(1,'TakondwaV','Banda is on fire!'),(1,'LimbaniF','Go Spikers!!');
