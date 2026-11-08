<?php
header('Content-Type: application/json');

$conn = new mysqli("localhost", "root", "", "imbotsilog_db");

if ($conn->connect_error) {
    die(json_encode(["status" => "error", "message" => "DB failed"]));
}

$data = json_decode(file_get_contents("php://input"), true);

$name = $data['customer_name'] ?? '';
$people = $data['people'] ?? 0;
$time = $data['time'] ?? '';
$area = $data['area'] ?? '';
$tables = json_encode($data['tables'] ?? []);

$sql = "INSERT INTO reservations (customer_name, people, time, area, tables)
        VALUES (?, ?, ?, ?, ?)";

$stmt = $conn->prepare($sql);
$stmt->bind_param("sisss", $name, $people, $time, $area, $tables);

if ($stmt->execute()) {
    echo json_encode(["status" => "success", "message" => "Saved"]);
} else {
    echo json_encode(["status" => "error", "message" => $stmt->error]);
}

$stmt->close();
$conn->close();
?>