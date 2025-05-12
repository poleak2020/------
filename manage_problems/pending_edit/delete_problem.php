<?php
// delete_problem.php
include '../../admin/db_connection.php';

// ตรวจสอบว่ามีการส่งค่า ID และเหตุผล
if (isset($_GET['id']) && isset($_GET['reason'])) {
    $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
    $reason = filter_input(INPUT_GET, 'reason', FILTER_SANITIZE_STRING);

    // ตรวจสอบว่า ID ถูกต้องหรือไม่
    if ($id && $reason) {
        // คำสั่ง SQL เพื่อเปลี่ยนสถานะของปัญหาเป็น deleted
        $sql = "UPDATE problems SET status = 'deleted' WHERE id = ?";

        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("i", $id);

            // ตรวจสอบการดำเนินการเปลี่ยนสถานะ
            if ($stmt->execute()) {
                // บันทึกเหตุผลในตาราง actions
                $sql_action = "INSERT INTO actions (problem_id, action, performed_at) VALUES (?, ?, NOW())";
                if ($stmt_action = $conn->prepare($sql_action)) {
                    $stmt_action->bind_param("is", $id, $reason);
                    $stmt_action->execute();
                    $stmt_action->close();
                }
                echo 'เปลี่ยนสถานะและบันทึกเหตุผลการลบเรียบร้อยแล้ว';
            } else {
                echo "เกิดข้อผิดพลาดในการเปลี่ยนสถานะ: " . $stmt->error;
            }

            $stmt->close();
        } else {
            echo "เกิดข้อผิดพลาดในการเตรียมคำสั่ง SQL: " . $conn->error;
        }
    } else {
        echo "ข้อมูล ID หรือเหตุผลไม่ถูกต้อง";
    }
} else {
    echo "ไม่มีการส่งข้อมูลที่จำเป็น";
}

// ปิดการเชื่อมต่อฐานข้อมูล
$conn->close();

// เปลี่ยนเส้นทางกลับไปยังหน้า pending_problems.php หลังจากเปลี่ยนสถานะและบันทึกข้อมูลเสร็จสิ้น
header("Location: http://localhost/%e0%b8%9d%e0%b8%b6%e0%b8%81%e0%b8%87%e0%b8%b2%e0%b8%99/admin/problems.php");
exit();
?>
