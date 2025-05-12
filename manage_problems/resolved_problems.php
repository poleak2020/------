<?php
include '../admin/db_connection.php';

// คำสั่ง SQL เพื่อดึงข้อมูลปัญหาที่มีสถานะเป็น 'resolved'
$sql = "SELECT p.id, p.house_number, p.description, p.status, p.image_url, p.created_at, u.owner_name, u.contact_number
        FROM problems p
        JOIN users u ON p.house_number = u.house_number
        WHERE p.status = 'resolved'
        ORDER BY p.created_at DESC";

$result = $conn->query($sql);

// ตรวจสอบว่ามีข้อมูลหรือไม่
if ($result->num_rows > 0) {
    echo '<table class="table table-bordered">
            <thead>
                <tr>
                    <th>บ้านเลขที่</th>
                    <th>ผู้ร้องเรียน</th>
                    <th>โทรศัพท์</th>
                    <th>เรื่องที่ร้องเรียน</th>
                    <th>เวลาแจ้ง</th>
                    <th>รูปภาพ</th>
                    <th>สถานะ</th>
                </tr>
            </thead>
            <tbody>';
    
    // แสดงข้อมูลในตาราง
    while ($row = $result->fetch_assoc()) {
        echo '<tr>';
        echo '<td>' . htmlspecialchars($row['house_number']) . '</td>';
        echo '<td>' . htmlspecialchars($row['owner_name']) . '</td>';
        echo '<td>' . htmlspecialchars($row['contact_number']) . '</td>';
        echo '<td>' . htmlspecialchars($row['description']) . '</td>';
        
        // แปลงวันที่ให้เป็นปี พ.ศ.
        $created_at = strtotime($row['created_at']);
        $be_year = date("Y", $created_at) + 543; // แปลงปี ค.ศ. เป็น พ.ศ.
        $be_date = date("d/m", $created_at) . '/' . $be_year . ' ' . date("H:i", $created_at);
        echo '<td>' . $be_date . '</td>';
        
        echo '<td class="icon-container">';
        
        // ตรวจสอบว่ามีรูปภาพหรือไม่และแสดงลิงก์เปิดในแท็บใหม่
        if (!empty($row['image_url'])) {
            echo '<a href="' . htmlspecialchars('/Pruksa28/' . $row['image_url']) . '" target="_blank">
                    <i class="fas fa-search-plus"></i>
                  </a>';
        } else {
            echo 'ไม่มีรูปภาพ';
        }
        
        echo '</td>';
        echo '<td>
                <div class="btn-group" role="group" aria-label="จัดการปัญหา">
                    <button class="btn btn-success btn-sm mx-1" title="แก้ไขเสร็จสิ้น" onclick="showSuccess()">
                        <i class="fas fa-check"></i> แก้ไขเสร็จสิ้น
                    </button>
                </div>
              </td>';
        echo '</tr>';
    }
    echo '</tbody>
        </table>';
} else {
    // แสดงข้อความเมื่อไม่มีปัญหา
    echo '<div class="alert alert-info" role="alert">ยังไม่มีการแจ้งปัญหาที่เสร็จสิ้น</div>';
}

// ปิดการเชื่อมต่อฐานข้อมูล
$conn->close();
?>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    function showSuccess() {
        Swal.fire({
            title: 'สำเร็จ!',
            text: 'การแก้ไขปัญหาสำเร็จ',
            icon: 'success',
            confirmButtonText: 'ตกลง'
        });
    }
</script>
