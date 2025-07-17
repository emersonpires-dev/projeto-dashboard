<?php
// public/my_expenses.php

session_start(); // Inicia a sessão

// Se o usuário não estiver logado, redireciona para a página de login
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

require_once '../src/Models/Expense.php'; // Inclua a classe Expense

$expense = new Expense();
$expense->user_id = $_SESSION['user_id']; // Define o ID do usuário logado

$expenseMessage = ''; // Para mensagens de adição/edição/exclusão
$isExpenseSuccess = false;
$editExpenseData = null; // Para pré-popular o modal de edição

// --- Processamento de Formulários POST (Edição e Exclusão de Gastos) ---

// 1. Processamento do formulário de Edição de Gasto
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['editExpenseFormSubmit'])) {
    $expense->id = $_POST['expense_id'] ?? null;
    $expense->description = $_POST['edit_description'] ?? '';
    $expense->value = $_POST['edit_value'] ?? '';
    $expense->category_id = $_POST['edit_category'] ?? '';
    $expense->expense_date = $_POST['edit_date'] ?? '';

    // Passa o ID do usuário logado para o método updateExpense para verificação de propriedade
    $expense->user_id = $_SESSION['user_id'];

    $_SESSION['expense_action_message'] = ''; // Para mensagens via sessão (PRG)
    $_SESSION['is_expense_action_success'] = false;
    $_SESSION['expense_action_type'] = 'edit';

    if (empty($expense->id) || empty($expense->description) || empty($expense->value) || empty($expense->category_id) || empty($expense->expense_date)) {
        $_SESSION['expense_action_message'] = 'Todos os campos são obrigatórios para a edição!';
        $_SESSION['is_expense_action_success'] = false;
    } elseif (!is_numeric($expense->value) || $expense->value <= 0) {
        $_SESSION['expense_action_message'] = 'O valor deve ser um número positivo para edição.';
        $_SESSION['is_expense_action_success'] = false;
    } else {
        if ($expense->updateExpense()) {
            $_SESSION['expense_action_message'] = 'Gasto atualizado com sucesso!';
            $_SESSION['is_expense_action_success'] = true;
        } else {
            $_SESSION['expense_action_message'] = 'Erro ao atualizar gasto. Verifique os dados ou se você é o proprietário.';
            $_SESSION['is_expense_action_success'] = false;
        }
    }
    header('Location: my_expenses.php'); // Redirecionamento PRG
    exit;
}

// 2. Processamento da Requisição de Exclusão de Gasto
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['deleteExpenseSubmit'])) {
    $expense->id = $_POST['expense_id'] ?? null; // ID do gasto a ser excluído
    $expense->user_id = $_SESSION['user_id']; // ID do usuário logado para verificação de propriedade

    $_SESSION['expense_action_message'] = '';
    $_SESSION['is_expense_action_success'] = false;
    $_SESSION['expense_action_type'] = 'delete';

    if (empty($expense->id)) {
        $_SESSION['expense_action_message'] = 'ID do gasto para exclusão não fornecido.';
        $_SESSION['is_expense_action_success'] = false;
    } else {
        if ($expense->deleteExpense()) {
            $_SESSION['expense_action_message'] = 'Gasto excluído com sucesso!';
            $_SESSION['is_expense_action_success'] = true;
        } else {
            $_SESSION['expense_action_message'] = 'Erro ao excluir gasto. Verifique se você é o proprietário.';
            $_SESSION['is_expense_action_success'] = false;
        }
    }
    header('Location: my_expenses.php'); // Redirecionamento PRG
    exit;
}

// --- Lógica para buscar mensagens de sessão após redirecionamento (GET) ---
if (isset($_SESSION['expense_action_message'])) {
    $expenseMessage = $_SESSION['expense_action_message'];
    $isExpenseSuccess = $_SESSION['is_expense_action_success'];
    $actionType = $_SESSION['expense_action_type'] ?? '';

    unset($_SESSION['expense_action_message']);
    unset($_SESSION['is_expense_action_success']);
    unset($_SESSION['expense_action_type']);
}

// --- Lógica para pré-popular o modal de edição em caso de erro ---
if (isset($_GET['action']) && $_GET['action'] == 'edit' && isset($_GET['id'])) {
    $expense_temp = new Expense();
    $expense_temp->id = $_GET['id'];
    $expense_temp->user_id = $_SESSION['user_id'];

    if ($expense_temp->getOneExpense() && $expense_temp->user_id === $_SESSION['user_id']) {
        $editExpenseData = [
            'id' => $expense_temp->id,
            'description' => $expense_temp->description,
            'value' => $expense_temp->value,
            'category_id' => $expense_temp->category_id,
            'expense_date' => $expense_temp->expense_date
        ];
        echo '<script type="text/javascript">document.addEventListener("DOMContentLoaded", function() { window.openEditExpenseModal(' . json_encode($editExpenseData) . '); });</script>';
    } else {
        header('Location: my_expenses.php');
        exit;
    }
}

