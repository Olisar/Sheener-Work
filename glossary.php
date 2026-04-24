<?php
/* File: sheener/glossary.php */

$page_title = 'Glossary';
$use_ai_navigator = true;
$user_role = 'User';
$user_id = '';
$user_name = 'User';
$additional_scripts = ['js/glossary.js'];
$additional_stylesheets = ['css/ui-standard.css'];
include 'includes/header.php';
?>



<main class="planner-main-horizontal">
    <div class="table-card">
        <!-- Search box -->
        <div class="standard-header">
            <h1><i class="fas fa-file-alt"></i> Glossary</h1>
            <div class="standard-search">
                <input type="text" id="glossary-search" placeholder="Search by Term or Definition...">
            </div>
        </div>


        <div class="glossary-table-container">
            <table class="glossary-table" id="glossaryTable">
                <thead>
                    <tr>
                        <th scope="col">ID</th>
                        <th scope="col">Term</th>
                        <th scope="col">Definition</th>
                        <th scope="col">Category</th>
                        <th scope="col">Source</th>
                        <th scope="col">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- Dynamically populated rows -->
                </tbody>
            </table>
        </div>


    </div>
</main>

<script>

</script>

<style>
    #glossary-search {
        padding: 5px;
        font-size: 14px;
        width: 450px;
        max-width: 100%;
        border-radius: 5px;
        border: 1px solid #ccc;
    }

    .no-data {
        text-align: center;
        color: #888;
        font-style: italic;
        padding: 20px;
    }

    /* Glossary Table Container and Table Layout */
    .glossary-table-container {
        overflow-x: auto;
    }

    .glossary-table {
        table-layout: fixed;
        width: auto;
        max-width: 900px;
        margin: 0 auto;
        border-collapse: collapse;
    }

    /* Column Widths */
    .glossary-table th:nth-child(1) {
        /* ID */
        width: 50px;
    }

    .glossary-table th:nth-child(2) {
        /* Term */
        min-width: 180px;
        width: 20%;
    }

    .glossary-table th:nth-child(3) {
        /* Definition */
        min-width: 320px;
        width: 35%;
    }

    .glossary-table th:nth-child(4) {
        /* Category */
        width: 120px;
    }

    .glossary-table th:nth-child(5) {
        /* Source */
        width: 120px;
    }

    .glossary-table th:nth-child(6) {
        /* Actions */
        width: 110px;
    }

    /* Cell Text Handling */
    .glossary-table td {
        white-space: normal;
        word-wrap: break-word;
        word-break: break-word;
        border-bottom: 1px solid #ddd;
    }

    /* Prevent wrapping and truncate overflow for ID and Actions */
    .glossary-table td:nth-child(1),
    .glossary-table td:nth-child(6) {
        white-space: nowrap;
    }

    /* Table Cell and Header Styling */
    .glossary-table th,
    .glossary-table td {
        font-size: 12px;
        padding: 8px;
        text-align: left;

    }

    .glossary-table th {
        background-color: #474747ff;
        color: white;
        font-weight: bold;
    }

    /* Row Styling */
    .glossary-table tr:nth-child(even) {
        background-color: #f9f9f9;
    }

    .glossary-table tr:hover {
        background-color: #f1f1f1;
    }

    /* Responsive Adjustments */
    @media (max-width: 768px) {

        .glossary-table th,
        .glossary-table td {
            font-size: 11px;
            padding: 6px;
            border-bottom: none;
        }
    }
</style>

<?php include 'includes/footer.php'; ?>