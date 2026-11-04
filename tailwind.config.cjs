/** @type {import('tailwindcss').Config} */
module.exports = {
    content: ["./assets/**/*.js", "./templates/**/*.html.twig"],
    darkMode: "class",
    theme: {
        extend: {
            colors: {
                "surface-container": "#1a1919",
                primary: "#f4ffc6",
                "primary-dim": "#c7ef00",
                surface: "#0e0e0e",
                "on-surface-variant": "#adaaaa",
                "primary-container": "var(--theme-main)",
                "primary-dim": "var(--theme-dim)",
                "surface-container-lowest": "#000000",
                "surface-container-high": "#201f1f",
                "outline-variant": "#484847",
                "on-primary-container": "var(--theme-text)",
                "surface-container-low": "#131313",
                "surface-container-highest": "#262626",
                "on-surface": "#ffffff",
                outline: "#767575",
                "error-container": "#b92902",
            },
            fontFamily: {
                headline: ["Space Grotesk", "sans-serif"],
                body: ["Manrope", "sans-serif"],
                label: ["Manrope", "sans-serif"],
            },
        },
    },
    plugins: [
        require("@tailwindcss/forms"),
        require("@tailwindcss/container-queries"),
    ],
};
