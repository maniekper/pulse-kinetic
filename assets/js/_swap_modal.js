// assets/js/_swap_modal.js

window.openSwapModal = function (
    exerciseId,
    machineName,
    muscleName,
    usedMachineIds = [],
) {
    const swapModal = document.getElementById("swapModal");

    if (!swapModal) return;

    const machinesDataRaw = swapModal.getAttribute("data-machines");
    const machinesData = machinesDataRaw ? JSON.parse(machinesDataRaw) : [];

    swapModal.classList.remove("hidden");
    swapModal.classList.add("flex");

    document.getElementById("currentMachineName").innerText = machineName;
    document.getElementById("selectedMachineId").value = "";

    const grid = document.getElementById("machineGrid");
    const submitBtn = document.getElementById("swapSubmitBtn");
    grid.innerHTML = "";
    submitBtn.classList.add("opacity-30", "pointer-events-none");

    // NOWE: Parsowanie użytych maszyn
    let excludedIds = [];
    if (typeof usedMachineIds === "string") {
        try {
            excludedIds = JSON.parse(usedMachineIds);
        } catch (e) {}
    } else if (Array.isArray(usedMachineIds)) {
        excludedIds = usedMachineIds;
    }

    // Zaktualizowany filtr - ODRZUCA MASZYNY Z excludedIds
    const compatibleMachines = machinesData.filter(
        (m) =>
            m.muscles.includes(muscleName) &&
            m.name !== machineName &&
            !excludedIds.includes(m.id),
    );

    if (compatibleMachines.length === 0) {
        grid.innerHTML =
            '<div class="col-span-2 text-[#767575] text-xs py-4 uppercase tracking-widest font-headline text-center">Brak alternatyw dla tej partii w bazie (lub wszystkie są już użyte w planie).</div>';
    } else {
        compatibleMachines.forEach((m) => {
            const card = document.createElement("div");
            card.className =
                "bg-[#161616] border border-white/10 p-3 flex flex-col gap-3 cursor-pointer hover:border-primary-container transition-all group select-none relative";
            card.setAttribute("data-id", m.id);

            const imgHtml = m.image
                ? `<img src="${m.image}" class="w-full h-24 object-contain p-1 mix-blend-luminosity opacity-80 group-hover:opacity-100 transition-opacity">`
                : `<div class="w-full h-24 bg-[#1a1a1a] flex items-center justify-center text-outline"><span class="material-symbols-outlined">fitness_center</span></div>`;

            card.innerHTML = `
                <div class="w-full overflow-hidden bg-[#1a1a1a] border border-white/5 relative">
                    ${imgHtml}
                </div>
                <span class="font-headline font-bold text-xs text-white uppercase tracking-tight text-center line-clamp-2">${m.name}</span>
            `;

            card.onclick = () => selectMachineCard(card, m.id, exerciseId);
            grid.appendChild(card);
        });
    }
};

function selectMachineCard(cardElement, machineId, currentExerciseId) {
    document.querySelectorAll("#machineGrid > div").forEach((c) => {
        c.classList.remove(
            "border-primary-container",
            "bg-primary-container/10",
        );
        c.classList.add("border-white/10", "bg-[#161616]");
    });

    cardElement.classList.remove("border-white/10", "bg-[#161616]");
    cardElement.classList.add(
        "border-primary-container",
        "bg-primary-container/10",
    );

    document.getElementById("selectedMachineId").value = machineId;

    const form = document.getElementById("swapForm");
    form.action = "/tracker/swap/" + currentExerciseId + "/" + machineId;

    document
        .getElementById("swapSubmitBtn")
        .classList.remove("opacity-30", "pointer-events-none");
}

window.closeSwapModal = function () {
    const swapModal = document.getElementById("swapModal");
    if (swapModal) {
        swapModal.classList.add("hidden");
        swapModal.classList.remove("flex");
    }
};
export {};
