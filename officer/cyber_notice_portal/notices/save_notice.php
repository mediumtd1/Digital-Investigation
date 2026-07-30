<?php
require_once '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $office_name = $_POST['office_name'] ?? '';
    $office_address = $_POST['office_address'] ?? '';
    $office_email = $_POST['office_email'] ?? '';
    $office_phone = $_POST['office_phone'] ?? '';
    $reference_no = $_POST['reference_no'] ?? '';
    $io_name = $_POST['io_name'] ?? '';
    $io_rank = $_POST['io_rank'] ?? '';
    
    $recipient_name = $_POST['recipient_name'] ?? '';
    $recipient_address = $_POST['recipient_address'] ?? '';
    $notice_type = $_POST['crime_type'] ?? 'Section 94 BNSS Notice';
    
    $data = $_POST['data'] ?? [];
    $data['requirements'] = $_POST['requirements'] ?? [];
    $data['category'] = $_POST['category'] ?? ''; // Save category for letter generation
    $content_json = json_encode($data);
    
    // FIR Number can be handled as part of the case or data
    $fir_number = $_POST['fir_number'] ?? 'N/A';

    try {
        $stmt = $pdo->prepare("INSERT INTO notices (notice_type, recipient_name, recipient_address, content_json, office_name, office_address, office_email, office_phone, reference_no, io_name, io_rank) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$notice_type, $recipient_name, $recipient_address, $content_json, $office_name, $office_address, $office_email, $office_phone, $reference_no, $io_name, $io_rank]);
        
        $notice_id = $pdo->lastInsertId();
        header("Location: ../view_notice.php?id=" . $notice_id);
        exit();
    } catch (PDOException $e) {
        die("Error saving notice: " . $e->getMessage());
    }
}
