// File: sw-register.js 

if ('serviceWorker' in navigator) {
  window.addEventListener('load', () => {
    // Register the worker from the base directory of the entire app.
    // Ensure the path `/capsfilesv2/sw-worker.js` is correct for your server structure.
    navigator.serviceWorker.register('/capsfilesv2/sw-worker.js', {
        scope: '/capsfilesv2/'
    }) 
      .then(registration => {
        console.log('ServiceWorker registration successful:', registration.scope);
      })
      .catch(error => {
        console.log('ServiceWorker registration failed:', error);
      });
  });
}