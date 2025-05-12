<?php
// เปิดใช้งานการแสดงข้อผิดพลาดสำหรับการดีบัก (ควรปิดใน production)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// เริ่มต้น session
session_start();

// รวมไฟล์ตรวจสอบการล็อกอินและเชื่อมต่อฐานข้อมูล
require_once '../admin/auth_check.php';
require_once '../admin/db_connection.php';

// ตรวจสอบว่ามีการส่งคำขอแบบ POST หรือไม่
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // ตรวจสอบ CSRF token
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $_SESSION['error'] = "การตรวจสอบ CSRF token ล้มเหลว.";
        header('Location: ../admin/announcements.php');
        exit();
    }

    // ตรวจสอบว่ามีการส่งค่า 'id' และเป็นตัวเลขบวก
    if (isset($_POST['id']) && filter_var($_POST['id'], FILTER_VALIDATE_INT, ["options" => ["min_range" => 1]])) {
        $id = intval($_POST['id']);

        // ดึงข้อมูลประกาศเพื่อรับค่า image_url
        $select_sql = "SELECT image_url FROM announcements WHERE id = ?";
        $select_stmt = $conn->prepare($select_sql);
        if ($select_stmt === false) {
            $_SESSION['error'] = "การเตรียมคำสั่ง SQL ล้มเหลว: " . htmlspecialchars($conn->error);
            header('Location: ../admin/announcements.php');
            exit();
        }
        $select_stmt->bind_param("i", $id);
        if (!$select_stmt->execute()) {
            $_SESSION['error'] = "การดำเนินการคำสั่ง SQL ล้มเหลว: " . htmlspecialchars($select_stmt->error);
            header('Location: ../admin/announcements.php');
            exit();
        }
        $select_result = $select_stmt->get_result();
        if ($select_result->num_rows === 0) {
            $_SESSION['error'] = "ไม่พบประกาศที่ต้องการลบ.";
            header('Location: ../admin/announcements.php');
            exit();
        }
        $announcement = $select_result->fetch_assoc();
        $image_url = $announcement['image_url'];
        $select_stmt->close();

        // เริ่มต้นการทำธุรกรรม
        $conn->begin_transaction();

        try {
            // ลบประกาศจากฐานข้อมูล
            $delete_sql = "DELETE FROM announcements WHERE id = ?";
            $delete_stmt = $conn->prepare($delete_sql);
            if ($delete_stmt === false) {
                throw new Exception("การเตรียมคำสั่ง SQL สำหรับการลบล้มเหลว: " . htmlspecialchars($conn->error));
            }
            $delete_stmt->bind_param("i", $id);
            if (!$delete_stmt->execute()) {
                throw new Exception("การดำเนินการคำสั่ง SQL สำหรับการลบล้มเหลว: " . htmlspecialchars($delete_stmt->error));
            } else {
                echo "SQL delete executed successfully."; // แสดงข้อความเมื่อการลบสำเร็จ
            }
            $delete_stmt->close();

            // ถ้ามีรูปภาพ, ลบไฟล์รูปภาพจากเซิร์ฟเวอร์
            if (!empty($image_url)) {
                $image_path = '../uploads/' . $image_url;
                if (file_exists($image_path)) {
                    echo "File exists: " . htmlspecialchars($image_path); // ตรวจสอบเส้นทางไฟล์
                    if (!unlink($image_path)) {
                        // ถ้าไม่สามารถลบไฟล์ได้, ยกเลิกการทำธุรกรรมและแจ้งข้อผิดพลาด
                        throw new Exception("ไม่สามารถลบไฟล์รูปภาพได้: " . htmlspecialchars($image_path));
                    } else {
                        echo "File deleted successfully."; // แสดงข้อความเมื่อไฟล์ถูกลบสำเร็จ
                    }
                } else {
                    echo "File does not exist: " . htmlspecialchars($image_path); // แสดงข้อความหากไม่พบไฟล์
                }
            }

            // ยืนยันการทำธุรกรรม
            $conn->commit();

            // ตั้งข้อความแจ้งเตือนความสำเร็จ
            $_SESSION['message'] = "ประกาศถูกลบเรียบร้อยแล้ว!";
            header('Location: ../admin/announcements.php');
            exit();
        } catch (Exception $e) {
            // ยกเลิกการทำธุรกรรมในกรณีที่เกิดข้อผิดพลาด
            $conn->rollback();

            // ตั้งข้อความแจ้งเตือนข้อผิดพลาด
            $_SESSION['error'] = "เกิดข้อผิดพลาดในการลบประกาศ: " . $e->getMessage();
            header('Location: ../admin/announcements.php');
            exit();
        }
    } else {
        // ถ้าไม่มีการส่งค่า 'id' หรือไม่เป็นตัวเลขบวก
        $_SESSION['error'] = "รหัสประกาศไม่ถูกต้อง.";
        header('Location: ../admin/announcements.php');
        exit();
    }
} else {
    // ถ้าไม่ใช่คำขอแบบ POST
    $_SESSION['error'] = "วิธีการเข้าถึงหน้าลบไม่ถูกต้อง.";
    header('Location: ../admin/announcements.php');
    exit();
}
