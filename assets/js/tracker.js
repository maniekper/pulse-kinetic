// assets/js/tracker.js

let timerRunning = false;
let currentExIndex = 0;
let restInterval = null;
let currentRestSeconds = 0;
let maxRestSeconds = 0;
const circumference = 552.92;
let isIntentionalExit = false;

const KG_TO_LBS = 2.2046226218;
const LBS_TO_KG = 0.45359237;

function formatWeightDisplay(val) {
    const rounded = Math.round(val * 100) / 100;
    return Number.isInteger(rounded) ? String(rounded) : String(rounded);
}

function getWeightInKg(inputEl) {
    if (!inputEl) return 0;
    const raw = parseFloat(String(inputEl.value || "").replace(",", "."));
    if (!Number.isFinite(raw) || raw < 0) return 0;

    const block = inputEl.closest(".exercise-block");
    const unit = block ? block.getAttribute("data-unit") || "kg" : "kg";
    const kg = unit === "lbs" ? raw * LBS_TO_KG : raw;
    return Math.round(kg * 100) / 100;
}

window.setExerciseUnit = function (btnEl, targetUnit) {
    const block = btnEl.closest(".exercise-block");
    if (!block) return;

    const currentUnit = block.getAttribute("data-unit") || "kg";
    if (currentUnit === targetUnit) return;

    block.setAttribute("data-unit", targetUnit);

    block.querySelectorAll("[data-unit-toggle]").forEach((btn) => {
        const isActive = btn.getAttribute("data-unit-toggle") === targetUnit;
        btn.classList.toggle("bg-primary-container", isActive);
        btn.classList.toggle("text-on-primary-container", isActive);
        btn.classList.toggle("font-black", isActive);
        btn.classList.toggle("text-outline", !isActive);
        btn.classList.toggle("font-bold", !isActive);
    });

    block.querySelectorAll(".weight-input").forEach((input) => {
        const storedKg = parseFloat(input.getAttribute("data-weight-kg") || "");
        if (Number.isFinite(storedKg) && storedKg > 0) {
            const displayVal =
                targetUnit === "lbs" ? storedKg * KG_TO_LBS : storedKg;
            input.value = formatWeightDisplay(displayVal);
        }
    });

    block.querySelectorAll(".completed-weight-display").forEach((el) => {
        const storedKg = parseFloat(el.getAttribute("data-weight-kg") || "");
        if (Number.isFinite(storedKg) && storedKg > 0) {
            const displayVal =
                targetUnit === "lbs" ? storedKg * KG_TO_LBS : storedKg;
            el.innerText = formatWeightDisplay(displayVal);
        }
    });
};

window.adjustWeightValue = function (inputId, direction) {
    const input = document.getElementById(inputId);
    if (!input || input.disabled) return;

    const block = input.closest(".exercise-block");
    const unit = block ? block.getAttribute("data-unit") || "kg" : "kg";
    const stepKg = block
        ? parseFloat(block.getAttribute("data-increment-step") || "2.5")
        : 2.5;

    const stepInCurrentUnit = unit === "lbs" ? stepKg * KG_TO_LBS : stepKg;
    let currentDisplayVal =
        parseFloat(String(input.value || "").replace(",", ".")) || 0;
    let nextDisplayVal = Math.max(
        0,
        currentDisplayVal + direction * stepInCurrentUnit,
    );

    input.value = formatWeightDisplay(nextDisplayVal);
    const nextKg = unit === "lbs" ? nextDisplayVal * LBS_TO_KG : nextDisplayVal;
    input.setAttribute(
        "data-weight-kg",
        String(Math.round(nextKg * 100) / 100),
    );

    if (block) {
        detectAndSyncIncrementStep(block);
    }
};

