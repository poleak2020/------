
<?php
session_start();
session_unset(); // ล้างข้อมูลเซสชัน
session_destroy(); // ทำลายเซสชัน
header("Location: Loginadmin.php"); // นำผู้ใช้กลับไปที่หน้าเข้าสู่ระบบ
exit();
?>
