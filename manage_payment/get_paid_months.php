<?php 
// เชื่อมต่อกับฐานข้อมูล
$servername = "localhost";
$username = "root"; // ปรับให้ตรงกับการตั้งค่าของคุณ
$password = ""; // ปรับให้ตรงกับการตั้งค่าของคุณ
$dbname = "corporation"; // ชื่อฐานข้อมูลของคุณ

$conn = new mysqli($servername, $username, $password, $dbname);

// ตรวจสอบการเชื่อมต่อ
if ($conn->connect_error) {
    die(json_encode(['success' => false, 'message' => 'การเชื่อมต่อฐานข้อมูลล้มเหลว: ' . $conn->connect_error]));
}

// รับหมายเลขบ้านและปีที่ส่งมาจาก AJAX
$house_number = trim($_POST['house_number']);
$payment_year = trim($_POST['payment_year']);

// ตรวจสอบว่าหมายเลขบ้านและปีมีการส่งมาหรือไม่
if (empty($house_number) || empty($payment_year)) {
    echo json_encode(['success' => false, 'message' => 'กรุณากรอกหมายเลขบ้านและปีที่ต้องการตรวจสอบ']);
    exit;
}

// ตรวจสอบให้แน่ใจว่าหมายเลขบ้านและปีเป็นตัวเลข
if (!preg_match('/^[0-9\/]+$/', $house_number) || !is_numeric($payment_year)) {
    echo json_encode(['success' => false, 'message' => 'หมายเลขบ้านและปีต้องเป็นตัวเลข']);
    exit;
}

// ตรวจสอบว่าปี พ.ศ. ที่เลือกอยู่ในช่วงที่ถูกต้อง
$currentYearInBE = date("Y") + 543;
if ($payment_year < 2543 || $payment_year > $currentYearInBE) {
    echo json_encode(['success' => false, 'message' => 'ปีที่เลือกไม่อยู่ในช่วงที่ยอมรับได้']);
    exit;
}

// สร้าง SQL query เพื่อตรวจสอบเดือนที่ชำระแล้ว
$sql = "SELECT start_month, end_month FROM payments WHERE house_number = ? AND payment_year = ?";
$stmt = $conn->prepare($sql);

if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'เกิดข้อผิดพลาดในการเตรียมคำสั่ง: ' . $conn->error]);
    exit;
}

$stmt->bind_param("si", $house_number, $payment_year);
$stmt->execute();
$result = $stmt->get_result();

// ตรวจสอบผลลัพธ์
if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'ไม่พบการชำระเงินสำหรับหมายเลขบ้านและปีที่กำหนด']);
    exit;
}

// สร้างอาร์เรย์เพื่อเก็บเดือนที่ชำระแล้ว
$paid_months = [];

while ($row = $result->fetch_assoc()) {
    // ฟอร์แมตให้แน่ใจว่า `start_month` และ `end_month` เป็นเลขสองหลัก
    $start_month = str_pad($row['start_month'], 2, '0', STR_PAD_LEFT);
    $end_month = str_pad($row['end_month'], 2, '0', STR_PAD_LEFT);

    for ($month = $start_month; $month <= $end_month; $month++) {
        $paid_months[] = str_pad($month, 2, '0', STR_PAD_LEFT); // ทำให้เดือนเป็นรูปแบบสองหลัก เช่น 01, 02
    }
}

// ปิดการเชื่อมต่อ
$stmt->close();
$conn->close();

// ส่งข้อมูลกลับไปยัง AJAX
echo json_encode(['success' => true, 'paid_months' => array_values(array_unique($paid_months))]); // ส่งคืนเดือนที่ชำระแล้ว
?>
