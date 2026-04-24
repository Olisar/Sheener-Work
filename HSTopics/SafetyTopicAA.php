<?php /* File: sheener/HSTopics/SafetyTopicAA.php */ ?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="shortcut icon" href="../img/favicon/faviconAY.ico" />
    <title>EHS Topics</title>
    <style>
        /* --- Your Original Styles --- */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: Arial, sans-serif;
        }

        body {
            margin: 0;
            padding: 0;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            background: linear-gradient(135deg, #1d1d1dff, #3f3f3fff);
            overflow: hidden;
            color: white;
        }

        .page-header {
            position: fixed;
            top: 40px;
            left: 0;
            width: 100%;
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 1200;
            background: transparent;
            pointer-events: none;
            /* allows clicking things behind it if necessary */
        }

        h2 {
            font-size: 1.8rem;
            text-transform: uppercase;
            color: white;
            margin: 0;
            letter-spacing: 8px;
            font-weight: 900;
            text-shadow: 0 0 10px rgba(0, 0, 0, 0.5);
        }

        .navbar {
            position: absolute;
            top: 0;
            left: 110px;
            width: 240px;
            height: 100vh;
            background: rgba(255, 255, 255, 0.1);
            padding: 20px 10px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            gap: 30px;
            border-right: 1px solid rgba(255, 255, 255, 0.1);
        }

        .site-logo {
            position: absolute;
            top: 30px;
            width: 85%;
            left: 50%;
            transform: translateX(-50%);
            display: block;
            /* Filter to turn white logo to yellow (#fce205) */
            filter: brightness(0) saturate(100%) invert(88%) sepia(82%) saturate(3475%) hue-rotate(357deg) brightness(102%) contrast(104%);
        }

        .navbar a {
            position: relative;
            display: inline-block;
            text-decoration: none;
            background: transparent;
            color: white;
            font-size: 1.15rem;
            text-transform: uppercase;
            padding: 7px 20px;
            border-radius: 5px;
            transition: background 0.2s, transform 0.2s;
            width: 90%;
            text-align: left;
            box-sizing: border-box;
        }

        .navbar a:hover {
            background: rgba(255, 174, 0, 1);
            transform: scale(1.5);
        }

        .navbar a span {
            display: inline-block;
            transition: transform 0.2s;
        }

        .navbar a:hover span {
            transform: scale(0.733);
        }

        #svg-container {
            position: relative;
            display: flex;
            justify-content: center;
            align-items: center;
            width: 50%;
        }

        #svg-container::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 240px;
            height: 20px;
            background: radial-gradient(ellipse at center, rgba(0, 0, 0, 0.9), transparent);
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
            margin: 0;
            display: inline-flex;
            align-items: center;
            justify-content: center;
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
                filter: drop-shadow(0 0 5px #fce205) drop-shadow(0 0 10px rgba(252, 226, 5, 0.6));
            }
        }

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
            color: #ffae00ff;
        }

        /* --- Modal overlay styles --- */
        .topics-modal {
            display: none;
            position: fixed;
            color: white;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -49%);
            background: #3434355b;
            /* Muted Navy - Professional Choice */
            border: 1px solid #343435ff;
            /* Subtle yellow border to tie in with branding */
            border-radius: 12px;
            padding: 32px 26px 20px 26px;
            box-shadow: 0 12px 60px rgba(0, 0, 0, 0.5);
            /* Enhanced depth */
            z-index: 2500;
            min-width: 350px;
            min-height: 120px;
            flex-direction: column;
            gap: 18px;
            align-items: center;
            backdrop-filter: blur(8px);
            /* Modern glassmorphism touch */
        }

        .topics-modal.active {
            display: flex;
        }

        .thumbnails-row {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            justify-content: center;

        }

        .thumbnail-link {
            display: flex;
            color: #1A2B3C;
            /* Match container background for branding consistency */
            flex-direction: column;
            align-items: center;
            text-decoration: none;

            background: #fce205;
            /* Solid yellow to pop against navy */
            border-radius: 10px;
            padding: 10px;
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
            width: 110px;
            min-height: 120px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
            position: relative;
        }

        .thumbnail-link:hover {
            background: #ffffff;
            /* Turn white on hover for a premium 'active' feel */
            color: #1A2B3C;
            transform: translateY(-8px) scale(1.05);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.3);
        }

        .thumbnail-img {
            width: 82px;
            height: 72px;
            object-fit: contain;

            border-radius: 7px;
            margin-bottom: 7px;
        }

        .thumbnail-label {
            font-size: 0.95rem;
            text-align: center;
        }
    </style>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
