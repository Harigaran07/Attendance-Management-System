<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Register</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
<script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
<style>
    body {
        font-family: 'Poppins', sans-serif;
        background: linear-gradient(135deg, #667eea, #764ba2);
        height: 100vh;
        display: flex;
        justify-content: center;
        align-items: center;
    }

    .register-card {
        background: #fff;
        border-radius: 20px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        overflow: hidden;
        max-width: 900px;
        width: 100%;
        display: flex;
        flex-wrap: wrap;
    }

    .register-left {
        flex: 1 1 300px;
        background: #6c63ff;
        color: #fff;
        padding: 40px;
        display: flex;
        flex-direction: column;
        justify-content: center;
        text-align: center;
    }

    .register-left h2 {
        margin-bottom: 20px;
        font-weight: 600;
    }

    .register-left p {
        font-size: 16px;
    }

    .register-right {
        flex: 2 1 500px;
        padding: 40px;
    }

    .form-label {
        font-weight: 500;
    }

    .form-control, .form-select {
        border-radius: 10px;
        height: 45px;
        margin-bottom: 15px;
        font-size: 16px;
    }

    .btn-register {
        width: 100%;
        background: #6c63ff;
        color: #fff;
        font-weight: 600;
        border-radius: 30px;
        padding: 12px;
        transition: 0.3s;
        border: none;
    }

    .btn-register:hover {
        background: #574fd6;
    }

    @media(max-width:768px) {
        .register-card {flex-direction: column;}
        .register-left, .register-right {flex: 1 1 100%; text-align: center;}
        .register-right {padding: 20px;}
    }
</style>
</head>
<body>

<div class="register-card">
    <!-- Left Side Info -->
    <div class="register-left">
        <h2>Welcome!</h2>
        <p>Create your account to manage your attendance efficiently. Fill the details on the right to get started.</p>
        <i class="fas fa-user-plus fa-3x mt-3"></i>
    </div>

    <!-- Right Side Form -->
    <div class="register-right">
        <form action="register_users.php" method="POST">
            <div class="row">
                <div class="col-md-6">
                    <input type="text" class="form-control" name="name" placeholder="Full Name" required>
                </div>
                <div class="col-md-6">
                    <input type="email" class="form-control" name="email" placeholder="Email" required>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6" id="parentEmailField">
                    <input type="email" class="form-control" name="parent_email" id="parent_email" placeholder="Parent Email">
                </div>
                <div class="col-md-6">
                    <input type="text" class="form-control" name="mobile" placeholder="Mobile Number" pattern="[0-9]{10}" required>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <select class="form-select" name="department" required>
                        <option value="">Department</option>
                        <option value="CSE">CSE</option>
                        <option value="EEE">EEE</option>
                        <option value="ECE">ECE</option>
                        <option value="MECH">MECH</option>
                        <option value="CIVIL">CIVIL</option>
                        <option value="DMT">IT</option>
                        <option value="DME">EIE</option>
                    </select>
                </div>
                <div class="col-md-6" id="yearField">
                    <select class="form-select" name="year">
                        <option value="">Year</option>
                        <option value="1st Year">1st Year</option>
                        <option value="2nd Year">2nd Year</option>
                        <option value="3rd Year">3rd Year</option>
                        <option value="4th Year">4th Year</option>

                    </select>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <select class="form-select" id="roleSelect" name="role" required>
                        <option value="">Role</option>
                        <option value="staff">Staff</option>
                        <option value="student">Student</option>
                    </select>
                </div>
                <div class="col-md-6" id="staffIdField" style="display:none;">
                    <input type="text" class="form-control" name="staff_id" placeholder="Staff ID">
                </div>
                <div class="col-md-6" id="studentRegField" style="display:none;">
                    <input type="text" class="form-control" name="register_no" placeholder="Register Number">
                </div>
            </div>

            <input type="password" class="form-control" name="password" placeholder="Password" required>

            <button type="submit" class="btn btn-register mt-3">Register</button>
        </form>
    </div>
</div>

<script>
document.getElementById("roleSelect").addEventListener("change", function() {
    let role = this.value;
    let staffField = document.getElementById("staffIdField");
    let studentField = document.getElementById("studentRegField");
    //let yearField = document.getElementById("yearField");
    let parentEmailField = document.getElementById("parentEmailField");
    let parentEmailInput = document.getElementById("parent_email");

    if(role === "staff"){
        staffField.style.display = "block";
        studentField.style.display = "none";
        //yearField.style.display = "none";
        parentEmailField.style.display = "none";
        parentEmailInput.removeAttribute("required");
    } else if(role === "student"){
        staffField.style.display = "none";
        studentField.style.display = "block";
        yearField.style.display = "block";
        parentEmailField.style.display = "block";
        parentEmailInput.setAttribute("required","required");
    } else {
        staffField.style.display = "none";
        studentField.style.display = "none";
        yearField.style.display = "none";
        parentEmailField.style.display = "none";
        parentEmailInput.removeAttribute("required");
    }
});
</script>

</body>
</html>
