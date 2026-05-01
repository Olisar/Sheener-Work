# Project Architecture: SHEEner

This document describes the design and structure of the SHEEner EHS Management System.

## Overview
SHEEner is a web-based platform designed for monitoring and managing Environment, Health, and Safety (EHS) performance metrics. It utilizes a classic client-server architecture with a PHP-based backend and a modern vanilla JavaScript frontend.

---

## 🏗️ Front-end (Client-Side)

The frontend is built for high responsiveness and a premium user experience without the overhead of heavy frameworks.

### Technologies
- **HTML5**: Semantic structure for accessibility and SEO.
- **Vanilla CSS**: Custom styling with a modular design system (found in `/css`).
- **Vanilla JavaScript**: All interactive logic and data fetching (found in `/js`).

### Key Components
- **Dynamic Dashboards**: Real-time data visualization using CSS and SVG.
- **Searchable Dropdowns**: Custom UI components for handling large data lists.
- **PDF Generation**: Client-side report generation using `html2canvas` and `jsPDF`.
- **Modular Includes**: Standardized headers and navigation bars provided via `/includes`.

---

## ⚙️ Back-end (Server-Side)

The backend provides a secure and scalable API layer for data persistence and business logic.

### Technologies
- **PHP 8.x**: Server-side processing.
- **MySQL**: Relational database for storing KPI figures, user data, and logs.

### Structure
- **API Endpoints**: Located in the `/php` directory. Each script handles specific actions (e.g., fetching monthly KPI data or saving form entries).
- **Security**: 
    - Session-based authentication.
    - CSRF (Cross-Site Request Fugery) protection on all POST requests.
    - Input validation and sanitization.

---

## 📂 Directory Structure

| Folder | Purpose |
| :--- | :--- |
| `/css` | Design system, layout styles, and component-specific CSS. |
| `/js` | Application logic, utility scripts, and vendor libraries. |
| `/php` | Backend API handlers and database interaction scripts. |
| `/includes` | Shared PHP components (Navigation, Header, Footer). |
| `/img` | Static assets, icons, and branding materials. |
| `/uploads` | Storage for user-uploaded documents and certificates. |

---

## 🔄 Data Flow

1. **User Action**: User interacts with a UI element (e.g., changing a date or clicking "Save").
2. **Event Handling**: JavaScript captures the event and gathers relevant data from the DOM.
3. **API Request**: JS sends an asynchronous `fetch` request (POST/GET) to a specific PHP script in `/php`.
4. **Processing**: PHP validates the session, processes the data, and interacts with the MySQL database.
5. **Response**: PHP returns a JSON object indicating success or failure.
6. **UI Update**: JavaScript parses the JSON and updates the UI (colors, notifications, or values) without reloading the page.

---

## 🛠️ Development Tools
- **Version Control**: Git (pushed to GitHub).
- **Environment**: XAMPP (Local Apache/MySQL stack).
- **Editor**: Cursor / VS Code.
