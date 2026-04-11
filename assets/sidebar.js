import "./css/sidebar.css";
import { mountApp } from "@/utils/mountApp.js";
import AppSidebar from "@/components/AppSidebar.vue";

mountApp("app-sidebar", AppSidebar, (data) => ({
    userName: data.userName || "",
    userEmail: data.userEmail || "",
    appVersion: data.appVersion || "",
    activeRoute: data.activeRoute || "",
    logoutCsrf: data.logoutCsrf || "",
    homePath: data.homePath || "/",
    profilePath: data.profilePath || "/profile",
    logoutPath: data.logoutPath || "/logout",
    loginPath: data.loginPath || "/login",
    isGuest: data.isGuest === "true",
    isDev: data.isDev === "true",
    devPath: data.devPath || "/dev",
    dashboardPath: data.dashboardPath || "/dashboard",
    userPlan: data.userPlan || "free",
    planPath: data.planPath || "/plan",
}));
