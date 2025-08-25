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
if (!$data || !isset($data["id"]) || !isset($data["username"])) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "Données invalides"]);
    exit;
}

$id = $data["id"];
$username = $data["username"];
$observation = $data["observation"] ?? null;
$action_propose = $data["action_propose"] ?? null;
$etat = $data["etat"] ?? null;
$date_traitement = $data["date_traitement"] ?? null;
$action_faite = $data["action_faite"] ?? null;
$designation = $data["designation"] ?? null;

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

$query = "UPDATE constat SET 
          observation = COALESCE($1, observation), 
          action_propose = COALESCE($2, action_propose), 
          etat = COALESCE($3, etat), 
          date_traitement = COALESCE($4, date_traitement), 
          action_faite = COALESCE($5, action_faite), 
          id_type = COALESCE($6, id_type)
          WHERE id = $7 AND user_id = $8";
$result = pg_query_params($conn, $query, [$observation, $action_propose, $etat, $date_traitement, $action_faite, $id_type, $id, $user_id]);

if ($result) {
    echo json_encode(["success" => true, "message" => "Constat mis à jour avec succès"]);
} else {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Erreur lors de la mise à jour", "error" => pg_last_error($conn)]);
}

pg_close($conn);
?>