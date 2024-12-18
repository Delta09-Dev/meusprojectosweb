<?php
require_once 'includes/auth.php';
require_once 'config/database.php';

$page_title = "Detalhes da Manutenção - Sistema de Manutenção";

// Verificar se o ID foi fornecido
if (!isset($_GET['id'])) {
    $_SESSION['error_msg'] = "ID da manutenção não fornecido.";
    header("Location: maintenance_list.php");
    exit;
}

// Buscar detalhes da manutenção
$stmt = $conn->prepare("
    SELECT 
        m.*,
        e.name as equipment_name,
        e.model as equipment_model,
        e.serial_number,
        t.name as team_name,
        u.username as supervisor_name
    FROM maintenance m
    JOIN equipment e ON m.equipment_id = e.id
    JOIN teams t ON m.team_id = t.id
    JOIN users u ON t.supervisor_id = u.id
    WHERE m.id = ?
");

$stmt->execute([$_GET['id']]);
$maintenance = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$maintenance) {
    $_SESSION['error_msg'] = "Manutenção não encontrada.";
    header("Location: maintenance_list.php");
    exit;
}

include 'includes/header.php';
?>

<div class="container mt-4">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <h4 class="mb-0">
                            <i class="bi bi-tools"></i> Detalhes da Manutenção
                        </h4>
                        <a href="maintenance_list.php" class="btn btn-secondary btn-sm">
                            <i class="bi bi-arrow-left"></i> Voltar
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <!-- Status da Manutenção -->
                    <div class="alert alert-<?php 
                        echo match($maintenance['status']) {
                            'scheduled' => 'info',
                            'in_progress' => 'warning',
                            'completed' => 'success',
                            'cancelled' => 'danger',
                            default => 'secondary'
                        };
                    ?>">
                        Status: <strong><?php 
                            echo match($maintenance['status']) {
                                'scheduled' => 'Agendada',
                                'in_progress' => 'Em Andamento',
                                'completed' => 'Concluída',
                                'cancelled' => 'Cancelada',
                                default => 'Desconhecido'
                            };
                        ?></strong>
                    </div>

                    <!-- Informações do Equipamento -->
                    <h5 class="card-title mt-3">Equipamento</h5>
                    <div class="row mb-4">
                        <div class="col-md-4">
                            <strong>Nome:</strong><br>
                            <?php echo htmlspecialchars($maintenance['equipment_name']); ?>
                        </div>
                        <div class="col-md-4">
                            <strong>Modelo:</strong><br>
                            <?php echo htmlspecialchars($maintenance['equipment_model']); ?>
                        </div>
                        <div class="col-md-4">
                            <strong>Número de Série:</strong><br>
                            <?php echo htmlspecialchars($maintenance['serial_number']); ?>
                        </div>
                    </div>

                    <!-- Informações da Manutenção -->
                    <h5 class="card-title">Detalhes da Manutenção</h5>
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <strong>Data Agendada:</strong><br>
                            <?php echo date('d/m/Y H:i', strtotime($maintenance['scheduled_date'])); ?>
                        </div>
                        <div class="col-md-6">
                            <strong>Tipo:</strong><br>
                            <span class="badge bg-<?php echo $maintenance['maintenance_type'] === 'preventive' ? 'primary' : 'warning'; ?>">
                                <?php echo $maintenance['maintenance_type'] === 'preventive' ? 'Preventiva' : 'Corretiva'; ?>
                            </span>
                        </div>
                    </div>

                    <!-- Equipe Responsável -->
                    <h5 class="card-title">Equipe Responsável</h5>
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <strong>Equipe:</strong><br>
                            <?php echo htmlspecialchars($maintenance['team_name']); ?>
                        </div>
                        <div class="col-md-6">
                            <strong>Supervisor:</strong><br>
                            <?php echo htmlspecialchars($maintenance['supervisor_name']); ?>
                        </div>
                    </div>

                    <!-- Descrição -->
                    <h5 class="card-title">Descrição</h5>
                    <div class="mb-4">
                        <?php if (!empty($maintenance['description'])): ?>
                            <p><?php echo nl2br(htmlspecialchars($maintenance['description'])); ?></p>
                        <?php else: ?>
                            <p class="text-muted">Nenhuma descrição fornecida.</p>
                        <?php endif; ?>
                    </div>

                    <!-- Datas do Sistema -->
                    <div class="row text-muted small">
                        <div class="col-md-6">
                            <strong>Criado em:</strong>
                            <?php echo date('d/m/Y H:i', strtotime($maintenance['created_at'])); ?>
                        </div>
                        <?php if ($maintenance['updated_at']): ?>
                            <div class="col-md-6">
                                <strong>Última atualização:</strong>
                                <?php echo date('d/m/Y H:i', strtotime($maintenance['updated_at'])); ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Botões de Ação -->
                    <?php if ($maintenance['status'] === 'scheduled'): ?>
                        <div class="mt-4 d-flex gap-2">
                            <a href="edit_maintenance.php?id=<?php echo $maintenance['id']; ?>" 
                               class="btn btn-primary">
                                <i class="bi bi-pencil"></i> Editar
                            </a>
                            <button type="button" class="btn btn-danger"
                                    onclick="cancelMaintenance(<?php echo $maintenance['id']; ?>)">
                                <i class="bi bi-x-circle"></i> Cancelar
                            </button>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
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
