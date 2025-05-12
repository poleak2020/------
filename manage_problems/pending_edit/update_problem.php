
<?php
// update_problem.php
include '../../admin/db_connection.php';

// ตรวจสอบว่ามีการส่งข้อมูลที่จำเป็น
if (isset($_POST['id']) && isset($_POST['description'])) {
    $id = $_POST['id'];
    $description = $_POST['description'];
    $image_url = isset($_POST['image_url']) ? $_POST['image_url'] : '';

    // คำสั่ง SQL เพื่ออัปเดตข้อมูลปัญหา
    $sql = "UPDATE problems SET description = ?, image_url = ? WHERE id = ?";

    // เตรียมและเชื่อมต่อคำสั่ง SQL
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("ssi", $description, $image_url, $id);

        // ตรวจสอบการดำเนินการ
        if ($stmt->execute()) {
            echo 'success';
        } else {
            echo "เกิดข้อผิดพลาดในการอัปเดตข้อมูล: " . $stmt->error;
        }

        // ปิดคำสั่ง SQL
        $stmt->close();
    } else {
        echo "เกิดข้อผิดพลาดในการเตรียมคำสั่ง SQL: " . $conn->error;
    }
} else {
    echo "ข้อมูลที่จำเป็นไม่ครบถ้วน";
}

// ปิดการเชื่อมต่อฐานข้อมูล
$conn->close();
?>
