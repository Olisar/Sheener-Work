<?php
/* File: sheener/php/permit_modal.js.php */

session_start();
header('Content-Type: application/javascript');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>

// --- CSRF setup ---
window.CSRF_TOKEN = <?= json_encode($_SESSION['csrf_token']) ?>;

// --- Modal Management (used by permitlist1 and permit_list1) ---

function openAddPermitModal() {
  const targetModal = document.getElementById("permitModal");

  if (!targetModal) {
    console.error("permitModal element not found in DOM.");
    alert("Cannot open permit modal — element #permitModal is missing.");
    return;
  }

  const activeModal = document.querySelector(".modal.show");
  try {
    // If another modal is open, close it first
    if (activeModal && activeModal !== targetModal) {
      const activeInstance = bootstrap.Modal.getInstance(activeModal);
      if (activeInstance) {
        activeInstance.hide();
        console.log("Closing active modal before opening permitModal...");
      }
    }

    // Wait a very short moment (~150ms) to allow closure animations to settle
    setTimeout(() => {
      const modalInstance = bootstrap.Modal.getOrCreateInstance(targetModal);
      modalInstance.show();
      console.log("Permit modal opened successfully (#permitModal).");
    }, 150);

  } catch (err) {
    console.error("Error opening permit modal:", err);
    alert("Cannot open permit modal — ensure 'php/permit_modal.js.php' is loaded after 'js/navpermit.js'");
  }
}

function closePermitModal() {
  const modalEl = document.getElementById("permitModal");
  if (modalEl) {
    const modal = bootstrap.Modal.getInstance(modalEl);
    modal?.hide();
    console.log("Permit modal closed.");
  }
}
