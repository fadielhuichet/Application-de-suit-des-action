<?php
ini_set('display_errors', 0);
error_reporting(E_ALL);
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: http://localhost:3000");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

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
    echo json_encode(["success" => false, "message" => "Connexion échouée"]);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);
if (!$data || !isset($data["username"]) || !isset($data["observation"]) || !isset($data["designation"])) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "Données invalides"]);
    exit;
}

$username = $data["username"];
$observation = $data["observation"];
$action_propose = $data["action_propose"] ?? null;
$etat = $data["etat"] ?? "En attente";
$date = $data["date"] ?? date("Y-m-d");
$date_traitement = $data["date_traitement"] ?? null;
$action_faite = $data["action_faite"] ?? null;
$designation = $data["designation"];

if (empty($username) || empty($observation) || empty($designation)) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "Champs obligatoires manquants"]);
    exit;
}

$checkUserQuery = "SELECT id FROM users WHERE login = $1 LIMIT 1";
$checkUserResult = pg_query_params($conn, $checkUserQuery, [$username]);
if (!$checkUserResult || pg_num_rows($checkUserResult) === 0) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "Utilisateur non trouvé"]);
    exit;
}
$userRow = pg_fetch_assoc($checkUserResult);
$user_id = $userRow["id"];

$checkTypeQuery = "SELECT id FROM type_constat WHERE designation = $1 LIMIT 1";
$checkTypeResult = pg_query_params($conn, $checkTypeQuery, [$designation]);
if ($checkTypeResult && pg_num_rows($checkTypeResult) > 0) {
    $row = pg_fetch_assoc($checkTypeResult);
    $id_type = $row["id"];
} else {
    $insertType = "INSERT INTO type_constat (designation) VALUES ($1) RETURNING id";
    $insertResult = pg_query_params($conn, $insertType, [$designation]);
    if (!$insertResult) {
        http_response_code(500);
        echo json_encode(["success" => false, "message" => "Erreur insertion designation", "error" => pg_last_error($conn)]);
        exit;
    }
    $row = pg_fetch_assoc($insertResult);
    $id_type = $row["id"];
}

$query = "INSERT INTO constat (date, user_id, observation, action_propose, etat, date_traitement, action_faite, id_type) 
          VALUES ($1, $2, $3, $4, $5, $6, $7, $8) RETURNING id";
$result = pg_query_params($conn, $query, [$date, $user_id, $observation, $action_propose, $etat, $date_traitement, $action_faite, $id_type]);

if ($result) {
    $row = pg_fetch_assoc($result);
    echo json_encode(["success" => true, "message" => "Constat ajouté avec succès", "id" => $row["id"]]);
} else {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Erreur lors de l'ajout du constat", "error" => pg_last_error($conn)]);
}

pg_close($conn);
?>