<?php
include("../config/mysql_connect.inc.php");
include("../header.php");
?>
<style>
.sidebar-dept-list {
    width: 100%;
    background: #f8f6f2;
    border-radius: 12px;
    box-shadow: 0 1px 8px #eee;
    padding: 1em 0.5em 1em 0.5em;
    margin: 0 0 2em 0;
    display: flex;
    flex-direction: row;
    gap: 1.5em;
    justify-content: center;
    align-items: center;
    height: auto;
}
.sidebar-dept-item {
    display: flex;
}
.sidebar-dept-item a {
    display: block;
    padding: 0.7em 2.2em;
    border-radius: 8px;
    color: #3a6ea5;
    font-weight: bold;
    text-decoration: none;
    font-size: 1.18em;
    letter-spacing: 2px;
    transition: background 0.2s, color 0.2s;
    text-align: center;
}
.sidebar-dept-item a.active,
.sidebar-dept-item a:hover {
    background: #e5e1d8;
    color: #d4af37;
}
.main-content-flex {
    display: block;
}
.doctor-marquee-container {
    width: 100%;
    max-width: 900px;
    overflow: hidden;
    margin: 0 auto 30px auto;
    background: transparent;
    position: relative;
    height: 270px;
    display: flex;
    align-items: center;
    justify-content: center;
}
.doctor-marquee-track {
    display: flex;
    align-items: center;
    height: 100%;
    animation: marquee 18s linear infinite;
    justify-content: center;
}
.doctor-marquee-card {
    flex: 0 0 320px;
    margin: 0 32px;
    background: #fff;
    border-radius: 18px;
    box-shadow: 0 2px 16px #e5e1d8;
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 2em 1em 1.5em 1em;
    text-align: center;
    transition: box-shadow 0.2s;
    cursor: pointer;
}
.doctor-marquee-card:hover {
    box-shadow: 0 4px 18px #d4af37;
}
.doctor-marquee-photo {
    width: 140px;
    height: 140px;
    object-fit: cover;
    border-radius: 50%;
    border: 3px solid #e5e1d8;
    background: #f8f6f2;
    margin-bottom: 1em;
    box-shadow: 0 2px 16px #e5e1d8;
    transition: box-shadow 0.3s;
}
.doctor-marquee-name {
    font-size: 1.25em;
    font-weight: bold;
    color: #3a6ea5;
    margin-bottom: 0.5em;
    word-break: break-all;
}
.doctor-marquee-profile {
    color: #555;
    font-size: 1.08em;
    line-height: 1.7;
    min-height: 40px;
    margin-bottom: 0.2em;
}
@keyframes marquee {
    0% { transform: translateX(0); }
    100% { transform: translateX(-50%); }
}
@media (max-width: 1100px) {
    .doctor-marquee-container { width: 98vw; }
}
@media (max-width: 600px) {
    .sidebar-dept-list { flex-direction: column; gap: 0.5em; }
    .sidebar-dept-item a { padding: 0.7em 1em; font-size: 1em;}
    .doctor-marquee-card { flex: 0 0 90vw; max-width: 320px; }
    .doctor-marquee-photo { width: 60vw; height: 60vw; max-width: 120px; max-height: 120px;}
}
</style>
<div class="dashboard" style="max-width:1200px;margin:40px auto;">
    <h2>👨‍⚕️ 醫師資訊查詢</h2>
    <?php
    $dep_id = isset($_GET['dep']) ? intval($_GET['dep']) : 0;
    // 只有在沒選科別時顯示所有醫師跑馬燈
    if (!$dep_id) {
        ?>
        <div class="doctor-marquee-container">
            <div class="doctor-marquee-track" id="allDoctorMarqueeTrack">
                <?php
                $sql = "SELECT d.doctor_id, u.name, d.photo_url, d.profile
                        FROM doctors d
                        JOIN users u ON d.user_id = u.id";
                $result = $conn->query($sql);
                $all_doctors = [];
                while ($row = $result->fetch_assoc()) {
                    $all_doctors[] = $row;
                }
                $marqueeDoctors = array_merge($all_doctors, $all_doctors);
                foreach ($marqueeDoctors as $row):
                    // 直接用資料庫的 photo_url 欄位（建議存 9.jpg 或 uploads/9.jpg）
                    if (!empty($row['photo_url'])) {
                        // 若存檔名
                        if (strpos($row['photo_url'], '/') === false) {
                            $photo = "/clinic/uploads/" . htmlspecialchars($row['photo_url']);
                        } else {
                            // 若存 uploads/9.jpg 這種相對路徑
                            $photo = "/clinic/" . ltrim($row['photo_url'], "/");
                        }
                    } else {
                        $photo = "/clinic/uploads/default.png";
                    }
                ?>
                <div class="doctor-marquee-card">
                    <img src="<?= $photo ?>" alt="醫師照片" class="doctor-marquee-photo">
                    <div class="doctor-marquee-name"><?= htmlspecialchars($row['name']) ?></div>
                    <div class="doctor-marquee-profile"><?= nl2br(htmlspecialchars($row['profile'] ?? '尚無簡介')) ?></div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <script>
        window.addEventListener('DOMContentLoaded', function() {
            var track = document.getElementById('allDoctorMarqueeTrack');
            var cardCount = track.children.length / 2;
            var duration = Math.max(10, cardCount * 3);
            track.style.animationDuration = duration + 's';
        });
        </script>
        <?php
    }
    ?>
    <div class="main-content-flex">
        <!-- 側邊欄科別 -->
        <div class="sidebar-dept-list">
            <?php
            $deps = $conn->query("SELECT department_id, name FROM departments ORDER BY department_id");
            $dept_arr = [];
            while ($dep = $deps->fetch_assoc()) {
                $dept_arr[] = $dep;
                $active = ($dep_id == $dep['department_id']) ? 'active' : '';
                echo '<div class="sidebar-dept-item"><a href="?dep=' . $dep['department_id'] . '" class="' . $active . '">' . htmlspecialchars($dep['name']) . '</a></div>';
            }
            ?>
        </div>
        <!-- 主要內容 -->
        <div style="flex:1;">
