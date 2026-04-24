<!-- file name sheener/index.php -->
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SHEEner MS - Risk Management System</title>
    <link rel="stylesheet" href="css/styles.css">
    <link rel="stylesheet" href="css/buttons.css">
    <link rel="icon" href="img/favicon/faviconAY.ico" type="image/x-icon">

    <!-- PWA Manifest and Meta Tags -->
    <link rel="manifest" href="manifest.json">
    <meta name="theme-color" content="#0A2F64">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="SHEEner Reporter">
    <meta name="mobile-web-app-capable" content="yes">

    <!-- === MICRO-POLISH 6 : faster loading === -->
    <link rel="preconnect" href="https://cdnjs.cloudflare.com">
    <link rel="dns-prefetch" href="https://cdnjs.cloudflare.com">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@100..900&display=swap" rel="stylesheet">
    <link rel="preload" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" as="style"
        onload="this.onload=null;this.rel='stylesheet'">
    <noscript>
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    </noscript>
    <script src="js/script.js" defer></script>
    <style>
        /* Prevent scrollbar on homepage - allow vertical snap scrolling */
        html.homepage,
        body.homepage {
            overflow: hidden !important;
            /* Block default scroll to use hijacking for 'jump' feel */
            scroll-behavior: smooth;
            height: 100vh;
            width: 100%;
            margin: 0;
            padding: 0;
            background: radial-gradient(circle at center, #1a1a3a 0%, #050510 100%);
            color: #fff;
        }

        /* Three-section layout: header, middle, footer */
        body.homepage {
            display: flex;
            flex-direction: column;
        }

        @media (max-width: 768px) {
            .page-section {
                padding-top: 20px;
                height: auto;
                min-height: 100vh;
                scroll-snap-align: none;
            }

            body.homepage {
                scroll-snap-type: none;
            }
        }

        .hero-nav {
            position: absolute;
            top: 40px;
            left: 0;
            right: 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0 60px;
            width: 100%;
            box-sizing: border-box;
            z-index: 10;
        }

        .hero-logo-group {
            display: flex;
            align-items: center;
            gap: 25px;
        }

        .hero-side-logo {
            height: 45px;
            filter: drop-shadow(0 0 10px rgba(255, 255, 255, 0.1));
        }

        .hero-product-name {
            font-family: var(--font-main);
            font-size: 1.8rem;
            font-weight: 800;
            color: #fff;
            letter-spacing: -0.02em;
        }

        .hero-product-name .dot {
            color: #3685fe;
        }

        .modern-login-btn {
            background: rgba(255, 255, 255, 0.03);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            color: rgba(255, 255, 255, 0.8);
            padding: 12px 28px;
            border-radius: 14px;
            font-weight: 600;
            font-size: 0.95rem;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 12px;
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            font-family: var(--font-main);
        }

        .modern-login-btn:hover {
            background: rgba(255, 255, 255, 0.08);
            border-color: rgba(255, 255, 255, 0.3);
            color: #fff;
            transform: translateY(-3px);
            box-shadow: 0 15px 30px -10px rgba(0, 0, 0, 0.5);
        }

        .modern-login-btn i {
            font-size: 1.1rem;
            color: #3685fe;
        }

        /* Middle section - takes remaining space, centered content */
        body.homepage main {
            display: block;
            min-height: 100vh;
            position: relative;
        }

        .page-section {
            height: 100vh;
            width: 100%;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            scroll-snap-align: start;
            scroll-snap-stop: always;
            position: relative;
            box-sizing: border-box;
            overflow: hidden;
        }

        /* Footer section - fixed height */
        body.homepage footer {
            flex-shrink: 0;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        /* Enhanced homepage styles */
        .homepage .hero {
            position: relative;
            z-index: 2;
            text-align: center;
            padding: 2vh 5vw;
            width: 100%;
            max-width: 1200px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            box-sizing: border-box;
            transition: transform 1s cubic-bezier(0.2, 0.8, 0.2, 1), opacity 1s ease;
        }

        .section-visible .hero {
            opacity: 1;
            transform: translateY(0);
        }

        .section-hidden .hero {
            opacity: 0;
            transform: translateY(50px);
        }

        .homepage .hero h2 {
            font-size: clamp(2rem, 4vw, 3.5rem);
            font-weight: 700;
            margin-bottom: 1rem;
            background: linear-gradient(135deg, #ffffff 0%, #e0f0ff 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            text-shadow: none;
            opacity: 0;
            animation: slideDownFromTop 1s ease-out forwards;
        }

        .hero-subtitle {
            font-size: clamp(0.95rem, 1.8vw, 1.2rem);
            color: rgba(255, 255, 255, 0.9);
            margin-bottom: 1rem;
            font-weight: 300;
            line-height: 1.4;
            opacity: 0;
            animation: slideDownFromTop 1s ease-out 0.2s forwards;
        }

        .cta-button0 {
            padding: 14px 35px;
            font-size: 1.1rem;
            font-weight: 600;
            background: linear-gradient(135deg, #3685fe 0%, #276ac8 100%);
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(54, 133, 254, 0.4);
            animation: fadeInUp 1.6s ease-out;
            position: relative;
            overflow: hidden;
            margin-bottom: 0.5rem;
            z-index: 1;
            pointer-events: auto;
        }

        .cta-button0::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 0;
            height: 0;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.3);
            transform: translate(-50%, -50%);
            transition: width 0.6s, height 0.6s;
        }

        .cta-button0:hover::before {
            width: 300px;
            height: 300px;
        }

        .cta-button0:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 20px rgba(54, 133, 254, 0.6);
            background: linear-gradient(135deg, #276ac8 0%, #1a5aa3 100%);
        }

        .cta-button0:active {
            transform: translateY(-1px);
        }

        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 0.3fr));
            gap: 1.5rem;
            justify-content: center;
            padding: 0 2rem;
            max-width: 1300px;
            width: 100%;
            box-sizing: border-box;
        }

        .feature-card {
            background: rgba(255, 255, 255, 0.03);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 20px;
            padding: 1.5rem;
            text-align: center;
            color: white;
            transition: all 0.5s cubic-bezier(0.4, 0, 0.2, 1);
            cursor: pointer;
            position: relative;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 12px;
            min-height: 240px;
        }

        .feature-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.1) 0%, transparent 100%);
            opacity: 0;
            transition: opacity 0.5s ease;
        }

        .feature-card:hover::before {
            opacity: 1;
        }

        /* Special styling for incident report card */
        .feature-card.incident-card {
            background: rgba(231, 76, 60, 0.05);
            border: 1px solid rgba(231, 76, 60, 0.2);
        }

        /* Remove fixed animation delays, will use scroll reveal */
        .feature-card {
            opacity: 1;
            transform: translateY(0);
        }

        .feature-card:hover {
            transform: translateY(-10px);
            background: rgba(255, 255, 255, 0.08) !important;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.4),
                0 0 20px rgba(54, 133, 254, 0.2);
            border-color: rgba(54, 133, 254, 0.4) !important;
            z-index: 10;
        }

        .feature-icon {
            font-size: 2.2rem;
            margin-bottom: 0.5rem;
            color: #3685fe;
            filter: drop-shadow(0 0 10px rgba(54, 133, 254, 0.5));
            transition: transform 0.8s cubic-bezier(0.25, 0.46, 0.45, 0.94),
                color 0.6s ease,
                filter 0.6s ease;
        }

        .feature-card:hover .feature-icon {
            transform: scale(1.15) rotateY(360deg);
            color: #00c6ff;
            filter: drop-shadow(0 0 20px rgba(0, 198, 255, 0.8));
        }

        .feature-card h3 {
            font-size: 1rem;
            margin-bottom: 0.4rem;
            color: #ffffff;
            transition: color 0.3s ease, transform 0.3s ease;
        }

        .feature-card:hover h3 {
            color: #00c6ff;
            transform: translateY(-2px);
        }

        .feature-card p {
            font-size: 0.85rem;
            color: rgba(255, 255, 255, 0.8);
            line-height: 1.5;
            margin: 0;
            transition: color 0.3s ease;
        }

        .feature-card:hover p {
            color: rgba(255, 255, 255, 1);
        }

        .login-box {
            backdrop-filter: blur(5px);
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 10000;
            overflow-y: auto;
            padding: 20px;
            box-sizing: border-box;
            background-color: rgba(0, 0, 0, 0.5);
            opacity: 1;
            visibility: visible;
            transition: opacity 0.3s cubic-bezier(0.4, 0, 0.2, 1), visibility 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        /* Email confirmation modal should appear above report modal */
        #emailConfirmationBox {
            z-index: 10001 !important;
        }

        /* New clean modal approach for report event */
        .event-modal-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.4);
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
            z-index: 10050 !important;
            overflow-y: auto;
            padding: 20px;
            box-sizing: border-box;
        }

        .event-modal-overlay.show {
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .event-modal {
            background: #ffffff;
            border-radius: 24px;
            box-shadow: 0 40px 100px rgba(0, 0, 0, 0.4);
            width: 95%;
            max-width: 1000px;
            max-height: 90vh;
            display: flex;
            flex-direction: column;
            overflow: hidden;
            animation: modalPopUp 0.5s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            font-family: var(--font-main);
            position: relative;
            z-index: 10051 !important;
        }

        @keyframes modalPopUp {
            from {
                opacity: 0;
                transform: scale(0.9) translateY(40px);
            }

            to {
                opacity: 1;
                transform: scale(1) translateY(0);
            }
        }

        .event-modal-header {
            padding: 20px 30px;
            background: linear-gradient(135deg, #050510 0%, #1a1a3a 100%);
            border-bottom: 2px solid #3685fe;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .event-modal-header h3 {
            margin: 0;
            font-size: 1.3rem;
            font-weight: 800;
            color: #ffffff;
            letter-spacing: -0.01em;
        }

        .event-modal-header .modal-close-x {
            color: rgba(255, 255, 255, 0.7);
        }

        .event-modal-header .modal-close-x:hover {
            background: rgba(255, 255, 255, 0.1);
            color: #ffffff;
        }

        .event-modal-body {
            padding: 30px;
            overflow-y: auto;
            background: #fff;
        }

        .event-modal-body label {
            font-size: 0.9rem;
            font-weight: 700;
            color: #444;
            margin-bottom: 10px;
            display: block;
        }

        .event-modal-body input,
        .event-modal-body select,
        .event-modal-body textarea {
            background: #fcfcfc;
            border: 1.5px solid #f0f0f0;
            border-radius: 10px;
            padding: 10px 14px;
            font-size: 0.92rem;
            font-family: var(--font-main);
            transition: all 0.3s ease;
            width: 100%;
            box-sizing: border-box;
            margin-bottom: 4px;
        }

        .event-modal-body input:focus,
        .event-modal-body select:focus,
        .event-modal-body textarea:focus {
            border-color: #3685fe;
            background: #fff;
            box-shadow: 0 0 0 4px rgba(54, 133, 254, 0.1);
            outline: none;
        }

        /* Modern Attachment UI */
        .attachment-zone {
            border: 2px dashed #ddd;
            border-radius: 16px;
            padding: 30px;
            text-align: center;
            background: #fafafa;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
            margin-top: 10px;
        }

        .attachment-zone:hover,
        .attachment-zone.drag-over {
            border-color: #3685fe;
            background: rgba(54, 133, 254, 0.02);
        }

        .attachment-zone i {
            font-size: 2.2rem;
            color: #bbb;
            margin-bottom: 12px;
            transition: color 0.3s ease;
        }

        .attachment-zone:hover i {
            color: #3685fe;
        }

        .attachment-zone p {
            margin: 0;
            font-weight: 600;
            color: #666;
            font-size: 0.95rem;
        }

        .attachment-zone small {
            color: #999;
            display: block;
            margin-top: 5px;
        }

        .file-preview-container {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
            gap: 12px;
            margin-top: 20px;
        }

        .file-preview-item {
            background: #fff;
            border: 1px solid #eee;
            border-radius: 10px;
            padding: 8px;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 0.85rem;
            color: #555;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
            max-width: 100%;
            overflow: hidden;
        }

        .file-preview-item i {
            color: #3685fe;
            flex-shrink: 0;
        }

        .file-preview-item span {
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .event-modal-footer {
            padding: 18px 30px;
            background: #fcfcfc;
            border-top: 1px solid #eee;
            display: flex;
            gap: 12px;
            justify-content: flex-end;
        }

        .event-modal-footer .cta-button {
            border-radius: 10px;
            padding: 10px 24px;
            font-size: 0.85rem;
            font-weight: 700;
            width: auto;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .event-modal-footer .cta-button.secondary {
            background: #fff;
            border: 1.5px solid #eee;
            color: #555;
            box-shadow: none;
        }

        .event-modal-footer .cta-button.secondary:hover {
            background: #f9f9f9;
            border-color: #ddd;
            color: #333;
        }

        .event-modal-footer .close-ghost-btn {
            background: transparent;
            border: none;
            color: #888;
            font-size: 0.85rem;
            font-weight: 600;
            cursor: pointer;
            padding: 10px 15px;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 6px;
            margin-right: auto;
        }

        .event-modal-footer .close-ghost-btn:hover {
            color: #e74c3c;
        }

        .popup {
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            background-color: white;
            border-radius: 12px;
            position: relative;
            margin: 0 auto;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            width: 100%;
            transform: translateY(0) scale(1);
            opacity: 1;
            transition: transform 0.3s cubic-bezier(0.34, 1.56, 0.64, 1), opacity 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .login-box.hidden .popup {
            transform: translateY(-30px) scale(0.95);
            opacity: 0;
        }

        .login-box:not(.hidden) .popup {
            animation: slideDown 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
            max-width: 400px;
            padding: 30px;
        }

        /* Modal header - sticky at top */
        #reportEventBox .popup h3 {
            margin: 0;
            padding: 20px 30px;
            color: #0A2F64;
            font-size: 1.6rem;
            text-align: center;
            border-bottom: 2px solid #e0e0e0;
            background: #f8f9fa;
            position: sticky;
            top: 0;
            z-index: 10;
        }

        /* Modal body - scrollable */
        #reportEventBox .popup form {
            padding: 25px 35px;
            overflow-y: auto;
            flex: 1;
            display: grid;
            gap: 15px;
            min-height: 0;
            width: 100%;
            box-sizing: border-box;
        }

        /* Default popup h3 (for login box only) */
        #loginBox .popup h3 {
            margin: 0 0 1.5rem 0;
            color: #0A2F64;
            font-size: 1.8rem;
            text-align: center;
            padding-bottom: 15px;
            border-bottom: 2px solid #e0e0e0;
        }

        /* Default popup form (for login box only) */
        #loginBox .popup form {
            width: 100%;
            display: block;
            padding: 0;
            overflow: visible;
            box-sizing: border-box;
        }

        /* Ensure login box popup has proper width */
        #loginBox .popup {
            width: 100%;
            max-width: 400px;
            min-width: 300px;
            position: relative;
        }

        /* Close X button in top right corner */
        .modal-close-x {
            position: absolute;
            top: 15px;
            right: 15px;
            background: transparent;
            border: none;
            color: #666;
            font-size: 20px;
            width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            border-radius: 50%;
            transition: all 0.3s ease;
            z-index: 10;
            padding: 0;
        }

        .modal-close-x:hover {
            background: #f0f0f0;
            color: #333;
            transform: rotate(90deg);
        }

        .modal-close-x:active {
            transform: rotate(90deg) scale(0.95);
        }

        /* Forgot Password Modal Styles */
        #forgotPasswordBox .popup {
            width: 100%;
            max-width: 450px;
            min-width: 350px;
            position: relative;
        }

        .password-step {
            animation: fadeIn 0.3s ease-in;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        #forgotPasswordMessage {
            margin-top: 1rem;
            padding: 12px;
            border-radius: 6px;
            font-size: 0.95rem;
            line-height: 1.5;
        }

        #passwordMatchMessage {
            padding: 8px 12px;
            border-radius: 4px;
            font-size: 0.9rem;
            line-height: 1.4;
        }

        /* Loading Overlay Animation */
        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(3px);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 99999;
            animation: fadeIn 0.3s ease;
        }

        .loading-overlay.show {
            display: flex;
        }

        .loading-spinner {
            background: white;
            padding: 30px 40px;
            border-radius: 12px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
            text-align: center;
            min-width: 200px;
        }

        .loading-spinner-icon {
            font-size: 48px;
            color: #3685fe;
            margin-bottom: 15px;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% {
                transform: rotate(0deg);
            }

            100% {
                transform: rotate(360deg);
            }
        }

        .loading-spinner-text {
            font-size: 16px;
            font-weight: 600;
            color: #333;
            margin-top: 10px;
        }

        .loading-spinner-subtext {
            font-size: 13px;
            color: #666;
            margin-top: 5px;
        }

        /* Autocomplete Styles for Location */
        .autocomplete-wrapper {
            position: relative;
            z-index: 1;
        }

        .autocomplete-wrapper:focus-within {
            z-index: 10001;
        }

        .autocomplete-dropdown {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: white;
            border: 1px solid #ddd;
            border-radius: 4px;
            max-height: 200px;
            overflow-y: auto;
            z-index: 10000;
            display: none;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            margin-top: 2px;
        }

        .autocomplete-dropdown.show {
            display: block;
        }

        .autocomplete-item {
            padding: 10px 12px;
            cursor: pointer;
            border-bottom: 1px solid #f0f0f0;
            transition: background-color 0.2s;
        }

        .autocomplete-item:hover,
        .autocomplete-item.selected {
            background-color: #e9ecef;
        }

        .autocomplete-item:last-child {
            border-bottom: none;
        }

        .autocomplete-item-name {
            font-weight: 500;
            color: #2c3e50;
            margin-bottom: 2px;
        }

        .autocomplete-item-details {
            font-size: 12px;
            color: #6c757d;
        }

        .popup label {
            display: block;
            margin-bottom: 0.5rem;
            color: #333;
            font-weight: 600;
            text-align: left;
            font-size: 0.95rem;
        }

        .popup input,
        .popup select,
        .popup textarea {
            width: 100%;
            padding: 12px;
            margin-bottom: 1rem;
            border: 2px solid #e0e0e0;
            border-radius: 6px;
            font-size: 1rem;
            transition: border-color 0.3s;
            box-sizing: border-box;
            font-family: inherit;
        }

        .popup input:focus,
        .popup select:focus,
        .popup textarea:focus {
            outline: none;
            border-color: #3685fe;
            box-shadow: 0 0 0 3px rgba(54, 133, 254, 0.1);
        }

        .popup textarea {
            resize: vertical;
            min-height: 100px;
        }

        .popup .cta-button {
            width: 100%;
            padding: 12px;
            margin-bottom: 0.5rem;
            font-size: 1rem;
            font-weight: 600;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .close-button {
            width: 100%;
            padding: 10px;
            background: #f5f5f5;
            color: #333;
            border: 1px solid #ddd;
            border-radius: 6px;
            cursor: pointer;
            font-size: 0.95rem;
            transition: all 0.3s;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .close-button:hover {
            background: #e0e0e0;
        }

        @keyframes slideDownFromTop {
            from {
                opacity: 0;
                transform: translateY(-50px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes slideUpFromBottom {
            from {
                opacity: 0;
                transform: translateY(50px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-30px) scale(0.95);
            }

            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

        .hidden {
            display: none !important;
        }

        /* Override hidden for login-box to allow smooth transitions */
        .login-box.hidden {
            display: flex !important;
            opacity: 0;
            visibility: hidden;
            pointer-events: none;
        }

        @media (max-width: 768px) {
            .features-grid {
                grid-template-columns: 1fr;
                gap: 1.5rem;
                padding: 0 1rem;
            }

            .homepage .hero {
                padding: 5vh 3vw;
            }

            /* QR Code Section - Stack on mobile */
            #qrCodeSection>div {
                flex-direction: column !important;
                text-align: center !important;
                padding: 20px !important;
            }

            #qrCodeSection .qr-text-content {
                text-align: center !important;
            }

            /* Tooltip positioning on mobile - show below QR code */
            #qrTooltip {
                position: absolute !important;
                right: auto !important;
                left: 50% !important;
                top: 100% !important;
                bottom: auto !important;
                transform: translateX(-50%) translateY(15px) !important;
                max-width: 90% !important;
                margin-top: 10px !important;
            }

            /* Hide arrow on mobile */
            #qrTooltip>div:last-child {
                display: none !important;
            }

            #reportEventBox {
                padding-top: calc(60px + 15px);
                padding-left: 15px;
                padding-right: 15px;
                padding-bottom: 15px;
                justify-content: center;
            }

            #reportEventBox .event-modal {
                width: 100%;
                min-width: unset;
                max-width: 100%;
                max-height: calc(100vh - 60px - 30px);
                margin: 0 auto;
            }

            #reportEventBox .event-modal-header h3 {
                padding: 15px 20px;
                font-size: 1.3rem;
            }

            #reportEventBox .event-modal-body {
                padding: 20px;
                padding-bottom: 120px;
                /* Extra padding on mobile for footer */
            }

            #reportEventBox .event-modal-body form>div[style*="grid-template-columns"] {
                grid-template-columns: 1fr !important;
            }

            .event-modal-footer {
                padding: 15px 20px;
                flex-direction: column;
                gap: 10px;
            }

            .event-modal-footer .cta-button,
            .event-modal-footer .close-button {
                width: 100%;
            }
        }

        .scroll-indicator {
            position: absolute;
            bottom: 30px;
            left: 50%;
            transform: translateX(-50%);
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 10px;
            color: rgba(255, 255, 255, 0.5);
            cursor: pointer;
            z-index: 10;
            animation: bounce 2s infinite;
            transition: all 0.3s ease;
        }

        .scroll-indicator:hover {
            color: #3685fe;
        }

        .scroll-indicator span {
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.2em;
            font-family: var(--font-main);
        }

        @keyframes bounce {

            0%,
            20%,
            50%,
            80%,
            100% {
                transform: translateX(-50%) translateY(0);
            }

            40% {
                transform: translateX(-50%) translateY(-10px);
            }

            60% {
                transform: translateX(-50%) translateY(-5px);
            }
        }

        /* Section Specific Animations */
        .reveal-content {
            opacity: 0;
            transform: translateY(30px);
            transition: all 1s cubic-bezier(0.2, 0.8, 0.2, 1);
        }

        .section-visible .reveal-content {
            opacity: 1;
            transform: translateY(0);
        }

        .stagger-1 {
            transition-delay: 0.1s;
        }

        .stagger-2 {
            transition-delay: 0.2s;
        }

        .stagger-3 {
            transition-delay: 0.3s;
        }

        .stagger-4 {
            transition-delay: 0.4s;
        }

        @media (max-width: 768px) {
            body.homepage header {
                height: 60px;
            }

            .page-section {
                padding-top: 60px;
                height: auto;
                min-height: 100vh;
                scroll-snap-align: none;
            }

            body.homepage {
                scroll-snap-type: none;
            }
        }

        .locked-card {
            cursor: not-allowed !important;
            opacity: 0.85;
            position: relative;
            overflow: hidden;
        }

        .locked-card:hover {
            transform: none !important;
            box-shadow: 0 8px 32px 0 rgba(0, 0, 0, 0.3) !important;
            border: 1px solid rgba(255, 255, 255, 0.08) !important;
        }

        .locked-card .feature-icon {
            filter: grayscale(1) opacity(0.5);
        }

        .lock-badge {
            position: absolute;
            top: 20px;
            right: 20px;
            font-size: 0.9rem;
            color: rgba(255, 255, 255, 0.3);
            transition: all 0.3s ease;
        }

        .locked-card:hover .lock-badge {
            color: #ff9800;
            transform: scale(1.2);
        }
    </style>
