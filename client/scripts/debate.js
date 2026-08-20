const urlParams = new URLSearchParams(window.location.search);
const urlTopic = urlParams.get("topic");
const urlStance = urlParams.get("stance");

if (urlTopic && urlStance) {
    startDebate(urlTopic, urlStance);
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
    document.getElementById("topicSelect").classList.add("hidden");
    document.getElementById("debateScreen").classList.remove("hidden");

    document.getElementById("debateTopicLabel").textContent = topic;
    document.getElementById("debateStanceLabel").textContent = "YOU: " + stance;

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