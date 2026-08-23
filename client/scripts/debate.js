const debateCase = loadStoredData("debateCase");

if (debateCase && debateCase.topic && debateCase.stance) {
    localStorage.removeItem("debateCase");
    startDebate(debateCase.topic, debateCase.stance);
}
else {
    document.getElementById("startCustomDebate").addEventListener("click", function () {
        const topic = document.getElementById("customTopic").value;
        const stance = selectedStance;

        if (!topic || !stance) {
            showNotification("Please enter a topic and pick a side.");
            return;
        }

        startDebate(topic, stance);
    });

    document.getElementById("startRandomDebate").addEventListener("click", function () {
        // we'll wire this to a real random topic later
        startDebate("Should social media have an age limit?", "FOR");
    });
}

function startDebate(topic, stance) {
    const user = loadStoredData("user");

    if (!user) {
        showNotification("Please sign up or log in before debating the AI.");

        setTimeout(function () {
            window.location.href = "/client/pages/profile.html";
        }, 1500);

        return;
    }

    document.getElementById("topicSelect").classList.add("hidden");
    document.getElementById("debateScreen").classList.remove("hidden");

    const opponentStance = stance === "FOR" ? "AGAINST" : "FOR";

    document.getElementById("debateTopicLabel").textContent = topic;
    document.getElementById("debateStanceLabel").textContent = "YOU: " + stance;
    document.getElementById("debateOpponentLabel").textContent = "OPPONENT: " + opponentStance;

    setTimeout(function () {
        document.body.classList.add("collapsed");
    }, 5000);
}

let selectedStance = null;

const stanceButtons = document.querySelectorAll(".stance-btn");

for (let i = 0; i < stanceButtons.length; i++) {
    stanceButtons[i].addEventListener("click", function () {
        const clickedStance = this.getAttribute("data-stance");

        for (let j = 0; j < stanceButtons.length; j++) {
            stanceButtons[j].classList.remove("selected");
        }

        if (selectedStance === clickedStance) {
            selectedStance = null;
        }
        else {
            this.classList.add("selected");
            selectedStance = clickedStance;
        }
    });
}