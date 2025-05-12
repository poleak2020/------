<?php
// เริ่มการใช้งาน session
session_start();

// นำเข้าการเชื่อมต่อฐานข้อมูล
include '../admin/db_connection.php';

// ตรวจสอบว่ามี ID ที่ส่งมาหรือไม่
if (isset($_GET['id'])) {
    $payment_id = intval($_GET['id']); // รับค่า ID ของการชำระเงิน

    // ตรวจสอบว่าการชำระเงินที่ต้องการปฏิเสธมีอยู่ในระบบหรือไม่
    $sql = "SELECT * FROM payments WHERE id = ?";
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param('i', $payment_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            // หากพบข้อมูลการชำระเงิน ให้ทำการอัปเดตสถานะเป็น 'rejected'
            $update_sql = "UPDATE payments SET status = 'rejected' WHERE id = ?";
            if ($update_stmt = $conn->prepare($update_sql)) {
                $update_stmt->bind_param('i', $payment_id);
                if ($update_stmt->execute()) {
                    // แสดงข้อความแจ้งเตือนสำเร็จ
                    echo "<script>alert('การชำระเงินถูกปฏิเสธแล้ว'); window.location.href = 'admin_verify_payments.php';</script>";
                } else {
                    // หากเกิดข้อผิดพลาดในการปฏิเสธการชำระเงิน
                    echo "<script>alert('เกิดข้อผิดพลาดในการปฏิเสธการชำระเงิน'); window.location.href = 'admin_verify_payments.php';</script>";
                }
            } else {
                // Log the error instead of displaying it directly
                error_log("Error preparing update statement: " . $conn->error);
                echo "<script>alert('เกิดข้อผิดพลาดในการดำเนินการ'); window.location.href = 'admin_verify_payments.php';</script>";
            }
        } else {
            // หากไม่พบการชำระเงินที่มี ID นี้
            echo "<script>alert('ไม่พบข้อมูลการชำระเงินนี้'); window.location.href = 'admin_verify_payments.php';</script>";
        }
        $stmt->close();
    } else {
        // Log the error instead of displaying it directly
        error_log("Error preparing select statement: " . $conn->error);
        echo "<script>alert('เกิดข้อผิดพลาดในการตรวจสอบการชำระเงิน'); window.location.href = 'admin_verify_payments.php';</script>";
    }
} else {
    // หากไม่มี ID ส่งเข้ามา
    echo "<script>alert('ไม่มีข้อมูลการชำระเงินที่เลือก'); window.location.href = 'admin_verify_payments.php';</script>";
}

// ปิดการเชื่อมต่อฐานข้อมูล
$conn->close();
?>
