// File: dashboard/dashboard.js - FINAL API CLIENT

document.addEventListener('DOMContentLoaded', function() {
    // --- 0. Element Initialization ---
    const chartCanvas = document.getElementById('financialTrendChart');
    const chartTitle = document.getElementById('chart-title');
    
    // Chatbot Elements
    const aiChatButton = document.getElementById('aiChatButton');
    const aiChatWindow = document.getElementById('aiChatWindow');
    const chatMessages = document.getElementById('chatMessages');
    const chatInput = document.getElementById('chatInput');
    const chatSendButton = document.getElementById('chatSendButton');
    
    // --- Helper Functions ---
    const formatCurrency = (amount) => {
        return '₱' + new Intl.NumberFormat('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(amount);
    };

    const displayMessage = (message, sender) => {
        if (!chatMessages) return; 
        const messageElement = document.createElement('div');
        
        // Tailwind/Custom classes for styling
        messageElement.classList.add('py-2', 'px-3', 'rounded-lg', 'max-w-[80%]', 'break-words'); 
        if (sender === 'user') {
            messageElement.classList.add('ml-auto', 'bg-accent', 'text-white');
        } else {
            // Added 'dot-flashing' class for the loading indicator when needed
            messageElement.classList.add('bg-gray-200', 'text-dark-text');
        }
        messageElement.innerHTML = message; 
        chatMessages.appendChild(messageElement);
        
        chatMessages.scrollTop = chatMessages.scrollHeight;
        return messageElement; // Return element for removal when using 'thinking' indicator
    };
    
    // --- 1. AI CHATBOT LOGIC (API Integration) ---
    if (aiChatButton && aiChatWindow) { 
        
        // Toggle window visibility and focus input
        aiChatButton.addEventListener('click', function(e) {
            e.stopPropagation(); 
            aiChatWindow.classList.toggle('hidden');
            if (!aiChatWindow.classList.contains('hidden') && chatInput) {
                chatInput.focus();
            }
        });
        
        // Close window when clicking outside
        document.addEventListener('click', function(e) {
            const isClickedOutside = !aiChatButton.contains(e.target) && !aiChatWindow.contains(e.target);
            
            if (isClickedOutside && !aiChatWindow.classList.contains('hidden')) {
                aiChatWindow.classList.add('hidden');
            }
        });

        // --- NEW GEMINI API CALL LOGIC ---
        const sendMessage = async () => { 
            const userMessage = chatInput.value.trim();
            if (userMessage === '') return;
            
            // 1. Display User Message
            displayMessage(userMessage, 'user');
            chatInput.value = '';
            
            // 2. Display 'Thinking' Indicator 
            // NOTE: You may need to add CSS for '.dot-flashing' to your index.php for this to look good.
            const thinkingIndicator = displayMessage('<span>Thinking...</span>', 'ai'); 
            
            try {
                // 3. Send Message to the new PHP API endpoint
                const response = await fetch('./chat_api.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    // Send the query, the PHP backend handles data fetching and sending to Gemini
                    body: JSON.stringify({ query: userMessage })
                });
                
                if (!response.ok) {
                    throw new Error(`API error: ${response.status}`);
                }
                
                const aiResponse = await response.json(); 
                
                // 4. Remove 'Thinking' and Display AI Response
                if (thinkingIndicator.parentNode) {
                    thinkingIndicator.parentNode.removeChild(thinkingIndicator);
                }

                // The PHP backend already formats the response using Gemini
                displayMessage(aiResponse.answer, 'ai'); 

            } catch (error) {
                console.error("AI Chat Error:", error);
                if (thinkingIndicator.parentNode) {
                    thinkingIndicator.parentNode.removeChild(thinkingIndicator);
                }
                displayMessage("Sorry, I'm having trouble connecting to the Gemini service or PHP backend. Check the console for API errors.", 'ai');
            }
        };

        if (chatSendButton && chatInput) {
             chatSendButton.addEventListener('click', sendMessage);
             chatInput.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    sendMessage();
                }
            });
        }
    }


    // --- 2. CHART RENDERING (Unchanged) ---
    async function fetchAndRenderChart() {
        if (!chartCanvas) return;
        
        try {
            // Fetch Historical Data 
            const historicalResponse = await fetch('../financial-overview/financial_data.php'); 
            const historicalData = await historicalResponse.json();
            
            // --- Chart Data Preparation: Use ONLY Historical Data ---
            const labels = historicalData.labels;
            
            const datasets = [
                {
                    label: 'Income (Actual)',
                    data: historicalData.income, 
                    backgroundColor: 'rgba(45, 212, 191, 0.7)', 
                    borderColor: 'rgba(45, 212, 191, 1)',
                    borderWidth: 2,
                    type: 'bar',
                },
                {
                    label: 'Expense (Actual)',
                    data: historicalData.expense, 
                    backgroundColor: 'rgba(220, 53, 69, 0.7)', 
                    borderColor: 'rgba(220, 53, 69, 1)',
                    borderWidth: 2,
                    type: 'bar',
                },
            ];

            // --- Render Chart ---
            const ctx = chartCanvas.getContext('2d');
            
            if (window.myFinancialChart) {
                window.myFinancialChart.destroy();
            }

            chartTitle.textContent = "Yearly Financial Trend (Income vs. Expense)"; 

            window.myFinancialChart = new Chart(ctx, { 
                type: 'bar', 
                data: { labels: labels, datasets: datasets },
                options: {
                    responsive: true,
                    maintainAspectRatio: false, 
                    scales: { y: { beginAtZero: true, title: { display: true, text: 'Amount (₱)' } } },
                    plugins: { 
                        legend: { position: 'top' },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    let label = context.dataset.label || '';
                                    if (label) { label += ': '; }
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

        } catch (error) {
            console.error('Error fetching chart data:', error);
            chartTitle.textContent = "Could not load financial trend data.";
        }
    }

    // --- STARTUP ---
    fetchAndRenderChart();
});