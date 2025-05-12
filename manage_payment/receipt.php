<?php
// รวมการเชื่อมต่อฐานข้อมูล
include '../admin/db_connection.php';

// ฟังก์ชันแปลงวันที่เป็น วัน เดือน ปีพุทธศักราช
function formatThaiDate($date)
{
    $months = [
        1 => 'มกราคม',
        2 => 'กุมภาพันธ์',
        3 => 'มีนาคม',
        4 => 'เมษายน',
        5 => 'พฤษภาคม',
        6 => 'มิถุนายน',
        7 => 'กรกฎาคม',
        8 => 'สิงหาคม',
        9 => 'กันยายน',
        10 => 'ตุลาคม',
        11 => 'พฤศจิกายน',
        12 => 'ธันวาคม'
    ];

    $year = date('Y', strtotime($date)) + 543; // แปลงเป็นปีพุทธศักราช
    $month = $months[date('n', strtotime($date))]; // แปลงเป็นชื่อเดือนภาษาไทย
    $day = date('j', strtotime($date)); // ดึงวันที่

    return "$day $month $year";
}

// ฟังก์ชันแปลงเดือนเป็นชื่อเดือนภาษาไทยและปีพุทธศักราช
function formatThaiMonthYear($start_month, $end_month, $year)
{
    $months = [
        1 => 'มกราคม',
        2 => 'กุมภาพันธ์',
        3 => 'มีนาคม',
        4 => 'เมษายน',
        5 => 'พฤษภาคม',
        6 => 'มิถุนายน',
        7 => 'กรกฎาคม',
        8 => 'สิงหาคม',
        9 => 'กันยายน',
        10 => 'ตุลาคม',
        11 => 'พฤศจิกายน',
        12 => 'ธันวาคม'
    ];

    // แปลงค่าเป็น integer เพื่อให้ตรงกับคีย์ใน array
    $start_month_int = (int)$start_month;
    $end_month_int = (int)$end_month;

    // ตรวจสอบว่าปีที่รับมาเป็นปี ค.ศ. แล้วบวก 543 เพื่อแปลงเป็น พ.ศ.
    if ($year < 2500) {
        $year_thai = $year + 543; // แปลงเป็นปีพุทธศักราช
    } else {
        $year_thai = $year; // หากเป็น พ.ศ. แล้วไม่ต้องแปลง
    }

    // ใช้ค่าที่แปลงแล้วเพื่อดึงชื่อเดือนจาก array
    $start_month_name = $months[$start_month_int];
    $end_month_name = $months[$end_month_int];

    // ตรวจสอบว่าเดือนเริ่มต้นและเดือนสิ้นสุดเหมือนกันหรือไม่
    if ($start_month_int === $end_month_int) {
        return "$start_month_name พ.ศ. $year_thai";
    } else {
        return "$start_month_name - $end_month_name พ.ศ. $year_thai";
    }
}

// รับ ID ของใบเสร็จจาก URL และตรวจสอบว่ามีการส่งค่าและเป็นเลขจำนวนเต็ม
$receipt_id = isset($_GET['id']) && filter_var($_GET['id'], FILTER_VALIDATE_INT) ? $_GET['id'] : 0;

