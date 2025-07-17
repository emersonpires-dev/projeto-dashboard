<?php
// src/Views/partials/sidebar.php

// Variável para controlar qual item da sidebar está ativo
// Será definida na página que incluir esta sidebar
$currentPage = $currentPage ?? '';
?>

<aside id="sidebar" class="w-64 bg-gray-800 text-white p-4 flex flex-col">
    <a href="dashboard.php" class="block text-2xl font-bold text-center mb-6 text-white hover:text-gray-300 transition duration-200">
        Dropship Insights
    </a>
    <nav class="space-y-4">
        <a href="dashboard.php" class="block py-2 px-4 rounded <?php echo ($currentPage === 'dashboard') ? 'bg-gray-700 text-white' : 'hover:bg-gray-700 transition duration-200'; ?>">
            Dashboard
        </a>
        <a href="add_expense.php" class="block py-2 px-4 rounded <?php echo ($currentPage === 'add_expense') ? 'bg-gray-700 text-white' : 'hover:bg-gray-700 transition duration-200'; ?>">
            Adicionar Gasto
        </a>
        <a href="my_expenses.php" class="block py-2 px-4 rounded <?php echo ($currentPage === 'my_expenses') ? 'bg-gray-700 text-white' : 'hover:bg-gray-700 transition duration-200'; ?>">
            Meus Gastos
        </a>
        <a href="reports.php" class="block py-2 px-4 rounded <?php echo ($currentPage === 'reports') ? 'bg-gray-700 text-white' : 'hover:bg-gray-700 transition duration-200'; ?>">
            Relatórios
        </a>
        <a href="manage_users.php" class="block py-2 px-4 rounded <?php echo ($currentPage === 'manage_users') ? 'bg-gray-700 text-white' : 'hover:bg-gray-700 transition duration-200'; ?>">
            Usuários
        </a>
        <a href="settings.php" class="block py-2 px-4 rounded <?php echo ($currentPage === 'settings') ? 'bg-gray-700 text-white' : 'hover:bg-gray-700 transition duration-200'; ?>">
            Configurações
        </a>
    </nav>
    <div class="mt-auto">
        <a href="logout.php" class="block py-2 px-4 rounded bg-red-600 hover:bg-red-700 text-white text-center transition duration-200">
            Sair
        </a>
    </div>
</aside>