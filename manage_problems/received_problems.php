<?php
include '../admin/db_connection.php';

function sanitize($data)
{
    return htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
}

// คำสั่ง SQL เพื่อดึงข้อมูลปัญหาที่มีสถานะเป็น 'received'
$sql = "SELECT p.id, p.house_number, p.description, p.status, p.created_at, u.owner_name, u.contact_number
        FROM problems p
        JOIN users u ON p.house_number = u.house_number
        WHERE p.status = 'received'
        ORDER BY p.created_at DESC";

$result = $conn->query($sql);

// ตรวจสอบข้อผิดพลาดของ SQL
if (!$result) {
    echo "<script>Swal.fire('ข้อผิดพลาด!', 'เกิดข้อผิดพลาดในการดึงข้อมูล: " . $conn->error . "', 'error');</script>";
} elseif ($result->num_rows > 0) {
    echo '<table class="table table-bordered">
            <thead>
                <tr>
                    <th>บ้านเลขที่</th>
                    <th>ผู้ร้องเรียน</th>
                    <th>โทรศัพท์</th>
                    <th>รายละเอียดปัญหา</th>
                    <th>เวลาแจ้ง</th>
                    <th>สถานะ</th>
                </tr>
            </thead>
            <tbody>';

    while ($row = $result->fetch_assoc()) {
        echo '<tr>';
        echo '<td>' . sanitize($row['house_number']) . '</td>';
        echo '<td>' . sanitize($row['owner_name']) . '</td>';
        echo '<td>' . sanitize($row['contact_number']) . '</td>';
        echo '<td>' . sanitize($row['description']) . '</td>';

        // แปลงวันที่ให้เป็นปี พ.ศ.
        $created_at = strtotime($row['created_at']);
        $be_year = date("Y", $created_at) + 543;
        $be_date = date("d/m", $created_at) . '/' . $be_year . ' ' . date("H:i", $created_at);
        echo '<td>' . $be_date . '</td>';

        echo '<td>
                <div class="btn-group" role="group" aria-label="จัดการปัญหา">
                    <a href="#" onclick="confirmResolve(' . (int)$row['id'] . ')" class="btn btn-success btn-sm mx-1" title="ดำเนินการเสร็จสิ้น">
                        ดำเนินการเสร็จสิ้น
                    </a>
                    <a href="#" onclick="confirmUnmodifiable(' . (int)$row['id'] . ')" class="btn btn-warning btn-sm mx-1" title="แก้ไขไม่ได้">
                        แก้ไขไม่ได้
                    </a>
                    <a href="#" onclick="confirmDelete(' . (int)$row['id'] . ')" class="btn btn-danger btn-sm mx-1" title="ลบ">
                        <i class="fas fa-trash-alt"></i> ลบ
                    </a>
                </div>
              </td>';
        echo '</tr>';
    }
    echo '</tbody></table>';
} else {
    echo '<div class="alert alert-info" role="alert">ยังไม่มีปัญหาที่กำลังดำเนินการ</div>';
}

// ปิดการเชื่อมต่อฐานข้อมูล
$conn->close();
?>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    function confirmResolve(id) {
        Swal.fire({
            title: 'ดำเนินการแก้ไขปัญหา',
            html: `
                <textarea id="actionText" class="swal2-textarea" placeholder="กรอกรายละเอียดการดำเนินการแก้ไขปัญหา"></textarea>
                <label>แนบรูปภาพ (ถ้ามี):</label>
                <input type="file" id="imageUpload" class="swal2-file">
            `,
            showCancelButton: true,
            confirmButtonText: 'ยืนยัน',
            cancelButtonText: 'ยกเลิก',
            preConfirm: () => {
                const actionText = Swal.getPopup().querySelector('#actionText').value.trim();
                const imageFile = Swal.getPopup().querySelector('#imageUpload').files[0];

                // ตรวจสอบว่าช่องกรอกรายละเอียดการแก้ไขไม่ว่าง
                if (!actionText) {
                    Swal.showValidationMessage('กรุณากรอกการดำเนินการ');
                    return false;
                }

                return {
                    actionText,
                    imageFile
                };
            }
        }).then((result) => {
            if (result.isConfirmed) {
                const actionText = result.value.actionText;
                const imageFile = result.value.imageFile;

                // สร้าง formData สำหรับส่งข้อมูล
                let formData = new FormData();
                formData.append('id', id);
                formData.append('action', actionText);
                formData.append('status', 'resolved');

                // ตรวจสอบว่ามีรูปภาพถูกอัปโหลดหรือไม่
                if (imageFile) {
                    formData.append('image', imageFile);
                }

                // ส่งข้อมูลไปยังเซิร์ฟเวอร์ผ่าน fetch API
                fetch('../manage_problems/pending_edit/change_status.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.text()) // ใช้ .text() เพื่อตรวจสอบ response
                    .then(data => {
                        console.log('Response Text:', data); // แสดงผลที่ได้รับจากเซิร์ฟเวอร์
                        try {
                            const jsonData = JSON.parse(data); // แปลง response เป็น JSON
                            if (jsonData.success) {
                                Swal.fire('สำเร็จ!', 'การดำเนินการแก้ไขปัญหาและรูปภาพถูกบันทึกเรียบร้อยแล้ว', 'success').then(() => {
                                    window.location.reload(); // โหลดหน้าใหม่
                                });
                            } else {
                                Swal.fire('ข้อผิดพลาด!', jsonData.message, 'error'); // แสดงข้อความ error ที่ได้รับจาก server
                            }
                        } catch (error) {
                            Swal.fire('ข้อผิดพลาด!', 'เกิดข้อผิดพลาดในการเชื่อมต่อ: ไม่สามารถแปลงข้อมูลเป็น JSON ได้', 'error');
                            console.error('JSON Parse Error:', error);
                        }
                    })
                    .catch(error => {
                        Swal.fire('ข้อผิดพลาด!', 'เกิดข้อผิดพลาดในการเชื่อมต่อ: ' + error.message, 'error');
                    });
            }
        });
    }

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
                    return false;
                }
                return reason;
            }
        }).then((result) => {
            if (result.isConfirmed) {
                const reason = result.value;
                window.location.href = "../manage_problems/pending_edit/delete_problem.php?id=" + id + "&reason=" + encodeURIComponent(reason);
            }
        });
    }

    function confirmUnmodifiable(id) {
        Swal.fire({
            title: 'กรุณาใส่เหตุผลว่าทำไมถึงแก้ไขไม่ได้',
            input: 'textarea',
            inputLabel: 'เหตุผล',
            inputPlaceholder: 'กรอกเหตุผลของคุณที่นี่...',
            inputAttributes: {
                'aria-label': 'กรอกเหตุผลของคุณที่นี่'
            },
            showCancelButton: true,
            confirmButtonText: 'ยืนยัน',
            cancelButtonText: 'ยกเลิก',
            preConfirm: (reason) => {
                if (!reason || reason.trim() === "") {
                    Swal.showValidationMessage('กรุณากรอกเหตุผล');
                    return false;
                }
                return reason;
            }
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = "../manage_problems/pending_edit/change_status1.php?id=" + id + "&status=unmodifiable&reason=" + encodeURIComponent(result.value);
            }
        });
    }
</script>