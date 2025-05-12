<?php
// เชื่อมต่อฐานข้อมูล
include '../admin/db_connection.php';

// ดึงข้อมูลจากตาราง problems
$sql = "SELECT p.id, p.house_number, p.description, p.status, p.image_url, p.created_at, u.owner_name, u.contact_number 
        FROM problems p
        JOIN users u ON p.house_number = u.house_number
        WHERE p.status = 'pending'
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
                    <th>รูปภาพ</th>
                    <th>เรื่องที่ร้องเรียน</th>
                    <th>เวลาแจ้ง</th>
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
        echo '<td>';
        if (!empty($row['image_url'])) {
            // ใช้ URL ที่ถูกต้องสำหรับการแสดงรูปภาพ
            $image_path = '/Pruksa28/uploads/' . urlencode(basename($row['image_url']));
            echo '<a href="' . htmlspecialchars($image_path) . '" target="_blank"><i class="fas fa-image" style="cursor:pointer;"></i></a>';
        } else {
            echo 'ไม่มีรูปภาพ';
        }
        echo '</td>';
        echo '<td>' . htmlspecialchars($row['description']) . '</td>';

        // แปลงวันที่ให้เป็นปี พ.ศ.
        $created_at = strtotime($row['created_at']);
        $be_year = date("Y", $created_at) + 543; // เพิ่ม 543 ให้กับปี
        $be_date = date("d/m", $created_at) . '/' . $be_year . ' ' . date("H:i", $created_at);

        echo '<td>' . $be_date . '</td>';

        echo '<td>
                <div class="btn-group" role="group" aria-label="สถานะ">
                    <a href="#" onclick="confirmChangeStatus(' . (int)$row['id'] . ')" class="btn btn-success btn-sm mx-1">รับเรื่อง</a>
                    <a href="../manage_problems/pending_edit/edit_problem.php?id=' . (int)$row['id'] . '" class="btn btn-warning btn-sm mx-1">แก้ไข</a>
                    <a href="#" onclick="confirmDelete(' . (int)$row['id'] . ')" class="btn btn-danger btn-sm mx-1">ลบ</a>
                </div>
              </td>';
        echo '</tr>';
    }
    echo '</tbody></table>';
} else {
    echo '<div class="alert alert-info" role="alert">ยังไม่มีการแจ้งปัญหา</div>';
}

$conn->close();
?>


<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
    function confirmDelete(id) {
        Swal.fire({
            title: 'คุณแน่ใจหรือไม่?',
            text: "คุณต้องการลบการร้องเรียนนี้หรือไม่? กรุณากรอกเหตุผลในการลบ",
            input: 'text',
            inputPlaceholder: 'กรอกเหตุผลในการลบ',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'ใช่, ลบเลย!',
            cancelButtonText: 'ยกเลิก',
            preConfirm: (reason) => {
                if (!reason || reason.trim() === "") {
                    Swal.showValidationMessage('กรุณากรอกเหตุผลในการลบ');
                    return false; // หยุดการส่งข้อมูลถ้าเหตุผลเป็นค่าว่างหรือเป็นช่องว่าง
                }
                return reason;
            }
        }).then((result) => {
            if (result.isConfirmed) {
                const reason = result.value;
                // ส่งคำขอการลบไปยังเซิร์ฟเวอร์พร้อมเหตุผลการลบ
                window.location.href = "../manage_problems/pending_edit/delete_problem.php?id=" + id + "&reason=" + encodeURIComponent(reason);
            }
        });
    }

    function confirmChangeStatus(id) {
        Swal.fire({
            title: 'ยืนยันการเปลี่ยนสถานะ',
            text: "คุณต้องการเปลี่ยนสถานะเป็นรับเรื่องหรือไม่?",
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'ใช่, เปลี่ยนสถานะ',
            cancelButtonText: 'ยกเลิก'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = "../manage_problems/pending_edit/change_status1.php?id=" + id + "&status=received";
            }
        });
    }
</script>
