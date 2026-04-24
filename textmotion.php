<?php
/* File: sheener/textmotion.php */

$page_title = 'Three-Word Staggered Animation';
$use_ai_navigator = false;
$user_role = 'User';
$user_id = '';
$user_name = 'User';
include 'includes/header.php';
?>


  <div class="pink-box">
    <div class="animated-paragraph" id="animatedParagraph">
      <!-- Words will be dynamically added here -->
    </div>
  </div>

  <script>
    const paragraph = [
      "Being a pedestrian carries the risk of accidents with vehicles, particularly",
      "in areas with high traffic, where the lack of dedicated crosswalks and insufficient visibility can increase",
      "the likelihood of injury."
    ];

    const animatedParagraph = document.getElementById("animatedParagraph");

    function startAnimation() {
      animatedParagraph.innerHTML = ""; // Clear the paragraph to restart animation

      let globalWordIndex = 0; // Reset the global animation sequence

      paragraph.forEach((line) => {
        // Create a container for each line
        const lineDiv = document.createElement("div");
        lineDiv.classList.add("line");

        // Split the line into words using spaces
        line.split(/\s+/).forEach((word, wordIndex) => {
          const wordSpan = document.createElement("span");
          wordSpan.textContent = word;

          // Apply staggered animation with three-word overlap
          wordSpan.style.animationDelay = `${Math.floor(globalWordIndex / 3) * 0.55}s`;
          lineDiv.appendChild(wordSpan);

          globalWordIndex++; // Increment the global word index

          // Add a space span (if not the last word)
          if (wordIndex < line.split(/\s+/).length - 1) {
            const spaceSpan = document.createElement("span");
            spaceSpan.textContent = " "; // A single space
            spaceSpan.style.animationDelay = `${Math.floor(globalWordIndex / 3) * 0.55}s`;
            lineDiv.appendChild(spaceSpan);

            globalWordIndex++; // Increment the global index for the space
          }
        });

        // Add the line to the paragraph container
        animatedParagraph.appendChild(lineDiv);
      });

      // Restart the animation after it's complete
      const totalAnimationTime = Math.ceil(globalWordIndex / 3) * 0.55 * 1000 + 1320; // Total time for all words + animation duration
      setTimeout(() => {
        startAnimation();
      }, totalAnimationTime + 2000); // Wait 2 seconds before restarting
    }

    startAnimation(); // Start the first animation
  </script>
<?php include 'includes/footer.php'; ?>
