// FILE: budget_record.js (Final Working Version)

// --- OFFLINE DATA PERSISTENCE SETUP ---
let offlineQueue = null; // Initialize as null

// Check if LocalForage library is available before initializing
if (typeof localforage !== 'undefined') {
    offlineQueue = localforage.createInstance({
        name: "AleinahBudgetDB",
        storeName: "syncQueue"
    });
    console.log("LocalForage initialized for offline queuing.");
} else {
    console.warn("LocalForage dependency missing. Offline queuing disabled.");
}

// Check connectivity status
function isOnline() {
    return navigator.onLine;
}

// Function to register background sync
function registerSync() {
    // Only register sync if offline queuing is actually enabled
    if (offlineQueue && 'serviceWorker' in navigator && 'SyncManager' in window) {
        navigator.serviceWorker.ready
            .then(reg => {
                return reg.sync.register('sync-budget-data');
            })
            .then(() => console.log('Background Sync registered: sync-budget-data'))
            .catch(err => console.error('Background Sync failed:', err));
    } else {
        console.warn('Background Sync API or LocalForage not supported/available. Relying on manual refresh.');
    }
}

// --- GLOBAL EXPORT FUNCTION (CSV Auto-Download) ---
window.exportBudgetTable = function() {
    const table = document.getElementById('budgetTable');
    if (!table) {
        alert("Budget table element not found for export.");
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

            // CRITICAL: Skip the 'Actions' column (last cell)
            if (cells[i].cellIndex === cells.length - 1) {
                continue;
            }
            
            // Clean currency symbols and formatting
            cellText = cellText.replace(/₱/g, '').replace(/,/g, '');

            // Handle commas/quotes within data (CSV safety)
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
    const monthSelect = document.getElementById('monthSelect');
    const month = monthSelect?.options[monthSelect.selectedIndex].text.replace(/\s/g, '_') || 'All_Months';
    const year = document.getElementById('yearSelect')?.value || 'All_Years';
    
    const filename = `Budget_Records_${month}_${year}.csv`;
    
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

// --- MODIFIED FUNCTION (No Password Check) ---
function openBudgetModal(category, approvedBy = '') {
  document.getElementById('approvedBy-' + category).value = approvedBy;
  const modal = document.getElementById('budgetModal-' + category);
  const form = modal.querySelector('form');
  
  // Set up form submission for offline queuing
  form.onsubmit = async function(e) {
      e.preventDefault();
      
      const formData = new FormData(form);
      const record = Object.fromEntries(formData.entries());
      
      // CRITICAL: Check if offlineQueue is initialized AND if online
      if (isOnline() || !offlineQueue) {
          form.submit(); // Standard online submission to add_budget.php
      } else {
          // If offline AND offlineQueue is available, queue the data
          const key = 'budget-' + Date.now();
          await offlineQueue.setItem(key, record);
          registerSync();
          
          alert(`Budget for ${record.category} saved locally. Will sync when online.`);
          closeBudgetModal(record.category);
          location.reload(); 
      }
  };
  
  modal.classList.remove('hidden');
}

function closeBudgetModal(category) {
  document.getElementById('budgetModal-' + category).classList.add('hidden');
}

// --- MAIN LOGIC EXECUTION ---
document.addEventListener('DOMContentLoaded', function () {
  
  // Register sync manager on load (will only run if LocalForage is available)
  registerSync();
  
  // Chart.js Pie Chart 
  const pieChart = document.getElementById('budgetPieChart');
  if (pieChart) {
    const urlParams = new URLSearchParams(window.location.search);
    const month = urlParams.get('month') || new Date().getMonth() + 1;
    const year = urlParams.get('year') || new Date().getFullYear();

    fetch('budget_chart_data.php?month=' + month + '&year=' + year)
      .then(response => {
        if (!response.ok) {
          throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.json();
      })
      .then(data => {
          // Check for data validity
          if (!data.labels || data.labels.length === 0) {
              console.warn("Chart data is empty. Not drawing pie chart.");
              return; 
          }

          // CRITICAL FIX: Ensure plugin is registered *before* calling new Chart
          if (typeof ChartDataLabels !== 'undefined') {
              Chart.register(ChartDataLabels);
          } else {
              console.warn("ChartDataLabels plugin not loaded or defined.");
          }

          // === CRITICAL FIX: Defer chart creation to ensure canvas dimensions are registered ===
          setTimeout(() => {
              const totalAmount = data.amounts.reduce((sum, current) => sum + current, 0); // Calculate total for percentage check

              new Chart(pieChart.getContext('2d'), {
                type: 'pie',
                data: {
                  labels: data.labels,
                  datasets: [{
                    data: data.amounts,
                    backgroundColor: [
                      '#56b4d3', '#e24e42', '#f59e42', '#eab308', '#2563eb', '#14b8a6'
                    ]
                  }]
                },
                options: {
                  responsive: true,
                  maintainAspectRatio: false,
                  plugins: {
                    legend: { position: 'bottom', labels: { font: { size: 14 } } },
                    datalabels: {
                      color: '#fff',
                      font: { weight: 'bold', size: 13 },
                      
                      // *** FIXED FORMATTER FOR SMALL SLICES ***
                      formatter: function(value, context) {
                        const percentage = (value / totalAmount) * 100;

                        // Only show label/amount if the slice size is >= 5% to prevent overlap
                        if (percentage < 5) {
                            return null;
                        }

                        const label = context.chart.data.labels[context.dataIndex];
                        return label + '\n₱' + (typeof value === 'number' ? value.toLocaleString() : value);
                      },
                      // ********************************************

                      textAlign: 'center', 
                      anchor: 'center',
                      padding: { top: 5, bottom: 5 }
                    }
                  }
                },
                plugins: [ChartDataLabels]
              });
          }, 0); 
          // =====================================================================================
          
      })
      .catch(error => console.error("Error loading or rendering chart:", error));
  }

  // Auto-submit filter form
  const monthSelect = document.getElementById('monthSelect');
  const yearSelect = document.getElementById('yearSelect');
  const filterForm = document.getElementById('filterForm');
  if (monthSelect && yearSelect && filterForm) {
    monthSelect.addEventListener('change', function () {
      filterForm.submit();
    });
    yearSelect.addEventListener('change', function () {
      filterForm.submit();
    });
  }
});