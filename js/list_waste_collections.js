/* File: sheener/js/list_waste_collections.js */
document.addEventListener('DOMContentLoaded', function() {
    fetch('php/list_waste_collections.php')
      .then(response => response.json())
      .then(data => {
        const tbody = document.querySelector('#wasteCollectionsTable tbody');
        tbody.innerHTML = '';
        data.forEach(item => {
          const tr = document.createElement('tr');
          tr.innerHTML = `
            <td>${item.RecordID}</td>
            <td>${item.WasteCategoryID}</td>
            <td>${item.WasteSubCategoryID ? item.WasteSubCategoryID : ''}</td>
            <td>${item.Amount}</td>
            <td>${item.UnitName ? item.UnitName : ''}</td>
            <td>${item.DisposalDate}</td>
            <td>${item.Comments}</td>
            <td class="actions-cell">
              <div class="action-buttons-wrapper">
                <button class="btn-table-action btn-view" title="View" onclick="window.location.href='view_waste_collection.html?id=${item.RecordID}'">
                  <i class="fas fa-eye"></i>
                </button>
                <button class="btn-table-action btn-edit" title="Edit" onclick="window.location.href='edit_waste_collection.html?id=${item.RecordID}'">
                  <i class="fas fa-edit"></i>
                </button>
                <button class="btn-table-action btn-delete" title="Delete" onclick="deleteWasteCollection(${item.RecordID})">
                  <i class="fas fa-trash"></i>
                </button>
              </div>
            </td>
          `;
          tbody.appendChild(tr);
        });
      })
      .catch(error => console.error('Error fetching waste collections:', error));
  });

  function deleteWasteCollection(id) {
    if (!confirm('Are you sure you want to delete this waste collection record?')) return;
    
    fetch('php/list_waste_collections.php?id=' + id, {
      method: 'DELETE'
    })
    .then(response => response.json())
    .then(data => {
      if (data.success) {
        window.location.reload();
      } else {
        alert('Error deleting record: ' + (data.error || 'Unknown error'));
      }
    })
    .catch(error => {
      console.error('Error:', error);
      alert('Network error deleting record');
    });
  }
  