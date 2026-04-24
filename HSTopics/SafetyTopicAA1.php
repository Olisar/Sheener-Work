<?php /* File: sheener/HSTopics/SafetyTopicAA1.php */ ?>
<!DOCTYPE html>
<html lang="en">
<!-- sheener/SafetyTopicAA.php -->

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="shortcut icon" href="../img/favicon/faviconAY.ico">
    <title>EHS Energy Topics</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: Arial, sans-serif;
        }

        body {
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            background: linear-gradient(135deg, #001f4d, #003366);
            overflow: hidden;
            color: white;
        }

        h2 {
            position: absolute;
            top: 9%;
            left: 50%;
            transform: translate(-50%, -50%);
            font-size: 2rem;
            text-transform: uppercase;
            color: white;
        }

        .navbar {
            position: absolute;
            top: 0;
            left: 110px;
            width: 240px;
            height: 100vh;
            background: rgba(255, 255, 255, 0.2);
            padding: 20px 10px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            gap: 15px;
        }

        .navbar a {
            position: relative;
            display: inline-block;
            text-decoration: none;
            background: transparent;
            color: white;
            font-size: 1rem;
            text-transform: uppercase;
            padding: 7px 20px;
            border-radius: 5px;
            transition: background 0.2s, transform 0.2s;
            width: 90%;
            text-align: left;
            box-sizing: border-box;
        }

        .navbar a:hover {
            background: rgba(255, 174, 0, 0.72);
            transform: scale(1.5);
        }

        .navbar a span {
            display: inline-block;
            transition: transform 0.2s;
        }

        .navbar a:hover span {
            transform: scale(0.733);
            /* 1.1 / 1.5 ≈ 0.733 (to make text appear 1.1x) */
        }


        #svg-container {
            position: relative;
            display: flex;
            justify-content: center;
            align-items: center;
            width: 50%;
            /* Adjust as needed */
        }

        #svg-container::before {
            content: '';
            position: absolute;
            width: 310px;
            height: 7%;
            /* Shadow proportional to container height */
            background: radial-gradient(ellipse at center, rgba(0, 0, 0, 0.3), transparent);
            bottom: 23%;
            /* 20% below the bottom of the SVG */
            left: 50%;
            transform: translateX(-50%);
            z-index: -1;
            filter: blur(10px);
            pointer-events: none;
        }

        svg {
            position: relative;
            fill: #fce205;
            animation: animate 5s linear infinite;
        }

        .banner-button {
            padding: 10px 20px;
            background-color: #3685fe;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            /* 👈 Force a more visible font size */
            margin: 0;
            /* 👈 Reset margin */
            display: inline-flex;
            /* 👈 Better for centering if needed */
            align-items: center;
            /* 👈 Optional: Vertical center */
            justify-content: center;
            /* 👈 Optional: Horizontal center */
        }


        .banner-button:hover {
            background-color: #276ac8;
        }

        @keyframes animate {

            0%,
            18%,
            20%,
            50.1%,
            60%,
            65.1%,
            80%,
            90.1%,
            92% {
                fill: #fce205;
                filter: none;
            }

            18.1%,
            20.1%,
            30%,
            50%,
            60.1%,
            65%,
            80.1%,
            90%,
            92.1%,
            100% {
                fill: #fce205;
                filter: drop-shadow(0 0 10px #fce205) drop-shadow(0 0 20px #fce205) drop-shadow(0 0 40px rgba(252, 226, 5, 0.4));
            }
        }

        /* Styles for the previous icon */
        .top-right-icons {
            position: fixed;
            top: 10px;
            right: 20px;
            display: flex;
            gap: 15px;
            z-index: 1000;
        }

        .previous-icon,
        .home-icon {
            font-size: 2rem;
            color: white;
            text-decoration: none;
            cursor: pointer;
            transition: color 0.2s;
        }

        .previous-icon:hover,
        .home-icon:hover {
            color: #03bcf4;
        }
    </style>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
</head>

<body>
    <h2>EHS Energy Topics</h2>
    <div class="navbar">
        <a href="SafetyTopicAmnealAccess.html"><span>Site Access</span></a>
        <a href="SafetyTopicStomacBug.html"><span>Stomac Bug</span></a>
        <a href="SafetyTopicWinterDriving.html"><span>Winter Driving</span></a>
        <a href="SafetyTopicElectrical.html"><span>Electrical</span></a>
        <a href="SafetyTopicSkinCancer.html"><span>Skin Cancer</span></a>
        <a href="SafetyTopicEHSDuties.html"><span>Dutiy of Care</span></a>
        <a href="SafetyTopicRolesResp.html"><span>Roles and Responsibilities</span></a>
        <a href="SafetyTopicAmnealDoorFingers.html"><span>Door jam</span></a>
        <a href="SafetyTopicEnvir1.html"><span>Carbon footprint</span></a>
        <a href="SafetyFireSafetyCharging.html"><span>Charging</span></a>
        <a href="SafetyTopicEvac.html"><span>Evacuation</span></a>
        <a href="SafetyTopicEnvirWasteWater.html"><span>Waste Water</span></a>

    </div>
    <div class="top-right-icons">
        <a class="previous-icon" onclick="history.back(); return false;" title="Go Back"><i
                class="fas fa-arrow-left"></i></a>
        <button id="loginButton" class="banner-button">Access SHEEner</button>


    </div>
    <div id="svg-container">
        <?php echo file_get_contents("../img/Amneal_A_Logo_new.svg"); ?>
    </div>





    <script>
        document.getElementById("loginButton").addEventListener("click", function () {
            window.location.href = "../index.php"; // Redirect to index.php
        });
    </script>
</body>

</html>
