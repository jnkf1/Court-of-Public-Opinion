if (loadStoredData("user")) {
    showProfile();
}

document.getElementById("signUpForm").addEventListener("submit", function (e) {
    e.preventDefault();

    const username = document.getElementById("signUpUsername").value;
    const email = document.getElementById("signUpEmail").value;
    const password = document.getElementById("signUpPassword").value;

    const formData = new FormData();
    formData.append("username", username);
    formData.append("email", email);
    formData.append("password", password);

    axios.post(BASE_URL + "/server/profile/signUp.php", formData)
        .then(function (response) {
            const data = response.data;
            showNotification(data.message);

            if (data.success) {
                saveStoredData("user", data.user);
                document.getElementById("signUpForm").reset();
                showProfile();
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

    axios.post(BASE_URL + "/server/profile/logIn.php", formData)
        .then(function (response) {
            const data = response.data;
            showNotification(data.message);

            if (data.success) {
                saveStoredData("user", data.user);
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

let deleteAccountArmed = false;
let deleteAccountArmTimer = null;

document.getElementById("deleteAccountBtn").addEventListener("click", function () {
    if (!deleteAccountArmed) {
        deleteAccountArmed = true;
        this.textContent = "CLICK AGAIN TO CONFIRM";
        showNotification("Click Delete Account again to confirm. This can't be undone.");

        deleteAccountArmTimer = setTimeout(function () {
            deleteAccountArmed = false;
            document.getElementById("deleteAccountBtn").textContent = "Delete Account";
        }, 4000);

        return;
    }

    clearTimeout(deleteAccountArmTimer);

    const user = loadStoredData("user");

    const formData = new FormData();
    formData.append("token", user.token);

    axios.post(BASE_URL + "/server/profile/deleteAccount.php", formData)
        .then(function (response) {
            const data = response.data;
            showNotification(data.message);

            if (data.success) {
                localStorage.removeItem("user");
                showAuthForms();
            }
        })
        .catch(function (error) {
            showNotification("Something went wrong. Try again.");
        });
});

document.getElementById("goToLogInBtn").addEventListener("click", function () {
    document.getElementById("signUp").classList.add("hidden");
    document.getElementById("logIn").classList.remove("hidden");
});

document.getElementById("goToSignUpBtn").addEventListener("click", function () {
    document.getElementById("logIn").classList.add("hidden");
    document.getElementById("signUp").classList.remove("hidden");
});

document.getElementById("goToForgotPasswordBtn").addEventListener("click", function () {
    document.getElementById("logIn").classList.add("hidden");
    document.getElementById("forgotPassword").classList.remove("hidden");
});

document.getElementById("backToLogInBtn").addEventListener("click", function () {
    document.getElementById("forgotPassword").classList.add("hidden");
    document.getElementById("logIn").classList.remove("hidden");
});

document.getElementById("forgotPasswordForm").addEventListener("submit", function (e) {
    e.preventDefault();

    const email = document.getElementById("forgotPasswordEmail").value;
    const newPassword = document.getElementById("forgotPasswordNew").value;

    const formData = new FormData();
    formData.append("email", email);
    formData.append("newPassword", newPassword);

    axios.post(BASE_URL + "/server/profile/resetPassword.php", formData)
        .then(function (response) {
            const data = response.data;
            showNotification(data.message);

            if (data.success) {
                document.getElementById("forgotPasswordForm").reset();
                document.getElementById("forgotPassword").classList.add("hidden");
                document.getElementById("logIn").classList.remove("hidden");
            }
        })
        .catch(function (error) {
            showNotification("Something went wrong. Try again.");
        });
});

function showAuthForms() {
    document.getElementById("signUp").classList.remove("hidden");
    document.getElementById("logIn").classList.add("hidden");
    document.getElementById("Profile").classList.add("hidden");
}

function showProfile() {
    const user = loadStoredData("user");

    document.getElementById("profileUsername").textContent = "@" + user.username;

    document.getElementById("signUp").classList.add("hidden");
    document.getElementById("logIn").classList.add("hidden");
    document.getElementById("Profile").classList.remove("hidden");

    loadProfileStats(user.token);
}

function loadProfileStats(token) {
    const formData = new FormData();
    formData.append("token", token);

    axios.post(BASE_URL + "/server/profile/getProfile.php", formData)
        .then(function (response) {
            const data = response.data;

            if (!data.success) {
                showNotification("Couldn't load profile stats.");
                return;
            }

            renderRecord(data.record);
            renderArgumentProfile(data.argument_profile);
            renderTrend(data.rebuttal_improvement, data.cases_needed_for_trend);
            renderRecentCases(data.recent_cases);
        })
        .catch(function (error) {
            showNotification("Something went wrong loading your profile.");
        });
}

function renderRecord(record) {
    document.getElementById("totalCases").textContent = record.total_cases;
    document.getElementById("totalWins").textContent = record.wins;
    document.getElementById("totalLosses").textContent = record.losses;
    document.getElementById("totalDraws").textContent = record.draws;
}

function renderArgumentProfile(profile) {
    setBar("logic", profile.avg_logic);
    setBar("rebuttal", profile.avg_rebuttal);
    setBar("evidence", profile.avg_evidence);
    setBar("persuasion", profile.avg_persuasion);
}

function setBar(name, value) {
    const bar = document.getElementById(name + "Bar");
    const valueLabel = document.getElementById(name + "Value");

    bar.style.width = value + "%";
    valueLabel.textContent = value;
}

function renderTrend(improvement, casesNeeded) {
    const trendText = document.getElementById("trendText");

    if (improvement === null) {
        trendText.textContent = "Complete " + casesNeeded + " more cases to see your rebuttal trend.";
    }
    else if (improvement >= 0) {
        trendText.textContent = "Your rebuttals have improved " + improvement + "% over your last 10 cases.";
    }
    else {
        trendText.textContent = "Your rebuttals have dropped " + Math.abs(improvement) + "% over your last 10 cases.";
    }
}

function renderRecentCases(cases) {
    const container = document.getElementById("recentCasesList");
    container.innerHTML = "";

    for (let i = 0; i < cases.length; i++) {
        const c = cases[i];

        const card = document.createElement("div");
        card.className = "recent-case";

        const verdictClass = "verdict-" + c.verdict.toLowerCase();

        card.innerHTML =
            "<p class='recent-case-number'>CASE #" + String(c.id).padStart(5, "0") + " &middot; " + c.user_stance + "</p>" +
            "<p class='recent-case-title'>" + c.topic + "</p>" +
            "<p class='recent-case-result'><span class='" + verdictClass + "'>" + c.verdict + "</span> &mdash; " + c.score + "</p>";

        container.appendChild(card);
    }
}