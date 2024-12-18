<?php
require_once 'includes/auth.php';
require_once 'config/database.php';

// Obtém o mês atual ou o selecionado
$month = isset($_GET['month']) ? $_GET['month'] : date('m');
$year = isset($_GET['year']) ? $_GET['year'] : date('Y');

// Busca todas as manutenções do mês
$stmt = $conn->prepare("
    SELECT 
        ms.*,
        e.name as equipment_name,
        t.name as team_name,
        GROUP_CONCAT(u.username) as team_members
    FROM maintenance_schedule ms
    JOIN equipment e ON ms.equipment_id = e.id
    JOIN teams t ON ms.team_id = t.id
    LEFT JOIN team_members tm ON t.id = tm.team_id
    LEFT JOIN users u ON tm.user_id = u.id
    WHERE MONTH(scheduled_date) = ? AND YEAR(scheduled_date) = ?
    GROUP BY ms.id
    ORDER BY ms.scheduled_date
");
$stmt->execute([$month, $year]);
$maintenances = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Organiza as manutenções por data
$calendar = [];
foreach ($maintenances as $maintenance) {
    $date = date('Y-m-d', strtotime($maintenance['scheduled_date']));
    if (!isset($calendar[$date])) {
        $calendar[$date] = [];
    }
    $calendar[$date][] = $maintenance;
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Calendário de Manutenções</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        .calendar-day {
            min-height: 120px;
            border: 1px solid #dee2e6;
        }
        .calendar-day:hover {
            background-color: #f8f9fa;
        }
        .calendar-event {
            margin: 2px;
            padding: 2px 5px;
            border-radius: 3px;
            font-size: 0.8em;
            cursor: pointer;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .event-preventive {
            background-color: #cce5ff;
            border: 1px solid #b8daff;
        }
        .event-corrective {
            background-color: #fff3cd;
            border: 1px solid #ffeeba;
        }
        .calendar-header {
            background-color: #f8f9fa;
            font-weight: bold;
        }
        .today {
            background-color: #e8f4f8;
        }
    </style>
</head>
<body>
    <?php include 'includes/navbar.php'; ?>

    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Calendário de Manutenções</h2>
            
            <!-- Navegação entre meses -->
            <div class="btn-group">
                <?php
                $prev_month = $month == 1 ? 12 : $month - 1;
                $prev_year = $month == 1 ? $year - 1 : $year;
                $next_month = $month == 12 ? 1 : $month + 1;
                $next_year = $month == 12 ? $year + 1 : $year;
                ?>
                <a href="?month=<?php echo $prev_month; ?>&year=<?php echo $prev_year; ?>" 
                   class="btn btn-outline-primary">
                    <i class="bi bi-chevron-left"></i>
                </a>
                <button class="btn btn-outline-primary" disabled>
                    <?php echo strftime('%B %Y', strtotime("$year-$month-01")); ?>
                </button>
                <a href="?month=<?php echo $next_month; ?>&year=<?php echo $next_year; ?>" 
                   class="btn btn-outline-primary">
                    <i class="bi bi-chevron-right"></i>
                </a>
            </div>
        </div>

        <!-- Calendário -->
        <div class="card">
            <div class="card-body">
                <div class="row calendar-header">
                    <div class="col">Domingo</div>
                    <div class="col">Segunda</div>
                    <div class="col">Terça</div>
                    <div class="col">Quarta</div>
                    <div class="col">Quinta</div>
                    <div class="col">Sexta</div>
                    <div class="col">Sábado</div>
                </div>
                <?php
                $first_day = strtotime("$year-$month-01");
                $days_in_month = date('t', $first_day);
                $first_day_of_week = date('w', $first_day);
                $last_day = strtotime("$year-$month-$days_in_month");
                
                // Início do calendário
                echo "<div class='row'>";
                
                // Dias vazios do início do mês
                for ($i = 0; $i < $first_day_of_week; $i++) {
                    echo "<div class='col calendar-day'></div>";
                }
                
                // Dias do mês
                for ($day = 1; $day <= $days_in_month; $day++) {
                    $date = sprintf('%04d-%02d-%02d', $year, $month, $day);
                    $is_today = $date == date('Y-m-d');
                    
                    if (($day + $first_day_of_week - 1) % 7 == 0) {
                        echo "</div><div class='row'>";
                    }
                    
                    echo "<div class='col calendar-day" . ($is_today ? ' today' : '') . "'>";
                    echo "<div class='d-flex justify-content-between'>";
                    echo "<span>$day</span>";
                    if (checkPermission('supervisor')) {
                        echo "<button class='btn btn-sm btn-link p-0' data-bs-toggle='modal' 
                              data-bs-target='#addMaintenanceModal' data-date='$date'>
                              <i class='bi bi-plus-circle'></i>
                              </button>";
                    }
                    echo "</div>";
                    
                    // Eventos do dia
                    if (isset($calendar[$date])) {
                        foreach ($calendar[$date] as $event) {
                            $class = $event['maintenance_type'] == 'preventive' ? 'event-preventive' : 'event-corrective';
                            echo "<div class='calendar-event $class' 
                                      data-bs-toggle='modal' 
                                      data-bs-target='#eventDetailsModal'
                                      data-event='" . htmlspecialchars(json_encode($event)) . "'>";
                            echo htmlspecialchars($event['equipment_name']);
                            echo "</div>";
                        }
                    }
                    echo "</div>";
                }
                
                // Dias vazios do fim do mês
                $last_day_of_week = date('w', $last_day);
                for ($i = $last_day_of_week; $i < 6; $i++) {
                    echo "<div class='col calendar-day'></div>";
                }
                echo "</div>";
                ?>
            </div>
        </div>
    </div>

    <!-- Modal de Detalhes do Evento -->
    <div class="modal fade" id="eventDetailsModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Detalhes da Manutenção</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <dl class="row">
                        <dt class="col-sm-4">Equipamento</dt>
                        <dd class="col-sm-8" id="modalEquipment"></dd>
                        
                        <dt class="col-sm-4">Equipe</dt>
                        <dd class="col-sm-8" id="modalTeam"></dd>
                        
                        <dt class="col-sm-4">Membros</dt>
                        <dd class="col-sm-8" id="modalMembers"></dd>
                        
                        <dt class="col-sm-4">Tipo</dt>
                        <dd class="col-sm-8" id="modalType"></dd>
                        
                        <dt class="col-sm-4">Status</dt>
                        <dd class="col-sm-8" id="modalStatus"></dd>
                    </dl>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de Adicionar Manutenção -->
    <?php if (checkPermission('supervisor')): ?>
    <div class="modal fade" id="addMaintenanceModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Agendar Manutenção</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="maintenance_schedule.php" method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="scheduled_date" id="maintenanceDate">
                        
                        <div class="mb-3">
                            <label for="equipment" class="form-label">Equipamento</label>
                            <select class="form-select" id="equipment" name="equipment_id" required>
                                <?php
                                $equipments = $conn->query("SELECT id, name FROM equipment ORDER BY name")->fetchAll();
                                foreach ($equipments as $equipment) {
                                    echo "<option value='{$equipment['id']}'>{$equipment['name']}</option>";
                                }
                                ?>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="team" class="form-label">Equipe</label>
                            <select class="form-select" id="team" name="team_id" required>
                                <?php
                                $teams = $conn->query("SELECT id, name FROM teams ORDER BY name")->fetchAll();
                                foreach ($teams as $team) {
                                    echo "<option value='{$team['id']}'>{$team['name']}</option>";
                                }
                                ?>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="type" class="form-label">Tipo</label>
                            <select class="form-select" id="type" name="maintenance_type" required>
                                <option value="preventive">Preventiva</option>
                                <option value="corrective">Corretiva</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Agendar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Preenche o modal de detalhes
        document.getElementById('eventDetailsModal').addEventListener('show.bs.modal', function (event) {
            var button = event.relatedTarget;
            var event = JSON.parse(button.getAttribute('data-event'));
            
            document.getElementById('modalEquipment').textContent = event.equipment_name;
            document.getElementById('modalTeam').textContent = event.team_name;
            document.getElementById('modalMembers').textContent = event.team_members;
            document.getElementById('modalType').textContent = event.maintenance_type;
            document.getElementById('modalStatus').textContent = event.status;
        });

        // Preenche a data no modal de agendamento
        document.getElementById('addMaintenanceModal').addEventListener('show.bs.modal', function (event) {
            var button = event.relatedTarget;
            var date = button.getAttribute('data-date');
            document.getElementById('maintenanceDate').value = date;
        });
    </script>
</body>
</html>
