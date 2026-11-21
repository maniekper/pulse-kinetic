import Chart from "chart.js/auto";

document.addEventListener("DOMContentLoaded", () => {
    const select = document.getElementById("strengthMachineSelect");
    const canvas = document.getElementById("strengthChart");
    const emptyMsg = document.getElementById("strengthChartEmpty");
    const metricButtons = document.querySelectorAll(".strength-metric-btn");

    if (!select || !canvas) {
        return;
    }

    let chartInstance = null;
    let currentData = null;
    let activeMetric = "oneRm";

    const metricConfig = {
        oneRm: { label: "Szacowane 1RM (kg)", unit: "kg" },
        maxWeight: { label: "Max ciężar w sesji (kg)", unit: "kg" },
        tonnage: { label: "Tonaż ćwiczenia (kg)", unit: "kg" },
    };

    const getThemeColor = () => {
        const val = getComputedStyle(document.documentElement)
            .getPropertyValue("--theme-main")
            .trim();
        return val || "#d1fc00";
    };

    const renderChart = () => {
        if (
            !currentData ||
            !currentData.labels ||
            currentData.labels.length === 0
        ) {
            if (chartInstance) {
                chartInstance.destroy();
                chartInstance = null;
            }
            canvas.classList.add("hidden");
            if (emptyMsg) emptyMsg.classList.remove("hidden");
            return;
        }

        canvas.classList.remove("hidden");
        if (emptyMsg) emptyMsg.classList.add("hidden");

        const color = getThemeColor();
        const cfg = metricConfig[activeMetric];
        const values = currentData[activeMetric] || [];

        if (chartInstance) {
            chartInstance.destroy();
        }

        const ctx = canvas.getContext("2d");
        const gradient = ctx.createLinearGradient(0, 0, 0, 300);
        gradient.addColorStop(0, "rgba(209, 252, 0, 0.25)");
        gradient.addColorStop(1, "rgba(209, 252, 0, 0.0)");

        chartInstance = new Chart(ctx, {
            type: "line",
            data: {
                labels: currentData.labels,
                datasets: [
                    {
                        label: cfg.label,
                        data: values,
                        borderColor: color,
                        backgroundColor: gradient,
                        borderWidth: 2.5,
                        pointBackgroundColor: "#111111",
                        pointBorderColor: color,
                        pointBorderWidth: 2,
                        pointRadius: 4,
                        pointHoverRadius: 6,
                        fill: true,
                        tension: 0.25,
                    },
                ],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: "#111111",
                        borderColor: "rgba(255,255,255,0.15)",
                        borderWidth: 1,
                        titleColor: "#767575",
                        bodyColor: "#ffffff",
                        padding: 12,
                        callbacks: {
                            label: (context) =>
                                ` ${context.parsed.y} ${cfg.unit}`,
                        },
                    },
                },
                scales: {
                    x: {
                        grid: { color: "rgba(255,255,255,0.04)" },
                        ticks: { color: "#767575", font: { size: 11 } },
                    },
                    y: {
                        grid: { color: "rgba(255,255,255,0.06)" },
                        ticks: {
                            color: "#767575",
                            font: { size: 11 },
                            callback: (val) => `${val} kg`,
                        },
                    },
                },
            },
        });
    };

    const loadMachineData = async (machineId) => {
        if (!machineId) return;
        const urlTemplate = select.dataset.urlTemplate;
        const url = urlTemplate.replace("__ID__", machineId);

        try {
            const res = await fetch(url, {
                headers: { "X-Requested-With": "XMLHttpRequest" },
            });
            if (!res.ok) return;
            currentData = await res.json();
            renderChart();
        } catch (err) {
            console.error("Błąd ładowania danych wykresu:", err);
        }
    };

    select.addEventListener("change", (e) => {
        loadMachineData(e.target.value);
    });

    metricButtons.forEach((btn) => {
        btn.addEventListener("click", () => {
            activeMetric = btn.dataset.metric;

            metricButtons.forEach((b) => {
                b.classList.remove(
                    "bg-primary-container",
                    "text-[#111111]",
                    "border-primary-container",
                );
                b.classList.add(
                    "bg-[#1a1a1a]",
                    "text-[#767575]",
                    "border-white/10",
                );
            });

            btn.classList.remove(
                "bg-[#1a1a1a]",
                "text-[#767575]",
                "border-white/10",
            );
            btn.classList.add(
                "bg-primary-container",
                "text-[#111111]",
                "border-primary-container",
            );

            renderChart();
        });
    });

    // Załaduj wykres dla pierwszej maszyny od razu po wejściu
    if (select.value) {
        loadMachineData(select.value);
    }
});
