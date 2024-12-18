<?php
require_once 'includes/auth.php';
require_once 'config/database.php';

// Verifica se tem permissão de admin
if (!checkPermission('admin')) {
    header('Location: index.php');
    exit;
}

// Processar exclusão de usuário
if (isset($_POST['delete_user'])) {
    $user_id = $_POST['user_id'];
    
    // Não permite excluir o próprio usuário
    if ($user_id != $_SESSION['user_id']) {
        $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        
        if ($stmt->rowCount() > 0) {
            $success = "Usuário excluído com sucesso!";
        }
    }
}

// Processar adição/edição de usuário
if (isset($_POST['save_user'])) {
    $user_id = $_POST['user_id'] ?? null;
    $username = $_POST['username'];
    $email = $_POST['email'];
    $role = $_POST['role'];
    $password = $_POST['password'];
    
    try {
        if ($user_id) { // Edição
            if ($password) {
                $stmt = $conn->prepare("
                    UPDATE users 
                    SET username = ?, email = ?, role = ?, password = ? 
                    WHERE id = ?
                ");
                $stmt->execute([
                    $username,
                    $email,
                    $role,
                    password_hash($password, PASSWORD_DEFAULT),
                    $user_id
                ]);
            } else {
                $stmt = $conn->prepare("
                    UPDATE users 
                    SET username = ?, email = ?, role = ?
                    WHERE id = ?
                ");
                $stmt->execute([$username, $email, $role, $user_id]);
            }
            $success = "Usuário atualizado com sucesso!";
        } else { // Novo usuário
            $stmt = $conn->prepare("
                INSERT INTO users (username, email, password, role) 
                VALUES (?, ?, ?, ?)
            ");
            $stmt->execute([
                $username,
                $email,
                password_hash($password, PASSWORD_DEFAULT),
                $role
            ]);
            $success = "Usuário criado com sucesso!";
        }
    } catch (PDOException $e) {
        if ($e->getCode() == 23000) { // Erro de duplicação
            $error = "Este nome de usuário ou email já está em uso.";
        } else {
            $error = "Erro ao salvar usuário: " . $e->getMessage();
        }
    }
}

// Buscar todos os usuários
$users = $conn->query("
    SELECT u.*, 
           COUNT(DISTINCT tm.team_id) as team_count,
           GROUP_CONCAT(DISTINCT t.name) as team_names
    FROM users u
    LEFT JOIN team_members tm ON u.id = tm.user_id
    LEFT JOIN teams t ON tm.team_id = t.id
    GROUP BY u.id
    ORDER BY u.username
")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestão de Usuários - Sistema de Manutenção</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body>
    <?php include 'includes/navbar.php'; ?>

    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Gestão de Usuários</h2>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#userModal">
                <i class="bi bi-person-plus"></i> Novo Usuário
            </button>
        </div>

        <?php if (isset($success)): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <?php echo $success; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if (isset($error)): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <?php echo $error; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Usuário</th>
                                <th>Email</th>
                                <th>Função</th>
                                <th>Equipes</th>
                                <th>Status</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $user): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($user['username']); ?></td>
                                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                                    <td>
                                        <span class="badge bg-<?php 
                                            echo $user['role'] === 'admin' ? 'danger' : 
                                                ($user['role'] === 'supervisor' ? 'warning' : 'info');
                                        ?>">
                                            <?php echo ucfirst($user['role']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($user['team_names']): ?>
                                            <?php echo htmlspecialchars($user['team_names']); ?>
                                        <?php else: ?>
                                            <span class="text-muted">Nenhuma equipe</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?php echo $user['active'] ? 'success' : 'secondary'; ?>">
                                            <?php echo $user['active'] ? 'Ativo' : 'Inativo'; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                            <button class="btn btn-sm btn-primary" 
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#userModal"
                                                    data-user='<?php echo json_encode($user); ?>'>
                                                <i class="bi bi-pencil"></i>
                                            </button>
                                            <button class="btn btn-sm btn-danger" 
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#deleteModal"
                                                    data-user-id="<?php echo $user['id']; ?>"
                                                    data-username="<?php echo htmlspecialchars($user['username']); ?>">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de Usuário -->
    <div class="modal fade" id="userModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Usuário</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="user_id" id="userId">
                        
                        <div class="mb-3">
                            <label for="username" class="form-label">Nome de Usuário</label>
                            <input type="text" class="form-control" id="username" name="username" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="role" class="form-label">Função</label>
                            <select class="form-select" id="role" name="role" required>
                                <option value="admin">Administrador</option>
                                <option value="supervisor">Supervisor</option>
                                <option value="technician">Técnico</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="password" class="form-label">Senha</label>
                            <input type="password" class="form-control" id="password" name="password">
                            <div class="form-text" id="passwordHelp"></div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" name="save_user" class="btn btn-primary">Salvar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal de Exclusão -->
    <div class="modal fade" id="deleteModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Confirmar Exclusão</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Tem certeza que deseja excluir o usuário <strong id="deleteUsername"></strong>?</p>
                    <p class="text-danger">Esta ação não pode ser desfeita!</p>
                </div>
                <div class="modal-footer">
                    <form method="POST">
                        <input type="hidden" name="user_id" id="deleteUserId">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" name="delete_user" class="btn btn-danger">Excluir</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Preenche o modal de edição
        document.getElementById('userModal').addEventListener('show.bs.modal', function (event) {
            var button = event.relatedTarget;
            var user = button.getAttribute('data-user');
            var title = document.querySelector('#userModal .modal-title');
            var passwordHelp = document.getElementById('passwordHelp');
            
            if (user) {
                user = JSON.parse(user);
                title.textContent = 'Editar Usuário';
                document.getElementById('userId').value = user.id;
                document.getElementById('username').value = user.username;
                document.getElementById('email').value = user.email;
                document.getElementById('role').value = user.role;
                passwordHelp.textContent = 'Deixe em branco para manter a senha atual';
            } else {
                title.textContent = 'Novo Usuário';
                document.getElementById('userId').value = '';
                document.getElementById('username').value = '';
                document.getElementById('email').value = '';
                document.getElementById('role').value = 'technician';
                document.getElementById('password').required = true;
                passwordHelp.textContent = '';
            }
        });

        // Preenche o modal de exclusão
        document.getElementById('deleteModal').addEventListener('show.bs.modal', function (event) {
            var button = event.relatedTarget;
            var userId = button.getAttribute('data-user-id');
            var username = button.getAttribute('data-username');
            
            document.getElementById('deleteUserId').value = userId;
            document.getElementById('deleteUsername').textContent = username;
        });
    </script>
</body>
</html>
