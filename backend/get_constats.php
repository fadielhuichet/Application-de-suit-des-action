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
if (!$data || !isset($data["username"])) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "Username manquant"]);
    exit;
}

$username = $data["username"];
$checkUserQuery = "SELECT id FROM users WHERE login = $1 LIMIT 1";
$checkUserResult = pg_query_params($conn, $checkUserQuery, [$username]);
if (!$checkUserResult || pg_num_rows($checkUserResult) === 0) {
    echo json_encode(["success" => true, "data" => []]);
    exit;
}
$userRow = pg_fetch_assoc($checkUserResult);
$user_id = $userRow["id"];

$query = "SELECT c.id, c.date, c.user_id, u.login AS username, u.nom, u.prenom, 
                 c.observation, c.action_propose, c.etat, c.date_traitement, c.action_faite, 
                 c.id_type, t.designation 
          FROM constat c 
          LEFT JOIN users u ON c.user_id = u.id 
          LEFT JOIN type_constat t ON c.id_type = t.id 
          WHERE c.user_id = $1";
$result = pg_query_params($conn, $query, [$user_id]);

if (!$result) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Erreur lors de la requête : " . pg_last_error($conn)]);
    exit;
}

$constats = [];
while ($row = pg_fetch_assoc($result)) {
    $row["nom"] = $row["nom"] ?? "Inconnu";
    $row["prenom"] = $row["prenom"] ?? "";
    $row["designation"] = $row["designation"] ?? "Inconnu";
    $constats[] = $row;
}

echo json_encode(["success" => true, "data" => $constats]);
pg_close($conn);
?>