<?php
    $pageTitle = 'Configurações do Sistema';
    $currentPage = 'settings';
    include '../src/Views/partials/header.php';
?>
<?php include '../src/Views/partials/sidebar.php'; ?>

<main class="flex-1 overflow-y-auto p-6">
    <header class="bg-white shadow-md rounded-lg p-4 mb-6 flex justify-between items-center">
        <h1 class="text-3xl font-bold text-gray-800">Configurações</h1>
        <div class="text-gray-600">Bem-vindo(a), [Nome do Usuário]!</div>
    </header>

    <section class="bg-white p-6 rounded-lg shadow-md">
        <h3 class="text-xl font-semibold text-gray-800 mb-4">Ajustes do Perfil e Sistema</h3>
        <p class="text-gray-600">Esta área permitirá gerenciar as configurações do seu perfil e do sistema em geral. (Conteúdo futuro do back-end)</p>
        </section>
</main>

<?php include '../src/Views/partials/footer.php'; ?>