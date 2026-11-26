import Chart from "chart.js/auto";

document.addEventListener("DOMContentLoaded", () => {
    const select = document.getElementById("strengthMachineSelect");
    const canvas = document.getElementById("strengthChart");
    const emptyMsg = document.getElementById("strengthChartEmpty");
    const metricButtons = document.querySelectorAll(".strength-metric-btn");
    const dateAxisBar = document.getElementById("strengthDateAxisBar");
    const latestDateLabel = document.getElementById("strengthLatestDateLabel");

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

    const hexToRgba = (hex, alpha) => {
        const clean = hex.replace("#", "").trim();
        if (clean.length !== 6) {
            return `rgba(209, 252, 0, ${alpha})`;
        }
        const r = parseInt(clean.substring(0, 2), 16);
        const g = parseInt(clean.substring(2, 4), 16);
        const b = parseInt(clean.substring(4, 6), 16);
        return `rgba(${r}, ${g}, ${b}, ${alpha})`;
    };

    const updateDateAxisSummary = () => {
        if (!dateAxisBar || !latestDateLabel) return;
        const labels = currentData?.labels || [];
        if (labels.length === 0) {
            dateAxisBar.classList.add("hidden");
            return;
        }
        dateAxisBar.classList.remove("hidden");
        const firstDate = labels[0];
        const lastDate = labels[labels.length - 1];
        latestDateLabel.textContent =
            labels.length > 1 ? `${firstDate} — ${lastDate}` : lastDate;
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
            updateDateAxisSummary();
            return;
        }

        canvas.classList.remove("hidden");
        if (emptyMsg) emptyMsg.classList.add("hidden");
        updateDateAxisSummary();

        const color = getThemeColor();
        const cfg = metricConfig[activeMetric];
        const values = currentData[activeMetric] || [];

        if (chartInstance) {
            chartInstance.destroy();
        }

        const ctx = canvas.getContext("2d");
        const gradient = ctx.createLinearGradient(0, 0, 0, 300);
        gradient.addColorStop(0, hexToRgba(color, 0.25));
        gradient.addColorStop(1, hexToRgba(color, 0.0));

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
                interaction: {
                    mode: "index",
                    intersect: false,
                },
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: "#111111",
                        borderColor: "rgba(255,255,255,0.15)",
                        borderWidth: 1,
                        titleColor: "#ffffff",
                        bodyColor: "#e5e5e5",
                        padding: 12,
                        callbacks: {
                            title: (items) => {
                                const label = items[0]?.label || "";
                                return label ? `Data treningu: ${label}` : "";
                            },
                            label: (context) =>
                                ` ${cfg.label}: ${context.parsed.y} ${cfg.unit}`,
                        },
                    },
                },
                scales: {
                    x: {
                        grid: { color: "rgba(255,255,255,0.05)" },
                        border: {
                            display: true,
                            color: "rgba(255,255,255,0.22)",
                            width: 1.5,
                        },
                        ticks: {
                            color: "#b0b0b0",
                            font: {
                                family: "Space Grotesk, sans-serif",
                                weight: "700",
                                size: 11,
                            },
                        },
                        title: {
                            display: true,
                            text: "DATA TRENINGU",
                            color: "#9e9e9e",
                            font: {
                                family: "Space Grotesk, sans-serif",
                                weight: "700",
                                size: 11,
                            },
                        },
                    },
                    y: {
                        beginAtZero: true,
                        grid: { color: "rgba(255,255,255,0.06)" },
                        border: {
                            display: true,
                            color: "rgba(255,255,255,0.18)",
                            width: 1,
                        },
                        ticks: {
                            color: "#9e9e9e",
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
