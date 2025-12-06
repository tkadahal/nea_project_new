import { defineConfig } from "vite";
import laravel from "laravel-vite-plugin";
import tailwindcss from "@tailwindcss/vite";

export default defineConfig({
    plugins: [
        laravel({
            input: [
                "resources/css/app.css",
                "resources/css/toastr.css",
                "resources/js/app.js",
                "resources/js/taskCalendar.js",
                "resources/js/departmentLoader.js",
                "resources/js/formDependencies.js",
                "resources/js/projectBudget.js",
                "resources/js/projectLoader.js",
                "resources/js/contract.js",
                "resources/js/fullcalendar.js",
            ],
            refresh: true,
        }),
        tailwindcss(),
    ],
    resolve: {
        alias: {
            toastr: "toastr/build/toastr.min.js",
        },
    },
});
