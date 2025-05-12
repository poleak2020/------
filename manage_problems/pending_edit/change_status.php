<?php
include '../../admin/db_connection.php';

// เปิดการแสดงข้อผิดพลาด
error_reporting(E_ALL);
ini_set('display_errors', 1);

function jsonResponse($success, $message)
{
    echo json_encode(['success' => $success, 'message' => $message]);
    exit();
}

// ตรวจสอบว่ามีการส่งค่า ID, สถานะ และเหตุผล (จาก method POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id']) && isset($_POST['status'])) {
    $id = intval($_POST['id']);
    $status = $_POST['status'];
    $reason = isset($_POST['action']) ? $_POST['action'] : '';

    // เริ่มต้นการอัปเดตสถานะของปัญหาในตาราง problems
    $sql = "UPDATE problems SET status = ? WHERE id = ?";

    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("si", $status, $id);

        // ตรวจสอบการอัปเดตสถานะ
        if ($stmt->execute()) {
            // บันทึกเหตุผลในตาราง actions ถ้ามีเหตุผลที่ส่งมา
            if (!empty($reason)) {
                $sql_insert_action = "INSERT INTO actions (problem_id, action, performed_at) VALUES (?, ?, NOW())";
                if ($stmt_action = $conn->prepare($sql_insert_action)) {
                    $stmt_action->bind_param("is", $id, $reason);
                    if (!$stmt_action->execute()) {
                        jsonResponse(false, 'เกิดข้อผิดพลาดในการบันทึกเหตุผล: ' . $stmt_action->error);
                    }
                    $stmt_action->close();
                } else {
                    jsonResponse(false, 'เกิดข้อผิดพลาดในการเตรียมคำสั่งบันทึกเหตุผล: ' . $conn->error);
                }
            }

            // ตรวจสอบว่ามีการอัปโหลดไฟล์รูปภาพหรือไม่
            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $imageFile = $_FILES['image'];
                $imageFileName = uniqid() . '.' . pathinfo($imageFile['name'], PATHINFO_EXTENSION);
                $imagePath = '../../uploads/' . $imageFileName; // สร้างชื่อไฟล์ที่ปลอดภัย

                // ตรวจสอบว่าสามารถย้ายไฟล์ได้หรือไม่
                if (move_uploaded_file($imageFile['tmp_name'], $imagePath)) {
                    // บันทึกรูปภาพในตาราง images
                    $sql_insert_image = "INSERT INTO images (related_id, file_name, uploaded_at, image_type) VALUES (?, ?, NOW(), 'problem')";
                    if ($stmt_image = $conn->prepare($sql_insert_image)) {
                        $stmt_image->bind_param("is", $id, $imageFileName); // บันทึกเพียงชื่อไฟล์ในฐานข้อมูล
                        if (!$stmt_image->execute()) {
                            jsonResponse(false, 'เกิดข้อผิดพลาดในการบันทึกรูปภาพ: ' . $stmt_image->error);
                        }
                        $stmt_image->close();
                    } else {
                        jsonResponse(false, 'เกิดข้อผิดพลาดในการเตรียมคำสั่งสำหรับรูปภาพ: ' . $conn->error);
                    }
                } else {
                    jsonResponse(false, 'เกิดข้อผิดพลาดในการอัปโหลดรูปภาพ');
                }
            }

            // ส่งข้อมูลตอบกลับเมื่อดำเนินการสำเร็จ
            jsonResponse(true, 'ดำเนินการสำเร็จ');
        } else {
            jsonResponse(false, 'เกิดข้อผิดพลาดในการอัปเดตสถานะ: ' . $stmt->error);
        }

        // ปิดการใช้งาน statement
        $stmt->close();
    } else {
        jsonResponse(false, 'เกิดข้อผิดพลาดในการเตรียมคำสั่ง: ' . $conn->error);
    }
} else {
    jsonResponse(false, 'ไม่มีการส่งข้อมูลที่จำเป็น');
}

// ปิดการเชื่อมต่อฐานข้อมูล
$conn->close();
?>
