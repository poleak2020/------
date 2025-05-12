<?php
include '../admin/db_connection.php'; // เชื่อมต่อฐานข้อมูล

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['id'])) {
    $id = intval($_GET['id']); // รับค่า id จากการลบ

    // เริ่มการเชื่อมต่อฐานข้อมูลแบบ Transaction
    $conn->begin_transaction();

    try {
        // ลบข้อมูลการชำระเงินออกจากตาราง payments
        $sql_delete_payment = "DELETE FROM payments WHERE id = ?";
        $stmt_delete = $conn->prepare($sql_delete_payment);
        $stmt_delete->bind_param("i", $id);

        if (!$stmt_delete->execute()) {
            throw new Exception("เกิดข้อผิดพลาดในการลบข้อมูลการชำระเงิน: " . $stmt_delete->error);
        }

        $stmt_delete->close();

        // เพิ่มเหตุผลการลบในตาราง actions
        if (isset($_POST['reason']) && !empty($_POST['reason'])) {
            $reason = $_POST['reason'];
            $sql_insert_action = "INSERT INTO actions (problem_id, action, performed_at) VALUES (?, ?, NOW())";
            $stmt_action = $conn->prepare($sql_insert_action);
            $stmt_action->bind_param("is", $id, $reason);

            if (!$stmt_action->execute()) {
                throw new Exception("เกิดข้อผิดพลาดในการบันทึกเหตุผลการลบ: " . $stmt_action->error);
            }
            $stmt_action->close();
        }

        // Commit การทำงาน
        $conn->commit();

        // แจ้งเตือนว่าเสร็จสิ้นและเปลี่ยนเส้นทางกลับไปหน้าประวัติ
        echo "<script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>";
        echo "<script>
            document.addEventListener('DOMContentLoaded', function() {
                Swal.fire({
                    icon: 'success',
                    title: 'การลบสำเร็จ',
                    text: 'การลบข้อมูลเสร็จสิ้นแล้ว.',
                    showConfirmButton: false,
                    timer: 2000
                }).then(() => {
                    window.location.href = '../manage_payment/view_payment_history.php?id=' + paymentId;
                });
            });
        </script>";

    } catch (Exception $e) {
        // Rollback ถ้ามีปัญหา
        $conn->rollback();
        echo "<script>alert('Error: " . $e->getMessage() . "');</script>";
    }

    $conn->close();

} else {
    echo "<script>alert('ไม่มีข้อมูลการลบที่ถูกต้อง'); window.location.href = '../manage_payment/view_payment_history.php?id=' + paymentId;</script>";
}
?>
