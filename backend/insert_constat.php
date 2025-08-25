<?php
header("Access-Control-Allow-Origin: http://localhost:3000");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

$host = "localhost";
$dbname = "Suit_Action";
$user = "postgres";
$password = "fedi1234";

$dbconn = pg_connect("host=$host dbname=$dbname user=$user password=$password");
if (!$dbconn) {
    echo json_encode(["success" => false, "message" => "Connexion échouée"]);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);

$date = $data["date"] ?? date("Y-m-d");
$user_id = $data["user_id"] ?? null;  
$observation = $data["observation"] ?? '';
$action_propose = $data["action_propose"] ?? '';
$etat = $data["etat"] ?? 'En attente';
$date_traitement = $data["date_traitement"] ?? null;
$action_faite = $data["action_faite"] ?? '';
$id_type = $data["id_type"] ?? null;

if (!$user_id || !$id_type) {
    echo json_encode(["success" => false, "message" => "user_id et id_type obligatoires"]);
    exit;
}

$query = "INSERT INTO constat (date, user_id, observation, action_propose, etat, date_traitement, action_faite, id_type) 
          VALUES ($1,$2,$3,$4,$5,$6,$7,$8) RETURNING id";

$result = pg_query_params($dbconn, $query, [
    $date, $user_id, $observation, $action_propose, $etat,
    $date_traitement, $action_faite, $id_type
]);

if ($result) {
    $row = pg_fetch_assoc($result);
    echo json_encode(["success" => true, "id" => $row["id"]]);
} else {
    echo json_encode(["success" => false, "message" => pg_last_error($dbconn)]);
}

pg_close($dbconn);
?>