// --- Lógica de Paginação ---
$records_per_page = 10; // Definir quantos itens por página
$current_page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($current_page - 1) * $records_per_page;

// Contar o total de gastos para calcular o número de páginas
$total_expenses = $expense->countAllExpensesByUserId();
$total_pages = ceil($total_expenses / $records_per_page);

// Buscar gastos para a página atual com limite e offset
$stmt = $expense->getAllExpensesByUserId($records_per_page, $offset);
$userExpenses = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Lógica para popular as categorias no dropdown do modal de edição
$allCategories = $expense->getAllCategories();


$pageTitle = 'Meus Gastos';
$currentPage = 'my_expenses';
include '../src/Views/partials/header.php';
include '../src/Views/partials/sidebar.php';
?>
<main class="flex-1 overflow-y-auto p-6">
    <header class="bg-white shadow-md rounded-lg p-4 mb-6 flex justify-between items-center">
        <h1 class="text-3xl font-bold text-gray-800">Meus Gastos</h1>
        <div class="text-gray-600">Bem-vindo(a), <?php echo htmlspecialchars($_SESSION['user_name'] ?? 'Usuário'); ?>!</div>
    </header>

    <section class="bg-white p-6 rounded-lg shadow-md">
        <h3 class="text-xl font-semibold text-gray-800 mb-4">Lista Completa dos Seus Gastos</h3>
        
        <?php if (!empty($expenseMessage)): ?>
            <div class="bg-<?php echo $isExpenseSuccess ? 'green' : 'red'; ?>-100 border border-<?php echo $isExpenseSuccess ? 'green' : 'red'; ?>-400 text-<?php echo $isExpenseSuccess ? 'green' : 'red'; ?>-700 px-4 py-3 rounded relative mb-4" role="alert">
                <?php echo htmlspecialchars($expenseMessage); ?>
            </div>
        <?php endif; ?>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Data
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Descrição
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Categoria
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Valor
                        </th>
                        <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Ações
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php if (!empty($userExpenses)): ?>
                        <?php foreach ($userExpenses as $expense_data): ?>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap"><?php echo htmlspecialchars(date('d/m/Y', strtotime($expense_data['expense_date']))); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap"><?php echo htmlspecialchars($expense_data['description']); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap"><?php echo htmlspecialchars($expense_data['category_name']); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-red-600 font-semibold">R$ <?php echo number_format($expense_data['value'], 2, ',', '.'); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <button class="text-indigo-600 hover:text-indigo-900 mr-4 edit-expense-btn"
                                            data-expense-id="<?php echo htmlspecialchars($expense_data['id']); ?>"
                                            data-expense-description="<?php echo htmlspecialchars($expense_data['description']); ?>"
                                            data-expense-value="<?php echo htmlspecialchars($expense_data['value']); ?>"
                                            data-expense-category-id="<?php echo htmlspecialchars($expense_data['category_id']); ?>"
                                            data-expense-date="<?php echo htmlspecialchars($expense_data['expense_date']); ?>">Editar</button>
                                    <button class="text-red-600 hover:text-red-900 delete-expense-btn"
                                            data-expense-id="<?php echo htmlspecialchars($expense_data['id']); ?>"
                                            data-expense-description="<?php echo htmlspecialchars($expense_data['description']); ?>">Excluir</button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="px-6 py-4 whitespace-nowrap text-center text-gray-500">Nenhum gasto encontrado. Adicione um!</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php if ($total_pages > 1): ?>
        <div class="flex justify-center mt-6">
            <nav class="relative z-0 inline-flex shadow-sm rounded-md -space-x-px" aria-label="Pagination">
                <?php if ($current_page > 1): ?>
                    <a href="?page=<?php echo $current_page - 1; ?>" class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                        <span class="sr-only">Anterior</span>
                        <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path fill-rule="evenodd" d="M12.79 5.29a1 1 0 010 1.42L9.41 10l3.38 3.29a1 1 0 01-1.42 1.42l-4-4a1 1 0 010-1.42l4-4a1 1 0 011.42 0z" clip-rule="evenodd" />
                        </svg>
                    </a>
                <?php else: ?>
                    <span class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-gray-50 text-sm font-medium text-gray-400 cursor-not-allowed">
                        <span class="sr-only">Anterior</span>
                        <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path fill-rule="evenodd" d="M12.79 5.29a1 1 0 010 1.42L9.41 10l3.38 3.29a1 1 0 01-1.42 1.42l-4-4a1 1 0 010-1.42l4-4a1 1 0 011.42 0z" clip-rule="evenodd" />
                        </svg>
                    </span>
                <?php endif; ?>

                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <a href="?page=<?php echo $i; ?>" class="<?php echo ($i === $current_page) ? 'z-10 bg-blue-50 border-blue-500 text-blue-600' : 'bg-white border-gray-300 text-gray-700 hover:bg-gray-50'; ?> relative inline-flex items-center px-4 py-2 border text-sm font-medium">
                        <?php echo $i; ?>
                    </a>
                <?php endfor; ?>

                <?php if ($current_page < $total_pages): ?>
                    <a href="?page=<?php echo $current_page + 1; ?>" class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                        <span class="sr-only">Próximo</span>
                        <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path fill-rule="evenodd" d="M7.21 14.71a1 1 0 010-1.42L10.59 10 7.21 6.71a1 1 0 011.42-1.42l4 4a1 1 0 010 1.42l-4 4a1 1 0 01-1.42 0z" clip-rule="evenodd" />
                        </svg>
                    </a>
                <?php else: ?>
                    <span class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-gray-50 text-sm font-medium text-gray-400 cursor-not-allowed">
                        <span class="sr-only">Próximo</span>
                        <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path fill-rule="evenodd" d="M7.21 14.71a1 1 0 010-1.42L10.59 10 7.21 6.71a1 1 0 011.42-1.42l4 4a1 1 0 010 1.42l-4 4a1 1 0 01-1.42 0z" clip-rule="evenodd" />
                        </svg>
                    </span>
                <?php endif; ?>
            </nav>
        </div>
        <?php endif; ?>
    </section>

    <div id="editExpenseModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden flex items-center justify-center z-50">
        <div class="relative p-8 bg-white w-full max-w-md mx-auto rounded-lg shadow-xl">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-2xl font-bold text-gray-800">Editar Gasto</h3>
                <button id="closeEditExpenseModal" class="text-gray-500 hover:text-gray-700">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>

            <form id="editExpenseForm" action="my_expenses.php" method="POST">
                <input type="hidden" name="editExpenseFormSubmit" value="1">
                <input type="hidden" id="edit_expense_id" name="expense_id">

                <div class="mb-4">
                    <label for="edit_description" class="block text-gray-700 text-sm font-bold mb-2">Descrição do Gasto:</label>
                    <input type="text" id="edit_description" name="edit_description" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" required>
                </div>
                <div class="mb-4">
                    <label for="edit_value" class="block text-gray-700 text-sm font-bold mb-2">Valor (R$):</label>
                    <input type="number" step="0.01" id="edit_value" name="edit_value" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" required>
                </div>
                <div class="mb-4">
                    <label for="edit_category" class="block text-gray-700 text-sm font-bold mb-2">Categoria:</label>
                    <select id="edit_category" name="edit_category" class="shadow border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" required>
                        <option value="">Selecione uma categoria</option>
                        <?php foreach ($allCategories as $category): ?>
                            <option value="<?php echo htmlspecialchars($category['id']); ?>">
                                <?php echo htmlspecialchars($category['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-6">
                    <label for="edit_date" class="block text-gray-700 text-sm font-bold mb-2">Data do Gasto:</label>
                    <input type="date" id="edit_date" name="edit_date" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" required>
                </div>
                <div class="flex items-center justify-between">
                    <button type="submit" class="bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline w-full">
                        Salvar Alterações
                    </button>
                </div>
            </form>
            <div id="editExpenseMessage" class="mt-4 hidden"></div>
        </div>
    </div>
</main>

<?php
// Injeção de script JavaScript para exibir mensagens após redirecionamento (PRG)
if (isset($expenseMessage) && !empty($expenseMessage)) {
    $script = 'document.addEventListener("DOMContentLoaded", function() {';
    // Modificado para sempre criar a div de mensagem se não existir ou se estiver vazia
    $script .= '  var messageContainer = document.querySelector("section > h3").nextElementSibling;'; // Pega a div após o h3
    // Adicionei uma verificação para não duplicar a mensagem se o PHP já a renderizou diretamente
    $script .= '  if (!messageContainer || !messageContainer.classList.contains("expense-alert-container")) {'; // Verifica se a div não existe ou não é a que queremos
    $script .= '    messageContainer = document.createElement("div");';
    $script .= '    messageContainer.id = "expenseAlertContainer";';
    $script .= '    messageContainer.classList.add("mb-4", "expense-alert-container");'; // Adiciona classes
    $script .= '    document.querySelector("section > h3").parentNode.insertBefore(messageContainer, document.querySelector("section > h3").nextSibling);'; // Insere após a div existente

    $script .= '  }'; // Fechar o if (messageContainer && messageContainer.innerHTML.trim() === "")
    
    $script .= '  messageContainer.classList.remove("hidden");'; // Mostra a div

    $alertClass = $isExpenseSuccess ? 'bg-green-100 border-green-400 text-green-700' : 'bg-red-100 border-red-400 text-red-700';
    $escapedMessage = addslashes($expenseMessage);
    $alertHtml = '<div class=\"' . $alertClass . ' px-4 py-3 rounded relative\" role=\"alert\">' . $escapedMessage . '</div>';
    $script .= '  messageContainer.innerHTML = \'' . $alertHtml . '\';';

    if ($isExpenseSuccess) {
        $script .= '  setTimeout(function() { messageContainer.classList.add("hidden"); messageContainer.innerHTML = ""; }, 3000);'; // Esconde após 3s
    }
    $script .= '});';
    echo '<script type="text/javascript">' . $script . '</script>';
}
?>
<script>
    // JavaScript para o modal de Edição de Gasto
    const editExpenseModal = document.getElementById('editExpenseModal');
    const closeEditExpenseModalBtn = document.getElementById('closeEditExpenseModal');
    const editExpenseButtons = document.querySelectorAll('.edit-expense-btn');

    window.openEditExpenseModal = function(expenseData) {
        document.getElementById('edit_expense_id').value = expenseData.id;
        document.getElementById('edit_description').value = expenseData.description;
        document.getElementById('edit_value').value = expenseData.value;
        // Popula o select de categoria corretamente
        document.getElementById('edit_category').value = expenseData.category_id;
        document.getElementById('edit_date').value = expenseData.expense_date; // Formato YYYY-MM-DD

        // Esconder mensagem anterior do modal de edição ao abrir
        document.getElementById("editExpenseMessage").classList.add("hidden");
        document.getElementById("editExpenseMessage").innerHTML = "";

        editExpenseModal.classList.remove('hidden');
    };

    if (editExpenseModal && closeEditExpenseModalBtn && editExpenseButtons.length > 0) { // Verifica se os elementos existem
        editExpenseButtons.forEach(button => {
            button.addEventListener('click', function() {
                const expenseId = this.dataset.expenseId;
                const expenseDescription = this.dataset.expenseDescription;
                const expenseValue = this.dataset.expenseValue;
                const expenseCategoryId = this.dataset.expenseCategoryId; // Pega o ID da categoria
                const expenseDate = this.dataset.expenseDate;

                const expenseData = {
                    id: expenseId,
                    description: expenseDescription,
                    value: expenseValue,
                    category_id: expenseCategoryId, // Passa o ID
                    expense_date: expenseDate
                };
                window.openEditExpenseModal(expenseData);
            });
        });

        closeEditExpenseModalBtn.addEventListener('click', () => {
            editExpenseModal.classList.add('hidden');
            document.getElementById("editExpenseMessage").classList.add("hidden"); // Esconde a mensagem ao fechar
            document.getElementById("editExpenseMessage").innerHTML = ""; // Limpa o conteúdo da mensagem
        });

        // Fechar modal ao clicar fora (no overlay)
        editExpenseModal.addEventListener('click', (event) => {
            if (event.target === editExpenseModal) {
                editExpenseModal.classList.add('hidden');
                document.getElementById("editExpenseMessage").classList.add("hidden");
                document.getElementById("editExpenseMessage").innerHTML = "";
            }
        });
    }

    // JavaScript para o botão de Excluir Gasto
    const deleteExpenseButtons = document.querySelectorAll('.delete-expense-btn');

    if (deleteExpenseButtons.length > 0) { // Verifica se existem botões de exclusão
        deleteExpenseButtons.forEach(button => {
            button.addEventListener('click', function() {
                const expenseId = this.dataset.expenseId;
                const expenseDescription = this.dataset.expenseDescription;

                if (confirm(`Tem certeza que deseja excluir o gasto "${expenseDescription}"? Esta ação é irreversível.`)) {
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.action = 'my_expenses.php'; // Envia para a mesma página

                    const expenseIdInput = document.createElement('input');
                    expenseIdInput.type = 'hidden';
                    expenseIdInput.name = 'expense_id';
                    expenseIdInput.value = expenseId;
                    form.appendChild(expenseIdInput);

                    const deleteSubmitInput = document.createElement('input');
                    deleteSubmitInput.type = 'hidden';
                    deleteSubmitInput.name = 'deleteExpenseSubmit';
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