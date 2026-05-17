// File: paymongo_sync.js
// PURPOSE: Triggers the server-side synchronization of PayMongo data to the local database.

document.addEventListener('DOMContentLoaded', function() {
    const syncButton = document.getElementById('syncButton');
    
    if (syncButton) {
        
        syncButton.addEventListener('click', function() {
            
            // 1. Disable button and show loading status
            syncButton.disabled = true;
            syncButton.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Syncing... Please wait.';
            
            // 2. Send request to the PHP processor. No body data is needed now.
            fetch('sync_paymongo.php', {
                method: 'POST', // Use POST even without data to imply an action/state change
            })
            .then(response => {
                // Since the server side script uses a header redirect, this 'then' 
                // block might not be fully reached before the page reloads.
                if (!response.ok) {
                    throw new Error('Server returned error status.');
                }
                
                // If sync_paymongo.php successfully redirects, this code won't run. 
                // If there's an issue before the redirect, this alerts the user.
                alert("Synchronization process initiated! Checking server status..."); 
                window.location.reload(); 
            })
            .catch(error => {
                alert("Synchronization failed due to a network error or server crash. Check the browser console.");
                console.error("Sync Error:", error);
                syncButton.disabled = false;
                syncButton.innerHTML = '<i class="fas fa-sync-alt mr-2"></i> Sync & Import Payments';
            });
        });
    }
});