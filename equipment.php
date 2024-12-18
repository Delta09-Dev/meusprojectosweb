<?php
require_once 'includes/auth.php';
require_once 'config/database.php';

$page_title = "Equipamentos - Sistema de Manutenção";

// Add new equipment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_equipment'])) {
    $name = $_POST['name'];
    $model = $_POST['model'];
    $serial = $_POST['serial_number'];
    $location = $_POST['location'];
    $purchase_date = $_POST['purchase_date'];
    $warranty_expiry = $_POST['warranty_expiry'];

    $stmt = $conn->prepare("INSERT INTO equipment (name, model, serial_number, location, purchase_date, warranty_expiry) 
                           VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$name, $model, $serial, $location, $purchase_date, $warranty_expiry]);
}

// Fetch all equipment
$stmt = $conn->query("SELECT * FROM equipment ORDER BY name");
$equipment = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<?php include 'includes/header.php'; ?>

<div class="container mt-4">
    <h2>Gestão de Equipamentos</h2>
        
    <!-- Add Equipment Form -->
    <div class="card mb-4">
        <div class="card-header">
            Adicionar Novo Equipamento
        </div>
        <div class="card-body">
            <form method="POST">
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label for="name" class="form-label">Nome</label>
                        <input type="text" class="form-control" id="name" name="name" required>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="model" class="form-label">Modelo</label>
                        <input type="text" class="form-control" id="model" name="model">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="serial_number" class="form-label">Número de Série</label>
                        <input type="text" class="form-control" id="serial_number" name="serial_number">
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label for="location" class="form-label">Localização</label>
                        <input type="text" class="form-control" id="location" name="location">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="purchase_date" class="form-label">Data de Compra</label>
                        <input type="date" class="form-control" id="purchase_date" name="purchase_date">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="warranty_expiry" class="form-label">Data Fim Garantia</label>
                        <input type="date" class="form-control" id="warranty_expiry" name="warranty_expiry">
                    </div>
                </div>
                <button type="submit" name="add_equipment" class="btn btn-primary">Adicionar Equipamento</button>
            </form>
        </div>
    </div>

    <!-- Equipment List -->
    <div class="card">
        <div class="card-header">
            Lista de Equipamentos
        </div>
        <div class="card-body">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Nome</th>
                        <th>Modelo</th>
                        <th>Número Série</th>
                        <th>Localização</th>
                        <th>Estado</th>
                        <th>Próxima Manutenção</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($equipment as $item): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($item['name']); ?></td>
                        <td><?php echo htmlspecialchars($item['model']); ?></td>
                        <td><?php echo htmlspecialchars($item['serial_number']); ?></td>
                        <td><?php echo htmlspecialchars($item['location']); ?></td>
                        <td><?php echo htmlspecialchars($item['status']); ?></td>
                        <td><?php echo $item['next_maintenance']; ?></td>
                        <td>
                            <a href="edit_equipment.php?id=<?php echo $item['id']; ?>" class="btn btn-sm btn-primary">Editar</a>
                            <a href="view_maintenance.php?equipment_id=<?php echo $item['id']; ?>" class="btn btn-sm btn-info">Ver Manutenções</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
