// Generic function to wrap initializers with error handling
// sheener/js/index.js
async function safeInitialize(initializer, name) {
    const start = performance.now();
    try {
      await initializer(); // Supports both async and sync initializers
      const end = performance.now();
      console.log(`${name} initialized successfully in ${end - start}ms.`);
    } catch (error) {
      console.error(`${name} initialization failed:`, error);
    }
  }
  
  // UI Initialization
  function initializeUI() {
    const uiInitializers = [
      { func: initializeNavbar, name: 'Navbar' },
      { func: initializePanel, name: 'Panel' },
      { func: initializeTopbar, name: 'Topbar' }
    ];
    uiInitializers.forEach(({ func, name }) => safeInitialize(func, name));
  }
  
  // Feature Initialization
  function initializeFeatures() {
    const featureInitializers = [
      { func: initializeAssessments, name: 'Assessments' },
      { func: initializeCalendar, name: 'Calendar' },
      { func: initializePermitCreation, name: 'Permit Creation' },
      { func: initializeDateSlicer, name: 'Date Slicer' }
    ];
    featureInitializers.forEach(({ func, name }) => safeInitialize(func, name));
  }
  
  // Core Feature Initialization
  function initializeCoreFeatures() {
    const coreInitializers = [
      { func: initializePlanner, name: 'Planner' },
      { func: initializeProcessMenu, name: 'Process Menu' }
    ];
    coreInitializers.forEach(({ func, name }) => safeInitialize(func, name));
  }
  