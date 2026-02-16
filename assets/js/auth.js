document.addEventListener("DOMContentLoaded", function () {

  // LOGIN FORM
  const loginForm = document.getElementById("loginForm");

  if (loginForm) {
    loginForm.addEventListener("submit", function (e) {
      e.preventDefault();

      let errors = [];

      const emailInput = loginForm.querySelector('input[name="email"]');
      const passwordInput = loginForm.querySelector('input[name="password"]');

      const email = emailInput.value.trim();
      const password = passwordInput.value;

      if (email === "") {
        errors.push("Email is required");
      } else if (!/^\S+@\S+\.\S+$/.test(email)) {
        errors.push("Invalid email format");
      }

      if (password === "") {
        errors.push("Password is required");
      }

      if (errors.length > 0) {
        Swal.fire({
          icon: "error",
          title: "Invalid Login",
          html: errors.join("<br>"),
        });
        return;
      }

      loginForm.submit();
    });
  }

  // OTP FORM
  const otpForm = document.getElementById("otpForm");

  if (otpForm) {
    otpForm.addEventListener("submit", function (e) {
      e.preventDefault();

      let errors = [];

      const otpInput = otpForm.querySelector('input[name="otp"]');
      const otp = otpInput.value.trim();

      if (!/^[0-9]{6}$/.test(otp)) {
        errors.push("OTP must be a 6-digit number");
      }

      if (errors.length > 0) {
        Swal.fire({
          icon: "error",
          title: "Invalid OTP",
          html: errors.join("<br>"),
        });
        return;
      }

      otpForm.submit();
    });
  }
});