function detectAndSyncIncrementStep(block) {
    if (!block) return;

    const weightsKg = [];

    block.querySelectorAll(".completed-weight-display").forEach((el) => {
        const kg = parseFloat(el.getAttribute("data-weight-kg") || "");
        if (Number.isFinite(kg) && kg > 0) weightsKg.push(kg);
    });

    block.querySelectorAll(".weight-input").forEach((input) => {
        const kg = getWeightInKg(input);
        if (kg > 0) {
            input.setAttribute("data-weight-kg", String(kg));
            weightsKg.push(kg);
        }
    });

    const uniqueOrdered = [];
    weightsKg.forEach((w) => {
        if (
            uniqueOrdered.length === 0 ||
            uniqueOrdered[uniqueOrdered.length - 1] !== w
        ) {
            uniqueOrdered.push(w);
        }
    });

    if (uniqueOrdered.length < 2) return;

    const prevW = uniqueOrdered[uniqueOrdered.length - 2];
    const lastW = uniqueOrdered[uniqueOrdered.length - 1];
    const diffKg = Math.round(Math.abs(lastW - prevW) * 100) / 100;

    if (diffKg <= 0 || diffKg > 100) return;

    const currentSavedStep = parseFloat(
        block.getAttribute("data-increment-step") || "0",
    );
    if (Math.abs(currentSavedStep - diffKg) < 0.01) return;

    block.setAttribute("data-increment-step", String(diffKg));

    const machineId = block.getAttribute("data-machine-id");
    if (!machineId) return;

    fetch(`/tracker/machine/${machineId}/increment-step`, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ step_kg: diffKg }),
    }).catch(() => {});
}

function hideMobileCoachBox() {
    const mobileCoachBox = document.getElementById("mobileCoachBox");
    if (mobileCoachBox) {
        mobileCoachBox.classList.add("hidden");
    }
}

window.startWorkoutSession = function () {
    const overlay = document.getElementById("readyOverlay");
    if (overlay) {
        overlay.style.opacity = "0";
        setTimeout(() => {
            overlay.style.display = "none";
            if (!timerRunning) {
                timerRunning = true;
                startGlobalTimer();
            }
        }, 500);
    }
    showExercise(0);
};

window.navigateExercise = function (direction) {
    window.skipRestTime();
    const blocks = document.querySelectorAll(".exercise-block");
    let newIndex = currentExIndex + direction;
    if (newIndex >= 0 && newIndex < blocks.length) {
        showExercise(newIndex);
    }
};

window.skipOccupiedMachine = function () {
    const blocks = document.querySelectorAll(".exercise-block");
    const currentBlock = blocks[currentExIndex];

    const isStarted =
        currentBlock.querySelectorAll(".set-row.opacity-50").length > 0;
    if (isStarted) {
        window.showCustomAlert(
            "Nie możesz pominąć ćwiczenia, które już zacząłeś realizować!",
        );
        return;
    }

    let nextUnstartedIndex = -1;

    for (let i = currentExIndex + 1; i < blocks.length; i++) {
        if (blocks[i].querySelectorAll(".set-row.opacity-50").length === 0) {
            nextUnstartedIndex = i;
            break;
        }
    }

    if (nextUnstartedIndex === -1) {
        for (let i = 0; i < currentExIndex; i++) {
            if (
                blocks[i].querySelectorAll(".set-row.opacity-50").length === 0
            ) {
                nextUnstartedIndex = i;
                break;
            }
        }
    }

    if (nextUnstartedIndex !== -1 && nextUnstartedIndex !== currentExIndex) {
        window.skipRestTime();
        currentBlock.style.opacity = "0";
        setTimeout(() => {
            currentBlock.style.opacity = "1";
            showExercise(nextUnstartedIndex);
        }, 300);
    } else {
        window.showCustomAlert(
            "To jedyne niezaczęte ćwiczenie, jakie pozostało.",
        );
    }
};

function normalizeMuscleName(name) {
    if (!name) return "";

    const normalized = name
        .trim()
        .toLowerCase()
        .replace(/\s*-\s*/g, "-")
        .replace(/[()]/g, "")
        .replace(/\s+/g, "-");

    const aliases = {
        "prosty-brzucha": "prosty-brzuch",
        "skośne-brzucha": "skośne-brzuch",
    };

    return aliases[normalized] ?? normalized;
}