</head>

<body class="homepage">
    <main>
        <!-- Section 1: Hero & QR Code -->
        <section id="hero-section" class="page-section">
            <div class="hero-nav reveal-content">
                <div class="hero-logo-group">
                    <div class="hero-product-name">Sheener<span class="dot">.</span></div>
                </div>
                <button type="button" class="modern-login-btn"
                    onclick="document.getElementById('loginBox').classList.remove('hidden')">
                    <i class="fas fa-shield-halved"></i>
                    <span>Secure Gateway</span>
                </button>
            </div>
            <div class="hero reveal-content">
                <!-- QR Code Section with Title on Right -->
                <div id="qrCodeSection" style="margin: 2rem auto; max-width: 900px;">
                    <div
                        style="background: rgba(255, 255, 255, 0.05); backdrop-filter: blur(40px); -webkit-backdrop-filter: blur(40px); border-radius: 32px; padding: 40px; display: flex; gap: 40px; align-items: center; border: 1px solid rgba(255, 255, 255, 0.08); flex-wrap: wrap; justify-content: center; box-shadow: 0 40px 100px -20px rgba(0, 0, 0, 0.7);">
                        <!-- Left Side: QR Code -->
                        <div style="text-align: center; flex-shrink: 0; position: relative;">
                            <div id="qrCodeWrapper" style="position: relative; display: inline-block;">
                                <canvas id="qrCodeCanvas"
                                    style="background: white; padding: 12px; border-radius: 16px; max-width: 220px; max-height: 220px; display: block; cursor: pointer; transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275); box-shadow: 0 10px 30px rgba(0,0,0,0.5);"></canvas>
                                <!-- Tooltip -->
                                <div id="qrTooltip"
                                    style="position: absolute; right: 105%; top: 50%; transform: translateY(-50%) translateX(-20px); background: rgba(10, 10, 30, 0.98); backdrop-filter: blur(15px); color: white; padding: 25px; border-radius: 20px; min-width: 300px; opacity: 0; visibility: hidden; transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275); pointer-events: none; z-index: 1000; box-shadow: 0 20px 50px rgba(0, 0, 0, 0.8); border: 1px solid rgba(255,255,255,0.15);">
                                    <h4
                                        style="color: #00c6ff; font-size: 1.2rem; font-weight: 800; margin-bottom: 12px; font-family: var(--font-main);">
                                        <i class="fas fa-rocket" style="margin-right: 12px;"></i>Mobile Access
                                    </h4>
                                    <p
                                        style="color: rgba(255, 255, 255, 0.8); font-size: 0.95rem; line-height: 1.7; margin-bottom: 18px;">
                                        Scan to launch the mobile reporting suite. Designed for field operations with
                                        full offline capability.
                                    </p>
                                    <div
                                        style="background: rgba(255, 255, 255, 0.05); padding: 15px; border-radius: 12px; border: 1px solid rgba(255,255,255,0.05);">
                                        <div
                                            style="display: flex; align-items: center; margin-bottom: 10px; font-size: 0.9rem;">
                                            <i class="fas fa-check-circle" style="width: 25px; color: #4CAF50;"></i>
                                            <span>Full Offline Mode</span>
                                        </div>
                                        <div
                                            style="display: flex; align-items: center; margin-bottom: 10px; font-size: 0.9rem;">
                                            <i class="fas fa-sync" style="width: 25px; color: #2196F3;"></i>
                                            <span>Cloud Syncing</span>
                                        </div>
                                        <div style="display: flex; align-items: center; font-size: 0.9rem;">
                                            <i class="fas fa-shield-alt" style="width: 25px; color: #FF9800;"></i>
                                            <span>Secure Connection</span>
                                        </div>
                                    </div>
                                    <div
                                        style="position: absolute; left: 100%; top: 50%; transform: translateY(-50%); width: 0; height: 0; border-top: 12px solid transparent; border-bottom: 12px solid transparent; border-left: 12px solid rgba(10, 10, 30, 0.98);">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Right Side -->
                        <div class="qr-text-content" style="flex: 1; min-width: 320px; color: white; text-align: left;">
                            <img src="img/Amneal Logo new y.png" alt="Amneal Logo"
                                style="height: clamp(30px, 4vw, 40px); margin-bottom: 1.5rem; filter: drop-shadow(0 0 15px rgba(255,255,255,0.1));">
                            <div
                                style="text-transform: uppercase; letter-spacing: 0.4em; font-size: 0.72rem; color: #00c6ff; font-weight: 800; margin-bottom: 0.8rem; font-family: var(--font-main); filter: drop-shadow(0 0 10px rgba(0, 198, 255, 0.3));">
                                Sheener Digital Platform
                            </div>
                            <h1
                                style="font-size: clamp(2.8rem, 6vw, 4.5rem); font-weight: 900; margin-bottom: 1.5rem; line-height: 1.05; letter-spacing: -0.04em; font-family: var(--font-main); background: linear-gradient(135deg, #ffffff 0%, #a0a0a0 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">
                                EHS<br>Intelligence
                            </h1>
                            <p
                                style="color: rgba(255, 255, 255, 0.5); font-size: clamp(1rem, 1.5vw, 1.2rem); line-height: 1.6; font-weight: 300; max-width: 450px; font-family: var(--font-main);">
                                Empowering pharmaceutical excellence through advanced safety analytics and proactive
                                risk management.
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Scroll Indicator -->
            <div class="scroll-indicator" onclick="scrollToSection(1)">
                <span>Continue</span>
                <i class="fas fa-chevron-down"></i>
            </div>
        </section>

        <!-- Section 2: Features -->
        <section id="features-section" class="page-section">
            <div class="hero reveal-content" style="padding-top: 0; padding-bottom: 0;">
                <h2
                    style="font-size: clamp(2rem, 4vw, 2.8rem); font-weight: 900; margin-bottom: 2.5rem; color: #fff; border: none; font-family: var(--font-main); letter-spacing: -0.02em;">
                    Digital Ecosystem</h2>
                <div class="features-grid">
                    <div class="feature-card reveal-content stagger-1">
                        <div class="feature-icon">
                            <i class="fas fa-shield-alt"></i>
                        </div>
                        <h3 style="font-family: var(--font-main); font-weight: 700;">Risk Analysis</h3>
                        <p style="color: rgba(255,255,255,0.6);">Intelligent identification and proactive mitigation
                            strategies.</p>
                    </div>
                    <div class="feature-card reveal-content stagger-2">
                        <div class="feature-icon">
                            <i class="fas fa-satellite-dish"></i>
                        </div>
                        <h3 style="font-family: var(--font-main); font-weight: 700;">Live Monitoring</h3>
                        <p style="color: rgba(255,255,255,0.6);">Global visibility with real-time analytics and
                            reporting.</p>
                    </div>
                    <div class="feature-card incident-card reveal-content stagger-3" onclick="openEventModal()">
                        <div class="feature-icon">
                            <i class="fas fa-bolt" style="color: #e74c3c;"></i>
                        </div>
                        <h3 style="font-family: var(--font-main); font-weight: 700;">Instant Report</h3>
                        <p style="color: rgba(255,255,255,0.6);">Rapid incident logging and alert dispatching.</p>
                    </div>
                    <div class="feature-card reveal-content stagger-4 locked-card">
                        <div class="lock-badge"><i class="fas fa-lock"></i></div>
                        <div class="feature-icon">
                            <i class="fas fa-brain"></i>
                        </div>
                        <h3 style="font-family: var(--font-main); font-weight: 700;">Knowledge</h3>
                        <p style="color: rgba(255,255,255,0.4);">Module offline. Access restricted to internal network
                            authorized terminals.</p>
                    </div>
                </div>
            </div>
        </section>

        <div id="loginBox" class="login-box hidden" onclick="if(event.target === this) this.classList.add('hidden')">
            <div class="popup" onclick="event.stopPropagation()">
                <button type="button" class="modal-close-x"
                    onclick="document.getElementById('loginBox').classList.add('hidden')"
                    aria-label="Close login window">
                    <i class="fas fa-times"></i>
                </button>
                <h3><i class="fas fa-lock" style="margin-right: 8px;"></i>Login</h3>
                <form id="loginForm" action="php/login.php" method="post">
                    <label for="username"><i class="fas fa-user" style="margin-right: 6px;"></i>Username:</label>
                    <input type="text" id="username" name="username" placeholder="Enter your username" required
                        autofocus>

                    <label for="password"><i class="fas fa-key" style="margin-right: 6px;"></i>Password:</label>
                    <input type="password" id="password" name="password" placeholder="Enter your password" required>

                    <div style="text-align: right; margin-bottom: 1rem;">
                        <a href="#" onclick="event.preventDefault(); openForgotPasswordModal();"
                            style="color: #3685fe; text-decoration: none; font-size: 0.9rem;">
                            <i class="fas fa-question-circle" style="margin-right: 4px;"></i>Forgot Password?
                        </a>
                    </div>

                    <button class="cta-button" type="submit">
                        <i class="fas fa-sign-in-alt" style="margin-right: 8px;"></i>Login
                    </button>
                </form>
            </div>
        </div>

        <!-- Email Confirmation Modal -->
        <div id="emailConfirmationBox" class="login-box hidden"
            onclick="if(event.target === this) closeEmailConfirmationModal()">
            <div class="popup" onclick="event.stopPropagation()" style="max-width: 450px; min-width: 350px;">
                <button type="button" class="modal-close-x" onclick="closeEmailConfirmationModal()" aria-label="Close">
                    <i class="fas fa-times"></i>
                </button>
                <h3><i class="fas fa-envelope" style="margin-right: 8px;"></i>Email Notifications</h3>
                <div style="margin: 20px 0;">
                    <p style="margin-bottom: 15px; color: #333; line-height: 1.6;">
                        Do you want to send email notifications to all users about this event report?
                    </p>
                    <div
                        style="background: #f8f9fa; padding: 15px; border-radius: 8px; margin-bottom: 20px; border-left: 4px solid #3685fe;">
                        <p style="margin: 0; font-size: 0.9rem; color: #666;">
                            <i class="fas fa-info-circle" style="margin-right: 6px; color: #3685fe;"></i>
                            <strong>Yes:</strong> Email notifications will be sent to all active users with email
                            addresses.<br>
                            <strong>No:</strong> The report will be saved without sending any emails.
                        </p>
                    </div>
                </div>
                <div style="display: flex; gap: 10px; justify-content: flex-end;">
                    <button type="button" class="cta-button" onclick="handleEmailConfirmation(false)"
                        style="background: #6c757d; flex: 1;">
                        <i class="fas fa-times" style="margin-right: 6px;"></i>No, Save Only
                    </button>
                    <button type="button" class="cta-button" onclick="handleEmailConfirmation(true)"
                        style="background: linear-gradient(135deg, #3685fe 0%, #2a6dd4 100%); flex: 1;">
                        <i class="fas fa-check" style="margin-right: 6px;"></i>Yes, Send Emails
                    </button>
                </div>
            </div>
        </div>

        <!-- Forgot Password Modal -->
        <div id="forgotPasswordBox" class="login-box hidden"
            onclick="if(event.target === this) closeForgotPasswordModal()">
            <div class="popup" onclick="event.stopPropagation()">
                <button type="button" class="modal-close-x" onclick="closeForgotPasswordModal()" aria-label="Close">
                    <i class="fas fa-times"></i>
                </button>
                <h3><i class="fas fa-key" style="margin-right: 8px;"></i>Change Password</h3>
                <form id="forgotPasswordForm">
                    <!-- Step 1: Verify Old Password -->
                    <div id="step1" class="password-step">
                        <label for="fp_username"><i class="fas fa-user" style="margin-right: 6px;"></i>Username:</label>
                        <input type="text" id="fp_username" name="username" placeholder="Enter your username" required>

                        <label for="fp_old_password"><i class="fas fa-lock" style="margin-right: 6px;"></i>Current
                            Password:</label>
                        <input type="password" id="fp_old_password" name="old_password"
                            placeholder="Enter your current password" required>

                        <button type="button" class="cta-button" onclick="verifyOldPassword(event)">
                            <i class="fas fa-check" style="margin-right: 8px;"></i>Verify Password
                        </button>
                    </div>

                    <!-- Step 2: Set New Password -->
                    <div id="step2" class="password-step" style="display: none;">
                        <label for="fp_new_password"><i class="fas fa-key" style="margin-right: 6px;"></i>New
                            Password:</label>
                        <input type="password" id="fp_new_password" name="new_password"
                            placeholder="Enter your new password" required>

                        <label for="fp_confirm_password"><i class="fas fa-key" style="margin-right: 6px;"></i>Confirm
                            New Password:</label>
                        <input type="password" id="fp_confirm_password" name="confirm_password"
                            placeholder="Confirm your new password" required>

                        <div id="passwordMatchMessage"
                            style="display: none; margin-bottom: 1rem; padding: 8px; border-radius: 4px; font-size: 0.9rem;">
                        </div>

                        <button type="button" class="cta-button" onclick="changePassword(event)">
                            <i class="fas fa-save" style="margin-right: 8px;"></i>Change Password
                        </button>
                        <button type="button" class="close-button" onclick="resetForgotPasswordForm()">
                            <i class="fas fa-arrow-left" style="margin-right: 6px;"></i>Back
                        </button>
                    </div>

                    <div id="forgotPasswordMessage"
                        style="display: none; margin-top: 1rem; padding: 12px; border-radius: 6px; font-size: 0.95rem;">
                    </div>
                </form>
            </div>
        </div>

        <!-- New Event Report Modal -->
        <div id="reportEventBox" class="event-modal-overlay" onclick="closeEventModal(event)">
            <div class="event-modal" onclick="event.stopPropagation()">
                <div class="event-modal-header">
                    <h3><i class="fas fa-exclamation-triangle" style="margin-right: 12px; color: #ff9800;"></i>Report an
                        Issue</h3>
                    <button type="button" class="modal-close-x" onclick="closeEventModal(event)"
                        aria-label="Close report form">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div class="event-modal-body">
                    <form id="anonymousEventForm" enctype="multipart/form-data">
                        <!-- First Row: Your Name, Your Email, Event Date & Time -->
                        <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 15px;">
                            <div>
                                <label for="anon_reporter_name"><i class="fas fa-user"
                                        style="margin-right: 6px;"></i>Your Name:</label>
                                <input type="text" id="anon_reporter_name" name="reporter_name"
                                    placeholder="Enter your name" required>
                            </div>
                            <div>
                                <label for="anon_reporter_email"><i class="fas fa-envelope"
                                        style="margin-right: 6px;"></i>Your Email (optional):</label>
                                <input type="email" id="anon_reporter_email" name="reporter_email"
                                    placeholder="Enter your email (optional)">
                            </div>
                            <div>
                                <label for="anon_eventDate"><i class="fas fa-calendar"
                                        style="margin-right: 6px;"></i>Event Date & Time:</label>
                                <input type="datetime-local" id="anon_eventDate" name="eventDate">
                            </div>
                        </div>

                        <!-- Second Row: Location, Event Category, Subcategory -->
                        <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 15px;">
                            <div>
                                <label for="anon_location" class="required"><i class="fas fa-map-marker-alt"
                                        style="margin-right: 6px;"></i>Location of Event/Observation:</label>
                                <div class="autocomplete-wrapper" style="position: relative;">
                                    <input type="text" id="anon_location" name="location"
                                        placeholder="Type to search for location..." required autocomplete="off">
                                    <input type="hidden" id="anon_location_id" name="location_id">
                                    <div id="anon_location_dropdown" class="autocomplete-dropdown"></div>
                                </div>
                            </div>
                            <div>
                                <label for="anon_primaryCategory" class="required"><i class="fas fa-tag"
                                        style="margin-right: 6px;"></i>Event Category:</label>
                                <select id="anon_primaryCategory" name="primaryCategory" required>
                                    <option value="">-- Select Category --</option>
                                    <option value="Audit">Audit</option>
                                    <option value="Near Miss">Near Miss</option>
                                    <option value="Accident">Accident</option>
                                    <option value="Good Catch">Good Catch</option>
                                    <option value="Opportunity for Improvement">Opportunity for Improvement</option>
                                </select>
                            </div>
                            <div>
                                <label for="anon_secondaryCategory" class="required"><i class="fas fa-tags"
                                        style="margin-right: 6px;"></i>Subcategory:</label>
                                <select id="anon_secondaryCategory" name="secondaryCategory" required disabled>
                                    <option value="">-- Select Category First --</option>
                                </select>
                            </div>
                        </div>

                        <div>
                            <label for="anon_description" class="required"><i class="fas fa-align-left"
                                    style="margin-right: 6px;"></i>Event Description / Observation / Comment:</label>
                            <textarea id="anon_description" name="description" rows="3"
                                placeholder="Provide a detailed description of the event, observation, or comment..."
                                required></textarea>
                        </div>

                        <div>
                            <label><i class="fas fa-paperclip" style="margin-right: 6px;"></i>Evidence & Attachments
                                (optional)</label>
                            <div class="attachment-zone" onclick="document.getElementById('anon_attachments').click()"
                                id="attachmentDropZone">
                                <i class="fas fa-cloud-arrow-up"></i>
                                <p>Click to browse or Drag & Drop files here</p>
                                <small>Max 10 files (Images, PDF, Word, Excel) • Max 5MB each</small>
                                <input type="file" id="anon_attachments" name="attachments[]" multiple hidden
                                    accept="image/*,.pdf,.doc,.docx,.xls,.xlsx" capture="environment"
                                    onchange="updateFilePreview(this)">
                            </div>
                            <div id="filePreviewContainer" class="file-preview-container"></div>
                        </div>

                        <!-- GPS Coordinates (Hidden Field) -->
                        <input type="hidden" id="gps_coordinates" name="gps_coordinates">
                        <p id="location_status"
                            style="font-size: 0.85rem; color: #3685fe; margin: 10px 0; text-align: center;"><i
                                class="fas fa-map-marker-alt" style="margin-right: 6px;"></i>Acquiring GPS location...
                        </p>

                        <div id="anonEventMessage"
                            style="display: none; padding: 10px; margin: 10px 0; border-radius: 4px;"></div>
                    </form>
                </div>
                <div class="event-modal-footer">
                    <button type="button" class="close-ghost-btn" onclick="closeEventModal()">
                        <i class="fas fa-times"></i> Close
                    </button>
                    <button type="button" class="cta-button secondary" id="generatePdfBtn">
                        <i class="fas fa-file-pdf"></i> PDF
                    </button>
                    <button class="cta-button" type="submit" id="submitReportBtn" form="anonymousEventForm"
                        style="background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%);">
                        <i class="fas fa-paper-plane"></i> Submit Report
                    </button>
                </div>
            </div>
        </div>

    </main>

    <footer style="margin: 0; padding: 0; height: 40px; display: flex; align-items: center; justify-content: center;">
        <p style="color: white; margin: 0;">&copy; 2026 SHEEner. All rights reserved.</p>
    </footer>

    <!-- now load the heavy libs deferred -->
    <script src="js/vendor/jspdf.umd.min.js" defer></script>
    <script src="js/vendor/qrcode.min.js" defer></script>
    <script>
        // Global error handler to catch dataset access errors on null elements
        window.addEventListener('error', function (event) {
            // Check if error is related to dataset access on null
            if (event.error && event.error.message && event.error.message.includes('dataset')) {
                console.warn('Dataset access error caught and handled:', event.error.message);
                event.preventDefault(); // Prevent error from breaking the page
                return true;
            }
            // Check if error message contains dataset
            if (event.message && event.message.includes('dataset')) {
                console.warn('Dataset access error caught and handled:', event.message);
                event.preventDefault();
                return true;
            }
        }, true);

        // Add safe dataset access helper function
        window.safeDatasetAccess = function (element, property) {
            if (!element || !element.dataset) {
                return null;
            }
            return element.dataset[property] || null;
        };
    </script>
    <script>
        // Modal management functions
        function openEventModal() {
            const modal = document.getElementById('reportEventBox');
            if (modal) {
                modal.classList.add('show');
                document.body.style.overflow = 'hidden'; // Prevent background scrolling
            }
        }

        function closeEventModal(event) {
            // If event is provided, check if it's from the overlay or close button
            if (event) {
                // If clicking the modal content (not overlay), don't close
                if (event.target !== event.currentTarget &&
                    !event.target.closest('.modal-close-x') &&
                    !event.target.closest('.close-button')) {
                    return;
                }
                // Stop event propagation if it's a close button click
                if (event.target.closest('.modal-close-x') || event.target.closest('.close-button')) {
                    event.stopPropagation();
                }
            }
            const modal = document.getElementById('reportEventBox');
            if (modal) {
                modal.classList.remove('show');
                document.body.style.overflow = ''; // Restore scrolling
            }
        }

        // Category mapping for anonymous event form
        const categoryMapping = {
            'Audit': ['Material', 'Equipment', 'Process', 'Documentation', 'Training', 'Environmental', 'Other'],
            'Near Miss': ['Material', 'Equipment', 'Injuries', 'Environmental', 'Process', 'Safety', 'Other'],
            'Accident': ['Material', 'Equipment', 'Injuries', 'Environmental', 'Process', 'Safety', 'Other'],
            'Good Catch': ['Material', 'Equipment', 'Process', 'Quality', 'Safety', 'Environmental', 'Other'],
            'Opportunity for Improvement': ['Material', 'Equipment', 'Process', 'Efficiency', 'Quality', 'Environmental', 'Other']
        };

        // Register Service Worker for PWA
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', function () {
                navigator.serviceWorker.register('service-worker.js')
                    .then(function (registration) {
                        console.log('ServiceWorker registration successful:', registration.scope);
                    })
                    .catch(function (err) {
                        console.log('ServiceWorker registration failed:', err);
                    });
            });
        }

        // GPS Geolocation Function
        function acquireGPSLocation() {
            const locationStatus = document.getElementById('location_status');
            const gpsCoordinates = document.getElementById('gps_coordinates');

            if (!locationStatus || !gpsCoordinates) return;

            if ("geolocation" in navigator) {
                locationStatus.innerHTML = '<i class="fas fa-spinner fa-spin" style="margin-right: 8px;"></i>Attempting to lock GPS coordinates...';
                locationStatus.style.color = '#3685fe';

                navigator.geolocation.getCurrentPosition(
                    function (position) {
                        const lat = position.coords.latitude;
                        const lon = position.coords.longitude;
                        const accuracy = position.coords.accuracy;

                        gpsCoordinates.value = lat + "," + lon;
                        locationStatus.innerHTML = '<i class="fas fa-check-circle" style="margin-right: 8px;"></i>Precision Location Locked (±' + Math.round(accuracy) + 'm)';
                        locationStatus.style.color = '#27ae60';
                        locationStatus.title = `Lat: ${lat}, Lon: ${lon}`;
                    },
                    function (error) {
                        let errorMsg = '';
                        let showRetry = false;
                        switch (error.code) {
                            case error.PERMISSION_DENIED:
                                errorMsg = "GPS access is currently disabled for this site.";
                                showRetry = true;
                                break;
                            case error.POSITION_UNAVAILABLE:
                                errorMsg = "Unable to determine your precise location.";
                                showRetry = true;
                                break;
                            case error.TIMEOUT:
                                errorMsg = "GPS request timed out. Poor signal?";
                                showRetry = true;
                                break;
                            default:
                                errorMsg = "An unexpected GPS error occurred.";
                        }

                        let html = `<i class="fas fa-exclamation-circle" style="margin-right: 8px;"></i>${errorMsg}`;
                        if (showRetry) {
                            html += ` <span onclick="acquireGPSLocation()" style="text-decoration: underline; cursor: pointer; margin-left: 10px; font-weight: 700;">Retry</span>`;
                        }
                        locationStatus.innerHTML = html;
                        locationStatus.style.color = '#e67e22'; // Use Amber for alerts (non-blocking)
                        gpsCoordinates.value = '';
                    },
                    {
                        enableHighAccuracy: true,
                        timeout: 8000,
                        maximumAge: 0
                    }
                );
            } else {
                locationStatus.innerHTML = '<i class="fas fa-times-circle" style="margin-right: 8px;"></i>Hardware does not support GPS tracking.';
                locationStatus.style.color = '#95a5a6';
            }
        }

        // Override openEventModal to trigger GPS acquisition
        const originalOpenEventModal = openEventModal;
        openEventModal = function () {
            originalOpenEventModal();
            // Acquire GPS location when modal opens
            setTimeout(acquireGPSLocation, 500);
        };

        // Generate QR Code for Mobile App
        function generateQRCode() {
            const canvas = document.getElementById('qrCodeCanvas');
            if (!canvas) return;

            // Use the local network IP address for QR code
            // This allows mobile devices on the same network to access the app
            // Update this IP if your server IP changes
            const localIP = '172.21.10.99';

            // Get the base path (e.g., /sheener)
            const currentPath = window.location.pathname;
            const basePath = currentPath.substring(0, currentPath.lastIndexOf('/'));

            // Construct the mobile app URL
            // TODO: Change to https:// after HTTPS is implemented on the server
            const protocol = 'http://'; // Change to 'https://' after SSL certificate is installed
            const mobileUrl = `${protocol}${localIP}${basePath}/mobile_report.php`;

            console.log('Generating QR code for:', mobileUrl);
            console.log('Network: Company Server (172.21.10.99)');

            QRCode.toCanvas(canvas, mobileUrl, {
                width: 200,
                margin: 2,
                color: {
                    dark: '#000000',
                    light: '#FFFFFF'
                }
            }, function (error) {
                if (error) {
                    console.error('QR Code generation error:', error);
                } else {
                    console.log('QR Code generated successfully for:', mobileUrl);
                }
            });
        }

        // QR Code Hover Tooltip Functionality
        function setupQRTooltip() {
            const qrWrapper = document.getElementById('qrCodeWrapper');
            const qrCanvas = document.getElementById('qrCodeCanvas');
            const tooltip = document.getElementById('qrTooltip');

            if (!qrWrapper || !qrCanvas || !tooltip) return;

            // Show tooltip on hover
            qrWrapper.addEventListener('mouseenter', function () {
                tooltip.style.opacity = '1';
                tooltip.style.visibility = 'visible';
                qrCanvas.style.transform = 'scale(1.05)';
            });

            // Hide tooltip on mouse leave
            qrWrapper.addEventListener('mouseleave', function () {
                tooltip.style.opacity = '0';
                tooltip.style.visibility = 'hidden';
                qrCanvas.style.transform = 'scale(1)';
            });
        }

        // Add animation-complete class after slide-up animations finish
        document.addEventListener('DOMContentLoaded', function () {
            // Generate QR code after page loads
            if (typeof QRCode !== 'undefined') {
                generateQRCode();
            } else {
                // Wait for QRCode library to load
                window.addEventListener('load', function () {
                    setTimeout(generateQRCode, 500);
                });
            }

            // Setup QR code tooltip
            setTimeout(setupQRTooltip, 1000);

            const featureCards = document.querySelectorAll('.feature-card');

            featureCards.forEach((card, index) => {
                // Calculate when animation should complete based on delay
                const animationDelay = 0.5 + (index * 0.2); // 0.5s, 0.7s, 0.9s, 1.1s
                const animationDuration = 0.575; // Animation duration
                const totalTime = (animationDelay + animationDuration) * 1000; // Convert to milliseconds

                // Listen for animation end event
                card.addEventListener('animationend', function () {
                    card.classList.add('animation-complete');
                }, { once: true });

                // Fallback: add class after animation should complete
                setTimeout(() => {
                    if (!card.classList.contains('animation-complete')) {
                        card.classList.add('animation-complete');
                    }
                }, totalTime);
            });

            // Ensure login button works
            const loginButton = document.querySelector('.cta-button0');
            if (loginButton) {
                loginButton.addEventListener('click', function (e) {
                    e.preventDefault();
                    e.stopPropagation();
                    const loginBox = document.getElementById('loginBox');
                    if (loginBox) {
                        loginBox.classList.remove('hidden');
                    }
                });
            }

            // Anonymous event form handling
            const anonPrimaryCategory = document.getElementById('anon_primaryCategory');
            const anonSecondaryCategory = document.getElementById('anon_secondaryCategory');
            const anonEventForm = document.getElementById('anonymousEventForm');

            if (anonPrimaryCategory && anonSecondaryCategory) {
                anonPrimaryCategory.addEventListener('change', function () {
                    const selectedPrimary = this.value;
                    anonSecondaryCategory.innerHTML = '<option value="">-- Select Subcategory --</option>';

                    if (selectedPrimary && categoryMapping[selectedPrimary]) {
                        anonSecondaryCategory.disabled = false;
                        categoryMapping[selectedPrimary].forEach(cat => {
                            const option = document.createElement('option');
                            option.value = cat;
                            option.textContent = cat;
                            anonSecondaryCategory.appendChild(option);
                        });
                    } else {
                        anonSecondaryCategory.disabled = true;
                        anonSecondaryCategory.innerHTML = '<option value="">-- Select Category First --</option>';
                    }
                });
            }

            // Location autocomplete functionality
            let areasData = [];
            const locationInput = document.getElementById('anon_location');
            const locationHiddenInput = document.getElementById('anon_location_id');
            const locationDropdown = document.getElementById('anon_location_dropdown');
            let selectedLocationIndex = -1;
            let filteredAreas = [];

            // Helper function to escape HTML
            function escapeHtml(text) {
                if (!text) return '';
                const div = document.createElement('div');
                div.textContent = text;
                return div.innerHTML;
            }

            // Fetch areas data
            async function loadAreasData() {
                try {
                    const response = await fetch('php/get_areas.php');
                    const result = await response.json();
                    if (result.success && result.data) {
                        areasData = result.data;
                    }
                } catch (error) {
                    console.error('Error loading areas:', error);
                }
            }

            // Initialize location autocomplete
            if (locationInput && locationHiddenInput && locationDropdown) {
                loadAreasData();

                locationInput.addEventListener('input', function () {
                    const query = this.value.toLowerCase().trim();
                    locationHiddenInput.value = ''; // Clear hidden input when typing

                    if (query.length === 0) {
                        locationDropdown.classList.remove('show');
                        return;
                    }

                    // Filter areas by name, type, description, or location code
                    filteredAreas = areasData.filter(area => {
                        const name = (area.area_name || '').toLowerCase();
                        const type = (area.area_type || '').toLowerCase();
                        const description = (area.description || '').toLowerCase();
                        const code = (area.location_code || '').toLowerCase();
                        return name.includes(query) ||
                            type.includes(query) ||
                            description.includes(query) ||
                            code.includes(query);
                    });

                    if (filteredAreas.length === 0) {
                        locationDropdown.classList.remove('show');
                        return;
                    }

                    // Render dropdown
                    locationDropdown.innerHTML = filteredAreas.map((area, index) => {
                        const type = area.area_type || 'Area';
                        const code = area.location_code ? ` (${area.location_code})` : '';
                        const description = area.description ? ` • ${area.description}` : '';
                        return `
                            <div class="autocomplete-item" data-index="${index}" data-id="${area.area_id}">
                                <div class="autocomplete-item-name">${escapeHtml(area.area_name)}${escapeHtml(code)}</div>
                                <div class="autocomplete-item-details">${escapeHtml(type)}${escapeHtml(description)}</div>
                            </div>
                        `;
                    }).join('');

                    locationDropdown.classList.add('show');
                    selectedLocationIndex = -1;

                    // Add click handlers
                    locationDropdown.querySelectorAll('.autocomplete-item').forEach(item => {
                        item.addEventListener('click', function () {
                            const area = filteredAreas[parseInt(this.dataset.index)];
                            locationInput.value = area.area_name;
                            locationHiddenInput.value = area.area_id;
                            locationDropdown.classList.remove('show');
                        });
                    });
                });

                // Keyboard navigation
                locationInput.addEventListener('keydown', function (e) {
                    if (!locationDropdown.classList.contains('show')) return;

                    const items = locationDropdown.querySelectorAll('.autocomplete-item');
                    if (items.length === 0) return;

                    if (e.key === 'ArrowDown') {
                        e.preventDefault();
                        selectedLocationIndex = Math.min(selectedLocationIndex + 1, items.length - 1);
                        items.forEach((item, idx) => {
                            item.classList.toggle('selected', idx === selectedLocationIndex);
                        });
                    } else if (e.key === 'ArrowUp') {
                        e.preventDefault();
                        selectedLocationIndex = Math.max(selectedLocationIndex - 1, -1);
                        items.forEach((item, idx) => {
                            item.classList.toggle('selected', idx === selectedLocationIndex);
                        });
                    } else if (e.key === 'Enter') {
                        e.preventDefault();
                        if (selectedLocationIndex >= 0 && selectedLocationIndex < items.length) {
                            items[selectedLocationIndex].click();
                        }
                    } else if (e.key === 'Escape') {
                        locationDropdown.classList.remove('show');
                    }
                });

                // Close dropdown when clicking outside
                document.addEventListener('click', function (e) {
                    if (!locationInput.contains(e.target) && !locationDropdown.contains(e.target)) {
                        locationDropdown.classList.remove('show');
                    }
                });
            }

            // PDF Generation Functions
            async function getLogoImageData() {
                return new Promise(resolve => {
                    const img = new Image();
                    img.crossOrigin = "Anonymous";
                    img.src = "img/Amneal_Logo_new.svg";
                    img.onload = function () {
                        const canvas = document.createElement("canvas");
                        canvas.width = img.width;
                        canvas.height = img.height;
                        canvas.getContext("2d").drawImage(img, 0, 0);
                        resolve(canvas.toDataURL("image/png"));
                    };
                    img.onerror = () => resolve(null);
                });
            }

            async function generateQRCodeData(text) {
                return new Promise((resolve, reject) => {
                    const canvas = document.createElement("canvas");
                    QRCode.toCanvas(canvas, text, {
                        width: 200,
                        margin: 2,
                        color: {
                            dark: '#000000',
                            light: '#FFFFFF'
                        }
                    }, (error) => {
                        if (error) {
                            console.error('QR Code generation error:', error);
                            reject(error);
                        } else {
                            resolve(canvas.toDataURL("image/png"));
                        }
                    });
                });
            }

            function formatDDMMMYYYY(date) {
                if (!date) return 'N/A';
                const d = new Date(date);
                const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
                const day = String(d.getDate()).padStart(2, '0');
                const month = months[d.getMonth()];
                const year = d.getFullYear();
                return `${day} ${month} ${year}`;
            }

            function formatDateTime(dateString) {
                if (!dateString) return 'Not specified';
                const d = new Date(dateString);
                return formatDDMMMYYYY(d) + ' ' + String(d.getHours()).padStart(2, '0') + ':' + String(d.getMinutes()).padStart(2, '0');
            }


            async function generateEventPDF() {
                // Get PDF button reference once at the start
                const generatePdfBtn = document.getElementById('generatePdfBtn');
                const originalPdfBtnText = generatePdfBtn ? generatePdfBtn.innerHTML : '';

                try {
                    // Get form data
                    const reporterName = document.getElementById('anon_reporter_name').value;
                    const reporterEmail = document.getElementById('anon_reporter_email').value;
                    const primaryCategory = document.getElementById('anon_primaryCategory').value;
                    const secondaryCategory = document.getElementById('anon_secondaryCategory').value;
                    const description = document.getElementById('anon_description').value;
                    const eventDate = document.getElementById('anon_eventDate').value;
                    const location = document.getElementById('anon_location').value;

                    // Validate required fields
                    if (!reporterName || !primaryCategory || !secondaryCategory || !description || !location) {
                        alert('Please fill in all required fields before generating PDF.');
                        return;
                    }

                    // Show loading overlay
                    showLoading('Generating PDF...', 'This may take a few seconds');

                    // Disable PDF button
                    if (generatePdfBtn) {
                        generatePdfBtn.disabled = true;
                        generatePdfBtn.innerHTML = '<i class="fas fa-spinner fa-spin" style="margin-right: 8px;"></i>Generating...';
                    }

                    // Generate unique event ID for preview
                    const eventId = 'PREVIEW-' + Date.now();

                    const { jsPDF } = window.jspdf;
                    const doc = new jsPDF();

                    doc.setProperties({
                        title: `Event Report - ${eventId}`,
                        subject: 'Event Reporting System',
                        author: 'SHEEner MS'
                    });

                    const margin = 20;
                    const pageWidth = doc.internal.pageSize.getWidth();
                    const pageHeight = doc.internal.pageSize.getHeight();
                    const lineHeight = 6;
                    const sectionSpacing = 8;
                    let yPosition = 25;
                    const headerHeight = 25;

                    // Header with background
                    doc.setFillColor(0, 0, 0);
                    doc.rect(0, 0, pageWidth, headerHeight, 'F');

                    // Add logo
                    try {
                        const logoData = await getLogoImageData();
                        if (logoData) {
                            doc.addImage(logoData, 'PNG', margin, 5, 0, 12);
                        }
                    } catch (e) {
                        console.log('Could not load logo:', e);
                    }

                    // Header text
                    doc.setFontSize(18);
                    doc.setFont(undefined, 'bold');
                    doc.setTextColor(255, 255, 255);
                    doc.text('Event Report', pageWidth / 2, 15, { align: 'center' });

                    yPosition = headerHeight + 15;

                    // Generate QR code
                    const baseUrl = window.location.origin + window.location.pathname.replace(/\/[^/]*$/, '');
                    const eventUrl = `${baseUrl}/index.php?event_id=${eventId}`;
                    let qrCodeData = null;
                    try {
                        qrCodeData = await generateQRCodeData(eventUrl);
                    } catch (err) {
                        console.error('Failed to generate QR code:', err);
                    }

                    // Event ID Section with better styling - full width
                    const eventIdBoxHeight = 15;

                    doc.setFillColor(240, 245, 250);
                    doc.rect(margin, yPosition - 5, pageWidth - 2 * margin, eventIdBoxHeight, 'F');
                    doc.setDrawColor(0, 0, 0);
                    doc.setLineWidth(0.5);
                    doc.rect(margin, yPosition - 5, pageWidth - 2 * margin, eventIdBoxHeight);

                    doc.setFontSize(16);
                    doc.setFont(undefined, 'bold');
                    doc.setTextColor(0, 0, 0);

                    // Event ID text - full width available
                    const eventIdText = `Event ID: ${eventId}`;
                    doc.text(eventIdText, margin + 5, yPosition + 3);

                    yPosition += eventIdBoxHeight + sectionSpacing;

                    // QR Code Section - dedicated box below Event ID
                    if (qrCodeData) {
                        const qrSize = 28;
                        const qrBoxHeight = qrSize + 18; // Space for QR code + padding + label
                        const qrBoxWidth = qrSize + 18;

                        // Center the QR code box horizontally
                        const qrBoxX = (pageWidth - qrBoxWidth) / 2;
                        const qrBoxY = yPosition;

                        // QR code container box with light border
                        doc.setFillColor(255, 255, 255);
                        doc.rect(qrBoxX, qrBoxY, qrBoxWidth, qrBoxHeight, 'F');
                        doc.setDrawColor(220, 220, 220);
                        doc.setLineWidth(0.5);
                        doc.rect(qrBoxX, qrBoxY, qrBoxWidth, qrBoxHeight);

                        // Center QR code in the box
                        const qrX = qrBoxX + (qrBoxWidth - qrSize) / 2;
                        const qrY = qrBoxY + 8;

                        // Add QR code image
                        doc.addImage(qrCodeData, 'PNG', qrX, qrY, qrSize, qrSize);

                        // Label below QR code
                        doc.setFontSize(8);
                        doc.setFont(undefined, 'bold');
                        doc.setTextColor(0, 0, 0);
                        doc.text('Quick Access QR Code', qrBoxX + qrBoxWidth / 2, qrY + qrSize + 6, { align: 'center' });

                        doc.setFontSize(7);
                        doc.setFont(undefined, 'normal');
                        doc.setTextColor(100, 100, 100);
                        doc.text('Scan to view event online', qrBoxX + qrBoxWidth / 2, qrY + qrSize + 10, { align: 'center' });

                        yPosition += qrBoxHeight + sectionSpacing;
                    }

                    // Event Details Section with better layout
                    if (yPosition > pageHeight - 60) {
                        doc.addPage();
                        yPosition = 20;
                    }

                    // Section header
                    doc.setFontSize(12);
                    doc.setFont(undefined, 'bold');
                    doc.setTextColor(0, 0, 0);
                    doc.text('Event Details', margin, yPosition);
                    yPosition += 8;

                    // Details box with background
                    const detailsStartY = yPosition;
                    const detailsBoxWidth = pageWidth - 2 * margin;
                    let detailsBoxHeight = 0;

                    doc.setFontSize(10);
                    doc.setFont(undefined, 'normal');
                    doc.setTextColor(0, 0, 0);

                    // Get additional fields if they exist
                    const eventTypeEl = document.getElementById('anon_eventType');
                    const statusEl = document.getElementById('anon_status');
                    const departmentEl = document.getElementById('anon_department');
                    const likelihoodEl = document.getElementById('anon_likelihood');
                    const severityEl = document.getElementById('anon_severity');
                    const riskRatingEl = document.getElementById('anon_riskRating');

                    const eventType = eventTypeEl ? eventTypeEl.value : null;
                    const status = statusEl ? statusEl.value : null;
                    const department = departmentEl ? departmentEl.value : null;
                    const likelihood = likelihoodEl ? likelihoodEl.value : null;
                    const severity = severityEl ? severityEl.value : null;
                    const riskRating = riskRatingEl ? riskRatingEl.value : null;

                    const details = [
                        eventType ? { label: 'Event Type', value: eventType } : null,
                        status ? { label: 'Status', value: status } : null,
                        { label: 'Reported By', value: reporterName || 'N/A' },
                        { label: 'Reported Date', value: formatDateTime(eventDate) || 'N/A' },
                        department ? { label: 'Department', value: department } : null,
                        { label: 'Primary Category', value: primaryCategory || 'N/A' },
                        { label: 'Secondary Category', value: secondaryCategory || 'N/A' },
                        reporterEmail ? { label: 'Reporter Email', value: reporterEmail } : null,
                        { label: 'Location', value: location || 'Not specified' },
                        likelihood ? { label: 'Likelihood', value: likelihood.toString() } : null,
                        severity ? { label: 'Severity', value: severity.toString() } : null,
                        riskRating ? { label: 'Risk Rating', value: riskRating.toString() } : null
                    ].filter(d => d !== null);

                    // Calculate box height
                    detailsBoxHeight = details.length * (lineHeight + 2) + 4;

                    // Draw details box
                    doc.setFillColor(250, 250, 252);
                    doc.rect(margin, detailsStartY - 2, detailsBoxWidth, detailsBoxHeight, 'F');
                    doc.setDrawColor(220, 220, 220);
                    doc.setLineWidth(0.3);
                    doc.rect(margin, detailsStartY - 2, detailsBoxWidth, detailsBoxHeight);

                    // Draw details in two columns for better space usage
                    const columnWidth = (detailsBoxWidth - 10) / 2;
                    let leftColumnY = detailsStartY + 2;
                    let rightColumnY = detailsStartY + 2;
                    const maxY = detailsStartY + detailsBoxHeight - 4;

                    details.forEach((detail, index) => {
                        const useRightColumn = index >= Math.ceil(details.length / 2);
                        const currentY = useRightColumn ? rightColumnY : leftColumnY;
                        const xPos = useRightColumn ? margin + columnWidth + 5 : margin + 5;

                        if (currentY > maxY) {
                            doc.addPage();
                            yPosition = 20;
                            return;
                        }

                        // Label
                        doc.setFont(undefined, 'bold');
                        doc.setTextColor(60, 60, 60);
                        doc.text(detail.label + ':', xPos, currentY);

                        // Value
                        doc.setFont(undefined, 'normal');
                        doc.setTextColor(0, 0, 0);
                        const labelWidth = doc.getTextWidth(detail.label + ': ');
                        const valueLines = doc.splitTextToSize(detail.value, columnWidth - labelWidth - 5);
                        const valueText = valueLines[0];
                        const valueTextWidth = doc.getTextWidth(valueText);

                        // Apply colored box for Risk Rating
                        if (detail.label === 'Risk Rating' && detail.value) {
                            const riskRating = parseInt(detail.value);
                            const boxX = xPos + labelWidth;
                            const boxY = currentY - 4.5;
                            const boxWidth = valueTextWidth + 8;
                            const boxHeight = 7.5;

                            if (riskRating > 10) {
                                // High Risk: background #ffe6e6 (255, 230, 230), border #e74c3c (231, 76, 60)
                                doc.setFillColor(255, 230, 230);
                                doc.rect(boxX - 3, boxY, boxWidth, boxHeight, 'F');
                                doc.setDrawColor(231, 76, 60);
                                doc.setLineWidth(1);
                                doc.rect(boxX - 3, boxY, boxWidth, boxHeight);
                            } else if (riskRating > 6) {
                                // Medium Risk: background #fff4e6 (255, 244, 230), border #f39c12 (243, 156, 18)
                                doc.setFillColor(255, 244, 230);
                                doc.rect(boxX - 3, boxY, boxWidth, boxHeight, 'F');
                                doc.setDrawColor(243, 156, 18);
                                doc.setLineWidth(1);
                                doc.rect(boxX - 3, boxY, boxWidth, boxHeight);
                            } else {
                                // Low Risk: background #e8f5e9 (232, 245, 233), border #4caf50 (76, 175, 80)
                                doc.setFillColor(232, 245, 233);
                                doc.rect(boxX - 3, boxY, boxWidth, boxHeight, 'F');
                                doc.setDrawColor(76, 175, 80);
                                doc.setLineWidth(1);
                                doc.rect(boxX - 3, boxY, boxWidth, boxHeight);
                            }
                        }

                        doc.text(valueText, xPos + labelWidth, currentY);

                        if (useRightColumn) {
                            rightColumnY += (valueLines.length * lineHeight) + 2;
                        } else {
                            leftColumnY += (valueLines.length * lineHeight) + 2;
                        }
                    });

                    // Ensure yPosition is clearly after the Event Details box
                    const detailsBoxEndY = detailsStartY - 2 + detailsBoxHeight;
                    yPosition = Math.max(Math.max(leftColumnY, rightColumnY), detailsBoxEndY) + sectionSpacing + 5;

                    // Event Description Section with better styling - clearly outside Event Details box
                    if (yPosition > pageHeight - 50) {
                        doc.addPage();
                        yPosition = 20;
                    }

                    // Description header
                    doc.setFontSize(12);
                    doc.setFont(undefined, 'bold');
                    doc.setTextColor(0, 0, 0);
                    doc.text('Description', margin, yPosition);
                    yPosition += 8;

                    // Description box
                    const descBoxStartY = yPosition;
                    const descBoxWidth = pageWidth - 2 * margin;
                    const descLines = doc.splitTextToSize(description || 'No description provided', descBoxWidth - 10);
                    const descBoxHeight = (descLines.length * lineHeight) + 8;

                    // Draw description box
                    doc.setFillColor(250, 250, 252);
                    doc.rect(margin, descBoxStartY - 2, descBoxWidth, descBoxHeight, 'F');
                    doc.setDrawColor(220, 220, 220);
                    doc.setLineWidth(0.3);
                    doc.rect(margin, descBoxStartY - 2, descBoxWidth, descBoxHeight);

                    // Description text
                    doc.setFontSize(10);
                    doc.setFont(undefined, 'normal');
                    doc.setTextColor(0, 0, 0);
                    let descY = descBoxStartY + 4;
                    descLines.forEach(line => {
                        if (descY > pageHeight - 20) {
                            doc.addPage();
                            descY = 20;
                        }
                        doc.text(line, margin + 5, descY);
                        descY += lineHeight;
                    });

                    yPosition = descY + sectionSpacing;

                    // File attachments info
                    const fileInput = document.getElementById('anon_attachments');
                    if (fileInput && fileInput.files.length > 0) {
                        yPosition += 5;
                        if (yPosition > pageHeight - 30) {
                            doc.addPage();
                            yPosition = 20;
                        }
                        doc.setFontSize(11);
                        doc.setTextColor(0, 0, 0);
                        doc.text('Attachments:', margin, yPosition);
                        yPosition += 7;
                        doc.setFontSize(10);
                        doc.setTextColor(0, 0, 0);
                        for (let i = 0; i < fileInput.files.length; i++) {
                            if (yPosition > pageHeight - 20) {
                                doc.addPage();
                                yPosition = 20;
                            }
                            const file = fileInput.files[i];
                            const fileSize = (file.size / 1024).toFixed(2) + ' KB';
                            doc.text(`- ${file.name} (${fileSize})`, margin, yPosition);
                            yPosition += lineHeight;
                        }
                    }

                    // Footer with generated date and page numbering
                    const totalPages = doc.internal.getNumberOfPages();
                    for (let i = 1; i <= totalPages; i++) {
                        doc.setPage(i);
                        doc.setFontSize(8);
                        doc.setTextColor(128, 128, 128);
                        doc.text(`Page ${i} of ${totalPages}`, pageWidth - margin, pageHeight - 10, { align: 'right' });
                        doc.text(`Generated: ${formatDDMMMYYYY(new Date())}`, margin, pageHeight - 10);
                    }

                    // Save PDF
                    const fileName = `Event_Report_${eventId}_${new Date().toISOString().split('T')[0]}.pdf`;
                    doc.save(fileName);

                    // Hide loading overlay
                    hideLoading();

                    // Re-enable PDF button
                    if (generatePdfBtn) {
                        generatePdfBtn.disabled = false;
                        generatePdfBtn.innerHTML = originalPdfBtnText;
                    }
                } catch (error) {
                    console.error('Error generating PDF:', error);
                    alert('Error generating PDF: ' + error.message);

                    // Hide loading overlay on error
                    hideLoading();

                    // Re-enable PDF button
                    if (generatePdfBtn) {
                        generatePdfBtn.disabled = false;
                        generatePdfBtn.innerHTML = originalPdfBtnText;
                    }
                }
            }

            // Add PDF generation button handler
            const pdfButton = document.getElementById('generatePdfBtn');
            if (pdfButton) {
                pdfButton.addEventListener('click', generateEventPDF);
            }

            // Store form submission data for email confirmation - make globally accessible
            window.pendingFormSubmission = null;

            // Email confirmation modal functions - make them globally accessible
            window.openEmailConfirmationModal = function () {
                const modal = document.getElementById('emailConfirmationBox');
                if (modal) {
                    modal.classList.remove('hidden');
                }
            };

            window.closeEmailConfirmationModal = function () {
                const modal = document.getElementById('emailConfirmationBox');
                if (modal) {
                    modal.classList.add('hidden');
                }
            };

            window.handleEmailConfirmation = function (sendEmails) {
                window.closeEmailConfirmationModal();

                if (window.pendingFormSubmission) {
                    submitAnonymousEvent(sendEmails, window.pendingFormSubmission);
                    window.pendingFormSubmission = null;
                }
            };

            // Main form submission function
            async function submitAnonymousEvent(sendEmails, formData) {
                const submitBtn = document.getElementById('submitReportBtn') || anonEventForm.querySelector('button[type="submit"]');
                const messageDiv = document.getElementById('anonEventMessage');
                const originalBtnText = submitBtn.innerHTML;

                // Show loading overlay
                showLoading('Submitting Report...', sendEmails ? 'Saving and sending notifications...' : 'Saving your event report');

                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin" style="margin-right: 8px;"></i>Submitting...';
                messageDiv.style.display = 'none';

                formData.append('anonymous', '1');
                formData.append('send_emails', sendEmails ? '1' : '0');

                try {
                    const response = await fetch('php/submit_anonymous_event.php', {
                        method: 'POST',
                        body: formData
                    });

                    const result = await response.json();

                    if (result.success) {
                        // Hide loading overlay
                        hideLoading();

                        messageDiv.style.display = 'block';
                        messageDiv.style.background = '#d4edda';
                        messageDiv.style.color = '#155724';
                        messageDiv.style.border = '1px solid #c3e6cb';
                        messageDiv.innerHTML = '<i class="fas fa-check-circle" style="margin-right: 8px;"></i>' + (result.message || 'Event reported successfully!');

                        // Reset form
                        anonEventForm.reset();
                        anonSecondaryCategory.disabled = true;
                        anonSecondaryCategory.innerHTML = '<option value="">-- Select Category First --</option>';

                        // Close modal after 3 seconds
                        setTimeout(() => {
                            closeEventModal();
                        }, 3000);
                    } else {
                        // Hide loading overlay
                        hideLoading();

                        messageDiv.style.display = 'block';
                        messageDiv.style.background = '#f8d7da';
                        messageDiv.style.color = '#721c24';
                        messageDiv.style.border = '1px solid #f5c6cb';
                        messageDiv.innerHTML = '<i class="fas fa-exclamation-circle" style="margin-right: 8px;"></i>' + (result.error || 'Error submitting event. Please try again.');
                    }
                } catch (error) {
                    // Hide loading overlay
                    hideLoading();

                    messageDiv.style.display = 'block';
                    messageDiv.style.background = '#f8d7da';
                    messageDiv.style.color = '#721c24';
                    messageDiv.style.border = '1px solid #f5c6cb';
                    messageDiv.innerHTML = '<i class="fas fa-exclamation-circle" style="margin-right: 8px;"></i>Network error: ' + error.message;
                } finally {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalBtnText;
                }
            }

            if (anonEventForm) {
                anonEventForm.addEventListener('submit', async function (e) {
                    e.preventDefault();

                    // Store form data for later submission
                    const formData = new FormData(anonEventForm);
                    window.pendingFormSubmission = formData;

                    // Show email confirmation modal
                    window.openEmailConfirmationModal();
                });
            }
        });

        // Forgot Password Modal Functions
        function openForgotPasswordModal() {
            const loginBox = document.getElementById('loginBox');
            const forgotPasswordBox = document.getElementById('forgotPasswordBox');
            if (loginBox) loginBox.classList.add('hidden');
            if (forgotPasswordBox) {
                forgotPasswordBox.classList.remove('hidden');
                resetForgotPasswordForm();
            }
        }

        function closeForgotPasswordModal() {
            const forgotPasswordBox = document.getElementById('forgotPasswordBox');
            if (forgotPasswordBox) {
                forgotPasswordBox.classList.add('hidden');
                resetForgotPasswordForm();
            }
        }

        function resetForgotPasswordForm() {
            const step1 = document.getElementById('step1');
            const step2 = document.getElementById('step2');
            const message = document.getElementById('forgotPasswordMessage');
            const form = document.getElementById('forgotPasswordForm');

            if (step1) step1.style.display = 'block';
            if (step2) step2.style.display = 'none';
            if (message) {
                message.style.display = 'none';
                message.innerHTML = '';
            }
            if (form) form.reset();
        }

        async function verifyOldPassword(event) {
            event.preventDefault();
            const username = document.getElementById('fp_username').value.trim();
            const oldPassword = document.getElementById('fp_old_password').value;
            const messageDiv = document.getElementById('forgotPasswordMessage');
            const step1 = document.getElementById('step1');
            const step2 = document.getElementById('step2');

            if (!username || !oldPassword) {
                showPasswordMessage('Please enter both username and current password.', 'error');
                return;
            }

            try {
                const formData = new FormData();
                formData.append('username', username);
                formData.append('old_password', oldPassword);
                formData.append('action', 'verify');

                const response = await fetch('php/change_password.php', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (result.success) {
                    showPasswordMessage('Password verified successfully! Please enter your new password.', 'success');
                    if (step1) step1.style.display = 'none';
                    if (step2) step2.style.display = 'block';
                } else {
                    showPasswordMessage(result.error || 'Invalid username or password. Please try again.', 'error');
                }
            } catch (error) {
                showPasswordMessage('Network error: ' + error.message, 'error');
            }
        }

        async function changePassword(event) {
            event.preventDefault();
            const username = document.getElementById('fp_username').value.trim();
            const newPassword = document.getElementById('fp_new_password').value;
            const confirmPassword = document.getElementById('fp_confirm_password').value;
            const messageDiv = document.getElementById('forgotPasswordMessage');

            // Validate passwords match
            if (newPassword !== confirmPassword) {
                showPasswordMessage('New password and confirmation do not match. Please try again.', 'error');
                return;
            }

            try {
                const formData = new FormData();
                formData.append('username', username);
                formData.append('new_password', newPassword);
                formData.append('action', 'change');

                const response = await fetch('php/change_password.php', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (result.success) {
                    showPasswordMessage('Password changed successfully! You can now login with your new password.', 'success');
                    setTimeout(() => {
                        closeForgotPasswordModal();
                        const loginBox = document.getElementById('loginBox');
                        if (loginBox) loginBox.classList.remove('hidden');
                    }, 2000);
                } else {
                    showPasswordMessage(result.error || 'Failed to change password. Please try again.', 'error');
                }
            } catch (error) {
                showPasswordMessage('Network error: ' + error.message, 'error');
            }
        }

        function showPasswordMessage(message, type) {
            const messageDiv = document.getElementById('forgotPasswordMessage');
            if (!messageDiv) return;

            messageDiv.style.display = 'block';
            messageDiv.innerHTML = message;

            if (type === 'success') {
                messageDiv.style.background = '#d4edda';
                messageDiv.style.color = '#155724';
                messageDiv.style.border = '1px solid #c3e6cb';
            } else {
                messageDiv.style.background = '#f8d7da';
                messageDiv.style.color = '#721c24';
                messageDiv.style.border = '1px solid #f5c6cb';
            }
        }

        // Check password match in real-time
        document.addEventListener('DOMContentLoaded', function () {
            const newPasswordInput = document.getElementById('fp_new_password');
            const confirmPasswordInput = document.getElementById('fp_confirm_password');
            const matchMessage = document.getElementById('passwordMatchMessage');

            if (confirmPasswordInput && matchMessage) {
                confirmPasswordInput.addEventListener('input', function () {
                    const newPassword = newPasswordInput ? newPasswordInput.value : '';
                    const confirmPassword = this.value;

                    if (confirmPassword.length > 0) {
                        if (newPassword === confirmPassword) {
                            matchMessage.style.display = 'block';
                            matchMessage.style.background = '#d4edda';
                            matchMessage.style.color = '#155724';
                            matchMessage.style.border = '1px solid #c3e6cb';
                            matchMessage.innerHTML = '<i class="fas fa-check-circle" style="margin-right: 6px;"></i>Passwords match';
                        } else {
                            matchMessage.style.display = 'block';
                            matchMessage.style.background = '#f8d7da';
                            matchMessage.style.color = '#721c24';
                            matchMessage.style.border = '1px solid #f5c6cb';
                            matchMessage.innerHTML = '<i class="fas fa-exclamation-circle" style="margin-right: 6px;"></i>Passwords do not match';
                        }
                    } else {
                        matchMessage.style.display = 'none';
                    }
                });
            }
        });

        // Section Data
        const sections = document.querySelectorAll('.page-section');
        let currentSectionIndex = 0;
        let isScrolling = false;
        const scrollCooldown = 1000; // ms

        // Function to scroll to specific section
        function scrollToSection(index) {
            if (index < 0 || index >= sections.length) return;
            isScrolling = true;
            currentSectionIndex = index;

            sections[index].scrollIntoView({ behavior: 'smooth' });

            setTimeout(() => {
                isScrolling = false;
            }, scrollCooldown);
        }

        // Mouse Wheel Hijacking for 'Jump' transition
        function isAnyModalOpen() {
            // Check for both the old class logic and new overlay logic
            const overlays = document.querySelectorAll('.login-box:not(.hidden), .event-modal-overlay.show');
            return overlays.length > 0;
        }

        window.addEventListener('wheel', (e) => {
            if (isScrolling || isAnyModalOpen()) return;

            if (e.deltaY > 20) {
                // Scroll Down
                if (currentSectionIndex < sections.length - 1) {
                    scrollToSection(currentSectionIndex + 1);
                }
            } else if (e.deltaY < -20) {
                // Scroll Up
                if (currentSectionIndex > 0) {
                    scrollToSection(currentSectionIndex - 1);
                }
            }
        }, { passive: false });

        // Touch handling for mobile jump
        let touchStartY = 0;
        window.addEventListener('touchstart', (e) => {
            touchStartY = e.touches[0].clientY;
        }, { passive: true });

        window.addEventListener('touchmove', (e) => {
            if (isScrolling || isAnyModalOpen()) return;
            const touchEndY = e.touches[0].clientY;
            const diff = touchStartY - touchEndY;

            if (Math.abs(diff) > 50) {
                if (diff > 0 && currentSectionIndex < sections.length - 1) {
                    scrollToSection(currentSectionIndex + 1);
                } else if (diff < 0 && currentSectionIndex > 0) {
                    scrollToSection(currentSectionIndex - 1);
                }
            }
        }, { passive: true });

        // Keyboard navigation
        window.addEventListener('keydown', (e) => {
            if (isScrolling || isAnyModalOpen()) return;
            if (e.key === 'ArrowDown' || e.key === 'PageDown') {
                scrollToSection(Math.min(currentSectionIndex + 1, sections.length - 1));
            } else if (e.key === 'ArrowUp' || e.key === 'PageUp') {
                scrollToSection(Math.max(currentSectionIndex - 1, 0));
            }
        });

        // Intersection Observer for scroll reveal animations
        const observerOptions = {
            threshold: 0.15,
            rootMargin: "0px"
        };

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                const section = entry.target;
                if (entry.isIntersecting) {
                    section.classList.add('section-visible');
                    section.classList.remove('section-hidden');
                } else {
                    section.classList.add('section-hidden');
                    section.classList.remove('section-visible');
                }
            });
        }, observerOptions);

        // Initialize components
        document.addEventListener('DOMContentLoaded', () => {
            // Observe sections
            document.querySelectorAll('.page-section').forEach(section => {
                observer.observe(section);
            });

            // QR Code initialization
            if (typeof QRCode !== 'undefined') {
                generateQRCode();
            } else {
                const checkQRCode = setInterval(() => {
                    if (typeof QRCode !== 'undefined') {
                        generateQRCode();
                        clearInterval(checkQRCode);
                    }
                }, 500);
            }

            setupQRTooltip();

            // Category mapping for anonymous form
            const primarySelect = document.getElementById('anon_primaryCategory');
            const secondarySelect = document.getElementById('anon_secondaryCategory');
            if (primarySelect && secondarySelect) {
                primarySelect.addEventListener('change', function () {
                    const category = this.value;
                    secondarySelect.innerHTML = '<option value="">-- Select Subcategory --</option>';
                    if (category && categoryMapping[category]) {
                        secondarySelect.disabled = false;
                        categoryMapping[category].forEach(sub => {
                            const option = document.createElement('option');
                            option.value = sub;
                            option.textContent = sub;
                            secondarySelect.appendChild(option);
                        });
                    } else {
                        secondarySelect.disabled = true;
                    }
                });
            }

            // Setup location autocomplete
            if (typeof setupLocationAutocomplete === 'function') {
                setupLocationAutocomplete();
            }
        });

        // QR Hover Logic refinement
        function setupQRTooltip() {
            const wrapper = document.getElementById('qrCodeWrapper');
            const tooltip = document.getElementById('qrTooltip');
            const canvas = document.getElementById('qrCodeCanvas');
            if (!wrapper || !tooltip) return;

            wrapper.addEventListener('mouseenter', () => {
                tooltip.style.opacity = '1';
                tooltip.style.visibility = 'visible';
                tooltip.style.transform = 'translateY(-50%) translateX(-15px)';
                if (canvas) canvas.style.transform = 'scale(1.05) translateY(-5px)';
            });

            wrapper.addEventListener('mouseleave', () => {
                tooltip.style.opacity = '0';
                tooltip.style.visibility = 'hidden';
                tooltip.style.transform = 'translateY(-50%) translateX(-20px)';
                if (canvas) canvas.style.transform = 'scale(1) translateY(0)';
            });
        }

        // --- File Preview Logic for Report Modal ---
        function updateFilePreview(input) {
            const container = document.getElementById('filePreviewContainer');
            container.innerHTML = '';

            if (input.files && input.files.length > 0) {
                Array.from(input.files).forEach(file => {
                    const item = document.createElement('div');
                    item.className = 'file-preview-item';

                    let iconClass = 'fa-file-alt';
                    if (file.type.includes('image')) iconClass = 'fa-file-image';
                    else if (file.type.includes('pdf')) iconClass = 'fa-file-pdf';
                    else if (file.type.includes('word')) iconClass = 'fa-file-word';
                    else if (file.type.includes('excel') || file.type.includes('spreadsheet')) iconClass = 'fa-file-excel';

                    item.innerHTML = `
                        <i class="fas ${iconClass}"></i>
                        <span title="${file.name}">${file.name}</span>
                    `;
                    container.appendChild(item);
                });
            }
        }

        // Drag & Drop visual feedback
        const dropZone = document.getElementById('attachmentDropZone');
        if (dropZone) {
            ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
                dropZone.addEventListener(eventName, (e) => {
                    e.preventDefault();
                    e.stopPropagation();
                }, false);
            });

            ['dragenter', 'dragover'].forEach(eventName => {
                dropZone.addEventListener(eventName, () => dropZone.classList.add('drag-over'), false);
            });

            ['dragleave', 'drop'].forEach(eventName => {
                dropZone.addEventListener(eventName, () => dropZone.classList.remove('drag-over'), false);
            });

            dropZone.addEventListener('drop', (e) => {
                const dt = e.dataTransfer;
                const files = dt.files;
                document.getElementById('anon_attachments').files = files;
                updateFilePreview(document.getElementById('anon_attachments'));
            }, false);
        }

    </script>
</body>

</html>