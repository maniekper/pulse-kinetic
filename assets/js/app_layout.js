function toggleMobileMenu() {
    const menu = document.getElementById("mobileMenuOverlay");
    const icon = document.getElementById("burgerIcon");

    const isOpen = !menu.classList.toggle("hidden");
    menu.classList.toggle("flex", isOpen);

    icon.innerText = isOpen ? "close" : "menu";
    document.body.style.overflow = isOpen ? "hidden" : "";
}

// Wystawiamy funkcję globalnie, aby onclick w HTML jej nie zgubił
window.toggleMobileMenu = toggleMobileMenu;
