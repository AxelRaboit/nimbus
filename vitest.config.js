import { defineConfig } from "vitest/config";
import vue from "@vitejs/plugin-vue";
import path from "path";

export default defineConfig({
    plugins: [vue()],
    resolve: {
        alias: {
            "@": path.resolve(__dirname, "assets"),
        },
    },
    test: {
        environment: "jsdom",
        globals: true,
        env: {
            TZ: "UTC",
        },
    },
});
