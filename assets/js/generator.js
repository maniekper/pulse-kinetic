document.addEventListener("DOMContentLoaded", () => {
    // =========================================================================
    // 1. KROK 01: Modal zmiany celu i Custom Dropdown (step1_days.html.twig)
    // =========================================================================
    const goalModal = document.getElementById("goalModal");
    const modalContent = document.getElementById("modalContent");

    if (goalModal && modalContent) {
        window.openModal = function () {
            goalModal.classList.remove("opacity-0", "pointer-events-none");
            goalModal.classList.add("opacity-100", "pointer-events-auto");
            modalContent.classList.remove("scale-95");
            modalContent.classList.add("scale-100");
        };

        window.closeModal = function () {
            modalContent.classList.remove("scale-100");
            modalContent.classList.add("scale-95");
            goalModal.classList.remove("opacity-100", "pointer-events-auto");
            goalModal.classList.add("opacity-0", "pointer-events-none");
        };
    }

    const customSelectContainer = document.getElementById(
        "customSelectContainer",
    );
    const customDropdownMenu = document.getElementById("customDropdownMenu");
    const dropdownArrow = document.getElementById("dropdownArrow");

    if (customSelectContainer && customDropdownMenu && dropdownArrow) {
        window.toggleDropdown = function () {
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

        document.addEventListener("click", (event) => {
            if (
                !customSelectContainer.contains(event.target) &&
                !customDropdownMenu.classList.contains("hidden")
            ) {
                window.toggleDropdown();
            }
        });
    }

    // =========================================================================
    // 2. KROK 02: Suwak długości mezocyklu (step2_duration.html.twig)
    // =========================================================================
    const weeksSlider = document.getElementById("weeksSlider");
    const weeksDisplay = document.getElementById("weeksDisplay");

    if (weeksSlider && weeksDisplay) {
        weeksSlider.addEventListener("input", function () {
            weeksDisplay.innerText = this.value;
        });
    }
});
