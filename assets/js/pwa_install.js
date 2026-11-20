document.addEventListener("DOMContentLoaded", () => {
    let deferredPrompt = null;
    const wrapper = document.getElementById("pwaInstallWrapper");
    const installBtn = document.getElementById("pwaInstallBtn");
    const iosHint = document.getElementById("iosInstallHint");

    const isStandalone =
        window.matchMedia("(display-mode: standalone)").matches ||
        window.navigator.standalone === true;
    const isIos = /iphone|ipad|ipod/i.test(window.navigator.userAgent);

    if (!wrapper || !installBtn || isStandalone) {
        return;
    }

    // Android / Chrome / Edge
    window.addEventListener("beforeinstallprompt", (e) => {
        e.preventDefault();
        deferredPrompt = e;
        wrapper.classList.remove("hidden");
        wrapper.classList.add("flex");
    });

    // iOS Safari
    if (isIos) {
        wrapper.classList.remove("hidden");
        wrapper.classList.add("flex");
    }

    installBtn.addEventListener("click", async () => {
        if (deferredPrompt) {
            deferredPrompt.prompt();
            const { outcome } = await deferredPrompt.userChoice;
            if (outcome === "accepted") {
                wrapper.classList.add("hidden");
                wrapper.classList.remove("flex");
            }
            deferredPrompt = null;
        } else if (isIos && iosHint) {
            iosHint.classList.toggle("hidden");
        }
    });

    window.addEventListener("appinstalled", () => {
        wrapper.classList.add("hidden");
        wrapper.classList.remove("flex");
        deferredPrompt = null;
    });
});
