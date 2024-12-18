<?php
require_once 'includes/auth.php';
require_once 'config/database.php';

// Verificar se o usuário é admin
if (!checkPermission('admin')) {
    $_SESSION['error_msg'] = "Acesso negado. Você precisa ser administrador.";
    header("Location: dashboard.php");
    exit;
}

$page_title = "Configurações do Sistema";

// Processar formulário
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Iniciar transação
        $conn->beginTransaction();

        // Atualizar cada configuração
        foreach ($_POST as $key => $value) {
            if (strpos($key, 'setting_') === 0) {
                $setting_key = substr($key, 8); // Remover 'setting_' do início
                
                $stmt = $conn->prepare("
                    UPDATE system_settings 
                    SET setting_value = ? 
                    WHERE setting_key = ?
                ");
                $stmt->execute([$value, $setting_key]);
            }
        }

        // Commit da transação
        $conn->commit();
        $_SESSION['success_msg'] = "Configurações atualizadas com sucesso!";
        
    } catch (Exception $e) {
        // Rollback em caso de erro
        $conn->rollBack();
        $_SESSION['error_msg'] = "Erro ao atualizar configurações: " . $e->getMessage();
    }
}

// Buscar configurações atuais
$settings = $conn->query("SELECT * FROM system_settings ORDER BY setting_key")->fetchAll(PDO::FETCH_ASSOC);

// Agrupar configurações por categoria
$grouped_settings = [
    'company' => [],
    'maintenance' => [],
    'notifications' => [],
    'schedule' => []
];