function showExercise(index) {
    const blocks = document.querySelectorAll(".exercise-block");
    if (index < 0 || index >= blocks.length) return;
    currentExIndex = index;

    const activeBlockRestTime =
        blocks[index].getAttribute("data-rest-time") || 120;
    const targetDataEl = document.getElementById("targetRestTimeData");
    if (targetDataEl)
        targetDataEl.setAttribute("data-time", activeBlockRestTime);
    maxRestSeconds = parseInt(activeBlockRestTime);

    blocks.forEach((block, i) => {
        block.style.display = i === index ? "block" : "none";

        const prevBtn = block.querySelector(".prev-ex-btn");
        const nextBtn = block.querySelector(".next-ex-btn");
        const label = block.querySelector(".current-ex-label");
        const skipBtn = block.querySelector(
            'button[onclick*="skipOccupiedMachine"]',
        );

        if (prevBtn) prevBtn.style.visibility = i === 0 ? "hidden" : "visible";
        if (nextBtn)
            nextBtn.style.visibility =
                i === blocks.length - 1 ? "hidden" : "visible";
        if (label) label.innerText = `ĆWICZENIE ${i + 1} Z ${blocks.length}`;

        if (skipBtn) {
            const isStarted =
                block.querySelectorAll(".set-row.opacity-50").length > 0;
            skipBtn.style.display = isStarted ? "none" : "flex";
        }
    });

    const queueItems = document.querySelectorAll(".queue-item");
    queueItems.forEach((item, i) => {
        const icon = item.querySelector(".queue-icon");
        const number = item.querySelector(".queue-number");
        if (i === index) {
            item.classList.add(
                "border-primary-container",
                "bg-surface-container-high",
            );
            if (number) number.classList.add("text-primary-container");
            if (icon) {
                icon.innerText = "fitness_center";
                icon.classList.add("text-primary-container");
            }
        } else {
            item.classList.remove(
                "border-primary-container",
                "bg-surface-container-high",
            );
            if (number) number.classList.remove("text-primary-container");
            if (icon) {
                icon.innerText = i < index ? "check_circle" : "hourglass_empty";
                icon.classList.remove("text-primary-container");
            }
        }
    });

    const activeBlock = blocks[index];
    const rawPrimary = (activeBlock.getAttribute("data-primary-muscles") || "")
        .split(",")
        .map((m) => m.trim())
        .filter(Boolean);
    const rawSecondary = (
        activeBlock.getAttribute("data-secondary-muscles") || ""
    )
        .split(",")
        .map((m) => m.trim())
        .filter(Boolean);

    document
        .querySelectorAll(".muscle-active-primary, .muscle-active-secondary")
        .forEach((el) => {
            el.classList.remove(
                "muscle-active-primary",
                "muscle-active-secondary",
            );
        });

    const labelsContainer = document.getElementById("anatomyLabels");
    if (labelsContainer) labelsContainer.innerHTML = "";

    const highlight = (muscleName, activeClass) => {
        const slug = normalizeMuscleName(muscleName);
        if (!slug) return;

        const selector = "." + CSS.escape("muscle-" + slug);
        document.querySelectorAll(selector).forEach((el) => {
            el.classList.add(activeClass);
        });
    };

    rawSecondary.forEach((m) => highlight(m, "muscle-active-secondary"));
    rawPrimary.forEach((m) => highlight(m, "muscle-active-primary"));

    if (labelsContainer) {
        rawPrimary.forEach((m) => {
            labelsContainer.innerHTML += `<span class="bg-primary-container/20 border border-primary-container text-primary-container px-2 py-1 font-headline font-bold text-[9px] uppercase tracking-widest">${m}</span>`;
        });
        rawSecondary.forEach((m) => {
            labelsContainer.innerHTML += `<span class="bg-surface-container-highest border border-outline-variant/30 text-outline px-2 py-1 font-headline font-bold text-[9px] uppercase tracking-widest">${m}</span>`;
        });
    }

    updateActiveStateVisuals();
}

