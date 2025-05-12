<?php
require_once '../admin/db_connection.php';

// ตรวจสอบการล็อกอินเพื่อความปลอดภัย
require_once '../admin/auth_check.php';

header('Content-Type: application/json');

// ฟังก์ชันสำหรับตรวจสอบยอดเงินคงเหลือจากตาราง payments ที่มีสถานะ 'approved'
function getAvailableBalance($conn) {
    $stmt = $conn->prepare("SELECT SUM(amount) as total_payments FROM payments WHERE status = 'approved'");
    if ($stmt === false) {
        return ['success' => false, 'message' => 'การเตรียมคำสั่ง SQL ล้มเหลว'];
    }
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    return ['success' => true, 'available_balance' => isset($result['total_payments']) ? $result['total_payments'] : 0];
}

// ตรวจสอบยอดเงินคงเหลือ
try {
    $balanceData = getAvailableBalance($conn);
    echo json_encode($balanceData);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'เกิดข้อผิดพลาดในการตรวจสอบยอดเงิน']);
}

$conn->close();
?>
