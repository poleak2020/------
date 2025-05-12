<?php
// ตรวจสอบว่าเซสชันยังไม่เริ่มต้น
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ตรวจสอบว่าผู้ใช้เข้าสู่ระบบหรือไม่
if (!isset($_SESSION['user_id'])) {
    // ถ้าไม่ได้เข้าสู่ระบบ จะถูกเปลี่ยนไปยังหน้า Loginadmin.php
    header("Location: Loginadmin.php");
    exit();
}

// เชื่อมต่อฐานข้อมูล
require '../admin/db_connection.php';

// ตรวจสอบว่าข้อมูลในตาราง admin ยังอยู่หรือไม่
$stmt = $conn->prepare("SELECT id FROM admin WHERE id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$stmt->store_result();

// ถ้าไม่มีข้อมูลในตาราง admin ให้เด้งกลับไปหน้า Loginadmin.php
if ($stmt->num_rows === 0) {
    session_destroy();
    header("Location: Loginadmin.php");
    exit();
}

// ปิด statement
$stmt->close();