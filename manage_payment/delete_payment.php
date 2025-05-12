<?php
include '../admin/db_connection.php'; // ปรับเส้นทางให้ถูกต้อง

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id']) && isset($_POST['reason'])) {
    $id = intval($_POST['id']);
    $reason = $_POST['reason'];

    // เริ่มต้นการเชื่อมต่อฐานข้อมูลแบบ Transaction
    $conn->begin_transaction();

    try {
        // เปลี่ยนสถานะในตาราง actions เป็น 'deleted' พร้อมเหตุผลการลบ
        $sql_insert_action = "INSERT INTO actions (problem_id, action, performed_at) VALUES (?, ?, NOW())";
        if ($stmt_action = $conn->prepare($sql_insert_action)) {
            $stmt_action->bind_param("is", $id, $reason);
            $stmt_action->execute();
            $stmt_action->close();
        } else {
            throw new Exception("เกิดข้อผิดพลาดในการบันทึกเหตุผลการลบ: " . $conn->error);
        }

        // ลบข้อมูลปัญหาออกจากตาราง problems
        $sql_delete_problem = "DELETE FROM problems WHERE id = ?";
        if ($stmt_delete = $conn->prepare($sql_delete_problem)) {
            $stmt_delete->bind_param("i", $id);
            if (!$stmt_delete->execute()) {
                throw new Exception("เกิดข้อผิดพลาดในการลบข้อมูลปัญหา: " . $stmt_delete->error);
            }
            $stmt_delete->close();
        } else {
            throw new Exception("เกิดข้อผิดพลาดในการเตรียมคำสั่งลบข้อมูลปัญหา: " . $conn->error);
        }

        // Commit การทำงาน
        $conn->commit();

        // ส่งผลลัพธ์เป็น JSON กลับไป
        echo json_encode(['success' => true]);

    } catch (Exception $e) {
        // Rollback หากมีข้อผิดพลาด
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'ไม่มีข้อมูลที่จำเป็น']);
}

// ปิดการเชื่อมต่อฐานข้อมูล
$conn->close();
?>
