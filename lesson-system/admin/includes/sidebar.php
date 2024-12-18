<?php
// Direct access kontrolü
defined('ADMIN_ACCESS') or die('Direct access not permitted');
?>
<!-- Sidebar -->
<div class="col-md-3 col-lg-2 admin-sidebar p-3 text-white">
    <h3 class="h5 mb-4">Admin Panel</h3>
    <ul class="nav flex-column">
        <li class="nav-item mb-2">
            <a class="nav-link text-white" href="dashboard.php">
                <i class="bi bi-speedometer2 me-2"></i>
                Dashboard
            </a>
        </li>
        <li class="nav-item mb-2">
            <a class="nav-link text-white" href="appointments.php">
                <i class="bi bi-calendar-event me-2"></i>
                Randevular
            </a>
        </li>
        <li class="nav-item mb-2">
            <div class="d-flex justify-content-between align-items-center">
                <a class="nav-link text-white" href="users.php">
                    <i class="bi bi-people me-2"></i>
                    Kullanıcılar
                </a>
                <a href="add-user.php" class="btn btn-sm btn-outline-light" title="Yeni Kullanıcı Ekle">
                    <i class="bi bi-plus"></i>
                </a>
            </div>
        </li>
        <li class="nav-item mb-2">
            <a class="nav-link text-white" href="settings.php">
                <i class="bi bi-gear me-2"></i>
                Ayarlar
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link text-white" href="../logout.php">
                <i class="bi bi-box-arrow-right me-2"></i>
                Çıkış Yap
            </a>
        </li>
    </ul>
</div>