window.adjustValue = function (inputId, delta) {
    const input = document.getElementById(inputId);
    if (!input || input.disabled) return;
    let val = parseFloat(input.value) || 0;
    val += delta;
    if (val < 0) val = 0;
    input.value = val % 1 === 0 ? val : val.toFixed(1);
};

let totalSets = 0;
let completedSets = 0;
let currentVolume = 0;

function recalculateInitialStats() {
    totalSets = document.querySelectorAll(".set-row").length;
    completedSets = document.querySelectorAll(
        ".set-row.bg-surface-container-low",
    ).length;
    currentVolume = 0;

    document
        .querySelectorAll(".set-row.bg-surface-container-low")
        .forEach((row) => {
            const weightEl =
                row.querySelector(".completed-weight-display") ||
                row.querySelector("div:nth-child(2)");
            const repsDiv = row.querySelector("div:nth-child(3)");
            if (weightEl && repsDiv) {
                const w = parseFloat(
                    weightEl.getAttribute("data-weight-kg") ||
                        weightEl.innerText,
                );
                const r = parseInt(repsDiv.innerText);
                if (!isNaN(w) && !isNaN(r)) {
                    currentVolume += w * r;
                }
            }
        });

    let elTotal = document.getElementById("totalSetsCounter");
    if (elTotal) elTotal.innerText = totalSets;
}

function updateStats() {
    let elCompleted = document.getElementById("setsCompletedCounter");
    let elVolume = document.getElementById("totalVolumeCounter");
    let progText = document.getElementById("workoutProgressText");
    let progBar = document.getElementById("workoutProgressBar");

    if (elCompleted) elCompleted.innerText = completedSets;
    if (elVolume) elVolume.innerText = Math.round(currentVolume * 100) / 100;

    let progress =
        totalSets > 0 ? Math.round((completedSets / totalSets) * 100) : 0;
    if (progText) progText.innerText = progress + "%";
    if (progBar) progBar.style.width = progress + "%";
    updateActiveStateVisuals();
}

function startGlobalTimer() {
    let workoutSeconds = 0;
    setInterval(() => {
        workoutSeconds++;
        let h = Math.floor(workoutSeconds / 3600);
        let m = Math.floor((workoutSeconds % 3600) / 60);
        let s = workoutSeconds % 60;
        let formattedStr = h > 0 ? (h < 10 ? "0" + h : h) + ":" : "";
        formattedStr += (m < 10 ? "0" + m : m) + ":" + (s < 10 ? "0" + s : s);

        let t1 = document.getElementById("globalWorkoutTimer");
        let t2 = document.getElementById("sessionTimerBottom");
        if (t1) t1.innerText = formattedStr;
        if (t2) t2.innerText = formattedStr;
    }, 1000);
}

function showStartRestButton() {
    clearInterval(restInterval);
    let timerView = document.getElementById("timerView");
    let startRestBtn = document.getElementById("startRestBtn");
    if (timerView) timerView.classList.add("hidden");
    if (startRestBtn) startRestBtn.classList.remove("hidden");
}

window.initiateRest = function () {
    let timerView = document.getElementById("timerView");
    let startRestBtn = document.getElementById("startRestBtn");
    if (startRestBtn) startRestBtn.classList.add("hidden");
    if (timerView) timerView.classList.remove("hidden");

    const targetDataEl = document.getElementById("targetRestTimeData");
    let timeToSet = targetDataEl
        ? parseInt(targetDataEl.getAttribute("data-time"))
        : 120;
    if (isNaN(timeToSet)) timeToSet = 120;

    startRestTimer(timeToSet);
};

