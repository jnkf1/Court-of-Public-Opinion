let debateTopic = "";
let userStance = "";
let aiStance = "";
let conversationHistory = [];
let countdownTimer = null;
let debateExpired = false;
let debateStartedAt = null;

const activeDebate = loadStoredData("activeDebate");
const debateCase = loadStoredData("debateCase");

if (activeDebate) {
    localStorage.removeItem("debateCase");
    resumeDebate(activeDebate);
}
else if (debateCase && debateCase.topic && debateCase.stance) {
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

        const formData = new FormData();
        formData.append("topic", topic);

        axios.post(BASE_URL + "/server/ai/checkTopic.php", formData)
            .then(function (response) {
                const data = response.data;

                if (data.success && data.debatable === false) {
                    showNotification("That doesn't look like a debatable topic. Try something with two clear sides.");
                    return;
                }

                startDebate(topic, stance);
            })
            .catch(function (error) {
                startDebate(topic, stance);
            });
    });

    document.getElementById("startRandomDebate").addEventListener("click", function () {
        axios.post(BASE_URL + "/server/topics/getRandomTopic.php")
            .then(function (response) {
                const data = response.data;

                if (!data.success) {
                    showNotification("Couldn't get a random topic.");
                    return;
                }

                const randomStance = Math.random() < 0.5 ? "FOR" : "AGAINST";

                startDebate(data.topic, randomStance);
            })
            .catch(function (error) {
                showNotification("Couldn't get a random topic.");
            });
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
    document.getElementById("chatSection").classList.remove("hidden");

    debateTopic = topic;
    userStance = stance;
    aiStance = stance === "FOR" ? "AGAINST" : "FOR";
    conversationHistory = [];
    debateStartedAt = new Date().toISOString();

    document.getElementById("debateTopicLabel").textContent = topic;
    document.getElementById("debateStanceLabel").textContent = "YOU: " + stance;
    document.getElementById("debateOpponentLabel").textContent = "OPPONENT: " + aiStance;

    saveActiveDebate();

    const endTime = new Date(debateStartedAt).getTime() + 1 * 60 * 1000;
    startCountdown(endTime);

    setTimeout(function () {
        document.body.classList.add("collapsed");
    }, 5000);
}

function resumeDebate(saved) {
    const user = loadStoredData("user");

    if (!user) {
        clearActiveDebate();
        showNotification("Please sign up or log in before debating the AI.");

        setTimeout(function () {
            window.location.href = "/client/pages/profile.html";
        }, 1500);

        return;
    }

    document.getElementById("topicSelect").classList.add("hidden");
    document.getElementById("debateScreen").classList.remove("hidden");
    document.getElementById("chatSection").classList.remove("hidden");

    debateTopic = saved.topic;
    userStance = saved.userStance;
    aiStance = saved.aiStance;
    conversationHistory = saved.conversationHistory;
    debateStartedAt = saved.startedAt;

    document.getElementById("debateTopicLabel").textContent = debateTopic;
    document.getElementById("debateStanceLabel").textContent = "YOU: " + userStance;
    document.getElementById("debateOpponentLabel").textContent = "OPPONENT: " + aiStance;

    for (let i = 0; i < conversationHistory.length; i++) {
        const turn = conversationHistory[i];
        addChatMessage(turn.content[0].text, turn.type === "user_input");
    }

    document.body.classList.add("collapsed");

    const endTime = new Date(debateStartedAt).getTime() + 1 * 60 * 1000;

    if (endTime - new Date().getTime() <= 0) {
        document.getElementById("debateTimer").textContent = "TIME'S UP";
        document.getElementById("messageInput").disabled = true;
        document.getElementById("sendMessageBtn").disabled = true;
        debateExpired = true;

        if (conversationHistory.length > 0) {
            judgeDebate();
        }
        else {
            clearActiveDebate();
        }

        return;
    }

    startCountdown(endTime);
}

function saveActiveDebate() {
    saveStoredData("activeDebate", {
        topic: debateTopic,
        userStance: userStance,
        aiStance: aiStance,
        conversationHistory: conversationHistory,
        startedAt: debateStartedAt
    });
}

function clearActiveDebate() {
    localStorage.removeItem("activeDebate");
}

function startCountdown(endTime) {
    countdownTimer = setInterval(function () {
        const remainingMs = endTime - new Date().getTime();

        if (remainingMs <= 0) {
            document.getElementById("debateTimer").textContent = "TIME'S UP";
            document.getElementById("messageInput").disabled = true;
            document.getElementById("sendMessageBtn").disabled = true;
            debateExpired = true;
            clearInterval(countdownTimer);
            judgeDebate();
            return;
        }

        const minutes = Math.floor(remainingMs / 60000);
        const seconds = Math.floor((remainingMs % 60000) / 1000);

        document.getElementById("debateTimer").textContent =
            String(minutes).padStart(2, "0") + ":" + String(seconds).padStart(2, "0");
    }, 1000);
}

function addChatMessage(text, isYou) {
    const log = document.getElementById("chatLog");

    const bubble = document.createElement("div");
    bubble.className = isYou ? "chat-message you" : "chat-message opponent";
    bubble.textContent = text;

    log.appendChild(bubble);
    log.scrollTop = log.scrollHeight;
}

document.getElementById("messageForm").addEventListener("submit", function (e) {
    e.preventDefault();

    if (debateExpired) {
        return;
    }

    const input = document.getElementById("messageInput");
    const message = input.value.trim();

    if (!message) {
        return;
    }

    addChatMessage(message, true);
    input.value = "";
    input.disabled = true;
    document.getElementById("sendMessageBtn").disabled = true;

    const formData = new FormData();
    formData.append("topic", debateTopic);
    formData.append("aiStance", aiStance);
    formData.append("history", JSON.stringify(conversationHistory));
    formData.append("message", message);

    axios.post(BASE_URL + "/server/ai/getAiResponse.php", formData)
        .then(function (response) {
            const data = response.data;

            if (data.success) {
                addChatMessage(data.reply, false);

                conversationHistory.push({
                    type: "user_input",
                    content: [{ type: "text", text: message }]
                });
                conversationHistory.push({
                    type: "model_output",
                    content: [{ type: "text", text: data.reply }]
                });

                saveActiveDebate();
            }
            else {
                showNotification(data.message);
            }

            input.disabled = false;
            document.getElementById("sendMessageBtn").disabled = false;
        })
        .catch(function (error) {
            showNotification("Something went wrong. Try again.");
            input.disabled = false;
            document.getElementById("sendMessageBtn").disabled = false;
        });
});

let forfeitArmed = false;
let forfeitArmTimer = null;

document.getElementById("forfeitBtn").addEventListener("click", function () {
    if (debateExpired) {
        return;
    }

    if (!forfeitArmed) {
        forfeitArmed = true;
        this.textContent = "CLICK AGAIN TO CONFIRM";
        showNotification("Click FORFEIT again to confirm. This will count as a loss.");

        forfeitArmTimer = setTimeout(function () {
            forfeitArmed = false;
            document.getElementById("forfeitBtn").textContent = "FORFEIT DEBATE";
        }, 4000);

        return;
    }

    clearTimeout(forfeitArmTimer);
    forfeitDebate();
});

function forfeitDebate() {
    const user = loadStoredData("user");

    if (!user) {
        return;
    }

    clearInterval(countdownTimer);
    debateExpired = true;

    document.getElementById("messageInput").disabled = true;
    document.getElementById("sendMessageBtn").disabled = true;
    document.getElementById("forfeitBtn").disabled = true;

    const formData = new FormData();
    formData.append("token", user.token);
    formData.append("topic", debateTopic);
    formData.append("userStance", userStance);

    axios.post(BASE_URL + "/server/ai/forfeitAiDebate.php", formData)
        .then(function (response) {
            const data = response.data;

            if (data.success) {
                showVerdict(data);
            }
            else {
                showNotification(data.message);
            }
        })
        .catch(function (error) {
            showNotification("Something went wrong forfeiting the debate.");
        });
}

function judgeDebate() {
    const user = loadStoredData("user");

    if (!user || conversationHistory.length === 0) {
        return;
    }

    const formData = new FormData();
    formData.append("token", user.token);
    formData.append("topic", debateTopic);
    formData.append("userStance", userStance);
    formData.append("history", JSON.stringify(conversationHistory));

    axios.post(BASE_URL + "/server/ai/judgeAiDebate.php", formData)
        .then(function (response) {
            const data = response.data;

            if (data.success) {
                showVerdict(data);
            }
            else {
                showNotification(data.message);
            }
        })
        .catch(function (error) {
            showNotification("Something went wrong judging the debate.");
        });
}

function showVerdict(data) {
    clearActiveDebate();

    document.getElementById("chatSection").classList.add("hidden");
    document.getElementById("verdictScreen").classList.remove("hidden");

    const verdictText = document.getElementById("verdictText");
    verdictText.textContent = data.verdict;
    verdictText.className = "verdict-" + data.verdict.toLowerCase();

    document.getElementById("verdictScoreText").textContent = "OVERALL SCORE: " + data.score;

    setBar("verdictLogic", data.logic_score);
    setBar("verdictRebuttal", data.rebuttal_score);
    setBar("verdictEvidence", data.evidence_score);
    setBar("verdictPersuasion", data.persuasion_score);
}

function setBar(name, value) {
    document.getElementById(name + "Bar").style.width = value + "%";
    document.getElementById(name + "Value").textContent = value;
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