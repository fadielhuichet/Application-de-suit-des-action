<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

header("Access-Control-Allow-Origin: http://localhost:3000");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

$host = "localhost";
$dbname = "Suit_Action";
$user = "postgres";
$password = "fedi1234";

$conn = pg_connect("host=$host dbname=$dbname user=$user password=$password");
if (!$conn) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Database connection failed"]);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);
if (!$data || !isset($data["id"]) || !isset($data["username"]) || !isset($data["observation"]) || !isset($data["designation"])) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "Invalid or missing data"]);
    exit;
}

$id = $data["id"];
$username = $data["username"];
$observation = $data["observation"];
$designation = $data["designation"];
$date = $data["date"] ?? date("Y-m-d");
$action_propose = $data["action_propose"] ?? null;
$etat = $data["etat"] ?? "En attente";
$date_traitement = $data["date_traitement"] ?? null;
$action_faite = $data["action_faite"] ?? null;

// Get user_id
$res_user = pg_query_params($conn, "SELECT id FROM users WHERE login = $1 LIMIT 1", [$username]);
if (!$res_user || pg_num_rows($res_user) === 0) {
    http_response_code(404);
    echo json_encode(["success" => false, "message" => "Utilisateur non trouvé"]);
    exit;
}
$user_row = pg_fetch_assoc($res_user);
$user_id = $user_row["id"];

// Get or insert type_id
$res_type = pg_query_params($conn, "SELECT id FROM type_constat WHERE designation = $1 LIMIT 1", [$designation]);
if (pg_num_rows($res_type) > 0) {
    $type_row = pg_fetch_assoc($res_type);
    $type_id = $type_row["id"];
} else {
    $res_insert_type = pg_query_params($conn, "INSERT INTO type_constat (designation) VALUES ($1) RETURNING id", [$designation]);
    $type_row = pg_fetch_assoc($res_insert_type);
    $type_id = $type_row["id"];
}

// Update constat
$query = "UPDATE constat SET date = $1, user_id = $2, observation = $3, action_propose = $4, etat = $5, date_traitement = $6, action_faite = $7, id_type = $8 WHERE id = $9";
$params = [$date, $user_id, $observation, $action_propose, $etat, $date_traitement, $action_faite, $type_id, $id];

$result = pg_query_params($conn, $query, $params);

if ($result) {
    echo json_encode(["success" => true, "message" => "Constat updated successfully"]);
} else {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Failed to update constat", "error" => pg_last_error($conn)]);
}

pg_close($conn);
?>