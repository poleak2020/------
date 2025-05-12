
<?php
// connect.php - ไฟล์สำหรับเชื่อมต่อฐานข้อมูล
$host = 'localhost';
$dbname = 'corporation';
$user = 'root';
$password = '';

// สร้างการเชื่อมต่อ
$conn = new mysqli($host, $user, $password, $dbname);

// เช็คการเชื่อมต่อ
if ($conn->connect_error) {
    die(json_encode(['error' => 'Connection failed: ' . $conn->connect_error]));
}

// SQL สำหรับดึงข้อมูลการชำระเงินทั้งหมด
$sql = "SELECT house_number, start_month, end_month, payment_year FROM payments";
$result = $conn->query($sql);

$payments = [];

if ($result) {
    if ($result->num_rows > 0) {
        // เก็บข้อมูลจากฐานข้อมูลในรูปแบบ array
        while ($row = $result->fetch_assoc()) {
            $payments[] = [
                'house_number' => $row['house_number'],
                'start_month' => $row['start_month'],
                'end_month' => $row['end_month'],
                'payment_year' => $row['payment_year']
            ];
        }
    }
} else {
    // ถ้ามีปัญหากับ SQL query
    echo json_encode(['error' => 'Error executing query: ' . $conn->error]);
    $conn->close();
    exit();
}

// ส่งข้อมูลกลับเป็น JSON
echo json_encode($payments);

// ปิดการเชื่อมต่อฐานข้อมูล
$conn->close();
?>
