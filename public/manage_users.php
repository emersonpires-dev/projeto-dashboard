<?php
// public/manage_users.php

session_start(); // Inicia a sessão

// Se o usuário não estiver logado, redireciona para a página de login
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Lógica para buscar e processar ações de usuário
require_once '../src/Models/User.php'; // Inclua a classe User aqui

$modalResponseMessage = ''; // Mensagem para o modal de adicionar usuário
$isModalSuccess = false;
$modalEditResponseMessage = ''; // Mensagem para o modal de editar usuário
$isEditSuccess = false;
$modalDeleteResponseMessage = ''; // Mensagem para a exclusão
$isDeleteSuccess = false;
$editUserData = null; // Para armazenar dados do usuário a ser editado (se houver)

// --- Processamento de Formulários POST ---

// 1. Processamento do formulário de Adicionar Usuário (modal)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['addUserFormSubmit'])) {
    $user = new User();
    $user->name = $_POST['name'] ?? '';
    $user->email = $_POST['email'] ?? '';
    $user->password_input = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $user->user_type = $_POST['user_type'] ?? 'viewer';

    // Para passar a mensagem via sessão para o redirecionamento PRG
    $_SESSION['modal_message_type'] = 'add'; // Identifica qual modal gerou a mensagem

    if (empty($user->name) || empty($user->email) || empty($user->password_input) || empty($confirm_password) || empty($user->user_type)) {
        $_SESSION['modal_message'] = 'Todos os campos são obrigatórios!';
        $_SESSION['is_modal_success'] = false;
    } elseif (!filter_var($user->email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['modal_message'] = 'Formato de e-mail inválido.';
        $_SESSION['is_modal_success'] = false;
    } elseif ($user->password_input !== $confirm_password) {
        $_SESSION['modal_message'] = 'As senhas não coincidem.';
        $_SESSION['is_modal_success'] = false;
    } else {
        if ($user->register()) {
            $_SESSION['modal_message'] = 'Usuário registrado com sucesso!';
            $_SESSION['is_modal_success'] = true;
        } else {
            $_SESSION['modal_message'] = 'Erro ao registrar usuário. O e-mail pode já estar em uso.';
            $_SESSION['is_modal_success'] = false;
        }
    }
    // Redirecionamento PRG após o POST para evitar reenvio do formulário
    header('Location: manage_users.php');
    exit;
}

// 2. Processamento do formulário de Edição de Usuário (modal)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['editUserFormSubmit'])) {
    $user = new User();
    $user->id = $_POST['user_id'] ?? null; // ID do usuário a ser editado
    $user->name = $_POST['edit_name'] ?? '';
    $user->email = $_POST['edit_email'] ?? '';
    $user->user_type = $_POST['edit_user_type'] ?? 'viewer';
    $user->password_input = $_POST['edit_password'] ?? ''; // Nova senha (pode ser vazia)

    // Para passar a mensagem via sessão para o redirecionamento PRG
    $_SESSION['modal_message_type'] = 'edit'; // Identifica qual modal gerou a mensagem
    $_SESSION['edited_user_id'] = $user->id; // Para pré-popular o modal em caso de erro

    if (empty($user->id) || empty($user->name) || empty($user->email) || empty($user->user_type)) {
        $_SESSION['modal_message'] = 'Todos os campos são obrigatórios para a edição!';
        $_SESSION['is_modal_success'] = false;
    } elseif (!filter_var($user->email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['modal_message'] = 'Formato de e-mail inválido para edição.';
        $_SESSION['is_modal_success'] = false;
    } else {
        // Tenta atualizar o usuário
        if ($user->update()) {
            $_SESSION['modal_message'] = 'Usuário atualizado com sucesso!';
            $_SESSION['is_modal_success'] = true;
        } else {
            $_SESSION['modal_message'] = 'Erro ao atualizar usuário. O e-mail pode já estar em uso por outro usuário ou ID inválido.';
            $_SESSION['is_modal_success'] = false;
        }
    }
    // Redirecionamento PRG após o POST para evitar reenvio do formulário
    header('Location: manage_users.php');
    exit;
}

