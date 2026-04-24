/* File: sheener/js/panel.js */
class Context {
  static PANEL_CLASS = "optipanel";
  static sharedData = []; // Ensures sharedData is always an array

  static async init() {
    try {
      console.log("🔄 Initializing panel...");
      Context.docHead = document.head || document.documentElement;

      // ✅ Fetch Data First
      Context.sharedData = await fetchData();
      if (
        !Array.isArray(Context.sharedData) ||
        Context.sharedData.length === 0
      ) {
        console.warn(
          "⚠️ No valid data received. Stopping panel initialization."
        );
        return;
      }

      // ✅ Ensure Data Exists Before Accessing It
      if (!Context.sharedData[0]) {
        console.warn("⚠️ Data array is empty or undefined.");
        return;
      }

      await Context.injectStyle();
      Context.setupRightColumn();
      Context.executeTools();
      populateTable(Context.sharedData);

      console.log("✅ Panel initialization complete.");
    } catch (error) {
      console.error("❌ Error during panel initialization:", error);
    }
  }

  static async injectStyle() {
    try {
      console.log("🎨 Injecting panel styles...");
      const response = await fetch("css/panel.css");

      if (!response.ok)
        throw new Error(`Failed to load panel.css. Status: ${response.status}`);

      const styles = await response.text();
      const styleElement = document.createElement("style");
      styleElement.textContent = styles;
      Context.docHead?.appendChild(styleElement) ||
        console.warn("⚠️ Document head is not available.");

      console.log("✅ Styles injected successfully.");
    } catch (error) {
      console.error("❌ Error injecting styles:", error);
    }
  }

  static setupRightColumn() {
    try {
      console.log("📌 Setting up right column...");
      const rightColumn = document.createElement("div");
      rightColumn.className = Context.PANEL_CLASS;

      document.body?.appendChild(rightColumn) ||
        console.warn("⚠️ Document body not available.");
      console.log("✅ Right column added.");
    } catch (error) {
      console.error("❌ Error setting up right column:", error);
    }
  }

  static executeTools() {
    try {
      console.log("🔧 Checking for tools to execute...");
      const toolsArray = Context.getToolsArray();

      if (toolsArray.length > 0) {
        console.log("✅ Executing tool:", toolsArray[0]);
      } else {
        console.warn("⚠️ No tools found to execute.");
      }
    } catch (error) {
      console.error("❌ Error executing tools:", error);
    }
  }

  static getToolsArray() {
    return []; // Always return an array to prevent undefined errors
  }
}

// ✅ Function to Fetch Data Safely
async function fetchData() {
  try {
    console.log("📡 Fetching change control data...");
    const response = await fetch("php/get_change_controls.php");

    if (!response.ok) throw new Error(`HTTP error! Status: ${response.status}`);

    const result = await response.json();

    if (result.success && Array.isArray(result.data)) {
      console.log("✅ Data fetched successfully:", result.data);
      return result.data.length > 0 ? result.data : []; // Always return an array
    } else {
      console.warn("⚠️ Invalid API response format or empty data:", result);
      return [];
    }
  } catch (error) {
    console.error("❌ Error fetching data:", error);
    return []; // Return an empty array to prevent crashes
  }
}

// ✅ Initialize Context When DOM is Ready
document.addEventListener("DOMContentLoaded", async () => {
  try {
    await Context.init();
  } catch (error) {
    console.error("❌ Error initializing panel:", error);
  }
});

// ✅ Function to Populate Table
function populateTable(data) {
  const tableBody = document.querySelector("#changeControlTable tbody");
  if (!tableBody) {
    console.warn("⚠️ Table body not found. Skipping table population.");
    return;
  }

  tableBody.innerHTML = data.length
    ? data
        .map(
          (changeControl) => `
            <tr data-cc-id="${changeControl.cc_id}">
                <td>${changeControl.cc_id}</td>
                <td class="change-title">${changeControl.title}</td>
                <td>${formatDate(changeControl.target_date)}</td>
                <td>${changeControl.change_type}</td>
                <td class="comments">${
                  changeControl.justification || "N/A"
                }</td>
                <td>
                    <button onclick="openViewTaskModal(${
                      changeControl.cc_id
                    })">View</button>
                    <button onclick="openEditTaskModal(${
                      changeControl.cc_id
                    })">Edit</button>
                    <button onclick="deleteChangeControl(${
                      changeControl.cc_id
                    })">Delete</button>
                </td>
            </tr>
        `
        )
        .join("")
    : `<tr><td colspan="6" class="no-data">No change controls found.</td></tr>`;
}

// ✅ Format Date Function
function formatDate(dateString) {
  if (!dateString) return "N/A";

  const months = [
    "Jan",
    "Feb",
    "Mar",
    "Apr",
    "May",
    "Jun",
    "Jul",
    "Aug",
    "Sep",
    "Oct",
    "Nov",
    "Dec",
  ];
  let dateObj = new Date(dateString);

  return isNaN(dateObj)
    ? dateString
    : `${dateObj.getDate().toString().padStart(2, "0")}-${
        months[dateObj.getMonth()]
      }-${dateObj.getFullYear().toString().slice(-2)}`;
}

// ✅ Search Functionality
document
  .getElementById("change-control-search")
  ?.addEventListener("input", function () {
    const searchValue = this.value.toLowerCase();
    document.querySelectorAll("#changeControlTable tbody tr").forEach((row) => {
      const title =
        row.querySelector(".change-title")?.textContent.toLowerCase() || "";
      const comments =
        row.querySelector(".comments")?.textContent.toLowerCase() || "";
      row.style.display =
        title.includes(searchValue) || comments.includes(searchValue)
          ? ""
          : "none";
    });
  });

// ✅ Function to Delete Change Control
function deleteChangeControl(cc_id) {
  if (!confirm("Are you sure you want to delete this Change Control?")) return;

  fetch(`php/delete_change_control.php?cc_id=${cc_id}`, { method: "DELETE" })
    .then((response) => response.json())
    .then((data) => {
      if (data.success) {
        alert("Change Control deleted successfully.");
        Context.init(); // Refresh data after deletion
      } else {
        alert("Error deleting Change Control: " + data.error);
      }
    })
    .catch((error) => console.error("Error deleting Change Control:", error));
}
