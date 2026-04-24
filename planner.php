<?php
/* File: sheener/planner.php */

$page_title = 'Energy List';
$use_ai_navigator = false;
$user_role = 'User';
$user_id = '';
$user_name = 'User';
$additional_scripts = ['https://cdn.jsdelivr.net/npm/fullcalendar@5.11.0/main.min.js', 'js/calendar.js', 'js/commonMenus.js', 'js/panel.js', 'js/script.js'];
include 'includes/header.php';
?>




<main class="planner-main-horizontal"></main>
    <div id="content-main" style="margin-left: 60px;">
        <h2>Calendar and Planner</h2>
        <p>Job List.</p>

        <div class="container">
            <div id="calendar-container">
                <div class="calendar-header">
                    <!-- Navigation Buttons Container -->
                    <div class="nav-buttons-left">
                        <!-- Previous Button -->
                        <button id="prevBtn" class="fc-button nav-btn">◀</button>
                        <!-- Next Button -->
                        <button id="nextBtn" class="fc-button nav-btn">▶</button>
                    </div>

                    <!-- Current Period Display in the Center -->
                    <div class="current-period-display">
                        <span id="currentYear" class="year-display">2024 October</span>
                    </div>

                    <!-- Period Selection Buttons Container -->
                    <div class="period-selection-right">
                        <button id="dayViewBtn" class="fc-button period-btn">Day</button>
                        <button id="weekViewBtn" class="fc-button period-btn">Week</button>
                        <button id="monthViewBtn" class="fc-button period-btn">Month</button>
                    </div>
                </div>

                <div id="calendar"></div>
            </div>
        </div>
    </div>

    <script src="js/calendar.js"></script>
    <script src="js/commonMenus.js"></script> <!-- Handles sidebar and top bar -->
    <script src="js/panel.js"></script>
    <script src="js/script.js"></script>

    <!-- Task Modal for Edit and Add -->
    <div id="taskModal" class="modal" style="display: none;">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h2>Edit Task</h2>
            <form id="editTaskForm" style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                <!-- Task Name -->
                <div class="form-group" style="grid-column: span 2;">
                    <label for="taskName">Task Name:</label>
                    <input type="text" id="taskName" name="taskName" class="form-control">
                </div>

                <!-- Task Description -->
                <div class="form-group" style="grid-column: span 2;">
                    <label for="taskDescription">Task Description:</label>
                    <textarea id="taskDescription" name="taskDescription" class="form-control"></textarea>
                </div>

                <!-- Task Owner -->
                <div class="form-group" style="grid-column: span 2;">
                    <label for="taskOwner">Task Owner:</label>
                    <input type="text" id="taskOwner" name="taskOwner" class="form-control">
                </div>

                <!-- Task Type -->
                <div class="form-group">
                    <label for="taskTypeId">Task Type:</label>
                    <select id="taskTypeId" name="taskTypeId" class="form-control">
                        <option value="">Select Task Type</option>
                        <!-- You could add task types dynamically from JS or pre-fetch them -->
                    </select>
                </div>
                <div class="form-row">
                    <div class="form-group inline-field">
                        <label for="startDate">Start Date:</label>
                        <input type="date" id="startDate" name="startDate" required>
                    </div>

                    <div class="form-group inline-field">
                        <label for="dueDate">Due Date:</label>
                        <input type="date" id="dueDate" name="dueDate" required>
                    </div>
                </div>

                <!-- Status -->
                <div class="form-group" style="grid-column: span 2;">
                    <label for="status">Status:</label>
                    <input type="text" id="status" name="status" class="form-control" value="Pending">
                </div>

                <!-- Save and Close Buttons -->
                <div style="grid-column: span 2; text-align: right;">
                    <button type="submit" class="save-task-button">Save Changes</button>
                    <button type="button" class="btn btn-secondary close-modal">Close</button>
                </div>
            </form>
        </div>
    </div>
    </main>





<style>

</style>


<script>
</script>



<?php include 'includes/footer.php'; ?>
