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
$username = $data["username"] ?? null;

if (!$username) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "Username is required"]);
    exit;
}

// Get user_id from username
$user_query = "SELECT id FROM users WHERE login = $1 LIMIT 1";
$user_result = pg_query_params($conn, $user_query, [$username]);
if (!$user_result || pg_num_rows($user_result) === 0) {
    echo json_encode(["success" => true, "data" => []]); // No constats for non-existent user
    exit;
}
$user_row = pg_fetch_assoc($user_result);
$user_id = $user_row["id"];

// Fetch constats with joins to get nom, prenom, and designation
$query = "SELECT c.id, c.date, c.user_id, c.observation, c.action_propose, c.etat, c.date_traitement, c.action_faite, c.id_type, 
                 u.nom, u.prenom, t.designation
          FROM constat c
          LEFT JOIN users u ON c.user_id = u.id
          LEFT JOIN type_constat t ON c.id_type = t.id
          WHERE c.user_id = $1";
$result = pg_query_params($conn, $query, [$user_id]);

$constats = [];
while ($row = pg_fetch_assoc($result)) {
    $constats[] = [
        "id" => $row["id"],
        "date" => $row["date"],
        "username" => $username, // Return username as login for consistency
        "observation" => $row["observation"],
        "action_propose" => $row["action_propose"],
        "etat" => $row["etat"],
        "date_traitement" => $row["date_traitement"],
        "action_faite" => $row["action_faite"],
        "designation" => $row["designation"],
        "nom" => $row["nom"],
        "prenom" => $row["prenom"]
    ];
}

echo json_encode(["success" => true, "data" => $constats]);
pg_close($conn);
?>