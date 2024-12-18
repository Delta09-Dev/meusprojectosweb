<?php
require_once 'includes/auth.php';
require_once 'config/database.php';

$page_title = "Lista de Manutenções - Sistema de Manutenção";

// Filtros
$status_filter = $_GET['status'] ?? 'all';
$type_filter = $_GET['type'] ?? 'all';

// Construir a query base
$query = "
    SELECT 
        m.*,
        e.name as equipment_name,
        t.name as team_name
    FROM maintenance m
    JOIN equipment e ON m.equipment_id = e.id
    JOIN teams t ON m.team_id = t.id
    WHERE 1=1
";

// Aplicar filtros
if ($status_filter !== 'all') {
    $query .= " AND m.status = " . $conn->quote($status_filter);
}
if ($type_filter !== 'all') {
    $query .= " AND m.maintenance_type = " . $conn->quote($type_filter);
}

// Ordenar por data
$query .= " ORDER BY m.scheduled_date DESC";

// Executar a query
$maintenances = $conn->query($query)->fetchAll(PDO::FETCH_ASSOC);

include 'includes/header.php';
?>

<div class="container mt-4">
    <?php if (isset($_SESSION['success_msg'])): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <?php 
            echo $_SESSION['success_msg'];
            unset($_SESSION['success_msg']);
            ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="card">
        <div class="card-header">
            <div class="d-flex justify-content-between align-items-center">
                <h4 class="mb-0">
                    <i class="bi bi-tools"></i> Lista de Manutenções
                </h4>
                <div>
                    <?php if (checkPermission('supervisor')): ?>
                    <a href="reports.php" class="btn btn-outline-secondary me-2">
                        <i class="bi bi-file-text"></i> Relatórios
                    </a>
                    <?php endif; ?>
                    <a href="schedule_maintenance.php" class="btn btn-primary">
                        <i class="bi bi-plus-circle"></i> Nova Manutenção
                    </a>
                </div>
            </div>
        </div>

        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="bi bi-calendar3"></i> Manutenções</h2>
        </div>

        <!-- Filtros -->
        <div class="card mb-4">
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <div class="col-md-4">
                        <label for="status" class="form-label">Status</label>
                        <select class="form-select" id="status" name="status">
                            <option value="all" <?php echo $status_filter === 'all' ? 'selected' : ''; ?>>Todos</option>
                            <option value="scheduled" <?php echo $status_filter === 'scheduled' ? 'selected' : ''; ?>>Agendada</option>
                            <option value="in_progress" <?php echo $status_filter === 'in_progress' ? 'selected' : ''; ?>>Em Andamento</option>
                            <option value="completed" <?php echo $status_filter === 'completed' ? 'selected' : ''; ?>>Concluída</option>
                            <option value="cancelled" <?php echo $status_filter === 'cancelled' ? 'selected' : ''; ?>>Cancelada</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label for="type" class="form-label">Tipo</label>
                        <select class="form-select" id="type" name="type">
                            <option value="all" <?php echo $type_filter === 'all' ? 'selected' : ''; ?>>Todos</option>
                            <option value="preventive" <?php echo $type_filter === 'preventive' ? 'selected' : ''; ?>>Preventiva</option>
                            <option value="corrective" <?php echo $type_filter === 'corrective' ? 'selected' : ''; ?>>Corretiva</option>
                        </select>
                    </div>
                    <div class="col-md-4 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary me-2">
                            <i class="bi bi-funnel"></i> Filtrar
                        </button>
                        <a href="maintenance_list.php" class="btn btn-secondary">
                            <i class="bi bi-arrow-counterclockwise"></i> Limpar
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Lista de Manutenções -->
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Data Agendada</th>
                        <th>Equipamento</th>
                        <th>Equipe</th>
                        <th>Tipo</th>
                        <th>Prioridade</th>
                        <th>Status</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($maintenances as $maintenance): ?>
                        <tr>
                            <td><?php echo date('d/m/Y H:i', strtotime($maintenance['scheduled_date'])); ?></td>
                            <td><?php echo htmlspecialchars($maintenance['equipment_name']); ?></td>
                            <td><?php echo htmlspecialchars($maintenance['team_name']); ?></td>
                            <td>
                                <span class="badge bg-<?php echo $maintenance['maintenance_type'] === 'preventive' ? 'primary' : 'warning'; ?>">
                                    <?php echo $maintenance['maintenance_type'] === 'preventive' ? 'Preventiva' : 'Corretiva'; ?>
                                </span>
                            </td>
                            <td>
                                <?php
                                $priority_classes = [
                                    'low' => 'success',
                                    'normal' => 'primary',
                                    'high' => 'warning',
                                    'urgent' => 'danger'
                                ];
                                $priority_labels = [
                                    'low' => 'Baixa',
                                    'normal' => 'Normal',
                                    'high' => 'Alta',
                                    'urgent' => 'Urgente'
                                ];
                                ?>
                                <span class="badge bg-<?php echo $priority_classes[$maintenance['priority']]; ?>">
                                    <?php echo $priority_labels[$maintenance['priority']]; ?>
                                </span>
                            </td>
                            <td>
                                <?php
                                $status_classes = [
                                    'scheduled' => 'info',
                                    'in_progress' => 'warning',
                                    'completed' => 'success',
                                    'cancelled' => 'danger'
                                ];
                                $status_labels = [
                                    'scheduled' => 'Agendada',
                                    'in_progress' => 'Em Andamento',
                                    'completed' => 'Concluída',
                                    'cancelled' => 'Cancelada'
                                ];
                                ?>
                                <span class="badge bg-<?php echo $status_classes[$maintenance['status']]; ?>">
                                    <?php echo $status_labels[$maintenance['status']]; ?>
                                </span>
                            </td>
                            <td>
                                <div class="btn-group">
                                    <a href="view_maintenance.php?id=<?php echo $maintenance['id']; ?>" 
                                       class="btn btn-sm btn-info" title="Ver Detalhes">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <?php if ($maintenance['status'] === 'scheduled'): ?>
                                        <a href="edit_maintenance.php?id=<?php echo $maintenance['id']; ?>" 
                                           class="btn btn-sm btn-primary" title="Editar">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <button type="button" class="btn btn-sm btn-danger" title="Cancelar"
                                                onclick="cancelMaintenance(<?php echo $maintenance['id']; ?>)">
                                            <i class="bi bi-x-circle"></i>
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
function cancelMaintenance(id) {
    if (confirm('Tem certeza que deseja cancelar esta manutenção?')) {
        window.location.href = 'cancel_maintenance.php?id=' + id;
    }
}
</script>

<?php include 'includes/footer.php'; ?>
