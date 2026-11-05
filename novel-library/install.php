<?php

declare(strict_types=1);

require __DIR__ . '/includes/bootstrap.php';

$pageTitle = 'ติดตั้งฐานข้อมูล';
$pageDescription = 'นำเข้าโครงสร้างคลังนิยาย';
$layout = 'auth';
$page = 'install';
$lock = APP_ROOT . '/install.lock';
$messages = [];
$ready = false;

try {
    $db = db();
    $ready = true;
    $exists = (bool) $db->query("SHOW TABLES LIKE 'users'")->fetchColumn();
} catch (Throwable $exception) {
    $exists = false;
    $messages[] = 'ยังเชื่อมต่อฐานข้อมูลไม่ได้ ตรวจค่าใน config/config.php หลังจากสร้างฐานข้อมูลใน phpMyAdmin แล้ว';
}

if (is_file($lock) && $exists) {
    $messages[] = 'ระบบถูกติดตั้งแล้ว หากต้องการนำเข้าใหม่ ให้ลบไฟล์ install.lock และสำรองข้อมูลเดิมก่อน';
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && $ready && !is_file($lock)) {
    verify_csrf();
    if (empty($_POST['confirm_import'])) {
        flash('warning', 'ติ๊กยืนยันก่อนนำเข้าโครงสร้าง');
        redirect('install.php');
    }

    $sql = file_get_contents(APP_ROOT . '/sql/schema.sql');
    if ($sql === false) {
        flash('error', 'ไม่พบไฟล์ sql/schema.sql');
        redirect('install.php');
    }

    $config = app_config()['db'];
    $mysqli = new mysqli(
        (string) $config['host'],
        (string) $config['user'],
        (string) $config['pass'],
        (string) $config['name'],
        (int) $config['port']
    );
    if ($mysqli->connect_error) {
        flash('error', 'เชื่อมต่อเพื่อนำเข้าข้อมูลไม่ได้');
        redirect('install.php');
    }
    $mysqli->set_charset('utf8mb4');
    if (!$mysqli->multi_query($sql)) {
        flash('error', 'นำเข้าไม่สำเร็จ: ' . $mysqli->error);
        $mysqli->close();
        redirect('install.php');
    }
    do {
        if ($result = $mysqli->store_result()) {
            $result->free();
        }
        if ($mysqli->errno) {
            $error = $mysqli->error;
            $mysqli->close();
            flash('error', 'นำเข้าไม่สำเร็จ: ' . $error);
            redirect('install.php');
        }
    } while ($mysqli->more_results() && $mysqli->next_result());
    $mysqli->close();
    file_put_contents($lock, 'installed ' . date('c'));
    flash('success', 'นำเข้าฐานข้อมูลแล้ว เข้าสู่ระบบด้วยบัญชีทดลองได้จากหน้าเข้าสู่ระบบ');
    redirect('index.php');
}

require __DIR__ . '/includes/header.php';
?>
<main id="content" class="page-shell">
    <?php render_flash_fallback(); ?>
    <section class="auth-card">
        <p class="eyebrow"><i class="fa-solid fa-database" aria-hidden="true"></i> ตัวช่วยติดตั้ง</p>
        <h1 class="h3">นำเข้าโครงสร้างคลังนิยาย</h1>
        <ul>
            <li>PHP <?= e(PHP_VERSION) ?> <?= PHP_VERSION_ID >= 80200 ? 'ผ่านเกณฑ์ 8.2.0' : 'ต่ำกว่า 8.2.0' ?></li>
            <li>PDO MySQL <?= extension_loaded('pdo_mysql') ? 'พร้อม' : 'ยังไม่มี' ?></li>
            <li>mysqli <?= extension_loaded('mysqli') ? 'พร้อม' : 'ยังไม่มี' ?></li>
            <li>mbstring <?= extension_loaded('mbstring') ? 'พร้อม' : 'ยังไม่มี' ?></li>
            <li>การเชื่อมต่อ <?= $ready ? 'สำเร็จ' : 'ยังไม่สำเร็จ' ?></li>
        </ul>
        <?php foreach ($messages as $message): ?>
            <div class="alert alert-warning"><?= e($message) ?></div>
        <?php endforeach; ?>
        <?php if ($ready && !is_file($lock)): ?>
            <form method="post" action="install.php" data-confirm="การนำเข้าจะลบตารางเดิมของคลังนิยายถ้ามีอยู่แล้ว ต้องการทำต่อหรือไม่" data-confirm-ok="นำเข้า">
                <?= csrf_field() ?>
                <div class="form-check mb-3">
                    <input class="form-check-input" type="checkbox" id="confirm_import" name="confirm_import" value="1" required>
                    <label class="form-check-label" for="confirm_import">ฉันสำรองข้อมูลแล้ว และยืนยันให้นำเข้า sql/schema.sql</label>
                </div>
                <button class="btn btn-seal" type="submit">นำเข้าฐานข้อมูล</button>
            </form>
        <?php endif; ?>
        <p class="mt-3 mb-0"><a href="index.php">ไปที่หน้าแรก</a></p>
    </section>
</main>
<?php
require __DIR__ . '/includes/footer.php';
