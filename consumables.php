<?php
require_once 'includes/auth.php';
require_once 'config/database.php';

// Adicionar novo consumível
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_consumable'])) {
    $name = $_POST['name'];
    $description = $_POST['description'];
    $quantity = $_POST['quantity'];
    $minimum_quantity = $_POST['minimum_quantity'];
    $unit = $_POST['unit'];
    
    $stmt = $conn->prepare("INSERT INTO consumables (name, description, quantity, minimum_quantity, unit, last_restocked) 
                           VALUES (?, ?, ?, ?, ?, CURRENT_DATE)");
    $stmt->execute([$name, $description, $quantity, $minimum_quantity, $unit]);
    
    // Verifica se precisa criar alerta de estoque baixo
    if ($quantity <= $minimum_quantity) {
        $stmt = $conn->prepare("
            INSERT INTO email_alerts (type, message, status)
            SELECT 'consumable_low', 
                   CONCAT('Estoque baixo do item: ', ?, ' - Quantidade atual: ', ?, ' - Mínimo: ', ?),
                   'pending'
        ");
        $stmt->execute([$name, $quantity, $minimum_quantity]);
    }
    
    header('Location: consumables.php');
    exit;
}

// Atualizar estoque
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_stock'])) {
    $id = $_POST['consumable_id'];
    $quantity = $_POST['new_quantity'];
    
    $stmt = $conn->prepare("UPDATE consumables SET quantity = ?, last_restocked = CURRENT_DATE WHERE id = ?");
    $stmt->execute([$quantity, $id]);
    
    header('Location: consumables.php');
    exit;
}

// Buscar todos os consumíveis
$stmt = $conn->query("SELECT * FROM consumables ORDER BY name");
$consumables = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestão de Consumíveis - Sistema de Manutenção</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body>
    <?php include 'includes/navbar.php'; ?>

    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Gestão de Consumíveis</h2>
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addConsumableModal">
                Novo Consumível
            </button>
        </div>

        <!-- Lista de Consumíveis -->
        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead>
                    <tr>
                        <th>Nome</th>
                        <th>Descrição</th>
                        <th>Quantidade</th>
                        <th>Mínimo</th>
                        <th>Unidade</th>
                        <th>Última Reposição</th>
                        <th>Status</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($consumables as $item): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($item['name']); ?></td>
                            <td><?php echo htmlspecialchars($item['description']); ?></td>
                            <td><?php echo $item['quantity']; ?></td>
                            <td><?php echo $item['minimum_quantity']; ?></td>
                            <td><?php echo htmlspecialchars($item['unit']); ?></td>
                            <td><?php echo $item['last_restocked']; ?></td>
                            <td>
                                <?php if ($item['quantity'] <= $item['minimum_quantity']): ?>
                                    <span class="badge bg-danger">Estoque Baixo</span>
                                <?php else: ?>
                                    <span class="badge bg-success">OK</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <button type="button" class="btn btn-sm btn-primary" 
                                        data-bs-toggle="modal" 
                                        data-bs-target="#updateStockModal" 
                                        data-id="<?php echo $item['id']; ?>"
                                        data-name="<?php echo htmlspecialchars($item['name']); ?>"
                                        data-quantity="<?php echo $item['quantity']; ?>">
                                    <i class="bi bi-plus-circle"></i> Atualizar Estoque
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal Adicionar Consumível -->
    <div class="modal fade" id="addConsumableModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Novo Consumível</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form method="POST">
                        <div class="mb-3">
                            <label for="name" class="form-label">Nome</label>
                            <input type="text" class="form-control" id="name" name="name" required>
                        </div>
                        <div class="mb-3">
                            <label for="description" class="form-label">Descrição</label>
                            <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                        </div>
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="quantity" class="form-label">Quantidade</label>
                                <input type="number" class="form-control" id="quantity" name="quantity" required min="0">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="minimum_quantity" class="form-label">Quantidade Mínima</label>
                                <input type="number" class="form-control" id="minimum_quantity" name="minimum_quantity" required min="0">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="unit" class="form-label">Unidade</label>
                                <input type="text" class="form-control" id="unit" name="unit" required>
                            </div>
                        </div>
                        <button type="submit" name="add_consumable" class="btn btn-primary">Adicionar</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Atualizar Estoque -->
    <div class="modal fade" id="updateStockModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Atualizar Estoque</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form method="POST">
                        <input type="hidden" name="consumable_id" id="update_consumable_id">
                        <p>Atualizando estoque de: <strong><span id="update_consumable_name"></span></strong></p>
                        <p>Quantidade atual: <span id="current_quantity"></span></p>
                        <div class="mb-3">
                            <label for="new_quantity" class="form-label">Nova Quantidade</label>
                            <input type="number" class="form-control" id="new_quantity" name="new_quantity" required min="0">
                        </div>
                        <button type="submit" name="update_stock" class="btn btn-primary">Atualizar</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Preenche o modal de atualização de estoque
        document.getElementById('updateStockModal').addEventListener('show.bs.modal', function (event) {
            var button = event.relatedTarget;
            var id = button.getAttribute('data-id');
            var name = button.getAttribute('data-name');
            var quantity = button.getAttribute('data-quantity');
            
            document.getElementById('update_consumable_id').value = id;
            document.getElementById('update_consumable_name').textContent = name;
            document.getElementById('current_quantity').textContent = quantity;
            document.getElementById('new_quantity').value = quantity;
        });
    </script>
</body>
</html>
