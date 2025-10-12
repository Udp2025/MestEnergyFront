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
        "resources/css/pages/timeseries.css",
        "resources/css/pages/heat_map.css",
        "resources/js/app.js",
        "resources/js/pages/scatter.js",
        "resources/js/pages/timeseries.js",
        "resources/js/pages/heat_map.js",
        "resources/css/pages/benchmarking.css",
        "resources/js/pages/benchmarking.js",
        "resources/js/pages/histogram.js",
        "resources/css/pages/histogram.css",
      ],
      refresh: true,
    }),
    vue(),
  ],
});
