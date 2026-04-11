import { createApp, h } from "vue";
import { Toaster, toast } from "vue-sonner";
import "vue-sonner/style.css";

document.addEventListener("DOMContentLoaded", () => {
    const el = document.createElement("div");
    document.body.appendChild(el);
    createApp({
        render: () =>
            h(Toaster, {
                theme: "dark",
                position: "bottom-center",
                richColors: true,
            }),
    }).mount(el);

    const flashes = window.__flash__ ?? {};
    for (const [type, messages] of Object.entries(flashes)) {
        for (const message of messages) {
            (toast[type] ?? toast)(message);
        }
    }
});
