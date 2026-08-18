document.getElementById("signUpForm").addEventListener("submit", function (e) {
    e.preventDefault();

    const username = document.getElementById("signUpUsername").value;
    const email = document.getElementById("signUpEmail").value;
    const password = document.getElementById("signUpPassword").value;

    const formData = new FormData();
    formData.append("username", username);
    formData.append("email", email);
    formData.append("password", password);

    axios.post(BASE_URL + "/server/signUp.php", formData)
        .then(function (response) {
            const data = response.data;
            showNotification(data.message);

            if (data.success) {
                document.getElementById("signUpForm").reset();
            }
        })
        .catch(function (error) {
            showNotification("Something went wrong. Try again.");
        });
});

document.getElementById("logInForm").addEventListener("submit", function (e) {
    e.preventDefault();

    const email = document.getElementById("logInEmail").value;
    const password = document.getElementById("logInPassword").value;

    const formData = new FormData();
    formData.append("email", email);
    formData.append("password", password);

    axios.post(BASE_URL + "/server/login.php", formData)
        .then(function (response) {
            const data = response.data;
            showNotification(data.message);

            if (data.success) {
                localStorage.setItem("user", JSON.stringify(data.user));
                document.getElementById("logInForm").reset();
                showProfile();
            }
        })
        .catch(function (error) {
            showNotification("Something went wrong. Try again.");
        });
});

document.getElementById("logOutBtn").addEventListener("click", function () {
    localStorage.removeItem("user");
    showAuthForms();
});

function showProfile() {
    const user = JSON.parse(localStorage.getItem("user"));

    document.getElementById("profileUsername").textContent = user.username;
    document.getElementById("profileEmail").textContent = user.email;

    document.getElementById("signUp").classList.add("hidden");
    document.getElementById("logIn").classList.add("hidden");
    document.getElementById("Profile").classList.remove("hidden");
}

function showAuthForms() {
    document.getElementById("signUp").classList.remove("hidden");
    document.getElementById("logIn").classList.remove("hidden");
    document.getElementById("Profile").classList.add("hidden");
}

function showNotification(message) {
    const notif = document.getElementById("notification");
    notif.textContent = message;
    notif.classList.remove("hidden");

    setTimeout(function () {
        notif.classList.add("hidden");
    }, 3000);
}