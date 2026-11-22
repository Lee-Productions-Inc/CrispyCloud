import react from "@vitejs/plugin-react";
import laravel from "laravel-vite-plugin";
import tailwindcss from "@tailwindcss/vite";
import { defineConfig } from "vite";

export default defineConfig({
    plugins: [
        laravel({
            input: [
                "resources/css/app.css",
                "resources/css/cc-admin.css",
                "resources/css/cc-customer.css",
                "resources/css/cc-auth.css",
                "resources/css/cc-legal.css",
                "resources/js/app.js",
                "resources/js/customer/auth-visibility.js",
                "resources/js/app.tsx",
            ],
            ssr: "resources/js/ssr.jsx",
            refresh: true,
        }),
        react(),
        tailwindcss(),
    ],
    esbuild: {
        jsx: "automatic",
    },
});
