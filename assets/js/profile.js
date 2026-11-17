// assets/js/profile.js
window.toggleDropdown = function (menuId, arrowId) {
    const menu = document.getElementById(menuId);
    const arrow = document.getElementById(arrowId);

    // Zamknij inne otwarte dropdowny
    document.querySelectorAll('[id$="Dropdown"]').forEach((el) => {
        if (el.id !== menuId && !el.classList.contains("hidden")) {
            el.classList.add("hidden");
            el.classList.remove("flex");
            const otherArrow = document.getElementById(
                el.id.replace("Dropdown", "Arrow"),
            );
            if (otherArrow) otherArrow.classList.remove("rotate-180");
        }
    });

    if (menu.classList.contains("hidden")) {
        menu.classList.remove("hidden");
        menu.classList.add("flex");
        arrow.classList.add("rotate-180");
    } else {
        menu.classList.add("hidden");
        menu.classList.remove("flex");
        arrow.classList.remove("rotate-180");
    }
};

window.selectOption = function (inputId, value, textId, text, menuId, arrowId) {
    document.getElementById(inputId).value = value;
    document.getElementById(textId).innerText = text;

    const menu = document.getElementById(menuId);
    const arrow = document.getElementById(arrowId);
    menu.classList.add("hidden");
    menu.classList.remove("flex");
    arrow.classList.remove("rotate-180");
};

// Zamykanie przy kliknięciu na zewnątrz
document.addEventListener("click", function (event) {
    const genderContainer = document.getElementById("genderSelectContainer");
    const goalContainer = document.getElementById("goalSelectContainer");

    if (genderContainer && !genderContainer.contains(event.target)) {
        const menu = document.getElementById("genderDropdown");
        const arrow = document.getElementById("genderArrow");
        if (menu && !menu.classList.contains("hidden")) {
            menu.classList.add("hidden");
            menu.classList.remove("flex");
            arrow.classList.remove("rotate-180");
        }
    }

    if (goalContainer && !goalContainer.contains(event.target)) {
        const menu = document.getElementById("goalDropdown");
        const arrow = document.getElementById("goalArrow");
        if (menu && !menu.classList.contains("hidden")) {
            menu.classList.add("hidden");
            menu.classList.remove("flex");
            arrow.classList.remove("rotate-180");
        }
    }
});

export {};