</head>

<body>
    <header class="page-header">
        <h2>EHS Topics</h2>
    </header>


    <div class="navbar">
        <img src="../img/Amneal_Logo_new.svg" class="site-logo" title="Amneal">
        <a class="main-topic" data-topic="safety">Health and Safety</a>
        <a class="main-topic" data-topic="energy">Energy</a>
        <a class="main-topic" data-topic="environment">Environmental</a>
    </div>

    <div class="top-right-icons">
        <a class="previous-icon" title="Go Back" style="font-size: 2.5rem;"><i class="fas fa-arrow-left"></i></a>
    </div>
    <div id="svg-container">
        <?php echo file_get_contents("../img/amneal_A_Logo_new1.svg"); ?>
    </div>
    <!-- Modal Overlay -->
    <div id="topics-modal" class="topics-modal">
        <span id="modal-title" style="font-weight:bold; font-size:1.15rem; margin-bottom:2px;"></span>
        <div class="thumbnails-row" id="thumbnails-row"></div>
    </div>
    <script>
        document.getElementById("loginButton")?.addEventListener("click", function () {
            window.location.href = "../index.php";
        });

        document.querySelector(".previous-icon").addEventListener("click", function () {
            window.history.back();
        });

        const topicsData = {
            safety: {
                title: "Health and Safety Topics",
                thumbnails: [{
                    href: "SafetyTopicAmnealAccess.html",
                    img: "../img/AmnealBuilding.jpg",
                    label: "Site Access"
                },
                {
                    href: "SafetyTopicStomacBug.html",
                    img: "../img/bug.jpg",
                    label: "Stomac Bug"
                }, // To be added
                {
                    href: "SafetyTopicWinterDriving.html",
                    img: "../img/image.png",
                    label: "Winter Driving"
                },
                {
                    href: "SafetyTopicElectrical.html",
                    img: "../img/ChristmasCard.jpg",
                    label: "Electrical"
                },
                {
                    href: "SafetyTopicSkinCancer.html",
                    img: "../img/IrishCancerBgnd1.jpg",
                    label: "Skin Cancer"
                },
                {
                    href: "SafetyTopicEHSDuties.html",
                    img: "../img/HSA.jpg",
                    label: "Duty of Care"
                }, // To be added
                {
                    href: "SafetyTopicRolesResp.html",
                    img: "../img/HS0.png",
                    label: "Roles & Responsibilities"
                }, // To be added
                {
                    href: "SafetyTopicAmnealDoorFingers.html",
                    img: "../img/doorjam.jpg",
                    label: "Door Jam"
                },
                {
                    href: "SafetyFireSafetyCharging.html",
                    img: "../img/img3.jpg",
                    label: "Charging"
                },
                {
                    href: "SafetyTopicEvac.html",
                    img: "../img/evac3.png",
                    label: "Evacuation"
                } // To be added
                ]
            },
            energy: {
                title: "Energy Topics",
                thumbnails: [
                    {
                        href: "SafetyTopicEnergy1.html",
                        img: "../img/compressedair.jpg",
                        label: "Compressed Air"
                    } // To be added
                    ,
                    {
                        href: "SafetyTopicEnergy2.html",
                        img: "../img/energyenergia.png",
                        label: "Energy Optimisation"
                    }
                ]
            },
            environment: {
                title: "Environmental Topics",
                thumbnails: [{
                    href: "SafetyTopicEnvir1.html",
                    img: "../img/img0.png",
                    label: "Carbon footprint"
                },
                {
                    href: "SafetyTopicEnvirWasteWater.html",
                    img: "../img/wastewat.jpg",
                    label: "Waste Water"
                },
                {
                    href: "SafetyTopicPlastic.html",
                    img: "../img/plastic.jpg",
                    label: "Plastic Waste"
                }
                ]
            }
        };

        const topicBtns = document.querySelectorAll('.main-topic');
        const modal = document.getElementById('topics-modal');
        const thumbnailsRow = document.getElementById('thumbnails-row');
        const modalTitle = document.getElementById('modal-title');
        let hideTimer = null;
        let activeBtn = null;
        let currentTopic = null;

        // Helper to populate and show modal
        function showModal(topic, btn) {
            // If already showing this topic, just clear hide timer
            if (currentTopic === topic && modal.classList.contains('active')) {
                if (hideTimer) {
                    clearTimeout(hideTimer);
                    hideTimer = null;
                }
                activeBtn = btn;
                return;
            }

            currentTopic = topic;
            activeBtn = btn;
            const data = topicsData[topic];
            if (!data) return;

            modalTitle.textContent = data.title;

            // Build content fragment for better performance and reduced flicker
            const fragment = document.createDocumentFragment();
            data.thumbnails.forEach(t => {
                const link = document.createElement('a');
                link.className = 'thumbnail-link';
                link.href = t.href;
                link.innerHTML = `<img src="${t.img}" class="thumbnail-img" alt="${t.label}" loading="lazy" width="82" height="72">
                                  <span class="thumbnail-label">${t.label}</span>`;
                fragment.appendChild(link);
            });

            thumbnailsRow.innerHTML = '';
            thumbnailsRow.appendChild(fragment);
            modal.classList.add('active');
        }

        // Listen for mouse movement between button and modal
        document.addEventListener('mousemove', function (e) {
            if (!activeBtn || !modal.classList.contains('active')) return;

            const btnRect = activeBtn.getBoundingClientRect();
            const modalRect = modal.getBoundingClientRect();
            const x = e.clientX;
            const y = e.clientY;

            // Compute corridor - the area between the button and the modal
            // Expanded to be more forgiving
            const corridor = {
                left: Math.min(btnRect.left, modalRect.left) - 50,
                right: Math.max(btnRect.right, modalRect.right) + 50,
                top: Math.min(btnRect.top, modalRect.top) - 50,
                bottom: Math.max(btnRect.bottom, modalRect.bottom) + 50
            };

            const insideModal = pointInRect(x, y, modalRect);
            const insideBtn = pointInRect(x, y, btnRect);
            const insideCorridor = pointInRect(x, y, corridor);

            // If outside modal, button, and corridor, start hide timer
            if (!insideModal && !insideBtn && !insideCorridor) {
                if (!hideTimer) {
                    hideTimer = setTimeout(() => {
                        modal.classList.remove('active');
                        currentTopic = null;
                        activeBtn = null;
                        hideTimer = null;
                    }, 400); // Slightly faster close but more stable
                }
            } else {
                // If back inside any safe zone, clear timer
                if (hideTimer) {
                    clearTimeout(hideTimer);
                    hideTimer = null;
                }
            }
        });

        function pointInRect(x, y, rect) {
            return (x >= rect.left && x <= rect.right && y >= rect.top && y <= rect.bottom);
        }

        // Events for topic buttons
        topicBtns.forEach(btn => {
            btn.addEventListener('mouseenter', e => {
                showModal(e.currentTarget.dataset.topic, btn);
            });
        });

        // Modal events
        modal.addEventListener('mouseenter', () => {
            if (hideTimer) {
                clearTimeout(hideTimer);
                hideTimer = null;
            }
        });

        document.addEventListener('mousedown', function (e) {
            if (!modal.classList.contains('active')) return;
            if (modal.contains(e.target) || Array.from(topicBtns).some(btn => btn.contains(e.target))) {
                return;
            }
            modal.classList.remove('active');
            currentTopic = null;
            activeBtn = null;
            if (hideTimer) {
                clearTimeout(hideTimer);
                hideTimer = null;
            }
        });
    </script>
</body>

</html>