foreach ($settings as $setting) {
    switch ($setting['setting_key']) {
        case 'company_name':
        case 'company_email':
            $grouped_settings['company'][] = $setting;
            break;
        case 'maintenance_notification_days':
        case 'default_maintenance_duration':
            $grouped_settings['maintenance'][] = $setting;
            break;
        case 'enable_email_notifications':
        case 'low_stock_threshold':
            $grouped_settings['notifications'][] = $setting;
            break;
        case 'working_hours_start':
        case 'working_hours_end':
        case 'weekend_maintenance':
            $grouped_settings['schedule'][] = $setting;
            break;
    }
}

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

    <?php if (isset($_SESSION['error_msg'])): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <?php 
            echo $_SESSION['error_msg'];
            unset($_SESSION['error_msg']);
            ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="card">
        <div class="card-header">
            <h4 class="mb-0">
                <i class="bi bi-gear-fill"></i> Configurações do Sistema
            </h4>
        </div>
        <div class="card-body">
            <form method="POST" class="needs-validation" novalidate>
                <!-- Abas para categorias -->
                <ul class="nav nav-tabs mb-3" id="settingsTabs" role="tablist">
                    <li class="nav-item">
                        <a class="nav-link active" id="company-tab" data-bs-toggle="tab" href="#company" role="tab">
                            <i class="bi bi-building"></i> Empresa
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="maintenance-tab" data-bs-toggle="tab" href="#maintenance" role="tab">
                            <i class="bi bi-tools"></i> Manutenção
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="notifications-tab" data-bs-toggle="tab" href="#notifications" role="tab">
                            <i class="bi bi-bell"></i> Notificações
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="schedule-tab" data-bs-toggle="tab" href="#schedule" role="tab">
                            <i class="bi bi-calendar"></i> Horários
                        </a>
                    </li>
                </ul>

                <!-- Conteúdo das abas -->
                <div class="tab-content" id="settingsTabContent">
                    <!-- Empresa -->
                    <div class="tab-pane fade show active" id="company" role="tabpanel">
                        <h5 class="mb-3">Informações da Empresa</h5>
                        <?php foreach ($grouped_settings['company'] as $setting): ?>
                            <div class="mb-3">
                                <label for="setting_<?php echo $setting['setting_key']; ?>" class="form-label">
                                    <?php echo htmlspecialchars($setting['setting_description']); ?>
                                </label>
                                <input type="text" 
                                       class="form-control" 
                                       id="setting_<?php echo $setting['setting_key']; ?>"
                                       name="setting_<?php echo $setting['setting_key']; ?>"
                                       value="<?php echo htmlspecialchars($setting['setting_value']); ?>"
                                       required>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Manutenção -->
                    <div class="tab-pane fade" id="maintenance" role="tabpanel">
                        <h5 class="mb-3">Configurações de Manutenção</h5>
                        <?php foreach ($grouped_settings['maintenance'] as $setting): ?>
                            <div class="mb-3">
                                <label for="setting_<?php echo $setting['setting_key']; ?>" class="form-label">
                                    <?php echo htmlspecialchars($setting['setting_description']); ?>
                                </label>
                                <input type="number" 
                                       class="form-control" 
                                       id="setting_<?php echo $setting['setting_key']; ?>"
                                       name="setting_<?php echo $setting['setting_key']; ?>"
                                       value="<?php echo htmlspecialchars($setting['setting_value']); ?>"
                                       min="1"
                                       required>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Notificações -->
                    <div class="tab-pane fade" id="notifications" role="tabpanel">
                        <h5 class="mb-3">Configurações de Notificações</h5>
                        <?php foreach ($grouped_settings['notifications'] as $setting): ?>
                            <?php if ($setting['setting_key'] === 'enable_email_notifications'): ?>
                                <div class="mb-3 form-check form-switch">
                                    <input type="checkbox" 
                                           class="form-check-input" 
                                           id="setting_<?php echo $setting['setting_key']; ?>"
                                           name="setting_<?php echo $setting['setting_key']; ?>"
                                           value="true"
                                           <?php echo $setting['setting_value'] === 'true' ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="setting_<?php echo $setting['setting_key']; ?>">
                                        <?php echo htmlspecialchars($setting['setting_description']); ?>
                                    </label>
                                </div>
                            <?php else: ?>
                                <div class="mb-3">
                                    <label for="setting_<?php echo $setting['setting_key']; ?>" class="form-label">
                                        <?php echo htmlspecialchars($setting['setting_description']); ?>
                                    </label>
                                    <input type="number" 
                                           class="form-control" 
                                           id="setting_<?php echo $setting['setting_key']; ?>"
                                           name="setting_<?php echo $setting['setting_key']; ?>"
                                           value="<?php echo htmlspecialchars($setting['setting_value']); ?>"
                                           min="0"
                                           required>
                                </div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>

                    <!-- Horários -->
                    <div class="tab-pane fade" id="schedule" role="tabpanel">
                        <h5 class="mb-3">Configurações de Horários</h5>
                        <?php foreach ($grouped_settings['schedule'] as $setting): ?>
                            <?php if ($setting['setting_key'] === 'weekend_maintenance'): ?>
                                <div class="mb-3 form-check form-switch">
                                    <input type="checkbox" 
                                           class="form-check-input" 
                                           id="setting_<?php echo $setting['setting_key']; ?>"
                                           name="setting_<?php echo $setting['setting_key']; ?>"
                                           value="true"
                                           <?php echo $setting['setting_value'] === 'true' ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="setting_<?php echo $setting['setting_key']; ?>">
                                        <?php echo htmlspecialchars($setting['setting_description']); ?>
                                    </label>
                                </div>
                            <?php else: ?>
                                <div class="mb-3">
                                    <label for="setting_<?php echo $setting['setting_key']; ?>" class="form-label">
                                        <?php echo htmlspecialchars($setting['setting_description']); ?>
                                    </label>
                                    <input type="time" 
                                           class="form-control" 
                                           id="setting_<?php echo $setting['setting_key']; ?>"
                                           name="setting_<?php echo $setting['setting_key']; ?>"
                                           value="<?php echo htmlspecialchars($setting['setting_value']); ?>"
                                           required>
                                </div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="d-grid gap-2 d-md-flex justify-content-md-end mt-4">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-save"></i> Salvar Configurações
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Ativar validação do Bootstrap
(function () {
    'use strict'
    var forms = document.querySelectorAll('.needs-validation')
    Array.prototype.slice.call(forms)
        .forEach(function (form) {
            form.addEventListener('submit', function (event) {
                if (!form.checkValidity()) {
                    event.preventDefault()
                    event.stopPropagation()
                }
                form.classList.add('was-validated')
            }, false)
        })
})()

// Manter aba ativa após submit
document.addEventListener('DOMContentLoaded', function() {
    // Verificar se há uma aba salva
    var activeTab = sessionStorage.getItem('activeSettingsTab');
    if (activeTab) {
        // Ativar a aba salva
        var tab = new bootstrap.Tab(document.querySelector(activeTab));
        tab.show();
    }

    // Salvar aba ativa quando mudar
    var tabs = document.querySelectorAll('a[data-bs-toggle="tab"]');
    tabs.forEach(tab => {
        tab.addEventListener('shown.bs.tab', function (e) {
            sessionStorage.setItem('activeSettingsTab', '#' + e.target.id);
        });
    });
});
</script>

<?php include 'includes/footer.php'; ?>
