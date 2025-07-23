import { defineConfig } from "vite";
import laravel from "laravel-vite-plugin";
import vue from "@vitejs/plugin-vue";

export default defineConfig({
  plugins: [
    laravel({
      input: [
        "resources/css/app.css",
        "resources/css/plot/common.css",
        "resources/css/pages/scatter.css",
        "resources/css/pages/benchmark.css",
        "resources/css/pages/heat_map.css",
        "resources/css/pages/time_series.css",
        "resources/js/app.js",
        "resources/js/pages/scatter.js",
        "resources/js/pages/benchmark.js",
        "resources/js/pages/heat_map.js",
        "resources/js/pages/time_series.js",
      ],
      refresh: true,
    }),
    vue(),
  ],
});
