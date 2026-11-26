/*
 * Welcome to your app's main JavaScript file!
 *
 * We recommend including the built version of this JavaScript file
 * (and its CSS file) in your base layout (base.html.twig).
 */

// any CSS you import will output into a single css file (app.css in this case)
import "./styles/app.css";

async function lockPortraitOrientation() {
    if (window.innerWidth >= 1024) return;

    const isStandalone =
        window.matchMedia("(display-mode: standalone)").matches ||
        window.navigator.standalone === true;

    if (isStandalone) {
        try {
            const docEl = document.documentElement;
            if (!document.fullscreenElement && docEl.requestFullscreen) {
                await docEl.requestFullscreen({ navigationUI: "hide" });
            }
        } catch (e) {}
    }

    try {
        if (
            screen.orientation &&
            typeof screen.orientation.lock === "function"
        ) {
            await screen.orientation.lock("portrait");
        }
    } catch (e) {}
}

document.addEventListener("DOMContentLoaded", () => {
    lockPortraitOrientation();
    document.addEventListener("pointerdown", lockPortraitOrientation, {
        once: true,
    });
});
