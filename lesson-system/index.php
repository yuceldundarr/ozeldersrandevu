<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Özel Ders Randevu Sistemi</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .hero-section {
            position: relative;
            min-height: 100vh;
            background: linear-gradient(rgba(0, 0, 0, 0.6), rgba(0, 0, 0, 0.6)),
                        url('https://images.unsplash.com/photo-1523240795612-9a054b0db644?auto=format&fit=crop&q=80');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            color: white;
        }
        .hero-content {
            position: relative;
            z-index: 1;
            padding: 2rem;
            background: rgba(0, 0, 0, 0.5);
            border-radius: 15px;
            backdrop-filter: blur(10px);
        }
        .navbar {
            background-color: rgba(13, 110, 253, 0.95) !important;
            backdrop-filter: blur(10px);
        }
        .card {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .animate-fade-in {
            animation: fadeIn 1s ease forwards;
        }
    </style>
</head>
<body class="home-page">
    <div class="page-container">
        <?php
        session_start();
        require_once 'config/database.php';
        require_once 'includes/functions.php';
        ?>

        <nav class="navbar navbar-expand-lg navbar-dark bg-primary fixed-top">
            <div class="container">
                <a class="navbar-brand" href="index.php">Özel Ders Randevu</a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="navbarNav">
                    <ul class="navbar-nav ms-auto">
                        <?php if(!isset($_SESSION['user_id'])): ?>
                            <li class="nav-item">
                                <a class="nav-link" href="login.php">Giriş Yap</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="register.php">Kayıt Ol</a>
                            </li>
                        <?php else: ?>
                            <li class="nav-item">
                                <a class="nav-link" href="teachers.php">Öğretmenler</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="appointments.php">Randevularım</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="profile.php">Profilim</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="logout.php">Çıkış Yap</a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
        </nav>

        <div class="hero-section d-flex align-items-center">
            <div class="container">
                <div class="row justify-content-center">
                    <div class="col-md-8">
                        <div class="hero-content text-center animate-fade-in">
                            <h1 class="display-4 mb-4">Özel Ders Randevu Sistemi</h1>
                            <p class="lead mb-4">Eğitim yolculuğunuzda size rehberlik edecek öğretmenlerle tanışın.</p>
                            <?php if(!isset($_SESSION['user_id'])): ?>
                                <div class="d-flex justify-content-center gap-3">
                                    <a href="login.php" class="btn btn-primary btn-lg">Giriş Yap</a>
                                    <a href="register.php" class="btn btn-outline-light btn-lg">Kayıt Ol</a>
                                </div>
                            <?php elseif($_SESSION['user_type'] === 'student'): ?>
                                <div class="d-flex justify-content-center gap-3">
                                    <a href="create-appointment.php" class="btn btn-primary btn-lg">Randevu Oluştur</a>
                                    <a href="appointments.php" class="btn btn-outline-light btn-lg">Randevularım</a>
                                </div>
                            <?php else: ?>
                                <div class="d-flex justify-content-center gap-3">
                                    <a href="appointments.php" class="btn btn-primary btn-lg">Randevularım</a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    </div>
</body>
</html>