// 3. Processamento da Requisição de Exclusão de Usuário
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['deleteUserSubmit'])) {
    $user = new User();
    $user->id = $_POST['user_id'] ?? null;

    $_SESSION['modal_message_type'] = 'delete'; // Identifica que a mensagem é da exclusão

    if (empty($user->id)) {
        $_SESSION['modal_message'] = 'ID do usuário para exclusão não fornecido.';
        $_SESSION['is_modal_success'] = false;
    } else {
        // Prevenção: Não permitir que um administrador exclua a si mesmo
        if ($user->id == $_SESSION['user_id']) {
            $_SESSION['modal_message'] = 'Você não pode excluir sua própria conta de administrador.';
            $_SESSION['is_modal_success'] = false;
        } else {
            if ($user->delete()) {
                $_SESSION['modal_message'] = 'Usuário excluído com sucesso!';
                $_SESSION['is_modal_success'] = true;
            } else {
                $_SESSION['modal_message'] = 'Erro ao excluir usuário.';
                $_SESSION['is_modal_success'] = false;
            }
        }
    }
    // Redirecionamento PRG após o POST para evitar reenvio do formulário
    header('Location: manage_users.php');
    exit;
}


// --- Lógica para buscar mensagens de sessão após redirecionamento (GET) ---
if (isset($_SESSION['modal_message'])) {
    if ($_SESSION['modal_message_type'] === 'add') {
        $modalResponseMessage = $_SESSION['modal_message'];
        $isModalSuccess = $_SESSION['is_modal_success'];
    } elseif ($_SESSION['modal_message_type'] === 'edit') {
        $modalEditResponseMessage = $_SESSION['modal_message'];
        $isEditSuccess = $_SESSION['is_modal_success'];
        // Se a edição falhou, busce os dados do usuário para pré-popular o modal
        if (!$isEditSuccess && isset($_SESSION['edited_user_id'])) {
            $user_temp = new User();
            $user_temp->id = $_SESSION['edited_user_id'];
            if ($user_temp->getOneUser()) {
                $editUserData = [
                    'id' => $user_temp->id,
                    'name' => $user_temp->name,
                    'email' => $user_temp->email,
                    'user_type' => $user_temp->user_type
                ];
            }
        }
    } elseif ($_SESSION['modal_message_type'] === 'delete') {
        $modalDeleteResponseMessage = $_SESSION['modal_message'];
        $isDeleteSuccess = $_SESSION['is_modal_success'];
    }

    // Limpa as variáveis de sessão para que as mensagens não apareçam novamente após um novo refresh
    unset($_SESSION['modal_message']);
    unset($_SESSION['is_modal_success']);
    unset($_SESSION['modal_message_type']);
    unset($_SESSION['edited_user_id']); // Limpa o ID do usuário editado também
}


// Verifica se o usuário tem permissão de administrador para adicionar/gerenciar
$canManageUsers = (isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'admin');

// Lógica para buscar e exibir usuários na tabela
$user = new User();
$stmt = $user->getAllUsers();
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Inclui o cabeçalho
$pageTitle = 'Gerenciamento de Usuários';
$currentPage = 'manage_users';
include '../src/Views/partials/header.php';
?>
<?php include '../src/Views/partials/sidebar.php'; ?>

