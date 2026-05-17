document.addEventListener('DOMContentLoaded', function() {
    const chartCanvas = document.getElementById('financialComparisonChart');
    const yearSelect = document.getElementById('yearSelect');
    const filterForm = document.getElementById('filterForm');
    const chartTitle = document.getElementById('chart-title');
    const comparisonButtons = document.querySelectorAll('.btn-compare');
    let financialChart = null;
    let rawChartData = null; // Store fetched data globally

    const colors = {
        income: { bg: 'rgba(45, 212, 191, 0.7)', border: 'rgba(45, 212, 191, 1)' }, // Accent for Income (Teal)
        budget: { bg: 'rgba(255, 193, 7, 0.7)', border: 'rgba(255, 193, 7, 1)' }, // Yellow/Orange for Budget
        expense: { bg: 'rgba(220, 53, 69, 0.7)', border: 'rgba(220, 53, 69, 1)' } // Red for Expense
    };

    // --- Helper for formatting currency ---
    const formatCurrency = (amount) => {
        return new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP' }).format(amount);
    };

    // --- Core function to fetch all data ---
    function fetchAllData(year) {
        if (!chartCanvas) return;
        
        fetch(`financial_data.php?year=${year}`)
            .then(response => response.json())
            .then(data => {
                rawChartData = data;
                
                // Calculate and display Net Profit for the mobile card
                calculateNetProfit();
                
                // Start by showing the default view (all three)
                // Determine which button is currently active on load (default to 'all')
                const activeBtn = document.querySelector('.btn-compare.active');
                compareData(activeBtn ? activeBtn.id.replace('btn-', '') : 'all');
            })
            .catch(error => console.error('Error fetching financial data:', error));
    }
    
    // --- Calculate Net Profit/Loss for Mobile Card ---
    function calculateNetProfit() {
        if (!rawChartData) return;

        const totalIncome = rawChartData.income.reduce((sum, amount) => sum + amount, 0);
        const totalExpense = rawChartData.expense.reduce((sum, amount) => sum + amount, 0);
        const netProfit = totalIncome - totalExpense;

        const amountElement = document.getElementById('netProfitAmount');
        const statusElement = document.getElementById('netProfitStatus');
        
        // Only update if elements exist (they are hidden on desktop)
        if (amountElement && statusElement) {
            amountElement.textContent = formatCurrency(Math.abs(netProfit));
            
            // Remove previous color classes
            amountElement.classList.remove('text-red-500', 'text-green-600');
            statusElement.classList.remove('text-red-500', 'text-green-600');
            
            if (netProfit >= 0) {
                amountElement.classList.add('text-green-600');
                statusElement.textContent = netProfit === 0 ? 'Net Zero for the year.' : 'Total Profit achieved!';
                statusElement.classList.add('text-green-600');
            } else {
                amountElement.classList.add('text-red-500');
                statusElement.textContent = 'Total Loss incurred.';
                statusElement.classList.add('text-red-500');
            }
        }
    }


    // --- Function to update the chart based on comparison type ---
    window.compareData = function(type) {
        if (!rawChartData) return;

        // 1. Determine datasets and title
        let datasets = [];
        let titleText = `Monthly Comparison for ${yearSelect.value}`;
        
        // Helper to create a dataset object
        const createDataset = (label, dataKey) => ({
            label: label,
            data: rawChartData[dataKey],
            backgroundColor: colors[dataKey].bg,
            borderColor: colors[dataKey].border,
            borderWidth: 1,
            // Ensure Bar charts stack nicely
            categoryPercentage: 0.8,
            barPercentage: 0.9,
        });

        switch (type) {
            case 'income_expense':
                datasets.push(createDataset('Income', 'income'));
                datasets.push(createDataset('Expense', 'expense'));
                titleText = `Monthly Income vs Expense for ${yearSelect.value}`;
                break;
            case 'income_budget':
                datasets.push(createDataset('Income', 'income'));
                datasets.push(createDataset('Budget', 'budget'));
                titleText = `Monthly Income vs Budget for ${yearSelect.value}`;
                break;
            case 'budget_expense':
                datasets.push(createDataset('Budget', 'budget'));
                datasets.push(createDataset('Expense', 'expense'));
                titleText = `Monthly Budget vs Expense for ${yearSelect.value}`;
                break;
            case 'all':
            default:
                datasets.push(createDataset('Income', 'income'));
                datasets.push(createDataset('Budget', 'budget'));
                datasets.push(createDataset('Expense', 'expense'));
                titleText = `Monthly Income vs Budget vs Expense for ${yearSelect.value}`;
                break;
        }

        // 2. Destroy old chart instance
        if (financialChart) {
            financialChart.destroy();
        }

        // 3. Update title
        chartTitle.textContent = titleText;

        // 4. Create new chart
        const ctx = chartCanvas.getContext('2d');
        financialChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: rawChartData.labels,
                datasets: datasets
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                animation: {
                    duration: 1000,
                    easing: 'easeInOutQuad'
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Amount (₱)'
                        },
                        // Ensure max is high enough to accommodate all values
                        suggestedMax: Math.max(...rawChartData.income, ...rawChartData.expense, ...rawChartData.budget) * 1.2
                    }
                },
                plugins: {
                    legend: {
                        position: 'top',
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                let label = context.dataset.label || '';
                                if (label) {
                                    label += ': ';
                                }
                                if (context.parsed.y !== null) {
                                    label += formatCurrency(context.parsed.y);
                                }
                                return label;
                            }
                        }
                    }
                }
            }
        });

        // 5. Update active button visual
        comparisonButtons.forEach(btn => btn.classList.remove('active'));
        document.getElementById(`btn-${type}`).classList.add('active');
    };

    // --- Event Listeners ---
    
    // Auto-submit on year change
    if (yearSelect && filterForm) {
        yearSelect.addEventListener('change', function() {
            filterForm.submit(); 
        });
    }

    // Initial load: Fetch data for the currently selected year
    const selectedYear = yearSelect.value || new Date().getFullYear();
    fetchAllData(selectedYear);
});