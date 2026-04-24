<?php
/* File: sheener/primereryelem.php */

$page_title = 'Process List';
$use_ai_navigator = false;
$user_role = 'User';
$user_id = '';
$user_name = 'User';
include 'includes/header.php';
?>



    <main>


        <h2>5Ps+</h2>
        <p>Here you can access the primary elements lists.</p>


        <div class="button-container">
            <button onclick="window.location.href='people_list.php'">People</button>
            <button onclick="window.location.href='material_list.php'">Products</button>
            <button onclick="window.location.href='area_list.php'">Places</button>

            <button onclick="window.location.href='equipment_list.php'">Plants</button>
            <button onclick="window.location.href='SOP_list.php'">Processes</button>
            <button onclick="window.location.href='energy_list.php'">Energies</button>


        </div>


    </main>





    <style>
        /* Center the button container */
        .button-container {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            /* 3 columns */
            grid-gap: 15px;
            /* Space between buttons */
            justify-content: center;
            align-items: center;
            max-width: 600px;
            margin: 40px auto;
            padding: 10px;
        }

        /* Style the buttons */
        .button-container button {
            width: 100%;
            padding: 12px;
            font-size: 16px;
            cursor: pointer;
            border: none;
            background-color: #94c7fd;
            color: white;
            border-radius: 5px;
            transition: 0.3s;
        }

        /* Hover effect */
        .button-container button:hover {
            background-color: #0056b3;
        }

        /* Responsive Design */
        @media (max-width: 600px) {
            .button-container {
                grid-template-columns: repeat(2, 1fr);
                /* 2 columns on smaller screens */
            }
        }

        @media (max-width: 400px) {
            .button-container {
                grid-template-columns: repeat(1, 1fr);
                /* 1 column on very small screens */
            }
        }

        .search-container {
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 4px;
            /* Reduce the gap between label and input */
            justify-content: center;
            /* Center the container */
        }

        #process-search {
            padding: 5px;
            font-size: 14px;
            width: 450px;
            /* Increase width for a longer input box */
            max-width: 100%;
            border-radius: 5px;
            border: 1px solid #ccc;
        }


        .process-menu-container {
            display: flex;
            justify-content: center;
            /* Center the menu */
            align-items: center;
            /* Vertically align the menu */
            margin-top: 20px;
            position: relative;
            /* Allow absolute positioning for children */
        }

        .process-frame {
            position: relative;
            z-index: 1;
            /* Ensure the frame is on top */
            width: 200px;
            /* Adjust the size of the SVG if necessary */
            height: auto;
        }

        .process-links {
            list-style-type: none;
            padding: 0;
            margin: 0;
            position: absolute;
            top: 50%;
            /* Move the list down relative to the SVG */
            left: 45%;
            /* Center horizontally */
            transform: translate(-50%, -50%);
            /* Center the list exactly */
            z-index: 2;
            /* Ensure the links are above the SVG */
            text-align: left;
        }

        .process-links li {
            margin: 8px 0;
            /* Adjust spacing between buttons */
        }

        .process-links a {
            text-decoration: none;
            color: white;
            font-size: 18px;
            /* Slightly smaller text for balance */
            font-weight: bold;
        }

        .process-links a:hover {
            color: lightblue;
        }

        /* Label at the top of the SVG */
        .process-label {
            position: absolute;
            top: 8px;
            /* Adjust the distance above the SVG */
            left: 40%;
            transform: translateX(-50%);
            font-size: 21px;
            font-weight: bold;
            color: white;
            z-index: 2;
        }
    </style>


    <script>
    </script>



<?php include 'includes/footer.php'; ?>
