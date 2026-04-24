// File: sheener/js/planner.js

document.addEventListener('DOMContentLoaded', function () {
    const calendar = document.getElementById('calendar');
    const currentDateRange = document.getElementById('currentDateRange');
    const prevWeekBtn = document.getElementById('prevWeekBtn');
    const nextWeekBtn = document.getElementById('nextWeekBtn');
    const modal = document.getElementById('taskEditModal');
    const closeModal = document.querySelector('.close-btn');
    const saveTaskBtn = document.getElementById('saveTaskBtn');

    let currentDate = new Date();

    // Render initial calendar view
    renderCalendar(currentDate);
    modal.style.display = 'none';

    // Event listeners for navigation buttons
    prevWeekBtn.addEventListener('click', () => {
        currentDate.setDate(currentDate.getDate() - 7);
        renderCalendar(currentDate);
    });

    nextWeekBtn.addEventListener('click', () => {
        currentDate.setDate(currentDate.getDate() + 7);
        renderCalendar(currentDate);
    });

    function renderCalendar(date) {
        calendar.innerHTML = '';
        const startOfWeek = getStartOfWeek(date);
        const endOfWeek = new Date(startOfWeek);
        endOfWeek.setDate(startOfWeek.getDate() + 6);

        // Display date range
        currentDateRange.textContent = `${formatDateWithoutDay(startOfWeek)} - ${formatDateWithoutDay(endOfWeek)}`;

        const daysRow = document.createElement('div');
        daysRow.className = 'days-row';

        // Store date objects for each day cell
        const dayDates = [];
        
        for (let i = 0; i < 7; i++) {
            const dayDate = new Date(startOfWeek);
            dayDate.setDate(startOfWeek.getDate() + i);
            dayDates.push(dayDate);

            const dayCell = document.createElement('div');
            dayCell.className = 'day-cell';
            dayCell.dataset.date = dayDate.toISOString().split('T')[0]; // Store as YYYY-MM-DD

            const dayTitle = document.createElement('div');
            dayTitle.className = 'day-title';
            dayTitle.textContent = formatDate(dayDate);
            dayCell.appendChild(dayTitle);

            dayCell.addEventListener('click', () => openTaskCreateModal(dayDate));

            const timeSlotsContainer = document.createElement('div');
            timeSlotsContainer.className = 'time-slots-container';
            dayCell.appendChild(timeSlotsContainer);
            daysRow.appendChild(dayCell);
        }

        calendar.appendChild(daysRow);
        fetchTasksAndRender(startOfWeek, endOfWeek);
    }

    function openTaskCreateModal(date) {
        // Reset form and set date
        document.getElementById('taskEditForm').reset();
        if (date) {
            const dateStr = date.toISOString().split('T')[0];
            document.getElementById('start_date').value = dateStr;
        }
        document.getElementById('task_id').value = '';
        modal.style.display = 'block';
    }

    function openTaskEditModal(taskId) {
        fetch(`php/get_all_tasks.php?task_id=${taskId}`)
            .then(response => response.json())
            .then(data => {
                if (!data.success || !data.data) {
                    alert('Task not found.');
                    return;
                }

                const task = data.data;
                
                // Populate form fields
                document.getElementById('task_id').value = task.task_id || '';
                document.getElementById('task_name').value = task.task_name || '';
                document.getElementById('description').value = task.task_description || '';
                
                // Format dates for input fields (YYYY-MM-DD)
                const formatDateForInput = (dateStr) => {
                    if (!dateStr) return '';
                    // Handle different date formats
                    const date = new Date(dateStr);
                    if (isNaN(date.getTime())) return '';
                    return date.toISOString().split('T')[0];
                };
                
                document.getElementById('start_date').value = formatDateForInput(task.start_date);
                document.getElementById('finish_date').value = formatDateForInput(task.finish_date);
                document.getElementById('priority').value = task.priority || 'Medium';
                document.getElementById('department').value = task.DepartmentName || task.department_id || '';
                document.getElementById('assigned_to').value = task.assigned_to || '';
                
                // Show modal
                modal.style.display = 'block';
            })
            .catch(error => {
                console.error('Error fetching task details:', error);
                alert('Error loading task details');
            });
    }

    closeModal.onclick = function () {
        modal.style.display = 'none';
    };

    window.onclick = function (event) {
        if (event.target === modal) {
            modal.style.display = 'none';
        }
    };

    saveTaskBtn.onclick = function () {
        saveTaskChanges();
    };

    function saveTaskChanges() {
        const taskId = document.getElementById('task_id').value;
        const taskName = document.getElementById('task_name').value;
        const description = document.getElementById('description').value;
        const startDate = document.getElementById('start_date').value;
        const finishDate = document.getElementById('finish_date').value;
        const priority = document.getElementById('priority').value;
        const department = document.getElementById('department').value;
        const assignedTo = document.getElementById('assigned_to').value;

        // Validate required fields
        if (!taskName || !startDate) {
            alert('Please fill in required fields: Task Name and Start Date');
            return;
        }

        // Prepare form data matching PHP endpoint expectations
        const formData = new FormData();
        if (taskId) formData.append('task_id', taskId);
        formData.append('task_name', taskName);
        formData.append('task_description', description);
        formData.append('start_date', startDate);
        formData.append('finish_date', finishDate || '');
        formData.append('priority', priority);
        formData.append('status', 'Pending'); // Default status
        formData.append('department_id', department || '');
        formData.append('assigned_to', assignedTo || '');

        // Determine if this is a new task or update
        const url = taskId ? 'php/update_task.php' : 'php/create_task.php';

        fetch(url, {
            method: 'POST',
            body: formData
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    modal.style.display = 'none';
                    // Refresh calendar to show updated task
                    renderCalendar(currentDate);
                    alert('Task saved successfully!');
                } else {
                    alert('Error saving task: ' + (data.error || 'Unknown error'));
                }
            })
            .catch(error => {
                console.error('Error saving task:', error);
                alert('Error saving task');
            });
    }

    function getStartOfWeek(date) {
        const start = new Date(date);
        const day = start.getDay();
        const diff = start.getDate() - day + (day === 0 ? -6 : 1);
        start.setDate(diff);
        start.setHours(0, 0, 0, 0); // Reset time to midnight
        return start;
    }

    function formatDateToDDMMYYYY(dateString) {
        const date = new Date(dateString);
        const day = String(date.getDate()).padStart(2, '0');
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const year = date.getFullYear();
        return `${day}-${month}-${year}`;
    }

    function formatDate(date) {
        const daysOfWeek = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
        const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];

        const dayOfWeek = daysOfWeek[date.getDay()];
        const day = date.getDate().toString().padStart(2, '0');
        const month = months[date.getMonth()];
        const year = date.getFullYear().toString().slice(-2);

        return `${dayOfWeek} ${day}-${month}-${year}`;
    }

    function formatDateWithoutDay(date) {
        const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];

        const day = date.getDate().toString().padStart(2, '0');
        const month = months[date.getMonth()];
        const year = date.getFullYear().toString().slice(-2);

        return `${day}-${month}-${year}`;
    }

    // ================= FIXED VERSION =================
    function fetchTasksAndRender(startOfWeek, endOfWeek) {
        // Set to true for debugging, false for production
        const DEBUG_MODE = false;
        
        if (DEBUG_MODE) {
            console.log('🔍 DEBUG: fetchTasksAndRender called');
            console.log('🔍 Week start:', startOfWeek.toISOString());
            console.log('🔍 Week end:', endOfWeek.toISOString());
        }
        
        // Clear existing tasks
        document.querySelectorAll('.time-slots-container').forEach(container => {
            container.innerHTML = '';
        });

        fetch('php/taskfetch.php')
            .then(response => response.json())
            .then(tasks => {
                if (DEBUG_MODE) {
                    console.log('🔍 Total tasks from server:', tasks.length);
                }
                
                if (!Array.isArray(tasks)) {
                    console.error('❌ Expected array but got:', typeof tasks);
                    return;
                }

                let renderedCount = 0;
                let skippedCount = 0;
                
                tasks.forEach((task, index) => {
                    // Parse date safely
                    const startDateStr = task.start_date ? task.start_date.split(' ')[0] : null;
                    const startDate = startDateStr ? new Date(startDateStr + 'T00:00:00') : null;
                    
                    // Skip tasks with invalid or null dates
                    if (!startDate || isNaN(startDate.getTime())) {
                        skippedCount++;
                        if (DEBUG_MODE) {
                            console.warn(`⚠️ Skipping task ${task.task_id} - invalid or missing start_date:`, task.start_date);
                        }
                        return;
                    }

                    // Normalize times for comparison
                    const taskDateOnly = new Date(startDate.getFullYear(), startDate.getMonth(), startDate.getDate());
                    const weekStartOnly = new Date(startOfWeek.getFullYear(), startOfWeek.getMonth(), startOfWeek.getDate());
                    const weekEndOnly = new Date(endOfWeek.getFullYear(), endOfWeek.getMonth(), endOfWeek.getDate());

                    const isInRange = taskDateOnly >= weekStartOnly && taskDateOnly <= weekEndOnly;
                    
                    if (DEBUG_MODE) {
                        console.log(`🔍 Task ${task.task_id}:`, {
                            taskDate: taskDateOnly.toISOString(),
                            weekStart: weekStartOnly.toISOString(),
                            weekEnd: weekEndOnly.toISOString(),
                            inRange: isInRange
                        });
                    }

                    if (isInRange) {
                        renderTaskOnCalendar(task, startDate);
                        renderedCount++;
                    }
                });
                
                if (DEBUG_MODE) {
                    console.log(`✅ Rendered ${renderedCount} of ${tasks.length} tasks (${skippedCount} skipped due to invalid dates)`);
                }
            })
            .catch(error => console.error('❌ Error fetching tasks:', error));
    }

    // ================= FIXED VERSION =================
    function renderTaskOnCalendar(task, taskDate) {
        // Set to true for debugging, false for production
        const DEBUG_MODE = false;
        
        if (DEBUG_MODE) {
            console.log('🎨 Rendering task:', task.task_id, 'on date:', taskDate.toISOString());
        }
        
        // Find day cell by data attribute instead of string matching
        const dateString = taskDate.toISOString().split('T')[0];
        const dayCell = document.querySelector(`.day-cell[data-date="${dateString}"]`);
        
        if (!dayCell) {
            if (DEBUG_MODE) {
                console.warn(`⚠️ No day cell found for date: ${dateString}`);
            }
            return;
        }

        const timeSlotsContainer = dayCell.querySelector('.time-slots-container');
        if (!timeSlotsContainer) {
            console.error('❌ No time-slots-container found in day cell');
            return;
        }

        const taskElement = document.createElement('div');
        taskElement.className = 'task';
        taskElement.textContent = `${task.task_name} (${task.priority})`;
        taskElement.dataset.taskId = task.task_id;
        taskElement.onclick = () => openTaskEditModal(task.task_id);
        
        timeSlotsContainer.appendChild(taskElement);
        
        if (DEBUG_MODE) {
            console.log(`✅ Task ${task.task_id} rendered successfully`);
        }
    }
});