<main class="flex-1 overflow-y-auto p-6">
    <header class="bg-white shadow-md rounded-lg p-4 mb-6 flex justify-between items-center">
        <h1 class="text-3xl font-bold text-gray-800">Gerenciamento de Usuários</h1>
        <div class="text-gray-600">Bem-vindo(a), <?php echo htmlspecialchars($_SESSION['user_name'] ?? 'Usuário'); ?>!</div>
    </header>

    <section class="bg-white p-6 rounded-lg shadow-md">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-xl font-semibold text-gray-800">Lista de Usuários</h3>
            <?php if ($canManageUsers): // Mostra o botão apenas para administradores ?>
                <button id="openAddUserModal" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                    Adicionar Novo Usuário
                </button>
            <?php endif; ?>
        </div>
        
        <div id="deleteUserMessage" class="mb-4 hidden"></div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            ID
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Nome
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Email
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Tipo
                        </th>
                        <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Ações
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php if (!empty($users)): ?>
                        <?php foreach ($users as $user_data): ?>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap"><?php echo htmlspecialchars($user_data['id']); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap"><?php echo htmlspecialchars($user_data['name']); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap"><?php echo htmlspecialchars($user_data['email']); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <?php
                                    $userTypeClass = '';
                                    $userTypeText = '';
                                    switch ($user_data['user_type']) {
                                        case 'admin':
                                            $userTypeClass = 'bg-green-100 text-green-800';
                                            $userTypeText = 'Administrador';
                                            break;
                                        case 'manager':
                                            $userTypeClass = 'bg-blue-100 text-blue-800';
                                            $userTypeText = 'Gerente';
                                            break;
                                        case 'viewer':
                                            $userTypeClass = 'bg-yellow-100 text-yellow-800';
                                            $userTypeText = 'Visualizador';
                                            break;
                                        default:
                                            $userTypeClass = 'bg-gray-100 text-gray-800';
                                            $userTypeText = ucfirst($user_data['user_type']);
                                            break;
                                    }
                                    ?>
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $userTypeClass; ?>">
                                        <?php echo $userTypeText; ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <?php if ($canManageUsers): ?>
                                        <button class="text-indigo-600 hover:text-indigo-900 mr-4 edit-user-btn"
                                                data-user-id="<?php echo $user_data['id']; ?>"
                                                data-user-name="<?php echo htmlspecialchars($user_data['name']); ?>"
                                                data-user-email="<?php echo htmlspecialchars($user_data['email']); ?>"
                                                data-user-type="<?php echo htmlspecialchars($user_data['user_type']); ?>">Editar</button>
                                        <button class="text-red-600 hover:text-red-900 delete-user-btn"
                                                data-user-id="<?php echo $user_data['id']; ?>"
                                                data-user-name="<?php echo htmlspecialchars($user_data['name']); ?>">Excluir</button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="px-6 py-4 whitespace-nowrap text-center text-gray-500">Nenhum usuário encontrado.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>

    <div id="addUserModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden flex items-center justify-center z-50">
        <div class="relative p-8 bg-white w-full max-w-md mx-auto rounded-lg shadow-xl">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-2xl font-bold text-gray-800">Adicionar Novo Usuário</h3>
                <button id="closeAddUserModal" class="text-gray-500 hover:text-gray-700">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>

            <form id="addUserForm" action="manage_users.php" method="POST">
                <input type="hidden" name="addUserFormSubmit" value="1"> <div class="mb-4">
                    <label for="name" class="block text-gray-700 text-sm font-bold mb-2">Nome Completo:</label>
                    <input type="text" id="name" name="name" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" placeholder="Nome do Usuário" required>
                </div>
                <div class="mb-4">
                    <label for="email" class="block text-gray-700 text-sm font-bold mb-2">Email:</label>
                    <input type="email" id="email" name="email" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" placeholder="email@exemplo.com" required>
                </div>
                <div class="mb-4">
                    <label for="password" class="block text-gray-700 text-sm font-bold mb-2">Senha:</label>
                    <input type="password" id="password" name="password" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" placeholder="********" required>
                </div>
                <div class="mb-4">
                    <label for="confirm_password" class="block text-gray-700 text-sm font-bold mb-2">Confirmar Senha:</label>
                    <input type="password" id="confirm_password" name="confirm_password" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" placeholder="********" required>
                </div>
                <div class="mb-6">
                    <label for="user_type" class="block text-gray-700 text-sm font-bold mb-2">Tipo de Usuário:</label>
                    <select id="user_type" name="user_type" class="shadow border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" required>
                        <option value="">Selecione o tipo</option>
                        <option value="admin">Administrador</option>
                        <option value="manager">Gerente</option>
                        <option value="viewer">Visualizador</option>
                    </select>
                </div>
                <div class="flex items-center justify-between">
                    <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline w-full">
                        Adicionar Usuário
                    </button>
                </div>
            </form>
            <div id="addUserMessage" class="mt-4 hidden"></div>
        </div>
    </div>

    <div id="editUserModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden flex items-center justify-center z-50">
        <div class="relative p-8 bg-white w-full max-w-md mx-auto rounded-lg shadow-xl">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-2xl font-bold text-gray-800">Editar Usuário</h3>
                <button id="closeEditUserModal" class="text-gray-500 hover:text-gray-700">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>

            <form id="editUserForm" action="manage_users.php" method="POST">
                <input type="hidden" name="editUserFormSubmit" value="1">
                <input type="hidden" id="edit_user_id" name="user_id"> <div class="mb-4">
                    <label for="edit_name" class="block text-gray-700 text-sm font-bold mb-2">Nome Completo:</label>
                    <input type="text" id="edit_name" name="edit_name" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" required>
                </div>
                <div class="mb-4">
                    <label for="edit_email" class="block text-gray-700 text-sm font-bold mb-2">Email:</label>
                    <input type="email" id="edit_email" name="edit_email" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" required>
                </div>
                <div class="mb-6">
                    <label for="edit_user_type" class="block text-gray-700 text-sm font-bold mb-2">Tipo de Usuário:</label>
                    <select id="edit_user_type" name="edit_user_type" class="shadow border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" required>
                        <option value="admin">Administrador</option>
                        <option value="manager">Gerente</option>
                        <option value="viewer">Visualizador</option>
                    </select>
                </div>
                <div class="mb-6">
                    <label for="edit_password" class="block text-gray-700 text-sm font-bold mb-2">Nova Senha (Opcional):</label>
                    <input type="password" id="edit_password" name="edit_password" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" placeholder="Deixe em branco para não alterar">
                </div>
                <div class="flex items-center justify-between">
                    <button type="submit" class="bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline w-full">
                        Salvar Alterações
                    </button>
                </div>
            </form>
            <div id="editUserMessage" class="mt-4 hidden"></div>
        </div>
    </div>
