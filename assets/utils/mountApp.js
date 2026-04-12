import { createApp } from "vue";
import { createAppI18n } from "@/i18n.js";

/**
 * Mount a Vue app to a DOM element with i18n support
 * @param {string} elementId - The ID of the element to mount to
 * @param {object} component - The Vue component
 * @param {function} [transformProps] - Optional function to transform el.dataset into component props
 */
export function mountApp(elementId, component, transformProps) {
    const el = document.getElementById(elementId);
    if (el) {
        const locale = el.dataset.locale || "fr";
        let props;

        if (transformProps) {
            props = transformProps(el.dataset);
        } else {
            // Convert string values to appropriate types
            props = {};
            for (const [key, value] of Object.entries(el.dataset).filter(
                ([k]) => k !== "locale",
            )) {
                if (value === "true" || value === "false") {
                    props[key] = value === "true";
                } else if (!isNaN(value) && value !== "") {
                    props[key] = Number(value);
                } else {
                    props[key] = value;
                }
            }
        }

        createApp(component, props).use(createAppI18n(locale)).mount(el);
    }
}
