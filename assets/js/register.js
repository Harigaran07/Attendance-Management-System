document.addEventListener("DOMContentLoaded", function () {
    const roleSelect = document.getElementById("roleSelect");
    const staffField = document.getElementById("staffIdField");
    const studentField = document.getElementById("studentRegField");

    function toggleFields() {
        if (roleSelect.value === "staff") {
            staffField.style.display = "block";
            studentField.style.display = "none";
        } else if (roleSelect.value === "student") {
            staffField.style.display = "none";
            studentField.style.display = "block";
        } else {
            staffField.style.display = "none";
            studentField.style.display = "none";
        }
    }

    roleSelect.addEventListener("change", toggleFields);
    toggleFields(); // Call function initially
});
