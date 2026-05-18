// main.js
function showChocolateBar(message, type = 'success') {
    const bar = document.getElementById('chocolateBar');
    if (!bar) return;
    
    bar.textContent = message;
    bar.classList.remove('error', 'success', 'hidden');
    bar.classList.add(type, 'show');

    // Auto hide after 4 seconds
    setTimeout(() => {
        bar.classList.remove('show');
        // Optionally add hidden class after transition ends
        setTimeout(() => {
            bar.classList.add('hidden');
        }, 300);
    }, 4000);
}

// Expose function globally if needed elsewhere
window.showChocolateBar = showChocolateBar;