// File: expense_record.js (FINAL WORKING CODE)

// --- GLOBAL EXPORT FUNCTION (Attached instantly for onclick attribute) ---
(function() {
    window.exportExpenseTable = function() {
        const table = document.getElementById('expenseTable');
        if (!table) {
            alert("Table element not found for export.");
            return;
        }

        const rows = table.querySelectorAll('tr');
        let csv = [];

        // Loop through table rows
        for (const row of rows) {
            const cells = row.querySelectorAll('th, td');
            const rowData = [];

            for (let i = 0; i < cells.length; i++) {
                let cellText = cells[i].innerText.trim();

                // CRITICAL: Skip the 'Proof' (Index 5) and 'Actions' (Index 6) columns in the export
                if (cells[i].cellIndex === 5 || cells[i].cellIndex === 6) { 
                    continue;
                }
                
                // Handle commas/quotes within data
                if (cellText.includes(',') || cellText.includes('"') || cellText.includes('\n')) {
                    cellText = `"${cellText.replace(/"/g, '""')}"`;
                }
                rowData.push(cellText);
            }
            csv.push(rowData.join(','));
        }

        // Trigger download
        const csvString = csv.join('\n');
        
        // --- CRITICAL FIX: Add UTF-8 BOM for Peso symbol (₱) display ---
        const BOM = '\uFEFF'; 
        const finalContent = BOM + csvString;

        const filename = 'Expense_Records_' + new Date().toISOString().slice(0, 10) + '.csv';
        const blob = new Blob([finalContent], { type: 'text/csv;charset=utf-8;' });

        const link = document.createElement("a");
        if (link.download !== undefined) {
            const url = URL.createObjectURL(blob);
            link.setAttribute("href", url);
            link.setAttribute("download", filename);
            link.style.visibility = 'hidden';
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        } else {
            alert("Export failed: Your browser does not support automatic downloads.");
        }
    };
})();
// --------------------------------------------------------------------------


document.addEventListener('DOMContentLoaded', function() {
  let expenseChart = null;
  const yearlyExpenseChart = document.getElementById('yearlyExpenseChart');
  const monthSelect = document.getElementById('monthSelect');
  const yearSelect = document.getElementById('yearSelect');
  const filterForm = document.getElementById('filterForm');
  const chartTitle = document.getElementById('chart-title');
  const expenseModal = document.getElementById('expenseModal');
  const expenseForm = expenseModal ? expenseModal.querySelector('form') : null;
  const offlineBanner = document.getElementById('offline-banner');


  // Function to fetch data and update the chart
  function updateChart() {
    if (!yearlyExpenseChart) return;

    const urlParams = new URLSearchParams(window.location.search);
    const selectedMonth = urlParams.get('month') || '';
    const selectedYear = urlParams.get('year') || ''; 

    fetch(`expense_chart_data.php?month=${selectedMonth}&year=${selectedYear}`)
      .then(response => {
        if (!response.ok) {
            // Log the error response from PHP if available
            console.error('PHP Response Error Status:', response.status); 
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.json();
      })
      .then(data => {
        if (expenseChart) {
          expenseChart.destroy();
        }
        
        // --- CHART FIX: Ensure data is mapped as Floats for Chart.js ---
        const amounts = data.amounts.map(a => parseFloat(a)); 

        let titleText = 'Expense Overview';
        let xAxesLabel = 'Period';

        if (data.chartType === 'monthly' && selectedYear) {
          titleText = `Monthly Expense for ${selectedYear}`;
          xAxesLabel = 'Month';
        } else if (data.chartType === 'yearly') {
          titleText = 'Yearly Expense Overview (All Time)';
          xAxesLabel = 'Year';
        }
        chartTitle.textContent = titleText;

        const ctx = yearlyExpenseChart.getContext('2d');
        expenseChart = new Chart(ctx, {
          type: 'line', 
          data: {
            labels: data.labels,
            datasets: [{
              label: 'Expenses',
              data: amounts,
              backgroundColor: 'rgba(226, 78, 66, 0.2)', 
              borderColor: 'rgba(226, 78, 66, 1)', 
              borderWidth: 2,
              fill: true,
              tension: 0.4
            }]
          },
          options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
              y: { 
                beginAtZero: true, 
                title: { display: true, text: 'Amount (₱)' },
                suggestedMax: 100000 
              },
              x: { grid: { display: false } }
            },
            plugins: {
                legend: { position: 'top' },
                tooltip: {
                    callbacks: {
                      label: function(context) {
                        let label = context.dataset.label || '';
                        if (label) { label += ': '; }
                        if (context.parsed.y !== null) {
                          label += new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP' }).format(context.parsed.y);
                        }
                        return label;
                      }
                    }
                }
            }
          }
        });
      })
      .catch(error => {
        console.error('Error fetching chart data:', error);
        chartTitle.textContent = "Error: Could not load chart data.";
        if (yearlyExpenseChart.parentNode) {
             yearlyExpenseChart.parentNode.innerHTML = '<p class="text-red-600 text-center py-10">Data loading failed. Check browser console for network status and data structure.</p>';
        }
      });
  }
  
  // Initial chart load
  updateChart();

  if (monthSelect && yearSelect && filterForm) {
    monthSelect.addEventListener('change', function() {
      filterForm.submit();
    });
    yearSelect.addEventListener('change', function() {
      filterForm.submit();
    });
  }
  
  // --- PWA OFFLINE SYNC LOGIC (Interceptor) ---
  function storeAndSync(formData) {
      if (!('serviceWorker' in navigator) || !('SyncManager' in window)) {
          alert('Offline saving is not supported by your browser.');
          return;
      }
      
      navigator.serviceWorker.ready.then(swRegistration => {
          return swRegistration.sync.register('sync-expense-data'); // Change tag to 'expense'
      }).then(() => {
          alert('Expense saved locally! It will sync automatically when you reconnect to the network.');
          expenseModal.classList.add('hidden');
          expenseForm.reset();
      }).catch(err => {
          alert('Failed to register offline sync.');
          console.error(err);
      });
  }

  function performOnlineSubmission(formData) {
      // IMPORTANT: Use the dedicated PHP file for file uploads
      fetch('add_expense.php', {
          method: 'POST',
          body: formData
      }).then(response => {
          if (response.ok) {
              alert('Expense saved successfully!');
              window.location.reload(); 
          } else {
              alert('Server error saving expense.');
          }
      }).catch(err => {
          // If the network request fails, queue for sync as a fallback
          storeAndSync(formData);
      });
  }

  // --- CRITICAL FORM INTERCEPTOR ---
  if (expenseForm) {
      expenseForm.addEventListener('submit', function(e) {
          e.preventDefault(); 
          
          const formData = new FormData(expenseForm);

          if (navigator.onLine) {
              // Network is available: Attempt immediate sync
              performOnlineSubmission(formData);
          } else {
              // Network is offline: Store request and queue for later sync
              storeAndSync(formData);
          }
      });
  }
  
  // --- NETWORK STATUS CHECKER ---
  function updateNetworkStatus() {
      if (navigator.onLine) {
          offlineBanner.classList.add('hidden');
      } else {
          offlineBanner.classList.remove('hidden');
      }
  }

  window.addEventListener('online', updateNetworkStatus);
  window.addEventListener('offline', updateNetworkStatus);
  
  updateNetworkStatus();
});