/* File: sheener/js/create_waste_collection.js */
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('createCollectionForm');
    if (!form) {
      console.error('Form element not found!');
      return;
    }
    
    form.addEventListener('submit', function(e) {
      e.preventDefault();
      const formData = new FormData(this);
      
      fetch('php/create_waste_collection.php', {
        method: 'POST',
        body: formData
      })
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          alert('Waste collection created successfully!');
          window.location.href = 'list_waste_collections.html';
        } else {
          alert('Error: ' + data.error);
        }
      })
      .catch(error => console.error('Error:', error));
    });
  });
  