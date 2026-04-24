<?php
/* File: sheener/includes/6ps_switcher.php */

/**
 * 6Ps Switcher Component
 * Reusable navigation component for switching between 6Ps pages
 * Usage: <?php include 'includes/6ps_switcher.php'; ?>
 */
?>
<div class="button-container">
    <button onclick="window.location.href='people_list.php'">People</button>
    <button onclick="window.location.href='material_list.php'">Products</button>
    <button onclick="window.location.href='area_list.php'">Places</button>
    <button onclick="window.location.href='equipment_list.php'">Plants</button>
    <button onclick="window.location.href='sop_list.php'">Procedures</button>
    <button onclick="window.location.href='energy_list.php'">Power</button>
</div>

<style>
    .button-container {
        display: grid;
        grid-template-columns: repeat(6, 1fr);
        grid-gap: 10px;
        justify-content: center;
        align-items: center;
        max-width: 700px;
        margin: 20px auto;
        padding: 10px;
    }

    .button-container button {
        width: 100%;
        padding: 12px;
        font-size: 12px;
        cursor: pointer;
        border: none;
        background-color: #94c7fd;
        color: white;
        border-radius: 5px;
        transition: 0.3s;
    }

    .button-container button:hover {
        background-color: #0056b3;
    }

    @media (max-width: 600px) {
        .button-container {
            grid-template-columns: repeat(2, 1fr);
        }
    }
</style>

