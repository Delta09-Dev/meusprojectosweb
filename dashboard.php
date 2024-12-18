<?php
require_once 'includes/auth.php';
require_once 'config/database.php';

$page_title = "Dashboard - Sistema de Manutenção";

// Busca estatísticas gerais
$stats = $conn->query("
    SELECT 
        COUNT(*) as total_equipment,
        SUM(CASE WHEN status = 'operational' THEN 1 ELSE 0 END) as operational_count,
        SUM(CASE WHEN status = 'maintenance' THEN 1 ELSE 0 END) as in_maintenance_count,
        SUM(CASE WHEN status = 'broken' THEN 1 ELSE 0 END) as broken_count
    FROM equipment
")->fetch(PDO::FETCH_ASSOC);

// Busca manutenções dos últimos 6 meses
$maintenance_stats = $conn->query("
    SELECT 
        DATE_FORMAT(scheduled_date, '%Y-%m') as month,
        COUNT(*) as total,
        SUM(CASE WHEN maintenance_type = 'preventive' THEN 1 ELSE 0 END) as preventive_count,
        SUM(CASE WHEN maintenance_type = 'corrective' THEN 1 ELSE 0 END) as corrective_count
    FROM maintenance_schedule
    WHERE scheduled_date >= DATE_SUB(CURRENT_DATE, INTERVAL 6 MONTH)
    GROUP BY DATE_FORMAT(scheduled_date, '%Y-%m')
    ORDER BY month
")->fetchAll(PDO::FETCH_ASSOC);

// Busca consumíveis com estoque baixo
$low_stock = $conn->query("
    SELECT name, quantity, minimum_quantity
    FROM consumables
    WHERE quantity <= minimum_quantity
    ORDER BY quantity/minimum_quantity ASC
    LIMIT 5
")->fetchAll(PDO::FETCH_ASSOC);

// Busca próximas manutenções
$upcoming = $conn->query("
    SELECT 
        ms.scheduled_date,
        e.name as equipment_name,
        t.name as team_name,
        ms.maintenance_type
    FROM maintenance_schedule ms
    JOIN equipment e ON ms.equipment_id = e.id
    JOIN teams t ON ms.team_id = t.id
    WHERE ms.scheduled_date >= CURRENT_DATE
    AND ms.status = 'scheduled'
    ORDER BY ms.scheduled_date
    LIMIT 5
")->fetchAll(PDO::FETCH_ASSOC);
?>

<?php include 'includes/header.php'; ?>

<div class="container mt-4">
    <h2>Dashboard</h2>

    <!-- Cards de Resumo -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <h5 class="card-title">Total de Equipamentos</h5>
                    <p class="card-text display-6"><?php echo $stats['total_equipment']; ?></p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <h5 class="card-title">Operacionais</h5>
                    <p class="card-text display-6"><?php echo $stats['operational_count']; ?></p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning text-white">
                <div class="card-body">
                    <h5 class="card-title">Em Manutenção</h5>
                    <p class="card-text display-6"><?php echo $stats['in_maintenance_count']; ?></p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-danger text-white">
                <div class="card-body">
                    <h5 class="card-title">Inoperantes</h5>
                    <p class="card-text display-6"><?php echo $stats['broken_count']; ?></p>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Gráfico de Manutenções -->
        <div class="col-md-8">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Histórico de Manutenções</h5>
                </div>
                <div class="card-body">
                    <canvas id="maintenanceChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Alertas de Estoque -->
        <div class="col-md-4">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Alertas de Estoque</h5>
                </div>
                <div class="card-body">
                    <?php if ($low_stock): ?>
                        <div class="list-group">
                            <?php foreach ($low_stock as $item): ?>
                                <div class="list-group-item">
                                    <h6 class="mb-1"><?php echo htmlspecialchars($item['name']); ?></h6>
                                    <div class="progress">
                                        <?php 
                                        $percentage = ($item['quantity'] / $item['minimum_quantity']) * 100;
                                        $color = $percentage < 50 ? 'danger' : 'warning';
                                        ?>
                                        <div class="progress-bar bg-<?php echo $color; ?>" 
                                             role="progressbar" 
                                             style="width: <?php echo $percentage; ?>%">
                                            <?php echo $item['quantity']; ?>/<?php echo $item['minimum_quantity']; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p class="text-success">Não há itens com estoque baixo.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Próximas Manutenções -->
    <div class="card">
        <div class="card-header">
            <h5 class="card-title mb-0">Próximas Manutenções</h5>
        </div>
        <div class="card-body">
            <?php if ($upcoming): ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Data</th>
                                <th>Equipamento</th>
                                <th>Equipe</th>
                                <th>Tipo</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($upcoming as $maintenance): ?>
                                <tr>
                                    <td><?php echo date('d/m/Y', strtotime($maintenance['scheduled_date'])); ?></td>
                                    <td><?php echo htmlspecialchars($maintenance['equipment_name']); ?></td>
                                    <td><?php echo htmlspecialchars($maintenance['team_name']); ?></td>
                                    <td>
                                        <span class="badge bg-<?php echo $maintenance['maintenance_type'] == 'preventive' ? 'primary' : 'warning'; ?>">
                                            <?php echo $maintenance['maintenance_type']; ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p>Não há manutenções agendadas para os próximos dias.</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
    // Gráfico de Manutenções
    var ctx = document.getElementById('maintenanceChart').getContext('2d');
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: <?php echo json_encode(array_column($maintenance_stats, 'month')); ?>,
            datasets: [{
                label: 'Preventivas',
                data: <?php echo json_encode(array_column($maintenance_stats, 'preventive_count')); ?>,
                backgroundColor: 'rgba(54, 162, 235, 0.5)',
                borderColor: 'rgba(54, 162, 235, 1)',
                borderWidth: 1
            }, {
                label: 'Corretivas',
                data: <?php echo json_encode(array_column($maintenance_stats, 'corrective_count')); ?>,
                backgroundColor: 'rgba(255, 99, 132, 0.5)',
                borderColor: 'rgba(255, 99, 132, 1)',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        stepSize: 1
                    }
                }
            }
        }
    });
</script>

<?php include 'includes/footer.php'; ?>
