<?php
define('DB_HOST',    'localhost');
define('DB_USER',    'root');
define('DB_PASS',    '');
define('DB_NAME',    'volleytrack');
define('APP_URL',    'http://localhost/volleyball');
define('APP_NAME',   'VolleyTrack');
define('TIMEZONE',   'Africa/Blantyre');

date_default_timezone_set(TIMEZONE);
session_start();

function getDB(): PDO {
  static $pdo = null;
  if ($pdo === null) {
    try {
      $pdo = new PDO(
        "mysql:host=".DB_HOST.";dbname=".DB_NAME.";charset=utf8mb4",
        DB_USER, DB_PASS,
        [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC, PDO::ATTR_EMULATE_PREPARES=>false]
      );
      // Schema migrations — silently ignored if column already exists
      try { $pdo->exec("ALTER TABLE teams   ADD COLUMN logo  VARCHAR(255) NULL"); } catch(PDOException $e) {}
      try { $pdo->exec("ALTER TABLE players ADD COLUMN photo VARCHAR(255) NULL"); } catch(PDOException $e) {}
    } catch (PDOException $e) {
      die(json_encode(['error'=>'DB connection failed: '.$e->getMessage()]));
    }
  }
  return $pdo;
}

// Role hierarchy: Viewer < Scorer < Referee < Admin
const ROLE_LEVELS = ['Viewer'=>1,'Scorer'=>2,'Referee'=>3,'Admin'=>4];

function isLoggedIn(): bool { return isset($_SESSION['user_id']); }

function userLevel(): int {
  return ROLE_LEVELS[$_SESSION['user_role'] ?? ''] ?? 0;
}

function requireLogin(): void {
  if (!isLoggedIn()) { header('Location:'.APP_URL.'/login.php'); exit; }
}

/** Redirect non-qualified users back to the homepage. */
function requireRole(string $minRole): void {
  if (!isLoggedIn()) { header('Location:'.APP_URL.'/login.php'); exit; }
  if (userLevel() < (ROLE_LEVELS[$minRole] ?? 99)) {
    header('Location:'.APP_URL.'/?error=forbidden'); exit;
  }
}

/** For API endpoints — emit JSON 403 instead of redirect. */
function requireRoleApi(string $minRole): void {
  if (!isLoggedIn()) { http_response_code(401); echo json_encode(['error'=>'Not authenticated']); exit; }
  if (userLevel() < (ROLE_LEVELS[$minRole] ?? 99)) { http_response_code(403); echo json_encode(['error'=>'Insufficient permissions']); exit; }
}

function currentUser(): ?array {
  if (!isLoggedIn()) return null;
  return ['id'=>$_SESSION['user_id'],'name'=>$_SESSION['user_name'],'role'=>$_SESSION['user_role']];
}

function hasRole(string $role): bool {
  return userLevel() >= (ROLE_LEVELS[$role] ?? 99);
}

function jsonResponse(array $data, int $code=200): never {
  http_response_code($code);
  header('Content-Type: application/json');
  echo json_encode($data);
  exit;
}

function csrfToken(): string {
  if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
  return $_SESSION['csrf'];
}

function verifyCsrf(): void {
  $t = $_POST['csrf'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
  if (!hash_equals($_SESSION['csrf'] ?? '', $t)) jsonResponse(['error'=>'Invalid CSRF token'], 403);
}

function logActivity(string $action, string $type=null, int $targetId=null): void {
  try {
    getDB()->prepare("INSERT INTO activity_log(user_id,action,target_type,target_id)VALUES(?,?,?,?)")
      ->execute([$_SESSION['user_id']??null, $action, $type, $targetId]);
  } catch(Exception $e) {}
}

function slug(string $str): string {
  return trim(preg_replace('/[\s-]+/','-',preg_replace('/[^a-z0-9\s-]/','',strtolower($str))),'-');
}
