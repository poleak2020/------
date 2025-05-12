<?php
include '../admin/auth_check.php'; // ตรวจสอบการล็อกอินและข้อมูล admin

// เชื่อมต่อฐานข้อมูล
require '../admin/db_connection.php';

// ตรวจสอบว่าได้รับ ID ของผู้ใช้งานหรือไม่
if (isset($_GET['id']) && filter_var($_GET['id'], FILTER_VALIDATE_INT)) {
    $user_id = $_GET['id'];

    // เตรียมคำสั่ง SQL เพื่อลบข้อมูลของผู้ใช้งาน
    $sql = "DELETE FROM users WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);

    // ดำเนินการคำสั่ง SQL
    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) { 
            // หลังจากลบสำเร็จ ให้เปลี่ยนเส้นทางไปยังหน้าผู้ใช้งาน
            header("Location: ../admin/users.php?success=ลบผู้ใช้งานสำเร็จแล้ว");
            exit();
        } else {
            echo "ไม่พบข้อมูลผู้ใช้งานที่ต้องการลบ";
        }
    } else {
        echo "เกิดข้อผิดพลาด: " . $stmt->error;
    }

    // ปิดการเชื่อมต่อ
    $stmt->close();
    $conn->close();
} else {
    die('ID ผู้ใช้งานไม่ถูกต้อง');
}
