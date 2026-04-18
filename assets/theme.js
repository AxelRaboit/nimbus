// ── Theme toggle ──────────────────────────────────────────────────────────────

const THEME_KEY = "nimbus-theme";

function currentTheme() {
    return document.documentElement.classList.contains("dark")
        ? "dark"
        : "light";
}

function applyTheme(theme) {
    const htmlElement = document.documentElement;
    htmlElement.classList.add("theme-transitioning");
    htmlElement.classList.toggle("dark", theme === "dark");
    localStorage.setItem(THEME_KEY, theme);
    window.setTimeout(
        () => htmlElement.classList.remove("theme-transitioning"),
        300,
    );
}

function initThemeToggle() {
    const button = document.getElementById("theme-toggle");
    const mobileButton = document.getElementById("theme-toggle-mobile");

    function render() {
        const dark = currentTheme() === "dark";
        [
            [button, ".icon-moon", ".icon-sun", ".theme-label"],
            [
                mobileButton,
                ".icon-moon-mobile",
                ".icon-sun-mobile",
                ".theme-label-mobile",
            ],
        ].forEach(([buttonElement, moonSel, sunSel, labelSel]) => {
            if (!buttonElement) return;
            buttonElement.querySelector(moonSel).style.display = dark
                ? "none"
                : "";
            buttonElement.querySelector(sunSel).style.display = dark
                ? ""
                : "none";
            buttonElement.querySelectorAll(labelSel).forEach((labelElement) => {
                labelElement.textContent = dark
                    ? buttonElement.dataset.labelDark
                    : buttonElement.dataset.labelLight;
            });
        });
    }

    function toggle() {
        applyTheme(currentTheme() === "dark" ? "light" : "dark");
        render();
    }

    button?.addEventListener("click", toggle);
    mobileButton?.addEventListener("click", toggle);

    render();
}

// ── Language switcher ─────────────────────────────────────────────────────────

function initLangSwitcher() {
    const langButton =
        document.getElementById("sidebar-lang-btn") ||
        document.getElementById("lang-btn");
    const dropdown =
        document.getElementById("sidebar-lang-dropdown") ||
        document.getElementById("lang-dropdown");
    const chevron =
        document.getElementById("sidebar-lang-chevron") ||
        document.getElementById("lang-chevron");
    if (!langButton || !dropdown) return;

    function open() {
        dropdown.classList.remove("hidden");
        if (chevron) chevron.style.transform = "rotate(180deg)";
    }

    function close() {
        dropdown.classList.add("hidden");
        if (chevron) chevron.style.transform = "";
    }

    langButton.addEventListener("click", (event) => {
        event.stopPropagation();
        dropdown.classList.contains("hidden") ? open() : close();
    });

    document.addEventListener("click", (event) => {
        const switcher =
            document.getElementById("sidebar-lang-switcher") ||
            document.getElementById("lang-switcher");
        if (!switcher?.contains(event.target)) close();
    });

    dropdown.querySelectorAll(".lang-option").forEach((option) => {
        option.addEventListener("click", async () => {
            const locale = option.dataset.locale;
            close();
            await fetch("/locale", {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify({ locale }),
            });
            window.location.reload();
        });
    });
}

// ── Sidebar collapse ──────────────────────────────────────────────────────────

function initSidebar() {
    const collapseBtn = document.getElementById("sidebar-collapse-btn");
    const expandBtn = document.getElementById("sidebar-expand-btn");

    function setCollapsed(collapsed) {
        document.documentElement.classList.toggle(
            "sidebar-collapsed",
            collapsed,
        );
        localStorage.setItem(
            "nimbus-sidebar",
            collapsed ? "collapsed" : "expanded",
        );
    }

    collapseBtn?.addEventListener("click", () => setCollapsed(true));
    expandBtn?.addEventListener("click", () => setCollapsed(false));
}

function initMobileMenu() {
    const openBtn = document.getElementById("mobile-menu-btn");
    const closeBtn = document.getElementById("mobile-menu-close");
    const overlay = document.getElementById("mobile-overlay");
    const backdrop = document.getElementById("mobile-backdrop");
    if (!openBtn || !overlay) return;

    function open() {
        overlay.classList.add("open");
        document.body.style.overflow = "hidden";
    }
    function close() {
        overlay.classList.remove("open");
        document.body.style.overflow = "";
    }

    openBtn.addEventListener("click", open);
    closeBtn?.addEventListener("click", close);
    backdrop?.addEventListener("click", close);
}

// ── Init ──────────────────────────────────────────────────────────────────────

document.addEventListener("DOMContentLoaded", () => {
    initThemeToggle();
    initLangSwitcher();
    initSidebar();
    initMobileMenu();
});