function startRestTimer(duration) {
    clearInterval(restInterval);
    currentRestSeconds = duration;
    maxRestSeconds = duration > maxRestSeconds ? duration : maxRestSeconds;

    const mobileOverlay = document.getElementById("mobileRestOverlay");
    if (window.innerWidth < 1024 && mobileOverlay) {
        mobileOverlay.classList.remove("hidden");
        mobileOverlay.classList.add("flex");
    }

    const display = document.getElementById("restTimerDisplay");
    if (display) {
        display.classList.replace("text-outline", "text-on-surface");
        display.classList.replace("text-primary-container", "text-on-surface");
    }

    updateRestDisplay();

    restInterval = setInterval(() => {
        if (currentRestSeconds <= 0) {
            clearInterval(restInterval);
            if (display) {
                display.innerText = "GOTOWY!";
                display.classList.replace(
                    "text-on-surface",
                    "text-primary-container",
                );
            }
            const circle = document.getElementById("restTimerCircle");
            if (circle) circle.style.strokeDashoffset = 0;

            if (mobileOverlay) {
                mobileOverlay.classList.add("hidden");
                mobileOverlay.classList.remove("flex");
            }
            hideMobileCoachBox();
        } else {
            currentRestSeconds--;
            updateRestDisplay();
        }
    }, 1000);
}

function updateRestDisplay() {
    const display = document.getElementById("restTimerDisplay");
    const mobileDisplay = document.getElementById("mobileRestTimerDisplay");

    let m = Math.floor(currentRestSeconds / 60);
    let s = currentRestSeconds % 60;
    let formattedStr = (m < 10 ? "0" + m : m) + ":" + (s < 10 ? "0" + s : s);

    if (display) display.innerText = formattedStr;
    if (mobileDisplay) mobileDisplay.innerText = formattedStr;

    let fraction = currentRestSeconds / maxRestSeconds;
    if (fraction > 1) fraction = 1;

    const circle = document.getElementById("restTimerCircle");
    if (circle) circle.style.strokeDashoffset = circumference * (1 - fraction);

    const mobileCircle = document.getElementById("mobileRestTimerCircle");
    const mobileCircumference = 753.98;
    if (mobileCircle)
        mobileCircle.style.strokeDashoffset =
            mobileCircumference * (1 - fraction);
}

window.addRestTime = function (seconds) {
    if (currentRestSeconds <= 0) currentRestSeconds = 0;
    currentRestSeconds += seconds;

    const startBtn = document.getElementById("startRestBtn");
    if (startBtn && !startBtn.classList.contains("hidden")) {
        window.initiateRest();
    } else {
        startRestTimer(currentRestSeconds);
    }
};

window.skipRestTime = function () {
    const mobileOverlay = document.getElementById("mobileRestOverlay");
    if (mobileOverlay) {
        mobileOverlay.classList.remove("flex");
        mobileOverlay.classList.add("hidden");
    }

    hideMobileCoachBox();
    clearInterval(restInterval);
    currentRestSeconds = 0;

    try {
        const circle = document.getElementById("restTimerCircle");
        if (circle) circle.style.strokeDashoffset = 0;
    } catch (e) {}

    try {
        const mobileCircle = document.getElementById("mobileRestTimerCircle");
        if (mobileCircle) mobileCircle.style.strokeDashoffset = 0;
    } catch (e) {}

    showStartRestButton();
};

function syncSequentialSetLocks() {
    document.querySelectorAll(".exercise-block").forEach((block) => {
        const rows = Array.from(block.querySelectorAll(".set-row"));
        let activeRowFound = false;

        rows.forEach((row) => {
            const isCompleted = row.classList.contains(
                "bg-surface-container-low",
            );
            const isUnlocked = !isCompleted && !activeRowFound;

            if (!isCompleted && !activeRowFound) {
                activeRowFound = true;
            }

            row.classList.toggle("opacity-40", !isCompleted && !isUnlocked);

            row.querySelectorAll("input, button").forEach((el) => {
                if (el.tagName === "INPUT" || el.tagName === "BUTTON") {
                    el.disabled = !isUnlocked;
                }
            });
        });
    });
}

