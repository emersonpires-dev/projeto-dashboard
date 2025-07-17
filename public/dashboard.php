<?php
// public/dashboard.php

session_start();

// Se o usuário não estiver logado, redireciona para a página de login
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

require_once '../src/Models/Expense.php';

$expense = new Expense();
$expense->user_id = $_SESSION['user_id']; // Define o ID do usuário logado

// Busca todos os gastos do usuário e pega apenas os 5 mais recentes
$stmt = $expense->getAllExpensesByUserId();
$latestExpenses = $stmt->fetchAll(PDO::FETCH_ASSOC);
$latestExpenses = array_slice($latestExpenses, 0, 5);

// Buscar dados para o gráfico
$expensesSummary = $expense->getExpensesSummaryByCategoryByUserId();

// Preparar os dados para o JavaScript
$chartLabels = [];
$chartData = [];
foreach ($expensesSummary as $summary) {
    $chartLabels[] = htmlspecialchars($summary['category_name']);
    $chartData[] = $summary['total_spent'];
}

// Codificar os dados para JSON para passar para o JavaScript
$jsChartLabels = json_encode($chartLabels);
$jsChartData = json_encode($chartData);

// NOVO: Buscar dados para os cards
$totalSpentThisMonth = $expense->getTotalSpentThisMonthByUserId();
$topCategoryThisMonth = $expense->getTopCategoryThisMonthByUserId();
$nextUpcomingExpense = $expense->getNextUpcomingExpenseByUserId();


$pageTitle = 'Dashboard de Gastos';
$currentPage = 'dashboard';
include '../src/Views/partials/header.php';
include '../src/Views/partials/sidebar.php';
?>
<main class="flex-1 overflow-y-auto p-6">
    <header class="bg-white shadow-md rounded-lg p-4 mb-6 flex justify-between items-center">
        <h1 class="text-3xl font-bold text-gray-800">Dashboard</h1>
        <div class="text-gray-600">Bem-vindo(a), <?php echo htmlspecialchars($_SESSION['user_name'] ?? 'Usuário'); ?>!</div>
    </header>

    <section class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-6">
        <div class="bg-white p-6 rounded-lg shadow-md">
            <h3 class="text-lg font-semibold text-gray-700 mb-2">Total Gasto no Mês</h3>
            <p class="text-3xl font-bold text-red-600">R$ <?php echo number_format($totalSpentThisMonth, 2, ',', '.'); ?></p>
            <p class="text-sm text-gray-500 mt-1">Até agora neste mês</p>
        </div>
        <div class="bg-white p-6 rounded-lg shadow-md">
            <h3 class="text-lg font-semibold text-gray-700 mb-2">Maior Gasto (Mês)</h3>
            <p class="text-3xl font-bold text-blue-600"><?php echo htmlspecialchars($topCategoryThisMonth); ?></p>
            <p class="text-sm text-gray-500 mt-1">Categoria principal de gastos</p>
        </div>
        <div class="bg-white p-6 rounded-lg shadow-md">
            <h3 class="text-lg font-semibold text-gray-700 mb-2">Próximo Gasto</h3>
            <?php if ($nextUpcomingExpense): ?>
                <p class="text-3xl font-bold text-green-600">R$ <?php echo number_format($nextUpcomingExpense['value'], 2, ',', '.'); ?></p>
                <p class="text-sm text-gray-500 mt-1">Data: <?php echo htmlspecialchars(date('d/m/Y', strtotime($nextUpcomingExpense['expense_date']))); ?></p>
                <p class="text-xs text-gray-400 mt-1"><?php echo htmlspecialchars($nextUpcomingExpense['description']); ?></p>
            <?php else: ?>
                <p class="text-3xl font-bold text-green-600">N/A</p>
                <p class="text-sm text-gray-500 mt-1">Nenhum gasto futuro</p>
            <?php endif; ?>
        </div>
    </section>

    <section class="bg-white p-6 rounded-lg shadow-md mb-6">
        <h3 class="text-xl font-semibold text-gray-800 mb-4">Visão Geral dos Gastos por Categoria</h3>
        <div class="h-80">
            <canvas id="expensesChart"></canvas>
        </div>
    </section>

    <section class="bg-white p-6 rounded-lg shadow-md">
        <h3 class="text-xl font-semibold text-gray-800 mb-4">Últimos Lançamentos</h3>
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
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php if (!empty($latestExpenses)): ?>
                        <?php foreach ($latestExpenses as $expense_data): ?>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap"><?php echo htmlspecialchars(date('d/m/Y', strtotime($expense_data['expense_date']))); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap"><?php echo htmlspecialchars($expense_data['description']); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap"><?php echo htmlspecialchars($expense_data['category_name']); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-red-600 font-semibold">R$ <?php echo number_format($expense_data['value'], 2, ',', '.'); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4" class="px-6 py-4 whitespace-nowrap text-center text-gray-500">Nenhum gasto recente encontrado.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <div class="text-right mt-4">
            <a href="my_expenses.php" class="text-blue-600 hover:text-blue-800 font-bold">Ver todos os gastos &rarr;</a>
        </div>
    </section>
</main>
<script>
    // Variáveis globais para o JavaScript (para Chart.js)
    // Usamos window. para torná-las acessíveis em main.js
    window.chartLabels = <?php echo $jsChartLabels; ?>;
    window.chartData = <?php echo $jsChartData; ?>;
</script>
<?php include '../src/Views/partials/footer.php'; ?>