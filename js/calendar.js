/* File: sheener/js/calendar.js */
document.addEventListener('DOMContentLoaded', function() {
    var calendarEl = document.getElementById('calendar');

    var calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: 'dayGridMonth',
        headerToolbar: false,
        editable: true,
        selectable: true,
        dayMaxEvents: true,
        events: function(fetchInfo, successCallback, failureCallback) {
            fetch('php/fetchTasks.php')
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok ' + response.statusText);
                    }
                    return response.json();
                })
                .then(data => {
                    console.log('Fetched Tasks:', data); // Display the fetched tasks in the console
                    successCallback(data);
                })
                .catch(error => {
                    console.error('Error fetching tasks:', error);
                    failureCallback(error);
                });
        },
        eventClick: function(info) {
            info.jsEvent.preventDefault(); // Prevent default behavior like following links
            openTaskModal(info.event); // Pass the clicked event to openTaskModal
        },
        dateClick: function(info) {
            // Open the task modal and pre-fill the selected date for start and due dates
            openAddTaskModal(info.dateStr);
        }
    });

    calendar.render();

    // Navigation and view buttons setup
    const prevBtn = document.getElementById('prevBtn');
    const nextBtn = document.getElementById('nextBtn');
    const dayViewBtn = document.getElementById('dayViewBtn');
    const weekViewBtn = document.getElementById('weekViewBtn');
    const monthViewBtn = document.getElementById('monthViewBtn');
    const currentYearEl = document.getElementById('currentYear');

    // Add event listeners for navigation buttons
    prevBtn.addEventListener('click', function() {
        calendar.prev();
        updatePeriodDisplay();
    });

    nextBtn.addEventListener('click', function() {
        calendar.next();
        updatePeriodDisplay();
    });

    dayViewBtn.addEventListener('click', function() {
        calendar.changeView('timeGridDay');
        updatePeriodDisplay();
    });

    weekViewBtn.addEventListener('click', function() {
        calendar.changeView('timeGridWeek');
        updatePeriodDisplay();
    });

    monthViewBtn.addEventListener('click', function() {
        calendar.changeView('dayGridMonth');
        updatePeriodDisplay();
    });

    function updatePeriodDisplay() {
        const currentView = calendar.view;
        const currentDate = currentView.currentStart;

        let displayText = '';

        if (currentView.type === 'dayGridMonth') {
            const month = currentDate.toLocaleString('default', { month: 'long' });
            displayText = `${currentDate.getFullYear()} ${month}`;
        } else if (currentView.type === 'timeGridWeek') {
            const month = currentDate.toLocaleString('default', { month: 'long' });
            displayText = `${currentDate.getFullYear()} ${month} - Week ${getWeekNumber(currentDate)}`;
        } else if (currentView.type === 'timeGridDay') {
            const month = currentDate.toLocaleString('default', { month: 'long' });
            const day = currentDate.getDate();
            displayText = `${currentDate.getFullYear()} ${month} ${day}`;
        }

        currentYearEl.textContent = displayText;
    }

    function getWeekNumber(date) {
        const startOfYear = new Date(date.getFullYear(), 0, 1);
        const pastDaysOfYear = (date - startOfYear) / 86400000;

        return Math.ceil((pastDaysOfYear + startOfYear.getDay() + 1) / 7);
    }

    updatePeriodDisplay();




         // Populate Task Types Dropdown
         function populateTaskTypesDropdown() {
            fetch('php/fetchTaskTypes.php')
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok ' + response.statusText);
                    }
                    return response.json();
                })
                .then(data => {
                    console.log('Fetched Task Types:', data); // Log fetched data to see if it's coming correctly
                    const taskTypeDropdown = document.getElementById('taskTypeId');
                    if (!taskTypeDropdown) {
                        console.error('Task Type dropdown not found in the document.');
                        return;
                    }
                    
                    // Clear dropdown
                    taskTypeDropdown.innerHTML = '<option value="">Select Task Type</option>';
                    
                    if (Array.isArray(data) && data.length > 0) {
                        data.forEach(taskType => {
                            const option = document.createElement('option');
                            option.value = taskType.task_type_id; // Make sure these keys match the response structure
                            option.textContent = taskType.type_name; // Make sure these keys match the response structure
                            taskTypeDropdown.appendChild(option);
                        });
                    } else {
                        console.error('Task Types data is not in expected format or empty');
                    }
                })
                .catch(error => {
                    console.error('Error fetching task types:', error);
                });
        }
        


        // Open Add Task Modal with Date Pre-filled
        function openAddTaskModal(dateStr) {
            const modal = document.getElementById('taskModal');
            if (!modal) {
                console.error('Modal element not found. Please ensure the modal exists in the HTML.');
                alert('Unable to add task because the add modal could not be found. Please contact the system administrator.');
                return;
            }

            // Set the modal visible and properly center it
            modal.style.display = 'flex'; // Display modal as flex for centering
            modal.style.justifyContent = 'center';
            modal.style.alignItems = 'center';
            console.log(`Opening modal for adding task on date: ${dateStr}`);

            // Clear existing form data
            document.getElementById('taskName').value = '';
            document.getElementById('taskDescription').value = '';
            document.getElementById('taskOwner').value = '';
            document.getElementById('taskTypeId').value = '';
            document.getElementById('startDate').value = dateStr;
            document.getElementById('dueDate').value = dateStr;
            document.getElementById('status').value = 'Pending';

            populateTaskTypesDropdown(); // Populate task types dropdown when adding a task
        }




        
        



    // Function to open the add task modal with pre-filled start and due dates
    function openAddTaskModal(selectedDate) {
        const modal = document.getElementById('taskModal');
        if (!modal) {
            console.error('Modal element not found. Please ensure the modal exists in the HTML.');
            alert('Unable to add task because the modal could not be found. Please contact the system administrator.');
            return;
        }

        // Set the modal visible and properly center it
        modal.style.display = 'flex';
        modal.style.justifyContent = 'center';
        modal.style.alignItems = 'center';
        console.log(`Opening modal for adding task on: ${selectedDate}`);

        // Clear existing values in the form
        document.getElementById('taskName').value = '';
        document.getElementById('taskDescription').value = '';
        document.getElementById('taskOwner').value = '';
        document.getElementById('taskTypeId').value = '';
        document.getElementById('startDate').value = selectedDate;
        document.getElementById('dueDate').value = selectedDate;
        document.getElementById('status').value = 'Pending';

   // Submit handler to add task via saveTask.php
document.getElementById('editTaskForm').onsubmit = function(event) {
    event.preventDefault(); // Prevent form from submitting the default way

    const taskData = {
        task_name: document.getElementById('taskName').value,
        task_description: document.getElementById('taskDescription').value,
        task_owner: document.getElementById('taskOwner').value,
        task_type_id: document.getElementById('taskTypeId').value,
        start_date: document.getElementById('startDate').value,
        due_date: document.getElementById('dueDate').value,
        status: document.getElementById('status').value
    };

    fetch('php/saveTask.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(taskData)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            calendar.refetchEvents(); // Refresh calendar events after adding the task
            modal.style.display = 'none'; // Close the modal
        } else {
            alert('Failed to add task: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error adding task:', error);
        alert('An error occurred while adding the task.');
    });
};

    }
});