function updateActiveStateVisuals() {
    let rows = document.querySelectorAll(".set-row");
    let foundActive = false;
    rows.forEach((row) => {
        row.classList.remove("kinetic-stripe", "bg-surface-container-high");
        let numberDiv = row.querySelector(".set-number");
        if (numberDiv) numberDiv.classList.remove("text-primary-container");

        if (
            !row.classList.contains("bg-surface-container-low") &&
            !foundActive
        ) {
            row.classList.add("kinetic-stripe", "bg-surface-container-high");
            if (numberDiv) numberDiv.classList.add("text-primary-container");
            foundActive = true;
        }
    });

    syncSequentialSetLocks();
}

window.saveSet = function (setId, btnElement) {
    const weightInput = document.getElementById("weight-" + setId);
    const repsInput = document.getElementById("reps-" + setId);

    if (!weightInput || !repsInput) return;

    const block = weightInput.closest(".exercise-block");
    if (block) {
        detectAndSyncIncrementStep(block);
    }

    const weight = getWeightInKg(weightInput);
    const reps = repsInput.value;

    const row = document.getElementById("set-row-" + setId);
    const targetReps = row
        ? parseInt(row.getAttribute("data-target-reps"))
        : 10;

    if (!weight || weight <= 0 || !reps) {
        window.showCustomAlert("Wprowadź ciężar i ilość powtórzeń.");
        return;
    }

    const originalBtnHtml = btnElement.innerHTML;
    btnElement.innerHTML = "...";
    btnElement.disabled = true;

    fetch("/tracker/set/complete/" + setId, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({
            weight: weight,
            reps: reps,
            target_reps: targetReps,
        }),
    })
        .then((response) => response.json())
        .then((data) => {
            if (data.success) {
                const row = document.getElementById("set-row-" + setId);
                if (row) {
                    const col2 = row.querySelector("div:nth-child(2)");
                    const col3 = row.querySelector("div:nth-child(3)");
                    const actionCont = row.querySelector(
                        ".set-action-container",
                    );
                    const currentUnit =
                        row
                            .closest(".exercise-block")
                            ?.getAttribute("data-unit") || "kg";
                    const displaySavedWeight =
                        currentUnit === "lbs"
                            ? formatWeightDisplay(weight * KG_TO_LBS)
                            : formatWeightDisplay(weight);

                    if (col2)
                        col2.innerHTML = `<div class="bg-surface-container-highest py-2.5 text-center font-headline font-black text-xl text-on-surface completed-weight-display" data-weight-kg="${weight}">${displaySavedWeight}</div>`;
                    if (col3)
                        col3.innerHTML = `<div class="bg-surface-container-highest py-2.5 text-center font-headline font-black text-xl text-on-surface">${reps}</div>`;
                    if (actionCont)
                        actionCont.innerHTML =
                            '<span class="material-symbols-outlined text-primary-container">check_circle</span>';

                    row.classList.remove(
                        "kinetic-stripe",
                        "bg-surface-container-high",
                    );
                    row.classList.add("opacity-50", "bg-surface-container-low");

                    let numberDiv = row.querySelector(".set-number");
                    if (numberDiv) {
                        numberDiv.classList.remove("text-primary-container");
                        numberDiv.classList.add("text-on-surface");
                    }

                    const skipBtn = row
                        .closest(".exercise-block")
                        .querySelector(
                            'button[onclick*="skipOccupiedMachine"]',
                        );
                    if (skipBtn) skipBtn.style.display = "none";
                }

                syncSequentialSetLocks();

                let restTimeSeconds = 120;
                hideMobileCoachBox();

                if (data.analysis) {
                    restTimeSeconds = data.analysis.rest_seconds;

                    const targetDataEl =
                        document.getElementById("targetRestTimeData");
                    if (targetDataEl) {
                        targetDataEl.setAttribute("data-time", restTimeSeconds);
                    }
                    maxRestSeconds =
                        restTimeSeconds > maxRestSeconds
                            ? restTimeSeconds
                            : maxRestSeconds;

                    const currentExerciseBlock = document.querySelector(
                        '.exercise-block[style="display: block;"]',
                    );
                    const machineId = currentExerciseBlock
                        ? currentExerciseBlock.getAttribute("data-machine-id")
                        : null;

                    if (machineId) {
                        const coachBox = document.getElementById(
                            "coach-message-box-" + machineId,
                        );
                        const coachText = document.getElementById(
                            "coach-message-text-" + machineId,
                        );

                        if (coachBox && coachText) {
                            coachText.innerText = data.analysis.message;
                            coachBox.classList.remove("hidden");
                            setTimeout(() => {
                                coachBox.classList.remove(
                                    "translate-y-[-10px]",
                                    "opacity-0",
                                );
                            }, 50);
                        }
                    }

                    const mobileCoachBox =
                        document.getElementById("mobileCoachBox");
                    const mobileCoachText =
                        document.getElementById("mobileCoachText");
                    const mobileNextWeightText = document.getElementById(
                        "mobileNextWeightText",
                    );

                    if (mobileCoachBox && mobileCoachText) {
                        mobileCoachText.innerText = data.analysis.message || "";
                        mobileCoachBox.classList.toggle(
                            "hidden",
                            !data.analysis.message,
                        );

                        if (mobileNextWeightText) {
                            if (
                                data.analysis.next_weight !== undefined &&
                                data.analysis.next_weight !== null &&
                                data.analysis.next_weight !== ""
                            ) {
                                mobileNextWeightText.innerText = `SUGEROWANY NASTĘPNY CIĘŻAR: ${data.analysis.next_weight} KG`;
                                mobileNextWeightText.classList.remove("hidden");
                            } else {
                                mobileNextWeightText.classList.add("hidden");
                            }
                        }
                    }

                    const currentRow = document.getElementById(
                        "set-row-" + setId,
                    );
                    if (currentRow) {
                        const nextRow = currentRow.nextElementSibling;
                        if (nextRow && nextRow.classList.contains("set-row")) {
                            const nextSetId =
                                nextRow.getAttribute("data-set-id");
                            const nextWeightInput = document.getElementById(
                                "weight-" + nextSetId,
                            );
                            if (nextWeightInput && !nextWeightInput.disabled) {
                                const nextKg = parseFloat(
                                    data.analysis.next_weight,
                                );
                                const unit =
                                    nextWeightInput
                                        .closest(".exercise-block")
                                        ?.getAttribute("data-unit") || "kg";
                                nextWeightInput.setAttribute(
                                    "data-weight-kg",
                                    String(nextKg),
                                );
                                nextWeightInput.value =
                                    unit === "lbs"
                                        ? formatWeightDisplay(
                                              nextKg * KG_TO_LBS,
                                          )
                                        : formatWeightDisplay(nextKg);
                            }
                        }
                    }
                }

                completedSets++;
                currentVolume += parseFloat(weight) * parseInt(reps);
                updateStats();

                if (completedSets >= totalSets) {
                    setTimeout(() => {
                        window.skipRestTime();
                        window.submitWorkoutForm();
                    }, 1000);
                } else {
                    let timerView = document.getElementById("timerView");
                    let startRestBtn = document.getElementById("startRestBtn");
                    if (startRestBtn) startRestBtn.classList.add("hidden");
                    if (timerView) timerView.classList.remove("hidden");

                    startRestTimer(restTimeSeconds);

                    const blocks = document.querySelectorAll(".exercise-block");
                    const currentBlock = blocks[currentExIndex];
                    if (currentBlock) {
                        const allSetsInBlock =
                            currentBlock.querySelectorAll(".set-row").length;
                        const completedSetsInBlock =
                            currentBlock.querySelectorAll(
                                ".set-row.opacity-50",
                            ).length;

                        if (allSetsInBlock === completedSetsInBlock) {
                            setTimeout(() => {
                                goToNextIncompleteExercise();
                            }, 1200);
                        }
                    }
                }
            } else {
                window.showCustomAlert(
                    "Wystąpił błąd zapisu serii w bazie danych.",
                );
                btnElement.innerHTML = originalBtnHtml;
                btnElement.disabled = false;
            }
        })
        .catch(() => {
            window.showCustomAlert(
                "Błąd połączenia z serwerem. Spróbuj ponownie.",
            );
            btnElement.innerHTML = originalBtnHtml;
            btnElement.disabled = false;
        });
};

