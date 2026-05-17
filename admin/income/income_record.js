// File: income_record.js (FINAL CODE - EXPORT ENCODING & PWA LOGIC)

// --- GLOBAL EXPORT FUNCTION (Defined and attached to the window instantly) ---
(function() {
    window.exportIncomeTable = function() {
        const table = document.getElementById('incomeTable');
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

                // CRITICAL: Skip the 'Actions' column in the export
                if (cells[i].cellIndex === cells.length - 1) {
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

        // Combine rows into a single string
        const csvString = csv.join('\n');
        
        // --- CRITICAL FIX: Add UTF-8 BOM to guarantee encoding recognition ---
        const BOM = '\uFEFF'; 
        const finalContent = BOM + csvString;

        // Trigger download
        const filename = 'Income_Records_' + new Date().toISOString().slice(0, 10) + '.csv';
        // Use the BOM content and specify the MIME type with UTF-8 charset
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
  let incomeChartInstance = null;
  const yearlyIncomeChart = document.getElementById('yearlyIncomeChart');
  const monthSelect = document.getElementById('monthSelect');
  const yearSelect = document.getElementById('yearSelect');
  const filterForm = document.getElementById('filterForm');
  const chartTitle = document.getElementById('chart-title');
  const incomeModal = document.getElementById('incomeModal');
  const incomeForm = incomeModal ? incomeModal.querySelector('form') : null;
  const offlineBanner = document.getElementById('offline-banner');


  // Function to fetch data and update the chart
  function updateChart() {
    if (!yearlyIncomeChart) return;

    const urlParams = new URLSearchParams(window.location.search);
    const selectedMonth = urlParams.get('month') || '';
    const selectedYear = urlParams.get('year') || ''; 

    fetch(`income_chart_data.php?month=${selectedMonth}&year=${selectedYear}`)
      .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.json();
      })
      .then(data => {
        if (incomeChartInstance) {
          incomeChartInstance.destroy();
        }

        const amounts = data.amounts.map(Number);
        let titleText = 'Income Overview';
        let xAxesLabel = 'Period';

        if (data.chartType === 'monthly' && selectedYear) {
          titleText = `Monthly Income for ${selectedYear}`;
          xAxesLabel = 'Month';
        } else if (data.chartType === 'yearly') {
          titleText = 'Yearly Income Overview (All Time)';
          xAxesLabel = 'Year';
        }
        chartTitle.textContent = titleText;

        const ctx = yearlyIncomeChart.getContext('2d');
        incomeChartInstance = new Chart(ctx, {
          type: 'bar',
          data: {
            labels: data.labels,
            datasets: [{
              label: 'Income',
              data: amounts, 
              backgroundColor: 'rgba(86, 180, 211, 0.9)', 
              borderColor: 'rgba(86, 180, 211, 1)',
              borderWidth: 1,
              borderRadius: 5,
            }]
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
                suggestedMax: 100000 
              },
              x: {
                title: {
                    display: true,
                    text: xAxesLabel
                }
              }
            },
            plugins: {
              legend: {
                display: true,
                position: 'top',
              },
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
        if (yearlyIncomeChart.parentNode) {
             yearlyIncomeChart.parentNode.innerHTML = '<p class="text-red-600 text-center py-10">Data loading failed. Check browser console for network status and data structure.</p>';
        }
      });
  }
  
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
          return swRegistration.sync.register('sync-income-data'); 
      }).then(() => {
          alert('Income saved locally! It will sync automatically when you reconnect to the network.');
          incomeModal.classList.add('hidden');
          incomeForm.reset();
      }).catch(err => {
          alert('Failed to register offline sync.');
          console.error(err);
      });
  }

  function performOnlineSubmission(formData) {
      fetch('add_income.php', {
          method: 'POST',
          body: formData
      }).then(response => {
          if (response.ok) {
              alert('Income saved successfully!');
              window.location.reload(); 
          } else {
              alert('Server error saving income.');
          }
      }).catch(err => {
          storeAndSync(formData);
      });
  }

  // --- CRITICAL FORM INTERCEPTOR ---
  if (incomeForm) {
      incomeForm.addEventListener('submit', function(e) {
          e.preventDefault(); 
          
          const formData = new FormData(incomeForm);

          if (navigator.onLine) {
              performOnlineSubmission(formData);
          } else {
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