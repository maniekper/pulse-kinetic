// assets/js/admin_machine.js
let currentDeleteFormId = null;

window.openDeleteModal = function (machineId, machineName) {
    currentDeleteFormId = "delete-form-" + machineId;
    document.getElementById("deleteMachineName").textContent = machineName;

    const modal = document.getElementById("customDeleteModal");
    modal.classList.remove("hidden");
    modal.classList.add("flex");
};

window.closeDeleteModal = function () {
    currentDeleteFormId = null;
    const modal = document.getElementById("customDeleteModal");
    modal.classList.add("hidden");
    modal.classList.remove("flex");
};

window.confirmDelete = function () {
    if (currentDeleteFormId) {
        document.getElementById(currentDeleteFormId).submit();
    }
};

export {};
