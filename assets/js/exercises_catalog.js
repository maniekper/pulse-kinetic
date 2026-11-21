document.addEventListener("DOMContentLoaded", () => {
    const searchInput = document.getElementById("exerciseSearchInput");
    const onlyBannedBtn = document.getElementById("onlyBannedToggle");
    const categoryBtns = document.querySelectorAll(".cat-filter-btn");
    const cards = document.querySelectorAll(".exercise-card");
    const emptyBox = document.getElementById("exercisesEmptyFilter");
    const bannedCounter = document.getElementById("bannedCounter");

    let activeCategory = "all";
    let onlyBanned = false;

    const applyFilters = () => {
        const query = (searchInput?.value || "").trim().toLowerCase();
        let visibleCount = 0;

        cards.forEach((card) => {
            const name = card.dataset.name || "";
            const category = card.dataset.category || "";
            const isBanned = card.dataset.banned === "1";

            const matchesQuery =
                !query || name.includes(query) || category.includes(query);
            const matchesCategory =
                activeCategory === "all" || category === activeCategory;
            const matchesBanned = !onlyBanned || isBanned;

            if (matchesQuery && matchesCategory && matchesBanned) {
                card.classList.remove("hidden");
                visibleCount++;
            } else {
                card.classList.add("hidden");
            }
        });

        if (emptyBox) {
            emptyBox.classList.toggle("hidden", visibleCount > 0);
        }
    };

    searchInput?.addEventListener("input", applyFilters);

    onlyBannedBtn?.addEventListener("click", () => {
        onlyBanned = !onlyBanned;
        onlyBannedBtn.dataset.active = onlyBanned ? "1" : "0";

        if (onlyBanned) {
            onlyBannedBtn.classList.remove(
                "bg-[#1a1a1a]",
                "text-[#767575]",
                "border-white/10",
            );
            onlyBannedBtn.classList.add(
                "bg-[#ff7351]",
                "text-[#111111]",
                "border-[#ff7351]",
            );
        } else {
            onlyBannedBtn.classList.remove(
                "bg-[#ff7351]",
                "text-[#111111]",
                "border-[#ff7351]",
            );
            onlyBannedBtn.classList.add(
                "bg-[#1a1a1a]",
                "text-[#767575]",
                "border-white/10",
            );
        }

        applyFilters();
    });

    categoryBtns.forEach((btn) => {
        btn.addEventListener("click", () => {
            activeCategory = btn.dataset.category || "all";

            categoryBtns.forEach((b) => {
                b.classList.remove(
                    "bg-primary-container",
                    "text-[#111111]",
                    "border-primary-container",
                    "font-black",
                );
                b.classList.add(
                    "bg-[#1a1a1a]",
                    "text-[#767575]",
                    "border-white/10",
                    "font-bold",
                );
            });

            btn.classList.remove(
                "bg-[#1a1a1a]",
                "text-[#767575]",
                "border-white/10",
                "font-bold",
            );
            btn.classList.add(
                "bg-primary-container",
                "text-[#111111]",
                "border-primary-container",
                "font-black",
            );

            applyFilters();
        });
    });

    const updateCardBanState = (card, btn, isBanned) => {
        card.dataset.banned = isBanned ? "1" : "0";

        const badge = card.querySelector(".banned-badge");
        const icon = btn.querySelector(".ban-icon");
        const label = btn.querySelector(".ban-label");

        if (isBanned) {
            card.classList.remove("border-white/10");
            card.classList.add("border-[#ff7351]/40", "opacity-75");
            badge?.classList.remove("hidden");
            if (icon) icon.textContent = "check_circle";
            if (label) label.textContent = "PRZYWRÓĆ DO GENERATORA";

            btn.classList.remove(
                "bg-[#1a1a1a]",
                "hover:bg-[#ff7351]",
                "text-[#adaaaa]",
                "border-white/10",
                "hover:border-[#ff7351]",
            );
            btn.classList.add(
                "bg-primary-container/15",
                "hover:bg-primary-container",
                "text-primary-container",
                "hover:text-[#111111]",
                "border-primary-container/40",
            );
        } else {
            card.classList.remove("border-[#ff7351]/40", "opacity-75");
            card.classList.add("border-white/10");
            badge?.classList.add("hidden");
            if (icon) icon.textContent = "block";
            if (label) label.textContent = "WYKLUCZ MASZYNĘ";

            btn.classList.remove(
                "bg-primary-container/15",
                "hover:bg-primary-container",
                "text-primary-container",
                "border-primary-container/40",
            );
            btn.classList.add(
                "bg-[#1a1a1a]",
                "hover:bg-[#ff7351]",
                "text-[#adaaaa]",
                "hover:text-[#111111]",
                "border-white/10",
                "hover:border-[#ff7351]",
            );
        }
    };

    document.querySelectorAll("[data-toggle-ban-btn]").forEach((btn) => {
        btn.addEventListener("click", async () => {
            const card = btn.closest(".exercise-card");
            const url = btn.dataset.url;
            const csrf = btn.dataset.csrf;

            if (!url || !card) return;

            btn.disabled = true;

            try {
                const res = await fetch(url, {
                    method: "POST",
                    headers: {
                        "X-CSRF-TOKEN": csrf,
                        "X-Requested-With": "XMLHttpRequest",
                    },
                });

                const data = await res.json().catch(() => ({}));

                if (!res.ok) {
                    console.error("toggle-ban failed:", res.status, data);
                    alert(data.error || `Błąd serwera (HTTP ${res.status})`);
                    return;
                }

                updateCardBanState(card, btn, Boolean(data.banned));

                if (bannedCounter && typeof data.bannedCount === "number") {
                    bannedCounter.textContent = String(data.bannedCount);
                }

                if (onlyBanned && !data.banned) {
                    applyFilters();
                }
            } catch (err) {
                console.error("Błąd przełączania blokady maszyny:", err);
                alert("Błąd połączenia z serwerem.");
            } finally {
                btn.disabled = false;
            }
        });
    });
});
