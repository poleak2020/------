<?php
// รวมการเชื่อมต่อฐานข้อมูล
include '../admin/db_connection.php';

// ตรวจสอบว่าแบบฟอร์มถูกส่งมาหรือไม่
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // รับข้อมูลจากแบบฟอร์ม
    $start_month = $_POST['start_month'] ?? '';
    $end_month = $_POST['end_month'] ?? '';
    $payment_year = $_POST['payment_year'] ?? '';
    $amount = $_POST['amount'] ?? '';
    $house_number = $_POST['house_number'] ?? '';
    $payment_method = $_POST['payment_method'] ?? '';

    // ตรวจสอบข้อมูลก่อนบันทึก
    if (empty($start_month) || empty($end_month) || empty($payment_year) || empty($amount) || empty($house_number) || empty($payment_method)) {
        die("กรุณากรอกข้อมูลให้ครบถ้วน");
    }

    if (!is_numeric($amount) || $amount <= 0) {
        die("จำนวนเงินต้องเป็นตัวเลขที่มากกว่า 0");
    }

    // ตรวจสอบรูปแบบของบ้านเลขที่ (อนุญาตให้มีตัวเลขและเครื่องหมาย /)
    if (!preg_match("/^\d{3}\/\d{3}$/", $house_number)) {
        die("บ้านเลขที่ไม่ถูกต้อง");
    }

    // ตรวจสอบช่วงเดือนว่าถูกต้องหรือไม่ (เดือนเริ่มต้นต้องน้อยกว่าเดือนสิ้นสุด)
    if ($start_month > $end_month) {
        die("เดือนเริ่มต้นต้องน้อยกว่าเดือนสิ้นสุด");
    }

    // ดึงข้อมูลผู้ใช้งานจากตาราง users โดยใช้ house_number
    $sql_user = "SELECT owner_name, house_number FROM users WHERE house_number = ?";
    $stmt_user = $conn->prepare($sql_user);
    $stmt_user->bind_param("s", $house_number);
    $stmt_user->execute();
    $result_user = $stmt_user->get_result();
    $user = $result_user->fetch_assoc();

    if ($user) {
        $customer_name = $user['owner_name'];  // ชื่อผู้ชำระ
        // กำหนดที่อยู่ตาม house_number
        if (strpos($user['house_number'], '189/') === 0) {
            $address = $user['house_number'] . ' หมู่ที่ 6 ถนนลำบางผี ตำบลแพรกษา อำเภอเมืองสมุทรปราการ จังหวัดสมุทรปราการ 10280';
        } elseif (strpos($user['house_number'], '109/') === 0) {
            $address = $user['house_number'] . ' หมู่ที่ 2 ถนนลำบางผี ตำบลแพรกษาใหม่ อำเภอเมืองสมุทรปราการ จังหวัดสมุทรปราการ 10280';
        } else {
            $address = "บ้านเลขที่ " . $user['house_number'];
        }
    } else {
        die("ไม่พบข้อมูลผู้ใช้งานสำหรับบ้านเลขที่นี้");
    }

    $stmt_user->close();

    // เริ่ม transaction
    $conn->begin_transaction();

    // บันทึกข้อมูลการชำระเงินลงในตาราง payments ก่อน
    $sql_payment = "INSERT INTO payments (payment_date, start_month, end_month, payment_year, amount, house_number, payment_method, status) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, 'approved')";
    $stmt_payment = $conn->prepare($sql_payment);
    $payment_date = date("Y-m-d");
    $stmt_payment->bind_param("sssssss", $payment_date, $start_month, $end_month, $payment_year, $amount, $house_number, $payment_method);
    $stmt_payment->execute();
    $payment_id = $stmt_payment->insert_id;  // เก็บค่า payment_id หลังจากบันทึกเสร็จ
    $stmt_payment->close();

    // ตรวจสอบความซ้ำซ้อนของการชำระเงิน
    $sql_check = "SELECT * FROM payments WHERE house_number = ? AND payment_year = ? AND (
                    (start_month <= ? AND end_month >= ?) OR (start_month <= ? AND end_month >= ?)
                 )";
    $stmt_check = $conn->prepare($sql_check);
    $stmt_check->bind_param("ssssss", $house_number, $payment_year, $start_month, $start_month, $end_month, $end_month);
    $stmt_check->execute();
    $result_check = $stmt_check->get_result();

    if ($result_check->num_rows > 1) { // เนื่องจากได้บันทึกการชำระเงินใหม่แล้ว, ต้องนับเกิน 1
        $conn->rollback();
        header("Location: ../manage_payment/payment.php?error=duplicate");
        exit();
    }

    $stmt_check->close();

    // เตรียม ID ของไฟล์หลักฐานการชำระเงิน (หากมี)
    $payment_proof_image_id = null;
    if ($payment_method == 'transfer' && isset($_FILES['payment_slip']) && $_FILES['payment_slip']['error'] == UPLOAD_ERR_OK) {
        $file_tmp_name = $_FILES['payment_slip']['tmp_name'];
        $file_extension = pathinfo($_FILES['payment_slip']['name'], PATHINFO_EXTENSION);
        $short_file_name = substr(uniqid(), 0, 10) . '.' . $file_extension; // ย่อชื่อไฟล์
        $upload_dir = '../uploads/';
        $file_path = $upload_dir . $short_file_name;
        
        // ตรวจสอบชนิดไฟล์และขนาดไฟล์
        $allowed_types = ['image/jpeg', 'image/png'];
        $file_type = mime_content_type($file_tmp_name);
        $file_size = $_FILES['payment_slip']['size'];

        if (in_array($file_type, $allowed_types) && $file_size <= 2 * 1024 * 1024) {
            if (move_uploaded_file($file_tmp_name, $file_path)) {
                $sql_image = "INSERT INTO images (related_id, file_name, image_type) VALUES (?, ?, ?)";
                $stmt_image = $conn->prepare($sql_image);

                $payment_proof_type = 'payment_proof'; // กำหนดตัวแปรสำหรับ image_type

                $stmt_image->bind_param("iss", $payment_id, $file_path, $payment_proof_type);
                $stmt_image->execute();
                $payment_proof_image_id = $stmt_image->insert_id;
                $stmt_image->close();

                // อัปเดต `payment_proof_image_id` ในตาราง payments
                $sql_update_payment = "UPDATE payments SET payment_proof_image_id = ? WHERE id = ?";
                $stmt_update_payment = $conn->prepare($sql_update_payment);
                $stmt_update_payment->bind_param("ii", $payment_proof_image_id, $payment_id);
                $stmt_update_payment->execute();
                $stmt_update_payment->close();
            } else {
                $conn->rollback();
                die("เกิดข้อผิดพลาดในการอัปโหลดไฟล์สลิป");
            }
        } else {
            $conn->rollback();
            die("ไฟล์สลิปต้องเป็น JPG/PNG และขนาดไม่เกิน 2MB");
        }
    }

    // สร้างเลขที่ใบเสร็จในรูปแบบ ปีพุทธศักราช + เดือน + ลำดับ เช่น 6709001
    $current_year_buddhist = substr(date('Y') + 543, -2); // ปี พ.ศ. (สองหลักท้าย)
    $current_month = date('m'); // เดือนปัจจุบัน

    // ดึงข้อมูลจำนวนใบเสร็จในเดือนและปีที่จ่ายเงิน โดยใช้ปี ค.ศ.
    $current_year_gregorian = date('Y'); // ปี ค.ศ. ปัจจุบัน

    // คิวรีนับจำนวนใบเสร็จที่เกิดขึ้นในเดือนและปีนั้นๆ
    $sql_count = "SELECT COUNT(*) as count FROM receipts WHERE YEAR(receipt_date) = ? AND MONTH(receipt_date) = ?";
    $stmt_count = $conn->prepare($sql_count);
    $stmt_count->bind_param("ii", $current_year_gregorian, $current_month);
    $stmt_count->execute();
    $result_count = $stmt_count->get_result();
    $row_count = $result_count->fetch_assoc();
    $order_number = $row_count['count'] + 1; // ลำดับของการชำระในเดือนนี้เริ่มจาก 1

    // แปลงลำดับเป็นเลขสามหลัก เช่น 001, 002, ...
    $formatted_order_number = str_pad($order_number, 3, '0', STR_PAD_LEFT);

    // สร้างเลขที่ใบเสร็จ
    $receipt_number = $current_year_buddhist . $current_month . $formatted_order_number;

    // บันทึกข้อมูลใบเสร็จลงในตาราง receipts
    $sql_receipt = "INSERT INTO receipts (id, receipt_number, customer_name, address, receipt_date, total_amount, payment_id) VALUES (?, ?, ?, ?, ?, ?, ?)";
    $stmt_receipt = $conn->prepare($sql_receipt);
    $receipt_date = date("Y-m-d");
    $stmt_receipt->bind_param("isssssi", $payment_id, $receipt_number, $customer_name, $address, $receipt_date, $amount, $payment_id);  // ใช้ $payment_id เป็น id ของใบเสร็จ
    $stmt_receipt->execute();

    // บันทึกข้อมูลรายการในใบเสร็จ
    $sql_items = "INSERT INTO receipt_items (receipt_id, description, amount) VALUES (?, ?, ?)";
    $stmt_items = $conn->prepare($sql_items);
    $description = "ค่าชำระรายเดือน";
    $stmt_items->bind_param("iss", $payment_id, $description, $amount);
    $stmt_items->execute();

    $conn->commit();

    // เปลี่ยนเส้นทางไปยังหน้าใบเสร็จ
    header("Location: ../manage_payment/receipt.php?id=" . $payment_id);
    exit();
}
?>
