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
$id = $data["id"] ?? null;

if (!$id) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "Constat ID is required"]);
    exit;
}

$query = "SELECT c.id, c.date, c.user_id, c.observation, c.action_propose, c.etat, c.date_traitement, c.action_faite, c.id_type, 
                 u.login as username, u.nom, u.prenom, t.designation
          FROM constat c
          LEFT JOIN users u ON c.user_id = u.id
          LEFT JOIN type_constat t ON c.id_type = t.id
          WHERE c.id = $1";
$result = pg_query_params($conn, $query, [$id]);

if ($result && pg_num_rows($result) > 0) {
    $row = pg_fetch_assoc($result);
    echo json_encode(["success" => true, "data" => $row]);
} else {
    http_response_code(404);
    echo json_encode(["success" => false, "message" => "Constat not found"]);
}

pg_close($conn);
?>