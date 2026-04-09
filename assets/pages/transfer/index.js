import { createApp } from "vue";
import TransferApp from "./TransferApp.vue";

const el = document.getElementById("app-transfer");
if (el) {
    createApp(TransferApp, { ...el.dataset }).mount(el);
}
