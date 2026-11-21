// assets/js/_swap_modal.js

const SWAP_MUSCLE_GROUPS = [
    [
        "Klatka piersiowa - góra",
        "Klatka piersiowa - środek",
        "Klatka piersiowa - dół",
    ],
    [
        "Najszerszy grzbietu",
        "Górna część pleców",
        "Prostownik grzbietu",
        "Kaptur",
    ],
    ["Barki - przód", "Barki - bok", "Barki - tył"],
    ["Biceps"],
    [
        "Triceps - głowa długa",
        "Triceps - głowa boczna",
        "Triceps - głowa przyśrodkowa",
    ],
    ["Przedramiona - zginacze (przód)", "Przedramiona - prostowniki (tył)"],
    ["Czworogłowy uda", "Przywodziciele", "Odwodziciele"],
    ["Tył uda (Kulszowo-goleniowe)", "Pośladki"],
    ["Łydki"],
    ["Prosty brzucha", "Skośne brzucha"],
];

const SWAP_SCROLL_KEY = "pulse_kinetic_swap_scroll";

function getRelatedMuscles(muscleName) {
    const cleanName = (muscleName || "").trim();
    const group = SWAP_MUSCLE_GROUPS.find((g) => g.includes(cleanName));
    return group ? group : [cleanName];
}

function isCardioMachine(machine) {
    const mech = (machine.mechanicType || "").toLowerCase();
    const cat = (machine.category || "").toLowerCase();
    const name = (machine.name || "").toLowerCase();

    return (
        mech === "cardio" ||
        cat === "cardio" ||
        name.includes("rower") ||
        name.includes("bieżnia") ||
        name.includes("orbitrek")
    );
}

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

    let excludedIds = [];
    if (typeof usedMachineIds === "string") {
        try {
            excludedIds = JSON.parse(usedMachineIds);
        } catch (e) {}
    } else if (Array.isArray(usedMachineIds)) {
        excludedIds = usedMachineIds;
    }
    excludedIds = excludedIds.map((id) => Number(id));

    const currentMachineObj = machinesData.find((m) => m.name === machineName);
    const currentIsCardio = currentMachineObj
        ? isCardioMachine(currentMachineObj)
        : false;
    const relatedMuscles = getRelatedMuscles(muscleName);

    const compatibleMachines = machinesData
        .filter((m) => {
            if (m.name === machineName) return false;
            if (excludedIds.includes(Number(m.id))) return false;
            if (isCardioMachine(m) !== currentIsCardio) return false;

            const muscles = Array.isArray(m.muscles) ? m.muscles : [];
            return muscles.some((name) => relatedMuscles.includes(name));
        })
        .sort((a, b) => {
            const aExact = a.muscles.includes(muscleName) ? 0 : 1;
            const bExact = b.muscles.includes(muscleName) ? 0 : 1;
            if (aExact !== bExact) return aExact - bExact;
            return a.name.localeCompare(b.name, "pl");
        });

    if (compatibleMachines.length === 0) {
        grid.innerHTML =
            '<div class="col-span-2 text-[#767575] text-xs py-4 uppercase tracking-widest font-headline text-center">Brak alternatyw dla tej partii w bazie (lub wszystkie są już użyte w planie).</div>';
    } else {
        compatibleMachines.forEach((m) => {
            const isExactMatch = m.muscles.includes(muscleName);
            const primaryLabel = m.muscles[0] || "";

            const card = document.createElement("div");
            card.className =
                "bg-[#161616] border border-white/10 p-3 flex flex-col gap-2 cursor-pointer hover:border-primary-container transition-all group select-none relative";
            card.setAttribute("data-id", m.id);

            const imgHtml = m.image
                ? `<img src="${m.image}" class="w-full h-24 object-contain p-1 mix-blend-luminosity opacity-80 group-hover:opacity-100 transition-opacity">`
                : `<div class="w-full h-24 bg-[#1a1a1a] flex items-center justify-center text-outline"><span class="material-symbols-outlined">fitness_center</span></div>`;

            const badgeHtml = isExactMatch
                ? `<span class="text-[9px] font-headline font-bold uppercase tracking-widest text-primary-container text-center">${primaryLabel}</span>`
                : `<span class="text-[9px] font-headline font-bold uppercase tracking-widest text-[#767575] text-center">${primaryLabel}</span>`;

            card.innerHTML = `
                <div class="w-full overflow-hidden bg-[#1a1a1a] border border-white/5 relative">
                    ${imgHtml}
                </div>
                <span class="font-headline font-bold text-xs text-white uppercase tracking-tight text-center line-clamp-2">${m.name}</span>
                ${badgeHtml}
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

// Zapamiętaj pozycję przewinięcia tuż przed wysłaniem formularza wymiany sprzętu
document.addEventListener("submit", (event) => {
    const form = event.target;
    if (form && form.id === "swapForm") {
        try {
            sessionStorage.setItem(
                SWAP_SCROLL_KEY,
                JSON.stringify({
                    y: window.scrollY,
                    path: window.location.pathname,
                    ts: Date.now(),
                }),
            );
        } catch (e) {}
    }
});

// Przywróć pozycję po przeładowaniu strony (jeśli zapis pochodzi z tej samej ścieżki i jest świeży)
document.addEventListener("DOMContentLoaded", () => {
    try {
        const raw = sessionStorage.getItem(SWAP_SCROLL_KEY);
        if (!raw) return;

        sessionStorage.removeItem(SWAP_SCROLL_KEY);
        const saved = JSON.parse(raw);

        if (
            saved &&
            typeof saved.y === "number" &&
            saved.path === window.location.pathname &&
            Date.now() - Number(saved.ts || 0) < 10000
        ) {
            requestAnimationFrame(() => {
                window.scrollTo({
                    top: saved.y,
                    left: 0,
                    behavior: "instant",
                });
            });
        }
    } catch (e) {}
});

export {};