</main>

<?php
// Gerar o script JavaScript para exibir a mensagem e controlar o modal (Adicionar Usuário)
if (isset($modalResponseMessage) && !empty($modalResponseMessage)) {
    $script = 'document.addEventListener("DOMContentLoaded", function() {';
    $script .= '  var addUserMessageDiv = document.getElementById("addUserMessage");';
    $script .= '  addUserMessageDiv.classList.remove("hidden");';

    $alertClass = $isModalSuccess ? 'bg-green-100 border-green-400 text-green-700' : 'bg-red-100 border-red-400 text-red-700';
    $escapedResponseMessage = addslashes($modalResponseMessage);
    $alertHtml = '<div class=\"' . $alertClass . ' px-4 py-3 rounded relative\" role=\"alert\">' . $escapedResponseMessage . '</div>';
    $script .= '  addUserMessageDiv.innerHTML = \'' . $alertHtml . '\';';

    if ($isModalSuccess) {
        $script .= '  document.getElementById("addUserForm").reset();';
        $script .= '  setTimeout(function() { document.getElementById("addUserModal").classList.add("hidden"); }, 3000);';
        // Recarrega a página para mostrar o novo usuário na lista, se for sucesso na adição
        $script .= '  location.reload();';
    } else {
        $script .= '  document.getElementById("addUserModal").classList.remove("hidden");';
        $script .= '  addUserMessageDiv.scrollIntoView({ behavior: "smooth", block: "center" });';
    }
    $script .= '});';
    echo '<script type="text/javascript">' . $script . '</script>';
}

