/* File: sheener/js/edit_waste_collection.js */
document.addEventListener('DOMContentLoaded', function() {
    // Retrieve record id from query parameter
    const params = new URLSearchParams(window.location.search);
    const recordID = params.get('id');
    
    if (recordID) {
      fetch(`php/get_waste_collection.php?id=${recordID}`)
        .then(response => response.json())
        .then(data => {
          if (data) {
            document.getElementById('RecordID').value = data.RecordID;
            document.getElementById('WasteCategoryID').value = data.WasteCategoryID;
            document.getElementById('WasteSubCategoryID').value = data.WasteSubCategoryID || '';
            document.getElementById('Amount').value = data.Amount;
            document.getElementById('UnitID').value = data.UnitID;
            document.getElementById('DisposalDate').value = data.DisposalDate;
            document.getElementById('Comments').value = data.Comments;
          }
        })
        .catch(error => console.error('Error fetching record:', error));
    }
    
    document.getElementById('editCollectionForm').addEventListener('submit', function(e) {
      e.preventDefault();
      
      const formData = new FormData(this);
      
      fetch('php/edit_waste_collection.php', {
        method: 'POST',
        body: formData
      })
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          alert('Waste collection updated successfully!');
          window.location.href = 'list_waste_collections.html';
        } else {
          alert('Error: ' + data.error);
        }
      })
      .catch(error => console.error('Error:', error));
    });
  });
  