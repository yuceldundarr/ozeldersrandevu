<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Özel Ders Sistemi</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.2/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="/lesson-system/assets/style.css">
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="index.php">Özel Ders Sistemi</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <?php if(isset($_SESSION['user_id'])): ?>
                        <?php if($_SESSION['user_type'] === 'student'): ?>
                            <li class="nav-item">
                                <a class="nav-link" href="teachers.php">Öğretmenler</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="appointments.php">Randevularım</a>
                            </li>
                        <?php elseif($_SESSION['user_type'] === 'teacher'): ?>
                            <li class="nav-item">
                                <a class="nav-link" href="appointments.php">Randevularım</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="teacher-availability.php">Müsaitlik Durumu</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="teacher-details.php?id=<?php echo $_SESSION['user_id']; ?>">Profilimi Görüntüle</a>
                            </li>
                        <?php endif; ?>
                    <?php endif; ?>
                </ul>
                
                <div class="d-flex align-items-center">
                    <?php if(isset($_SESSION['user_id'])): ?>
                        <div class="dropdown">
                            <button class="btn btn-link text-white text-decoration-none dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                <i class="bi bi-person-circle me-1"></i>
                                <?php echo htmlspecialchars($_SESSION['user_name']); ?>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <?php if($_SESSION['user_type'] === 'teacher'): ?>
                                    <li><a class="dropdown-item" href="edit-profile.php">Profil Düzenle</a></li>
                                <?php else: ?>
                                    <li><a class="dropdown-item" href="profile.php">Profilim</a></li>
                                <?php endif; ?>
                                <?php if($_SESSION['user_type'] === 'admin'): ?>
                                    <li><a class="dropdown-item" href="admin/dashboard.php">Admin Panel</a></li>
                                <?php endif; ?>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="logout.php">Çıkış Yap</a></li>
                            </ul>
                        </div>
                    <?php else: ?>
                        <a href="login.php" class="btn btn-outline-light me-2">Giriş Yap</a>
                        <a href="register.php" class="btn btn-light">Kayıt Ol</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>

<script>
// Aktif sayfayı belirle
document.addEventListener('DOMContentLoaded', function() {
    const currentPage = window.location.pathname.split('/').pop();
    const navLinks = document.querySelectorAll('.nav-link');
    
    navLinks.forEach(link => {
        const linkPage = link.getAttribute('href');
        if (linkPage === currentPage) {
            link.classList.add('active');
        }
    });
});
</script>
