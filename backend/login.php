<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Content-Type: application/json; charset=UTF-8");

// Lire le JSON reçu
$input = json_decode(file_get_contents("php://input"), true);
if (!$input || !isset($input["login"]) || !isset($input["mot_de_passe"])) {
    echo json_encode(["success" => false, "message" => "Données invalides"]);
    exit;
}

$login = trim($input["login"]);
$password = trim($input["mot_de_passe"]);

try {
    // Connexion à PostgreSQL
    $pdo = new PDO("pgsql:host=localhost;port=5432;dbname=Suit_Action", "postgres", "fedi1234");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Vérifier si l'utilisateur existe
    $stmt = $pdo->prepare("SELECT id, login, mot_de_passe FROM users WHERE login = :login LIMIT 1");
    $stmt->execute([":login" => $login]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        $storedPassword = rtrim($user["mot_de_passe"]); // enlève espaces si CHAR(n)

        // Vérifie si c’est un mot de passe haché (bcrypt / argon2)
        if (preg_match('/^\$2y\$|\$argon2id\$/', $storedPassword)) {
            $isValid = password_verify($password, $storedPassword);
        } else {
            // comparaison en clair (si tu n’as pas encore mis de hash)
            $isValid = hash_equals($storedPassword, $password);
        }

        if ($isValid) {
            echo json_encode([
                "success" => true,
                "message" => "Connexion réussie",
                "user" => [
                    "id" => $user["id"],
                    "login" => $user["login"]
                ]
            ]);
        } else {
            echo json_encode(["success" => false, "message" => "Mot de passe incorrect"]);
        }
    } else {
        echo json_encode(["success" => false, "message" => "Utilisateur non trouvé"]);
    }

} catch (PDOException $e) {
    echo json_encode(["success" => false, "message" => "Erreur : " . $e->getMessage()]);
}
