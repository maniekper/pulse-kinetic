// assets/js/generator.js

// =========================================================================
// 1. KROK 01: Modal zmiany celu i Custom Dropdown (step1_days.html.twig)
// =========================================================================
window.openModal = function () {
    const goalModal = document.getElementById("goalModal");
    const modalContent = document.getElementById("modalContent");
    if (!goalModal || !modalContent) return;

    goalModal.classList.remove("opacity-0", "pointer-events-none");
    goalModal.classList.add("opacity-100", "pointer-events-auto");
    modalContent.classList.remove("scale-95");
    modalContent.classList.add("scale-100");
};

window.closeModal = function () {
    const goalModal = document.getElementById("goalModal");
    const modalContent = document.getElementById("modalContent");
    if (!goalModal || !modalContent) return;

    modalContent.classList.remove("scale-100");
    modalContent.classList.add("scale-95");
    goalModal.classList.remove("opacity-100", "pointer-events-auto");
    goalModal.classList.add("opacity-0", "pointer-events-none");
};

window.toggleDropdown = function () {
    const customDropdownMenu = document.getElementById("customDropdownMenu");
    const dropdownArrow = document.getElementById("dropdownArrow");
    if (!customDropdownMenu || !dropdownArrow) return;

    const isHidden = customDropdownMenu.classList.contains("hidden");
    if (isHidden) {
        customDropdownMenu.classList.remove("hidden");
        customDropdownMenu.classList.add("flex");
        dropdownArrow.classList.add("rotate-180");
    } else {
        customDropdownMenu.classList.add("hidden");
        customDropdownMenu.classList.remove("flex");
        dropdownArrow.classList.remove("rotate-180");
    }
};

window.selectGoal = function (id, name) {
    const input = document.getElementById("newGoalIdInput");
    const text = document.getElementById("selectedGoalText");
    if (input) input.value = id;
    if (text) text.innerText = name;
    window.toggleDropdown();
};

// =========================================================================
// 3. KROK 03: Przełączanie całej grupy mięśni (step3_exclusions.html.twig)
// =========================================================================
window.toggleMuscleSection = function (sectionId, btn) {
    const checkboxes = document.querySelectorAll(
        `#${sectionId} input[type="checkbox"]`,
    );
    if (!checkboxes.length) return;

    const allChecked = Array.from(checkboxes).every((cb) => cb.checked);
    checkboxes.forEach((cb) => {
        cb.checked = !allChecked;
    });

    if (btn) {
        btn.innerText = allChecked ? "POMIŃ CAŁĄ GRUPĘ" : "PRZYWRÓĆ GRUPĘ";
    }
};

// Nasłuchiwacze (zamykanie dropdownu + suwak w Kroku 02)
document.addEventListener("DOMContentLoaded", () => {
    const customSelectContainer = document.getElementById(
        "customSelectContainer",
    );
    const customDropdownMenu = document.getElementById("customDropdownMenu");

    if (customSelectContainer && customDropdownMenu) {
        document.addEventListener("click", (event) => {
            if (
                !customSelectContainer.contains(event.target) &&
                !customDropdownMenu.classList.contains("hidden")
            ) {
                window.toggleDropdown();
            }
        });
    }

    const weeksSlider = document.getElementById("weeksSlider");
    const weeksDisplay = document.getElementById("weeksDisplay");

    if (weeksSlider && weeksDisplay) {
        weeksSlider.addEventListener("input", function () {
            weeksDisplay.innerText = this.value;
        });
    }
});

export {};