// Gerar o script JavaScript para exibir a mensagem e controlar o modal (Editar Usuário)
if (isset($modalEditResponseMessage) && !empty($modalEditResponseMessage)) {
    $script = 'document.addEventListener("DOMContentLoaded", function() {';
    $script .= '  var editUserMessageDiv = document.getElementById("editUserMessage");';
    $script .= '  editUserMessageDiv.classList.remove("hidden");';

    $alertClass = $isEditSuccess ? 'bg-green-100 border-green-400 text-green-700' : 'bg-red-100 border-red-400 text-red-700';
    $escapedResponseMessage = addslashes($modalEditResponseMessage);
    $alertHtml = '<div class=\"' . $alertClass . ' px-4 py-3 rounded relative\" role=\"alert\">' . $escapedResponseMessage . '</div>';
    $script .= '  editUserMessageDiv.innerHTML = \'' . $alertHtml . '\';';

    // Se a edição falhou e queremos que o modal permaneça aberto com os dados
    if (!$isEditSuccess && isset($editUserData) && !empty($editUserData)) {
        $script .= '  window.openEditUserModal(' . json_encode($editUserData) . ');'; // Reabre o modal com os dados
    }

    if ($isEditSuccess) {
        // Redireciona para recarregar a lista (e fechar o modal)
        $script .= '  setTimeout(function() { location.reload(); }, 3000);';
    } else {
        // Já reabrimos o modal acima se falhou. Apenas garante que a mensagem está visível.
        $script .= '  editUserMessageDiv.scrollIntoView({ behavior: "smooth", block: "center" });';
    }
    $script .= '});';
    echo '<script type="text/javascript">' . $script . '</script>';
}

// Gerar o script JavaScript para exibir a mensagem de exclusão
if (isset($modalDeleteResponseMessage) && !empty($modalDeleteResponseMessage)) {
    $script = 'document.addEventListener("DOMContentLoaded", function() {';
    $script .= '  var deleteUserMessageDiv = document.getElementById("deleteUserMessage");';
    $script .= '  deleteUserMessageDiv.classList.remove("hidden");';

    $alertClass = $isDeleteSuccess ? 'bg-green-100 border-green-400 text-green-700' : 'bg-red-100 border-red-400 text-red-700';
    $escapedResponseMessage = addslashes($modalDeleteResponseMessage);
    $alertHtml = '<div class=\"' . $alertClass . ' px-4 py-3 rounded relative\" role=\"alert\">' . $escapedResponseMessage . '</div>';
    $script .= '  deleteUserMessageDiv.innerHTML = \'' . $alertHtml . '\';';

    if ($isDeleteSuccess) {
        $script .= '  setTimeout(function() { deleteUserMessageDiv.classList.add("hidden"); }, 3000);'; // Esconde a mensagem após 3 segundos
        $script .= '  location.reload();'; // Recarrega a página para atualizar a lista
    } else {
        $script .= '  deleteUserMessageDiv.scrollIntoView({ behavior: "smooth", block: "center" });';
    }
    $script .= '});';
    echo '<script type="text/javascript">' . $script . '</script>';
}
?>

