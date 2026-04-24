/* File: sheener/js/date_slicer.js */
// Function to render slicers dynamically
function renderSlicer(containerId, data, selectedItems, updateCallback) {
    const container = document.getElementById(containerId);
    container.innerHTML = ''; // Clear existing buttons

    data.forEach((item) => {
        const button = document.createElement('button');
        button.textContent = item;
        button.className = selectedItems.includes(item) ? 'selected' : '';

        button.addEventListener('click', () => {
            if (selectedItems.includes(item)) {
                selectedItems.splice(selectedItems.indexOf(item), 1);
                button.classList.remove('selected');
            } else {
                selectedItems.push(item);
                button.classList.add('selected');
            }
            updateCallback(); // Call the provided callback (e.g., fetchData)
        });

        container.appendChild(button);
    });
}
