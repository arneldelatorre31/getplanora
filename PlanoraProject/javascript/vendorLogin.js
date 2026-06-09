const showSignup = document.getElementById("showSignup");
const showLogin = document.getElementById("showLogin");

const loginForm = document.getElementById("loginForm");
const signupForm = document.getElementById("signupForm");
const termsModal = document.getElementById("termsModal");
const closeTerms = document.getElementById("closeTerms");
const cancelTerms = document.getElementById("cancelTerms");
const acceptTerms = document.getElementById("acceptTerms");

const params = new URLSearchParams(window.location.search);
const shouldShowNewAccountTerms = params.get("new_account") === "1";

showSignup.addEventListener("click", () => {
    loginForm.style.display = "none";
    signupForm.style.display = "block";
});

showLogin.addEventListener("click", () => {
    signupForm.style.display = "none";
    loginForm.style.display = "block";
});

function openTermsModal() {
    termsModal.classList.add("show");
    termsModal.setAttribute("aria-hidden", "false");
    document.body.classList.add("modal-open");
}

function closeTermsModal() {
    termsModal.classList.remove("show");
    termsModal.setAttribute("aria-hidden", "true");
    document.body.classList.remove("modal-open");
}

function continueToDashboard() {
    window.location.href = "dashboard.php";
}

if (shouldShowNewAccountTerms) {
    openTermsModal();
}

acceptTerms.addEventListener("click", continueToDashboard);
closeTerms.addEventListener("click", closeTermsModal);
cancelTerms.addEventListener("click", closeTermsModal);

termsModal.addEventListener("click", (event) => {
    if (event.target === termsModal) {
        closeTermsModal();
    }
});

document.addEventListener("keydown", (event) => {
    if (event.key === "Escape" && termsModal.classList.contains("show")) {
        closeTermsModal();
    }
});