<script>
    // JavaScript para o modal de Adicionar Usuário
    const addUserModal = document.getElementById('addUserModal');
    const openAddUserModalBtn = document.getElementById('openAddUserModal');
    const closeAddUserModalBtn = document.getElementById('closeAddUserModal');

    // Verifica se os elementos existem antes de adicionar event listeners
    if (openAddUserModalBtn && addUserModal && closeAddUserModalBtn) {
        openAddUserModalBtn.addEventListener('click', () => {
            addUserModal.classList.remove('hidden');
        });

        closeAddUserModalBtn.addEventListener('click', () => {
            addUserModal.classList.add('hidden');
            // Opcional: Limpar formulário e mensagem ao fechar manualmente
            document.getElementById('addUserForm').reset();
            document.getElementById('addUserMessage').classList.add('hidden');
            document.getElementById('addUserMessage').innerHTML = '';
        });

        // Fechar modal ao clicar fora (no overlay)
        addUserModal.addEventListener('click', (event) => {
            if (event.target === addUserModal) {
                addUserModal.classList.add('hidden');
                document.getElementById('addUserForm').reset();
                document.getElementById('addUserMessage').classList.add('hidden');
                document.getElementById('addUserMessage').innerHTML = '';
            }
        });
    }


    // JavaScript para o modal de Edição de Usuário
    const editUserModal = document.getElementById('editUserModal');
    const closeEditUserModalBtn = document.getElementById('closeEditUserModal');
    const editUserButtons = document.querySelectorAll('.edit-user-btn'); // Seleciona todos os botões de editar

    // Função global para abrir o modal de edição e preencher os dados
    window.openEditUserModal = function(userData) {
        document.getElementById('edit_user_id').value = userData.id;
        document.getElementById('edit_name').value = userData.name;
        document.getElementById('edit_email').value = userData.email;
        document.getElementById('edit_user_type').value = userData.user_type;
        document.getElementById('edit_password').value = ''; // Sempre limpa o campo de senha
        
        // Esconder mensagem anterior do modal de edição ao abrir
        document.getElementById("editUserMessage").classList.add("hidden"); 
        document.getElementById("editUserMessage").innerHTML = "";

        editUserModal.classList.remove('hidden');
    };

    if (editUserModal && closeEditUserModalBtn && editUserButtons.length > 0) { // Verifica se os elementos existem
        editUserButtons.forEach(button => {
            button.addEventListener('click', function() {
                const userId = this.dataset.userId;
                const userName = this.dataset.userName;
                const userEmail = this.dataset.userEmail;
                const userType = this.dataset.userType;

                const userData = {
                    id: userId,
                    name: userName,
                    email: userEmail,
                    user_type: userType
                };
                window.openEditUserModal(userData);
            });
        });

        closeEditUserModalBtn.addEventListener('click', () => {
            editUserModal.classList.add('hidden');
            document.getElementById("editUserMessage").classList.add("hidden"); // Esconde a mensagem ao fechar
            document.getElementById("editUserMessage").innerHTML = ""; // Limpa o conteúdo da mensagem
        });

        // Fechar modal ao clicar fora (no overlay)
        editUserModal.addEventListener('click', (event) => {
            if (event.target === editUserModal) {
                editUserModal.classList.add('hidden');
                document.getElementById("editUserMessage").classList.add("hidden");
                document.getElementById("editUserMessage").innerHTML = "";
            }
        });
    }

    // JavaScript para o botão de Excluir Usuário
    const deleteUserButtons = document.querySelectorAll('.delete-user-btn');

    if (deleteUserButtons.length > 0) { // Verifica se existem botões de exclusão
        deleteUserButtons.forEach(button => {
            button.addEventListener('click', function() {
                const userId = this.dataset.userId;
                const userName = this.dataset.userName;

                // Confirmação antes de excluir
                if (confirm(`Tem certeza que deseja excluir o usuário "${userName}" (ID: ${userId})? Esta ação é irreversível.`)) {
                    // Cria um formulário dinamicamente para enviar a requisição POST
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.action = 'manage_users.php'; // Envia para a mesma página

                    const userIdInput = document.createElement('input');
                    userIdInput.type = 'hidden';
                    userIdInput.name = 'user_id';
                    userIdInput.value = userId;
                    form.appendChild(userIdInput);

                    const deleteSubmitInput = document.createElement('input');
                    deleteSubmitInput.type = 'hidden';
                    deleteSubmitInput.name = 'deleteUserSubmit';
                    deleteSubmitInput.value = '1';
                    form.appendChild(deleteSubmitInput);

                    document.body.appendChild(form); // Adiciona o formulário ao corpo do documento
                    form.submit(); // Envia o formulário
                }
            });
        });
    }
</script>

<?php include '../src/Views/partials/footer.php'; ?>