if ($receipt_id) {
    // ดึงข้อมูลใบเสร็จจากตาราง receipts และ payments รวมถึงข้อมูล plot_number จาก users
    $sql = "SELECT r.receipt_number, r.customer_name, r.address, r.receipt_date, r.total_amount, ri.description, ri.amount, p.start_month, p.end_month, p.payment_year, u.plot_number, p.house_number
            FROM receipts r
            LEFT JOIN receipt_items ri ON r.id = ri.receipt_id
            LEFT JOIN payments p ON r.payment_id = p.id
            LEFT JOIN users u ON u.house_number = p.house_number
            WHERE r.id = ?";

    // เตรียมคิวรี SQL
    $stmt = $conn->prepare($sql);

    // ตรวจสอบว่าเตรียมคำสั่งสำเร็จหรือไม่
    if (!$stmt) {
        die("SQL Error: " . $conn->error);
    }

    // ทำการ bind parameter และ execute
    $stmt->bind_param("i", $receipt_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $receipt = [];
    while ($row = $result->fetch_assoc()) {
        $receipt[] = $row;
    }

    // ตรวจสอบว่าพบใบเสร็จหรือไม่
    if (!empty($receipt)) {
        // ดึงข้อมูลจากฐานข้อมูลแทนการสร้างใหม่
        $receipt_number = htmlspecialchars($receipt[0]['receipt_number']);
        $plot_number = htmlspecialchars($receipt[0]['plot_number']); // ดึงข้อมูลแปลงที่จาก users

        // แปลงวันที่ให้อยู่ในรูปแบบ "วัน เดือน ปี"
        $formatted_date = isset($receipt[0]['receipt_date']) ? formatThaiDate($receipt[0]['receipt_date']) : '';

        // คำนวณจำนวนเดือนตามจริง
        $start_month = $receipt[0]['start_month'];
        $end_month = $receipt[0]['end_month'];
        $payment_year = $receipt[0]['payment_year'];
        $num_months = ($end_month - $start_month) + 1; // คำนวณจำนวนเดือนตามจริง

        // ตรวจสอบว่าจำนวนเดือนมีค่ามากกว่า 0
        if ($num_months <= 0) {
            die("จำนวนเดือนไม่ถูกต้อง");
        }

        // คำนวณจำนวนเงินต่อเดือน
        $monthly_amount = $receipt[0]['total_amount'] / $num_months;

        // ดึง house_number จาก payments แทน address
        $house_number = htmlspecialchars($receipt[0]['house_number']);

        // ตรวจสอบเลขที่บ้านเพื่อแสดงที่อยู่ที่เหมาะสม
        $full_address = '';

        if (strpos($house_number, '109/') === 0) {
            $full_address = "$house_number หมู่ที่ 2 ถนนลำบางผี ตำบลแพรกษาใหม่ อำเภอเมืองสมุทรปราการ จังหวัดสมุทรปราการ 10280";
        } elseif (strpos($house_number, '189/') === 0) {
            $full_address = "$house_number หมู่ที่ 6 ถนนลำบางผี ตำบลแพรกษา อำเภอเมืองสมุทรปราการ จังหวัดสมุทรปราการ 10280";
        } else {
            $full_address = "บ้านเลขที่ " . $house_number;
        }

        // แสดงข้อมูลใบเสร็จ
        echo '<!DOCTYPE html>
        <html lang="th">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>ใบเสร็จ</title>
            <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
            <style>
                body {
                    font-family: "TH SarabunPSK", sans-serif;
                    font-size: 18px;
                    padding: 20px;
                }
                .receipt-container {
                    padding: 20px;
                    border: 2px solid #000;
                    border-radius: 10px;
                    max-width: 800px;
                    margin: 0 auto;
                }
                .header-text {
                    font-weight: bold;
                    text-align: center;
                    margin-bottom: 20px;
                }
                .contact-info {
                    text-align: center;
                    font-size: 16px;
                    margin-bottom: 10px;
                }
                .info-table, .table {
                    width: 100%;
                    margin-top: 20px;
                }
                .table th, .table td {
                    text-align: center;
                    padding: 8px;
                    border: 1px solid #000;
                }
                .text-right-align {
                    text-align: right;
                    display: inline-block;
                    width: 50%;
                }
                .text-left-align {
                    text-align: left;
                    display: inline-block;
                    width: 50%;
                }
                .footer {
                    margin-top: 30px;
                    text-align: center;
                    font-weight: bold;
                    font-size: 18px;
                }
                @media print {
                    .receipt-container {
                        border: none;
                        padding: 0;
                    }
                    .footer {
                        page-break-before: always;
                    }
                }
            </style>
        </head>
        <body>
            <div class="receipt-container">
                <div class="header-text">
                    <h2>สำนักงานนิติบุคคลหมู่บ้านพฤกษา 28/1</h2>
                    <p>189/429 หมู่ที่ 6 ต.แพรกษาใหม่ อ.เมือง จ.สมุทรปราการ 10280</p>
                    <p>เบอร์โทร: 090-4011529 , 02-1865217 | E-mail: niti2pruksa28_1@hotmail.com</p>
                    <hr>
                </div>

                <table class="info-table">
                    <tr>
                        <td class="text-left-align"><strong>แปลงที่:</strong> ' . $plot_number . '</td> <!-- แสดงแปลงที่ -->
                        <td class="text-right-align"><strong>เลขที่:</strong> ' . $receipt_number . '</td>
                    </tr>
                    <tr>
                        <td class="text-left-align"><strong>ชื่อ:</strong> ' . htmlspecialchars($receipt[0]['customer_name']) . '</td>
                        <td class="text-right-align"><strong>วันที่:</strong> ' . $formatted_date . '</td>
                    </tr>
                    <tr>
                       <td colspan="2"><strong>ที่อยู่:</strong> ' . $full_address . '</td>                 
                     </tr>
                </table>

                <table class="table table-bordered mt-4">
                    <thead>
                        <tr>
                            <th>ลำดับ</th>
                            <th>รายการ</th>
                            <th>จำนวน (เดือน)</th>
                            <th>เดือนละ (บาท)</th>
                            <th>จำนวนเงิน (บาท)</th>
                        </tr>
                    </thead>
                    <tbody>';

        $index = 1;
        foreach ($receipt as $item) {
            // แสดงข้อมูลเดือนตามช่วงเดือน
            $thai_month_year = formatThaiMonthYear($item['start_month'], $item['end_month'], $item['payment_year']);

            echo '<tr>';
            echo '<td>' . $index . '</td>';
            echo '<td>ค่าส่วนกลางเดือน ' . htmlspecialchars($thai_month_year) . '</td>';
            echo '<td>' . htmlspecialchars($num_months) . '</td>'; // แสดงจำนวนเดือนตามจริง
            echo '<td>' . number_format($monthly_amount, 2) . '</td>'; // แสดงจำนวนเงินต่อเดือน
            echo '<td>' . number_format($item['amount'], 2) . ' </td>';
            echo '</tr>';
            $index++;
        }

        echo '<tr>
                <td colspan="4" class="text-end"><strong>รวม</strong></td>
                <td><strong>' . number_format($receipt[0]['total_amount'], 2) . ' </strong></td>
              </tr>
            </tbody>
        </table>';

        // เพิ่มปุ่มพิมพ์ใบเสร็จและปุ่มย้อนกลับไปหน้า payment.php
        echo '
        <div class="footer">
            <p>ขอบคุณที่ใช้บริการ</p>
        </div>
        <div class="text-center mt-4">
            <button class="btn btn-primary" onclick="window.print()">พิมพ์ใบเสร็จ</button>
            <a href="javascript:history.back()" class="btn btn-secondary">ย้อนกลับ</a>
        </div>
    </div>
    </body>
    </html>';
    } else {
        echo "ไม่พบใบเสร็จ";
    }

    $stmt->close();
} else {
    echo "ไม่มี ID ของใบเสร็จ";
}

$conn->close();
?>
