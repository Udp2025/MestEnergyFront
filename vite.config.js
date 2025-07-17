import { defineConfig } from "vite";
import laravel from "laravel-vite-plugin";
import vue from "@vitejs/plugin-vue";

export default defineConfig({
  plugins: [
    laravel({
      input: [
        "resources/js/app.js",
        "resources/js/pages/benchmark.js",
        "resources/css/app.css",
        "resources/css/pages/benchmark.css",
      ],
      refresh: true,
    }),
    vue(),
  ],
});
