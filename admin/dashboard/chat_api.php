<?php
// File: dashboard/chat_api.php - FINAL GEMINI INTEGRATION

// 1. Setup and Security
header('Content-Type: application/json');
require_once '../../db_config.php'; 
// Include the new config file for the API key
require_once '../../config.php';
include 'data_fetch.php';          

/**
 * Sends the user query and the detailed financial context to the Gemini API.
 */
function getGeminiResponse($userQuery, $context) {
    
    // --- 1. Construct the Prompt (System Instruction) ---
    $systemInstruction = "You are the highly knowledgeable AI Assistant for Aleinah's Resort Management Dashboard. 
    Your primary role is to analyze the provided financial and operational context (KPIs) and provide a professional, conversational, and actionable response. 
    
    Instructions:
    1. Base your entire response on the provided CURRENT DATA SUMMARY.
    2. Use PHP Pesos (₱) for all monetary values.
    3. Adopt the persona of a senior financial analyst and speak to the General Manager.
    4. When discussing predictions, clearly state they are based on a 3-month rolling average.
    5. Always give a concise, confident, and professional answer.";

    $fullPrompt = "SYSTEM CONTEXT:\n" . $context . "\n\nUSER QUESTION:\n" . $userQuery;

    // --- 2. Prepare the API Request Payload ---
    $payload = json_encode([
        'contents' => [
            [
                'role' => 'user',
                'parts' => [
                    ['text' => $fullPrompt]
                ]
            ]
        ],
        'config' => [
            'systemInstruction' => $systemInstruction,
            'temperature' => 0.6 // A bit creative, but still grounded
        ]
    ]);

    // --- 3. Execute the cURL Request ---
    $ch = curl_init();
    
    curl_setopt($ch, CURLOPT_URL, GEMINI_API_URL . '?key=' . GEMINI_API_KEY);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Accept: application/json'
    ]);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    if ($error) {
        return "API Error: Could not connect to the Gemini service. Check your cURL setup. (" . htmlspecialchars($error) . ")";
    }

    if ($httpCode !== 200) {
        return "API Error: Gemini service returned HTTP Code " . $httpCode . ". Check your API Key and URL in config.php.";
    }

    // --- 4. Parse and Return Response ---
    $data = json_decode($response, true);
    
    // Check if the response structure is valid
    if (isset($data['candidates'][0]['content']['parts'][0]['text'])) {
        return $data['candidates'][0]['content']['parts'][0]['text'];
    } elseif (isset($data['error']['message'])) {
        return "AI Error: " . htmlspecialchars($data['error']['message']);
    } else {
        return "AI Error: Received an unexpected response format from the API.";
    }
}


// 2. Data Gathering: Fetch current KPIs and new Predictions
$kpis = getDashboardKPIs(); 
$predictions = getFinancialPrediction(); 

$data = json_decode(file_get_contents("php://input"), true);
$userQuery = $data['query'] ?? 'No query provided.';

// 3. LLM Context Construction (Crucial step: send raw data for AI to format)
$context = "CURRENT ALEINAH'S RESORT DATA SUMMARY (All figures in PHP):\n";
$context .= "- Total Income (Current Mo): " . $kpis['totalIncome'] . "\n";
$context .= "- Total Expense (Current Mo): " . $kpis['totalExpense'] . "\n";
$context .= "- Net Profit: " . $kpis['netProfit'] . "\n";
$context .= "- Total Budget: " . $kpis['totalBudget'] . "\n";
$context .= "- Budget Utilization (%): " . number_format($kpis['budgetUtilization'], 1) . "\n"; 
$context .= "- Low Stock Items: " . $kpis['lowStockCount'] . "\n";
$context .= "- Out of Stock Items: " . $kpis['outOfStockCount'] . "\n";
$context .= "- Active Employees: " . $kpis['activeEmployees'] . "\n";
$context .= "- Last Payroll Total: " . $kpis['lastPayrollTotal'] . "\n";
$context .= "- Last Payroll Date: " . $kpis['lastPayrollDate'] . "\n";

$nextMonthName = date('F', strtotime('+1 month'));
$context .= "- Next Month Income Forecast (3-Mo Avg): " . $predictions['predictedIncome'] . "\n"; 
$context .= "- Next Month Expense Estimate (3-Mo Avg): " . $predictions['predictedExpense'] . "\n";
$context .= "- Next Month Name: $nextMonthName\n";

// 4. Call the REAL Gemini API
// $aiAnswer = getGeminiResponse($userQuery, $context); // ORIGINAL LINE

// --- TEMPORARY DEBUG LINE ---
$aiAnswer = "DEBUG TEST: If you see this, the JavaScript is calling PHP correctly!";

echo json_encode(['answer' => $aiAnswer]);
?>
echo json_encode(['answer' => $aiAnswer]);
?>