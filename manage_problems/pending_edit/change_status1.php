<?php
// change_status.php
include '../../admin/db_connection.php'; // ปรับเส้นทางให้ถูกต้อง

// ตรวจสอบว่ามีการส่งค่า ID, สถานะ และเหตุผล
if (isset($_GET['id']) && isset($_GET['status'])) {
    $id = intval($_GET['id']);
    $status = $_GET['status'];
    $reason = isset($_GET['reason']) ? $_GET['reason'] : '';

    // อัปเดตสถานะของปัญหาในตาราง problems
    $sql = "UPDATE problems SET status = ? WHERE id = ?";
    
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("si", $status, $id);

        // ตรวจสอบการอัปเดตสถานะ
        if ($stmt->execute()) {
            echo "สถานะได้รับการอัปเดตเรียบร้อยแล้ว";

            // บันทึกเหตุผลในตาราง actions ถ้ามีเหตุผลที่ส่งมา
            if (!empty($reason)) {
                $sql_insert_action = "INSERT INTO actions (problem_id, action, performed_at) VALUES (?, ?, NOW())";
                if ($stmt_action = $conn->prepare($sql_insert_action)) {
                    $stmt_action->bind_param("is", $id, $reason);
                    $stmt_action->execute();
                    $stmt_action->close();
                }
            }
        } else {
            echo "เกิดข้อผิดพลาดในการอัปเดตสถานะ: " . $stmt->error;
        }

        // ปิดคำสั่ง SQL
        $stmt->close();
    } else {
        echo "เกิดข้อผิดพลาดในการเตรียมคำสั่ง: " . $conn->error;
    }
} else {
    echo "ไม่มีการส่งข้อมูลที่จำเป็น";
}

// ปิดการเชื่อมต่อฐานข้อมูล
$conn->close();

// ส่งผู้ใช้กลับไปที่หน้าก่อนหน้า
header("Location: " . $_SERVER['HTTP_REFERER']);
exit();
?>
