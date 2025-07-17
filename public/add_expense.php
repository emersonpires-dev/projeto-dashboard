<?php
// public/add_expense.php

session_start(); // Inicia a sessão

// Se o usuário não estiver logado, redireciona para a página de login
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Lógica para processar a adição de gastos
require_once '../src/Models/Expense.php'; // Inclua a classe Expense
require_once '../src/Models/User.php'; // Inclua a classe User (se precisar de user_id da sessão)

$expenseMessage = '';
$isExpenseSuccess = false;

// Processamento do formulário de Adicionar Gasto
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $expense = new Expense();
    $expense->user_id = $_SESSION['user_id'] ?? null; // Pega o ID do usuário logado
    $expense->description = $_POST['description'] ?? '';
    $expense->value = $_POST['value'] ?? '';
    $expense->category_id = $_POST['category'] ?? ''; // O name do select é 'category'
    $expense->expense_date = $_POST['date'] ?? '';

    // Validação
    if (empty($expense->user_id) || empty($expense->description) || empty($expense->value) || empty($expense->category_id) || empty($expense->expense_date)) {
        $expenseMessage = 'Todos os campos são obrigatórios!';
        $isExpenseSuccess = false;
    } elseif (!is_numeric($expense->value) || $expense->value <= 0) {
        $expenseMessage = 'O valor deve ser um número positivo.';
        $isExpenseSuccess = false;
    } else {
        // Tenta adicionar o gasto
        if ($expense->add()) {
            $expenseMessage = 'Gasto registrado com sucesso!';
            $isExpenseSuccess = true;
            // Limpar campos do formulário após sucesso (opcional)
            $_POST = array(); // Zera o array POST para limpar os campos no formulário
        } else {
            $expenseMessage = 'Erro ao registrar gasto. Verifique os dados.';
            $isExpenseSuccess = false;
        }
    }
    // Redirecionamento PRG: Recarregar a mesma página via GET para limpar o POST
    header('Location: add_expense.php?message=' . urlencode($expenseMessage) . '&success=' . ($isExpenseSuccess ? '1' : '0'));
    exit;
}

// Lógica para popular as categorias no dropdown
$expenseModel = new Expense();
$categories = $expenseModel->getAllCategories();

// Lógica para exibir mensagem após o redirecionamento PRG (GET)
if (isset($_GET['message'])) {
    $expenseMessage = urldecode($_GET['message']);
    $isExpenseSuccess = ($_GET['success'] === '1');
}

$pageTitle = 'Adicionar Gasto';
$currentPage = 'add_expense';
include '../src/Views/partials/header.php';
include '../src/Views/partials/sidebar.php';
?>
<main class="flex-1 overflow-y-auto p-6">
    <header class="bg-white shadow-md rounded-lg p-4 mb-6 flex justify-between items-center">
        <h1 class="text-3xl font-bold text-gray-800">Adicionar Novo Gasto</h1>
        <div class="text-gray-600">Bem-vindo(a), <?php echo htmlspecialchars($_SESSION['user_name'] ?? 'Usuário'); ?>!</div>
    </header>

    <section class="bg-white p-8 rounded-lg shadow-md w-full max-w-2xl mx-auto">
        <?php if (!empty($expenseMessage)): ?>
            <div class="bg-<?php echo $isExpenseSuccess ? 'green' : 'red'; ?>-100 border border-<?php echo $isExpenseSuccess ? 'green' : 'red'; ?>-400 text-<?php echo $isExpenseSuccess ? 'green' : 'red'; ?>-700 px-4 py-3 rounded relative mb-4" role="alert">
                <?php echo htmlspecialchars($expenseMessage); ?>
            </div>
        <?php endif; ?>

        <form action="add_expense.php" method="POST">
            <div class="mb-4">
                <label for="description" class="block text-gray-700 text-sm font-bold mb-2">Descrição do Gasto:</label>
                <input type="text" id="description" name="description" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" placeholder="Ex: Anúncios Facebook Ads" value="<?php echo htmlspecialchars($_POST['description'] ?? ''); ?>" required>
            </div>
            <div class="mb-4">
                <label for="value" class="block text-gray-700 text-sm font-bold mb-2">Valor (R$):</label>
                <input type="number" step="0.01" id="value" name="value" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" placeholder="0.00" value="<?php echo htmlspecialchars($_POST['value'] ?? ''); ?>" required>
            </div>
            <div class="mb-4">
                <label for="category" class="block text-gray-700 text-sm font-bold mb-2">Categoria:</label>
                <select id="category" name="category" class="shadow border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" required>
                    <option value="">Selecione uma categoria</option>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?php echo htmlspecialchars($category['id']); ?>" <?php echo (isset($_POST['category']) && $_POST['category'] == $category['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($category['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="mb-6">
                <label for="date" class="block text-gray-700 text-sm font-bold mb-2">Data do Gasto:</label>
                <input type="date" id="date" name="date" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" value="<?php echo htmlspecialchars($_POST['date'] ?? date('Y-m-d')); ?>" required>
            </div>
            <div class="flex items-center justify-between">
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline w-full">
                    Registrar Gasto
                </button>
            </div>
        </form>
    </section>
</main>
<?php include '../src/Views/partials/footer.php'; ?>