<?php
// public/reports.php

session_start(); // Inicia a sessão

// Se o usuário não estiver logado, redireciona para a página de login
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Lógica para exportar CSV (ativada)
if (isset($_GET['export_csv']) && $_GET['export_csv'] == '1') {
    $queryString = http_build_query($_GET);
    header('Location: export_expenses.php?' . $queryString); // <-- Redirecionamento ativo
    exit;
}


require_once '../src/Models/Expense.php'; // Inclua a classe Expense
require_once '../src/Models/User.php'; // Pode ser útil para filtros de usuário no futuro

$expense = new Expense();
$expense->user_id = $_SESSION['user_id']; // Define o ID do usuário logado

// Obter todas as categorias para popular o filtro
$allCategories = $expense->getAllCategories();

$filters = [
    'start_date' => $_GET['start_date'] ?? '',
    'end_date' => $_GET['end_date'] ?? '',
    'category_id' => $_GET['category_id'] ?? '',
    'search_term' => $_GET['search_term'] ?? ''
];

// Lógica para definir datas padrão se não forem fornecidas
if (empty($filters['start_date'])) {
    $filters['start_date'] = date('Y-m-01'); // Início do mês atual
}
if (empty($filters['end_date'])) {
    $filters['end_date'] = date('Y-m-t'); // Último dia do mês atual
}

// Buscar gastos com base nos filtros
$stmt = $expense->getFilteredExpensesByUserId($filters);
$filteredExpenses = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Para o resumo do relatório
$totalFilteredSpent = array_sum(array_column($filteredExpenses, 'value'));


// Buscar dados para o gráfico de linha (Evolução Mensal)
$monthlySummary = $expense->getMonthlyExpensesSummaryByUserId($filters);
$monthlyLabels = [];
$monthlyData = [];
foreach ($monthlySummary as $month_data) {
    $monthlyLabels[] = date('M Y', strtotime($month_data['expense_month'])); // Ex: Jul 2025
    $monthlyData[] = $month_data['total_spent_month'];
}
$jsMonthlyLabels = json_encode($monthlyLabels);
$jsMonthlyData = json_encode($monthlyData);

// Buscar dados para o gráfico de pizza (Distribuição por Categoria)
$categoryDistribution = $expense->getCategoryDistributionByUserId($filters);
$categoryLabels = [];
$categoryData = [];
foreach ($categoryDistribution as $cat_data) {
    $categoryLabels[] = htmlspecialchars($cat_data['category_name']);
    $categoryData[] = $cat_data['total_spent']; // Ou percentage se preferir o percentual direto
}
$jsCategoryLabels = json_encode($categoryLabels);
$jsCategoryData = json_encode($categoryData);


$pageTitle = 'Relatórios de Gastos';
$currentPage = 'reports';
include '../src/Views/partials/header.php';
include '../src/Views/partials/sidebar.php';
?>
<main class="flex-1 overflow-y-auto p-6">
    <header class="bg-white shadow-md rounded-lg p-4 mb-6 flex justify-between items-center">
        <h1 class="text-3xl font-bold text-gray-800">Relatórios</h1>
        <div class="text-gray-600">Bem-vindo(a), <?php echo htmlspecialchars($_SESSION['user_name'] ?? 'Usuário'); ?>!</div>
    </header>

    <section class="bg-white p-6 rounded-lg shadow-md mb-6">
        <h3 class="text-xl font-semibold text-gray-800 mb-4">Filtros de Relatório</h3>
        <form action="reports.php" method="GET" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 items-end">
            <div>
                <label for="start_date" class="block text-gray-700 text-sm font-bold mb-2">Data Inicial:</label>
                <input type="date" id="start_date" name="start_date" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" value="<?php echo htmlspecialchars($filters['start_date']); ?>">
            </div>
            <div>
                <label for="end_date" class="block text-gray-700 text-sm font-bold mb-2">Data Final:</label>
                <input type="date" id="end_date" name="end_date" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" value="<?php echo htmlspecialchars($filters['end_date']); ?>">
            </div>
            <div>
                <label for="category_id" class="block text-gray-700 text-sm font-bold mb-2">Categoria:</label>
                <select id="category_id" name="category_id" class="shadow border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                    <option value="">Todas as Categorias</option>
                    <?php foreach ($allCategories as $category): ?>
                        <option value="<?php echo htmlspecialchars($category['id']); ?>" <?php echo (isset($filters['category_id']) && (string)$filters['category_id'] === (string)$category['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($category['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label for="search_term" class="block text-gray-700 text-sm font-bold mb-2">Buscar Descrição:</label>
                <input type="text" id="search_term" name="search_term" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" placeholder="Descrição do gasto" value="<?php echo htmlspecialchars($filters['search_term']); ?>">
            </div>
            <div class="col-span-full md:col-span-1">
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline w-full mb-2">
                    Aplicar Filtros
                </button>
                <button type="submit" name="export_csv" value="1" class="bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline w-full">
                    Exportar para CSV
                </button>
            </div>
        </form>
    </section>

    <section class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        <div class="bg-white p-6 rounded-lg shadow-md">
            <h3 class="text-xl font-semibold text-gray-800 mb-4">Evolução Mensal de Gastos</h3>
            <div class="h-80">
                <canvas id="monthlyExpensesChart"></canvas>
            </div>
        </div>
        <div class="bg-white p-6 rounded-lg shadow-md">
            <h3 class="text-xl font-semibold text-gray-800 mb-4">Distribuição por Categoria</h3>
            <div class="h-80">
                <canvas id="categoryDistributionChart"></canvas>
            </div>
        </div>
    </section>

    <section class="bg-white p-6 rounded-lg shadow-md">
        <h3 class="text-xl font-semibold text-gray-800 mb-4">Resultados do Relatório</h3>
        <p class="text-gray-600 mb-4">Total de gastos no período filtrado: <span class="font-bold text-red-600">R$ <?php echo number_format($totalFilteredSpent, 2, ',', '.'); ?></span></p>

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
                    <?php if (!empty($filteredExpenses)): ?>
                        <?php foreach ($filteredExpenses as $expense_data): ?>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap"><?php echo htmlspecialchars(date('d/m/Y', strtotime($expense_data['expense_date']))); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap"><?php echo htmlspecialchars($expense_data['description']); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap"><?php echo htmlspecialchars($expense_data['category_name']); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-red-600 font-semibold">R$ <?php echo number_format($expense_data['value'], 2, ',', '.'); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4" class="px-6 py-4 whitespace-nowrap text-center text-gray-500">Nenhum gasto encontrado para os filtros aplicados.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>
</main>
<script>
    // Variáveis globais para o JavaScript (para Chart.js)
    // Dados para o gráfico de linha (evolução mensal)
    window.monthlyLabels = <?php echo json_encode($monthlyLabels); ?>;
    window.monthlyData = <?php echo json_encode($monthlyData); ?>;

    // Dados para o gráfico de pizza (distribuição por categoria)
    window.categoryLabels = <?php echo json_encode($categoryLabels); ?>;
    window.categoryData = <?php echo json_encode($categoryData); ?>;
</script>
<?php include '../src/Views/partials/footer.php'; ?>