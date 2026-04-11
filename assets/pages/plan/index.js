import { mountApp } from "@/utils/mountApp.js";
import PlanApp from "./PlanApp.vue";

mountApp("app-plan", PlanApp, (data) => ({
    isPro: data.isPro === "true",
    proPrice: Number(data.proPrice) || 9.99,
    freeMaxSizeMb: Number(data.freeMaxSizeMb) || 100,
    freeMaxFiles: Number(data.freeMaxFiles) || 3,
    freeMaxExpiryHours: Number(data.freeMaxExpiryHours) || 24,
    proMaxSizeMb: Number(data.proMaxSizeMb) || 10000,
    proMaxFiles: Number(data.proMaxFiles) || 20,
    proMaxExpiryDays: Number(data.proMaxExpiryDays) || 7,
    upgradePath: data.upgradePath || "/plan/upgrade",
    downgradePath: data.downgradePath || "/plan/downgrade",
    csrfToken: data.csrfToken || "",
}));