window.showCustomAlert = function (message) {
    const msgEl = document.getElementById("customAlertMessage");
    const modal = document.getElementById("customAlertModal");
    if (msgEl) msgEl.innerText = message;
    if (modal) {
        modal.classList.remove("hidden");
        modal.classList.add("flex");
    }
};

window.closeCustomAlert = function () {
    const modal = document.getElementById("customAlertModal");
    if (modal) {
        modal.classList.add("hidden");
        modal.classList.remove("flex");
    }
};

window.finishWorkout = function () {
    const modal = document.getElementById("customConfirmModal");
    if (modal) {
        modal.classList.remove("hidden");
        modal.classList.add("flex");
    }
};

window.closeCustomConfirm = function () {
    const modal = document.getElementById("customConfirmModal");
    if (modal) {
        modal.classList.add("hidden");
        modal.classList.remove("flex");
    }
};

window.submitWorkoutForm = function () {
    isIntentionalExit = true;
    const finishUrlEl = document.getElementById("trackerFinishUrl");
    const finishAction = finishUrlEl
        ? finishUrlEl.value
        : window.location.href + "/finish";

    const form = document.createElement("form");
    form.method = "POST";
    form.action = finishAction;
    document.body.appendChild(form);
    form.submit();
};

function goToNextIncompleteExercise() {
    const blocks = document.querySelectorAll(".exercise-block");
    let nextIndex = -1;

    for (let i = currentExIndex + 1; i < blocks.length; i++) {
        const allSets = blocks[i].querySelectorAll(".set-row").length;
        const doneSets = blocks[i].querySelectorAll(
            ".set-row.opacity-50",
        ).length;
        if (doneSets < allSets) {
            nextIndex = i;
            break;
        }
    }

    if (nextIndex === -1) {
        for (let i = 0; i < currentExIndex; i++) {
            const allSets = blocks[i].querySelectorAll(".set-row").length;
            const doneSets = blocks[i].querySelectorAll(
                ".set-row.opacity-50",
            ).length;
            if (doneSets < allSets) {
                nextIndex = i;
                break;
            }
        }
    }

    if (nextIndex !== -1 && nextIndex !== currentExIndex) {
        const currentBlock = blocks[currentExIndex];
        currentBlock.style.opacity = "0";
        setTimeout(() => {
            currentBlock.style.opacity = "1";
            showExercise(nextIndex);
        }, 300);
    }
}

