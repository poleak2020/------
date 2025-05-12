<?php
// เชื่อมต่อกับฐานข้อมูล
$servername = "localhost";
$username = "root"; // ปรับให้ตรงกับการตั้งค่าของคุณ
$password = ""; // ปรับให้ตรงกับการตั้งค่าของคุณ
$dbname = "corporation"; // ชื่อฐานข้อมูลของคุณ

$conn = new mysqli($servername, $username, $password, $dbname);

// ตรวจสอบการเชื่อมต่อ
if ($conn->connect_error) {
    die("การเชื่อมต่อฐานข้อมูลล้มเหลว: " . $conn->connect_error);
}

// กำหนดรูปแบบการตอบกลับเป็น JSON
header('Content-Type: application/json');

// รับหมายเลขบ้านที่ส่งมาจาก AJAX
$house_number = $_POST['house_number'];

// ตรวจสอบว่าหมายเลขบ้านมีในฐานข้อมูลหรือไม่ และดึงข้อมูลชื่อเจ้าของบ้าน
$sql = "SELECT house_number, owner_name FROM users WHERE house_number = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $house_number);
$stmt->execute();
$result = $stmt->get_result();

// ตรวจสอบผลลัพธ์
if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    echo json_encode([
        'exists' => true,
        'owner_name' => $row['owner_name']
    ]); // ส่งข้อมูลชื่อเจ้าของบ้านกลับในรูปแบบ JSON
} else {
    echo json_encode([
        'exists' => false
    ]); // ไม่มีในระบบ
}

$stmt->close();
$conn->close();
?>
