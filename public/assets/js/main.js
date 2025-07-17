// public/assets/js/main.js

document.addEventListener('DOMContentLoaded', () => {
    // Lógica para o Gráfico de Gastos do Dashboard (Chart.js)
    const ctxDashboard = document.getElementById('expensesChart');
    if (ctxDashboard) {
        const dynamicLabels = (window.chartLabels && window.chartLabels.length > 0) ? window.chartLabels : ['Nenhuma Categoria'];
        const dynamicData = (window.chartData && window.chartData.length > 0) ? window.chartData : [0];

        new Chart(ctxDashboard, {
            type: 'bar',
            data: {
                labels: dynamicLabels,
                datasets: [{
                    label: 'Gastos (R$)',
                    data: dynamicData,
                    backgroundColor: [
                        'rgba(255, 99, 132, 0.6)',
                        'rgba(54, 162, 235, 0.6)',
                        'rgba(255, 206, 86, 0.6)',
                        'rgba(75, 192, 192, 0.6)',
                        'rgba(153, 102, 255, 0.6)',
                        'rgba(201, 203, 207, 0.6)',
                        'rgba(255, 159, 64, 0.6)',
                        'rgba(100, 100, 200, 0.6)',
                        'rgba(200, 100, 100, 0.6)'
                    ],
                    borderColor: [
                        'rgba(255, 99, 132, 1)',
                        'rgba(54, 162, 235, 1)',
                        'rgba(255, 206, 86, 1)',
                        'rgba(75, 192, 192, 1)',
                        'rgba(153, 102, 255, 1)',
                        'rgba(201, 203, 207, 1)',
                        'rgba(255, 159, 64, 1)',
                        'rgba(100, 100, 200, 1)',
                        'rgba(200, 100, 100, 1)'
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: { beginAtZero: true, title: { display: true, text: 'Valor (R$)' } },
                    x: { title: { display: true, text: 'Categoria de Gasto' } }
                },
                plugins: {
                    legend: { display: false },
                    title: { display: true, text: 'Distribuição de Gastos por Categoria' }
                }
            }
        });
    }

    // Lógica para o Gráfico de Linha (Evolução Mensal de Gastos) em reports.php
    const ctxMonthly = document.getElementById('monthlyExpensesChart');
    if (ctxMonthly) {
        const monthlyLabels = window.monthlyLabels || [];
        const monthlyData = window.monthlyData || [];

        new Chart(ctxMonthly, {
            type: 'line',
            data: {
                labels: monthlyLabels,
                datasets: [{
                    label: 'Gasto Total (R$)',
                    data: monthlyData,
                    borderColor: 'rgb(75, 192, 192)',
                    tension: 0.1,
                    fill: false
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        title: { display: true, text: 'Valor (R$)' }
                    },
                    x: {
                        title: { display: true, text: 'Mês/Ano' }
                    }
                },
                plugins: {
                    legend: { display: true, position: 'top' },
                    title: { display: true, text: 'Evolução Mensal de Gastos' }
                }
            }
        });
    }

    // Lógica para o Gráfico de Pizza (Distribuição por Categoria) em reports.php
    const ctxCategory = document.getElementById('categoryDistributionChart');
    if (ctxCategory) {
        const categoryLabels = window.categoryLabels || [];
        const categoryData = window.categoryData || [];

        const backgroundColors = [
            'rgba(255, 99, 132, 0.7)',
            'rgba(54, 162, 235, 0.7)',
            'rgba(255, 206, 86, 0.7)',
            'rgba(75, 192, 192, 0.7)',
            'rgba(153, 102, 255, 0.7)',
            'rgba(255, 159, 64, 0.7)',
            'rgba(199, 199, 199, 0.7)',
            'rgba(100, 100, 200, 0.7)',
            'rgba(200, 100, 100, 0.7)'
        ];
        const borderColors = [
            'rgba(255, 99, 132, 1)',
            'rgba(54, 162, 235, 1)',
            'rgba(255, 206, 86, 1)',
            'rgba(75, 192, 192, 1)',
            'rgba(153, 102, 255, 1)',
            'rgba(255, 159, 64, 1)',
            'rgba(199, 199, 199, 1)',
            'rgba(100, 100, 200, 1)',
            'rgba(200, 100, 100, 1)'
        ];

        new Chart(ctxCategory, {
            type: 'pie',
            data: {
                labels: categoryLabels,
                datasets: [{
                    label: 'Gastos por Categoria (R$)',
                    data: categoryData,
                    backgroundColor: backgroundColors,
                    borderColor: borderColors,
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: true, position: 'right' },
                    title: { display: true, text: 'Distribuição Percentual de Gastos' }
                }
            }
        });
    }

    // Lógica para o Modal de Adicionar Usuário
    const addUserModal = document.getElementById('addUserModal');
    const openAddUserModalBtn = document.getElementById('openAddUserModal');
    const closeAddUserModalBtn = document.getElementById('closeAddUserModal');
    const addUserMessageDiv = document.getElementById('addUserMessage'); // Certifique-se que esta div existe no HTML do modal
    const addUserForm = document.getElementById('addUserForm'); // Certifique-se que este form existe no HTML do modal

    if (openAddUserModalBtn && addUserModal && closeAddUserModalBtn) {
        openAddUserModalBtn.addEventListener('click', () => {
            addUserModal.classList.remove('hidden');
            if (addUserForm) addUserForm.reset(); // Verifica se o form existe antes de resetar
            if (addUserMessageDiv) { // Verifica se a div de mensagem existe
                addUserMessageDiv.classList.add('hidden');
                addUserMessageDiv.innerHTML = '';
            }
        });

        closeAddUserModalBtn.addEventListener('click', () => {
            addUserModal.classList.add('hidden');
            if (addUserForm) addUserForm.reset();
            if (addUserMessageDiv) {
                addUserMessageDiv.classList.add('hidden');
                addUserMessageDiv.innerHTML = '';
            }
        });

        addUserModal.addEventListener('click', (event) => {
            if (event.target === addUserModal) {
                addUserModal.classList.add('hidden');
                if (addUserForm) addUserForm.reset();
                if (addUserMessageDiv) {
                    addUserMessageDiv.classList.add('hidden');
                    addUserMessageDiv.innerHTML = '';
                }
            }
        });
    }

    // Lógica para o Modal de Edição de Usuário
    const editUserModal = document.getElementById('editUserModal');
    const closeEditUserModalBtn = document.getElementById('closeEditUserModal');
    const editUserButtons = document.querySelectorAll('.edit-user-btn'); // Seleciona todos os botões de editar
    const editUserMessageDiv = document.getElementById('editUserMessage'); // Certifique-se que esta div existe no HTML do modal
    const editUserForm = document.getElementById('editUserForm'); // Certifique-se que este form existe no HTML do modal

    window.openEditUserModal = function(userData) {
        if (editUserModal && editUserForm) { // Verifica se os elementos do modal existem
            document.getElementById('edit_user_id').value = userData.id;
            document.getElementById('edit_name').value = userData.name;
            document.getElementById('edit_email').value = userData.email;
            document.getElementById('edit_user_type').value = userData.user_type;
            document.getElementById('edit_password').value = ''; // Sempre limpa o campo de senha
            
            if (editUserMessageDiv) { // Verifica se a div de mensagem existe
                editUserMessageDiv.classList.add("hidden");
                editUserMessageDiv.innerHTML = "";
            }
            editUserModal.classList.remove('hidden');
        }
    };

    if (editUserModal && closeEditUserModalBtn && editUserButtons.length > 0) {
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
            if (editUserMessageDiv) {
                editUserMessageDiv.classList.add("hidden");
                editUserMessageDiv.innerHTML = "";
            }
        });

        editUserModal.addEventListener('click', (event) => {
            if (event.target === editUserModal) {
                editUserModal.classList.add('hidden');
                if (editUserMessageDiv) {
                    editUserMessageDiv.classList.add("hidden");
                    editUserMessageDiv.innerHTML = "";
                }
            }
        });
    }

    // Lógica para o botão de Excluir Usuário
    const deleteUserButtons = document.querySelectorAll('.delete-user-btn');
    // A div de mensagem para exclusão de usuário é 'deleteUserMessage' que está no HTML de manage_users.php

    if (deleteUserButtons.length > 0) {
        deleteUserButtons.forEach(button => {
            button.addEventListener('click', function() {
                const userId = this.dataset.userId;
                const userName = this.dataset.userName;

                if (confirm(`Tem certeza que deseja excluir o usuário "${userName}" (ID: ${userId})? Esta ação é irreversível.`)) {
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.action = 'manage_users.php';

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

                    document.body.appendChild(form);
                    form.submit();
                }
            });
        });
    }

    // Lógica para o modal de Edição de Gasto
    const editExpenseModal = document.getElementById('editExpenseModal');
    const closeEditExpenseModalBtn = document.getElementById('closeEditExpenseModal');
    const editExpenseButtons = document.querySelectorAll('.edit-expense-btn'); // Botões de editar gastos

    window.openEditExpenseModal = function(expenseData) {
        if (editExpenseModal) { // Verifica se o modal existe no DOM
            document.getElementById('edit_expense_id').value = expenseData.id;
            document.getElementById('edit_description').value = expenseData.description;
            document.getElementById('edit_value').value = expenseData.value;
            document.getElementById('edit_category').value = expenseData.category_id;
            document.getElementById('edit_date').value = expenseData.expense_date;

            const editExpenseMessageDiv = document.getElementById("editExpenseMessage"); // Mensagem do modal de gasto
            if (editExpenseMessageDiv) {
                editExpenseMessageDiv.classList.add("hidden");
                editExpenseMessageDiv.innerHTML = "";
            }
            editExpenseModal.classList.remove('hidden');
        }
    };

    if (editExpenseModal && closeEditExpenseModalBtn && editExpenseButtons.length > 0) {
        editExpenseButtons.forEach(button => {
            button.addEventListener('click', function() {
                const expenseId = this.dataset.expenseId;
                const expenseDescription = this.dataset.expenseDescription;
                const expenseValue = this.dataset.expenseValue;
                const expenseCategoryId = this.dataset.expenseCategoryId;
                const expenseDate = this.dataset.expenseDate;

                const expenseData = {
                    id: expenseId,
                    description: expenseDescription,
                    value: expenseValue,
                    category_id: expenseCategoryId,
                    expense_date: expenseDate
                };
                window.openEditExpenseModal(expenseData);
            });
        });

        closeEditExpenseModalBtn.addEventListener('click', () => {
            editExpenseModal.classList.add('hidden');
            const editExpenseMessageDiv = document.getElementById("editExpenseMessage");
            if (editExpenseMessageDiv) {
                editExpenseMessageDiv.classList.add("hidden");
                editExpenseMessageDiv.innerHTML = "";
            }
        });

        editExpenseModal.addEventListener('click', (event) => {
            if (event.target === editExpenseModal) {
                editExpenseModal.classList.add('hidden');
                const editExpenseMessageDiv = document.getElementById("editExpenseMessage");
                if (editExpenseMessageDiv) {
                    editExpenseMessageDiv.classList.add("hidden");
                    editExpenseMessageDiv.innerHTML = "";
                }
            }
        });
    }

    // Lógica para o botão de Excluir Gasto
    const deleteExpenseButtonsMyExpenses = document.querySelectorAll('.delete-expense-btn');

    if (deleteExpenseButtonsMyExpenses.length > 0) {
        deleteExpenseButtonsMyExpenses.forEach(button => {
            button.addEventListener('click', function() {
                const expenseId = this.dataset.expenseId;
                const expenseDescription = this.dataset.expenseDescription;

                if (confirm(`Tem certeza que deseja excluir o gasto "${expenseDescription}"? Esta ação é irreversível.`)) {
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.action = 'my_expenses.php';

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

                    document.body.appendChild(form);
                    form.submit();
                }
            });
        });
    }
});