<?php
// 取得所有科別的醫師（依科別分組）
$all_doctors_by_dept = [];
foreach ($dept_arr as $dept) {
    $sql = "SELECT d.doctor_id, u.name, d.photo_url, d.profile
            FROM doctors d
            JOIN users u ON d.user_id = u.id
            WHERE d.department_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $dept['department_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $all_doctors_by_dept[$dept['department_id']] = [];
    while ($row = $result->fetch_assoc()) {
        $all_doctors_by_dept[$dept['department_id']][] = $row;
    }
    $stmt->close();
}

// 只顯示目前選取的科別
if (!$dep_id) {
    echo '<div style="text-align:center;color:#888;margin-top:80px;">請點選左側科別以瀏覽該科醫師</div>';
} else {
    $doctors = $all_doctors_by_dept[$dep_id];
    if (count($doctors) === 0) {
        echo '<div style="text-align:center;color:#888;">此科別暫無醫師資料</div>';
    } else {
        ?>
        <div class="doctor-marquee-container">
            <div class="doctor-marquee-track" id="doctorMarqueeTrack">
                <?php
                // 跑馬燈無縫，重複一份
                $marqueeDoctors = array_merge($doctors, $doctors);
                foreach ($marqueeDoctors as $row):
                    if (!empty($row['photo_url'])) {
                        if (strpos($row['photo_url'], '/') === false) {
                            $photo = "/clinic/uploads/" . htmlspecialchars($row['photo_url']);
                        } else {
                            $photo = "/clinic/" . ltrim($row['photo_url'], "/");
                        }
                    } else {
                        $photo = "/clinic/uploads/default.png";
                    }
                ?>
                <div class="doctor-marquee-card">
                    <img src="<?= $photo ?>" alt="醫師照片" class="doctor-marquee-photo">
                    <div class="doctor-marquee-name"><?= htmlspecialchars($row['name']) ?></div>
                    <div class="doctor-marquee-profile"><?= nl2br(htmlspecialchars($row['profile'] ?? '尚無簡介')) ?></div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <script>
        window.addEventListener('DOMContentLoaded', function() {
            var track = document.getElementById('doctorMarqueeTrack');
            var cardCount = track.children.length / 2;
            var duration = Math.max(10, cardCount * 3);
            track.style.animationDuration = duration + 's';
        });
        </script>
        <?php
    }
}
?>
        </div>
    </div>
</div>
<?php include("../footer.php"); ?>