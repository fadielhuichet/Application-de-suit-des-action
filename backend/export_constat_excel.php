<?php
require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

ini_set('display_errors', 1);
error_reporting(E_ALL);

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
    $constat = pg_fetch_assoc($result);
} else {
    http_response_code(404);
    echo json_encode(["success" => false, "message" => "Constat not found"]);
    exit;
}

pg_close($conn);

// Create spreadsheet
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

$sheet->setCellValue('A1', 'Constat ID');
$sheet->setCellValue('B1', 'Date');
$sheet->setCellValue('C1', 'Responsable');
$sheet->setCellValue('D1', 'Observation');
$sheet->setCellValue('E1', 'Action proposée');
$sheet->setCellValue('F1', 'Action faite');
$sheet->setCellValue('G1', 'État');
$sheet->setCellValue('H1', 'Date de traitement');
$sheet->setCellValue('I1', 'Type');

$sheet->setCellValue('A2', $constat['id']);
$sheet->setCellValue('B2', $constat['date']);
$sheet->setCellValue('C2', "{$constat['nom']} {$constat['prenom']} ({$constat['username']})");
$sheet->setCellValue('D2', $constat['observation']);
$sheet->setCellValue('E2', $constat['action_propose'] ?: 'Aucune');
$sheet->setCellValue('F2', $constat['action_faite'] ?: 'Aucune');
$sheet->setCellValue('G2', $constat['etat']);
$sheet->setCellValue('H2', $constat['date_traitement'] ?: 'Non définie');
$sheet->setCellValue('I2', $constat['designation']);

$writer = new Xlsx($spreadsheet);
$tempFile = sys_get_temp_dir() . "/constat_{$id}.xlsx";
$writer->save($tempFile);

header("Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet");
header("Content-Disposition: attachment; filename=constat_{$id}.xlsx");
readfile($tempFile);
unlink($tempFile);
exit;
?>