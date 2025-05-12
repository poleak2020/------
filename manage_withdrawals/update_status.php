<?php
// เริ่มต้น session
session_start();

// รวมไฟล์เชื่อมต่อฐานข้อมูล
require_once '../admin/db_connection.php';

// ตรวจสอบการเชื่อมต่อฐานข้อมูล
if ($conn->connect_error) {
    $_SESSION['error'] = "ไม่สามารถเชื่อมต่อฐานข้อมูลได้ กรุณาลองใหม่อีกครั้ง";
    header('Location: ../admin/withdrawals.php');
    exit();
}

// ตรวจสอบว่ามีการร้องขอแบบ POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // ตรวจสอบ CSRF Token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $_SESSION['error'] = "CSRF Token ไม่ถูกต้อง";
        header('Location: ../admin/withdrawals.php');
        exit();
    } else {
        // สร้าง CSRF Token ใหม่หลังจากการตรวจสอบ
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    // รับค่า ID และการดำเนินการจากฟอร์ม
    $withdrawal_id = $_POST['id'];
    $action = $_POST['action'];

    // ตรวจสอบการดำเนินการ
    $valid_actions = ['approve', 'reject']; // กำหนด action ที่ยอมรับ
    if (!in_array($action, $valid_actions)) {
        $_SESSION['error'] = "การกระทำไม่ถูกต้อง";
        header('Location: ../admin/withdrawals.php');
        exit();
    }

    if ($action === 'approve') {
        $status = 'Approved'; // เปลี่ยนสถานะเป็น Approved
    } elseif ($action === 'reject') {
        $status = 'Rejected'; // เปลี่ยนสถานะเป็น Rejected
    }

    // ตรวจสอบว่ามีการอัปโหลดไฟล์หรือไม่
    if (!empty($_FILES['related_files']['name'][0])) {
        $upload_dir = '../uploads/'; // โฟลเดอร์เก็บไฟล์ที่อัปโหลด
        $uploaded_files = [];

        // กำหนดชนิดไฟล์และขนาดไฟล์ที่ยอมรับ
        $allowed_types = ['pdf', 'jpeg', 'jpg', 'png'];
        $max_size = 5 * 1024 * 1024; // ขนาดไฟล์สูงสุด 5MB

        foreach ($_FILES['related_files']['name'] as $key => $filename) {
            $file_tmp = $_FILES['related_files']['tmp_name'][$key];
            $file_ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
            $file_size = $_FILES['related_files']['size'][$key];
            $new_filename = uniqid() . '.' . $file_ext; // ตั้งชื่อไฟล์ใหม่เพื่อป้องกันชื่อซ้ำกัน
            $destination = $upload_dir . $new_filename;

            // ตรวจสอบชนิดและขนาดไฟล์
            if (in_array($file_ext, $allowed_types) && $file_size <= $max_size) {
                if (move_uploaded_file($file_tmp, $destination)) {
                    $uploaded_files[] = $new_filename;
                }
            } else {
                $_SESSION['error'] = "ไฟล์ที่อัปโหลดไม่ถูกต้อง หรือขนาดไฟล์เกินกำหนด";
                header('Location: ../admin/withdrawals.php');
                exit();
            }
        }

        // บันทึกข้อมูลไฟล์ที่อัปโหลดลงในฐานข้อมูล (ถ้ามีไฟล์ที่อัปโหลด)
        if (!empty($uploaded_files)) {
            $uploaded_files_str = implode(',', $uploaded_files); // รวมชื่อไฟล์เป็นสตริงเดียว
            $stmt = $conn->prepare("UPDATE withdrawals SET related_files = ? WHERE id = ?");
            if ($stmt) {
                $stmt->bind_param("si", $uploaded_files_str, $withdrawal_id);
                $stmt->execute();
                $stmt->close();
            }
        }
    }

    // ทำการอัปเดตสถานะเบิกเงิน
    $stmt = $conn->prepare("UPDATE withdrawals SET status = ? WHERE id = ?");
    if ($stmt) {
        $stmt->bind_param("si", $status, $withdrawal_id);
        $stmt->execute();
        $stmt->close();
        $_SESSION['success'] = "การเปลี่ยนสถานะสำเร็จ";
    } else {
        $_SESSION['error'] = "เกิดข้อผิดพลาดในการเปลี่ยนสถานะ";
    }

    header('Location: ../admin/withdrawals.php');
    exit();
} else {
    header('Location: ../admin/withdrawals.php');
    exit();
}
