import { mountApp } from "@/utils/mountApp.js";
import HomeApp from "./HomeApp.vue";

mountApp("app-home", HomeApp, (data) => ({
    userEmail: data.userEmail || "",
    isGuest: data.isGuest === "true",
    maxSizeMb: Number(data.maxSizeMb) || 500,
    maxFiles: Number(data.maxFiles) || 20,
    maxRecipients: Number(data.maxRecipients) || 20,
    maxExpiryDays: Number(data.maxExpiryDays) || 7,
    expiryOptions: data.expiryOptions || "[24]",
    extensionGroups: data.extensionGroups || "{}",
    accessPasswordEnabled: data.accessPasswordEnabled === "true",
    accessGranted: data.accessGranted !== "false",
    isPro: data.isPro === "true",
    planPath: data.planPath || "/plan",
    loginPath: data.loginPath || "/login",
    registerPath: data.registerPath || "/register",
    registrationEnabled: data.registrationEnabled !== "false",
}));
