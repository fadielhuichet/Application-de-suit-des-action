<?php
// Enable error reporting for debugging (disable in production)
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Set CORS headers
header("Access-Control-Allow-Origin: http://localhost:3000");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Max-Age: 86400"); // Cache preflight for 24 hours

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

$host = "localhost";
$dbname = "Suit_Action";
$user = "postgres";
$password = "fedi1234";

$conn = pg_connect("host=$host port=5432 dbname=$dbname user=$user password=$password");

if (!$conn) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Database connection failed", "error" => pg_last_error()]);
    exit;
}


$data = json_decode(file_get_contents("php://input"), true);
if (!$data || !isset($data["login"]) || !isset($data["nom"]) || !isset($data["prenom"]) || !isset($data["role"])) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "Invalid data"]);
    exit;
}

$login = $data["login"];
$nom = $data["nom"];
$prenom = $data["prenom"];
$role = $data["role"];
$mot_de_passe = $data["mot_de_passe"] ?? null;

$checkUserQuery = "SELECT id FROM users WHERE login = $1 LIMIT 1";
$checkUserResult = pg_query_params($conn, $checkUserQuery, [$login]);
if (!$checkUserResult || pg_num_rows($checkUserResult) === 0) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "User not found"]);
    exit;
}

$query = "UPDATE users SET nom = $1, prenom = $2, role = $3";
$params = [$nom, $prenom, $role];
$paramIndex = 4;

if (!empty($mot_de_passe)) {
    $hashed_password = password_hash($mot_de_passe, PASSWORD_BCRYPT);
    $query .= ", mot_de_passe = $" . $paramIndex;
    $params[] = $hashed_password;
    $paramIndex++;
}

$query .= " WHERE login = $" . $paramIndex;
$params[] = $login;

$result = pg_query_params($conn, $query, $params);

if ($result) {
    echo json_encode(["success" => true, "message" => "Profile updated successfully"]);
} else {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Update failed", "error" => pg_last_error($conn)]);
}

pg_close($conn);
?>