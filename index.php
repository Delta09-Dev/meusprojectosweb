<?php
require_once 'includes/auth.php';
require_once 'config/database.php';
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema de Gestão de Manutenção</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="#">SGM</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="dashboard.php">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="equipment.php">Equipamentos</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="teams.php">Equipes</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="maintenance.php">Manutenções</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="consumables.php">Consumíveis</a>
                    </li>
                    <?php if (checkPermission('supervisor')): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="reports.php">Relatórios</a>
                    </li>
                    <?php endif; ?>
                    <?php if (checkPermission('admin')): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="users.php">Usuários</a>
                    </li>
                    <?php endif; ?>
                </ul>
                <ul class="navbar-nav">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                            <?php echo htmlspecialchars($_SESSION['username']); ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="profile.php">Meu Perfil</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="logout.php">Sair</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <div class="row">
            <div class="col-md-12">
                <h2>Bem-vindo, <?php echo htmlspecialchars($_SESSION['username']); ?>!</h2>
                
                <!-- Cards de Resumo -->
                <div class="row mt-4">
                    <div class="col-md-3">
                        <div class="card text-white bg-primary mb-3">
                            <div class="card-body">
                                <h5 class="card-title">Manutenções Pendentes</h5>
                                <?php
                                $stmt = $conn->query("SELECT COUNT(*) FROM maintenance_schedule WHERE status = 'scheduled'");
                                $pending = $stmt->fetchColumn();
                                ?>
                                <p class="card-text h2"><?php echo $pending; ?></p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-white bg-success mb-3">
                            <div class="card-body">
                                <h5 class="card-title">Equipamentos</h5>
                                <?php
                                $stmt = $conn->query("SELECT COUNT(*) FROM equipment");
                                $equipment = $stmt->fetchColumn();
                                ?>
                                <p class="card-text h2"><?php echo $equipment; ?></p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-white bg-warning mb-3">
                            <div class="card-body">
                                <h5 class="card-title">Alertas</h5>
                                <?php
                                $stmt = $conn->query("SELECT COUNT(*) FROM email_alerts WHERE status = 'pending'");
                                $alerts = $stmt->fetchColumn();
                                ?>
                                <p class="card-text h2"><?php echo $alerts; ?></p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-white bg-info mb-3">
                            <div class="card-body">
                                <h5 class="card-title">Equipes</h5>
                                <?php
                                $stmt = $conn->query("SELECT COUNT(*) FROM teams");
                                $teams = $stmt->fetchColumn();
                                ?>
                                <p class="card-text h2"><?php echo $teams; ?></p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Calendário de Manutenções -->
                <div class="card mt-4">
                    <div class="card-header">
                        Calendário de Manutenções
                    </div>
                    <div class="card-body">
                        <div id="calendar"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var calendarEl = document.getElementById('calendar');
            var calendar = new FullCalendar.Calendar(calendarEl, {
                initialView: 'dayGridMonth',
                locale: 'pt',
                headerToolbar: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'dayGridMonth,timeGridWeek,timeGridDay'
                },
                events: 'api/get_maintenance_events.php'
            });
            calendar.render();
        });
    </script>
</body>
</html>
