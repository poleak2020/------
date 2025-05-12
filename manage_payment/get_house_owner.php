<?php
include '../admin/db_connection.php';

// ตรวจสอบการส่งข้อมูล
if (isset($_POST['house_number'])) {
    $house_number = $_POST['house_number'];
    
    // ดึงข้อมูลเจ้าของบ้านเลขที่
    $stmt = $conn->prepare("SELECT owner_name FROM users WHERE house_number = ?");
    $stmt->bind_param("s", $house_number);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        echo json_encode(['success' => true, 'owner_name' => $row['owner_name']]);
    } else {
        echo json_encode(['success' => false]);
    }

    $stmt->close();
} else {
    echo json_encode(['success' => false, 'message' => 'ไม่มีข้อมูลบ้านเลขที่']);
}

$conn->close();
?>
