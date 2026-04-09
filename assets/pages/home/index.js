import { createApp } from "vue";
import HomeApp from "./HomeApp.vue";

const el = document.getElementById("app-home");
if (el) {
    createApp(HomeApp).mount(el);
}