document.addEventListener("DOMContentLoaded", () => {
    recalculateInitialStats();
    updateStats();
    syncSequentialSetLocks();

    document.querySelectorAll(".weight-input").forEach((input) => {
        input.addEventListener("change", () => {
            const block = input.closest(".exercise-block");
            const kg = getWeightInKg(input);
            input.setAttribute("data-weight-kg", kg > 0 ? String(kg) : "");
            if (block) detectAndSyncIncrementStep(block);
        });
    });

    const globalBtn = document.getElementById("globalFinishSetBtn");
    if (globalBtn) {
        globalBtn.addEventListener("click", function () {
            const overlay = document.getElementById("readyOverlay");
            if (
                overlay &&
                overlay.style.display !== "none" &&
                overlay.style.opacity !== "0"
            )
                return;

            const activeRow = document.querySelector(".set-row.kinetic-stripe");
            if (activeRow) {
                const btn = activeRow.querySelector(".save-set-btn");
                if (btn && !btn.disabled)
                    window.saveSet(activeRow.getAttribute("data-set-id"), btn);
            } else {
                window.showCustomAlert(
                    "Trening zakończony! Oczekiwanie na przekierowanie...",
                );
            }
        });
    }

    window.addEventListener("beforeunload", function (e) {
        if (!isIntentionalExit) {
            e.preventDefault();
            e.returnValue = "";
        }
    });

    document.querySelectorAll("form").forEach((form) => {
        form.addEventListener("submit", () => {
            isIntentionalExit = true;
        });
    });
});

export {};
