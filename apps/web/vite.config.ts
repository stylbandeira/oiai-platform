import { defineConfig } from "vite";
import react from "@vitejs/plugin-react-swc";
import path from "path";

export default defineConfig(({ mode }) => ({
  server: {
    host: "::",
    port: 3000,
    watch: {
      usePolling: true,
    }
  },
  build: {
    esbuild: mode === "production"
      ? {
          drop: ["console", "debugger"],
        }
      : undefined,
    rollupOptions: {
      output: {
        manualChunks: undefined
      }
    }
  },
  plugins: [
    react(),
  ],
  resolve: {
    alias: {
      "@": path.resolve(__dirname, "./src"),
    },
  },
}));
