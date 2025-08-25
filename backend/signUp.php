<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *"); 
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");

$host = "localhost";
$dbname = "Suit_Action";
$user = "postgres";
$password = "fedi1234";


$conn = pg_connect("host=$host dbname=$dbname user=$user password=$password");

if (!$conn) {
    echo json_encode(["message" => "Erreur connexion DB"]);
    exit;
}


$data = json_decode(file_get_contents("php://input"), true);

if (!$data) {
    echo json_encode(["message" => "Aucune donnée reçue"]);
    exit;
}

$nom = $data['nom'];
$prenom = $data['prenom'];
$username = $data['username'];
$passwordHash = password_hash($data['password'], PASSWORD_BCRYPT);
$poste = $data['poste'];
$role = $data['role'];


$query = "INSERT INTO users (nom, prenom, login, mot_de_passe, poste, role) 
          VALUES ($1, $2, $3, $4, $5, $6)";
$result = pg_query_params($conn, $query, [$nom, $prenom, $username, $passwordHash, $poste, $role]);

if ($result) {
    echo json_encode(["message" => "Utilisateur créé avec succès"]);
} else {
    echo json_encode(["message" => "Erreur lors de l'inscription"]);
}
?>
