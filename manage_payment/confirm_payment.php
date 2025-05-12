<?php
session_start();
include '../admin/db_connection.php';

// ตรวจสอบว่ามีการส่ง ID ของการชำระเงินเข้ามาหรือไม่
if (isset($_GET['id'])) {
    $payment_id = intval($_GET['id']); // แปลง ID เป็นตัวเลข

    // ตรวจสอบการเชื่อมต่อฐานข้อมูล
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    // ดึงข้อมูลการชำระเงินตาม ID ที่ส่งมา
    $sql = "SELECT p.*, u.owner_name, u.house_number 
            FROM payments p
            INNER JOIN users u ON p.house_number = u.house_number
            WHERE p.id = ?";

    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param('i', $payment_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $payment = $result->fetch_assoc();

            // ตรวจสอบข้อมูลว่ามีครบหรือไม่
            if (empty($payment['owner_name']) || empty($payment['house_number'])) {
                header("Location: ../manage_payment/admin_verify_payments.php");
                exit();
            }

            // อัปเดตสถานะการชำระเงินเป็น 'approved'
            $update_sql = "UPDATE payments SET status = 'approved' WHERE id = ?";
            if ($update_stmt = $conn->prepare($update_sql)) {
                $update_stmt->bind_param('i', $payment_id);

                if ($update_stmt->execute()) {
                    // สร้างเลขใบเสร็จ
                    $receipt_number = generateReceiptNumber($conn);
                    if ($receipt_number) {
                        // เพิ่มใบเสร็จลงในฐานข้อมูล
                        if (insertReceipt($conn, $payment, $receipt_number)) {
                            header("Location: ../manage_payment/admin_verify_payments.php");
                            exit();
                        } else {
                            echo "Error inserting receipt: " . $conn->error;
                        }
                    } else {
                        echo "Error: Unable to generate receipt number.";
                    }
                } else {
                    echo "Error: Unable to confirm payment. " . $conn->error;
                }
            } else {
                echo "Error preparing update statement: " . $conn->error;
            }
        } else {
            echo "Payment information not found.";
        }
        $stmt->close();
    } else {
        echo "Error preparing statement: " . $conn->error;
    }
} else {
    echo "No payment selected.";
}

$conn->close();

/**
 * ฟังก์ชันสำหรับสร้างเลขที่ใบเสร็จใหม่
 */
function generateReceiptNumber($conn) {
    $current_year_th = date('Y') + 543; 
    $current_year_short = substr($current_year_th, 2, 2); 
    $current_month = date('m');

    // ตรวจสอบเลขที่ใบเสร็จล่าสุด
    $last_receipt_sql = "SELECT receipt_number FROM receipts WHERE receipt_number LIKE ? ORDER BY receipt_number DESC LIMIT 1";
    $search_pattern = $current_year_short . $current_month . '%';

    $last_receipt_number = ''; // กำหนดตัวแปรก่อนการใช้

    if ($stmt = $conn->prepare($last_receipt_sql)) {
        $stmt->bind_param('s', $search_pattern);
        $stmt->execute();
        $stmt->bind_result($last_receipt_number);
        $stmt->fetch();
        $stmt->close();
    }

    if (!empty($last_receipt_number)) {
        $last_sequence = intval(substr($last_receipt_number, -3));
        $new_sequence = str_pad($last_sequence + 1, 3, '0', STR_PAD_LEFT);
    } else {
        $new_sequence = '001'; // หากไม่มีใบเสร็จ ให้เริ่มที่ 001
    }

    return $current_year_short . $current_month . $new_sequence;
}

/**
 * ฟังก์ชันสำหรับเพิ่มใบเสร็จลงในฐานข้อมูล
 */
function insertReceipt($conn, $payment, $receipt_number) {
    $receipt_sql = "INSERT INTO receipts (id, receipt_number, customer_name, address, receipt_date, total_amount, payment_id) 
                    VALUES (?, ?, ?, ?, NOW(), ?, ?)";
    if ($stmt = $conn->prepare($receipt_sql)) {
        $stmt->bind_param('isssdi', $payment['id'], $receipt_number, $payment['owner_name'], $payment['house_number'], $payment['amount'], $payment['id']);
        return $stmt->execute();
    } else {
        return false; // หากไม่สามารถเพิ่มใบเสร็จได้
    }
}
?>
