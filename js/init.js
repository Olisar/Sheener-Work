/* File: sheener/js/init.js */
// init.js

document.addEventListener("DOMContentLoaded", () => {
  console.log("Initializing the application...");

  let checkAttempts = 0;
  const maxAttempts = 10; // Stop checking after 10 attempts (5 seconds)

  const interval = setInterval(() => {
    checkAttempts++;

    if (typeof Context !== "undefined" && typeof Context.init === "function") {
      clearInterval(interval); // Stop checking
      try {
        Context.init();
        console.log("Context initialized successfully.");
      } catch (error) {
        console.error("Error initializing Context:", error);
      }
    } else if (checkAttempts >= maxAttempts) {
      clearInterval(interval); // Stop checking after maxAttempts
      console.warn("Context is undefined or missing init(). Check panel.js.");
    }
  }, 500); // Check every 500ms
});
