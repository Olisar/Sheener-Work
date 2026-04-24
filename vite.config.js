/* File: sheener/vite.config.js */
import { defineConfig } from "vite";
import react from "@vitejs/plugin-react";

export default defineConfig({
  plugins: [react()],
  root: "src", // Set the React source folder
  server: {
    port: 5173, // Change the port if needed
  },
});
