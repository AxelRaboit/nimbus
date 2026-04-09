import { createApp } from "vue";
import ManageApp from "./ManageApp.vue";

const el = document.getElementById("app-manage");
if (el) {
    createApp(ManageApp, { ...el.dataset }).mount(el);
}
