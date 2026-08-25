const urlParams = new URLSearchParams(window.location.search);
const roomId = urlParams.get("room_id");

const user = loadStoredData("user");

if (!user) {
    showNotification("Please log in to view this room.");

    setTimeout(function () {
        window.location.href = "/client/pages/profile.html";
    }, 1500);
}

if (!roomId) {
    window.location.href = "/client/pages/courtroom.html";
}

let pollTimer = null;
let countdownTimer = null;
let expired = false;
let previousRoomStatus = null;

if (user && roomId) {
    loadRoomState();
    pollTimer = setInterval(loadRoomState, 3000);
}

function loadRoomState() {
    const formData = new FormData();
    formData.append("token", user.token);
    formData.append("room_id", roomId);

    axios.post(BASE_URL + "/server/rooms/getRoomState.php", formData)
        .then(function (response) {
            const data = response.data;

            if (!data.success) {
                showNotification(data.message);
                clearInterval(pollTimer);

                setTimeout(function () {
                    window.location.href = "/client/pages/courtroom.html";
                }, 1500);

                return;
            }

            renderRoom(data.room, data.myVerdict);
            renderMessages(data.messages);
        })
        .catch(function (error) {
            showNotification("Something went wrong loading the debate.");
        });
}

function renderRoom(room, myVerdict) {
    document.getElementById("roomTopicLabel").textContent = room.topic;

    const isHost = room.host_id == user.id;
    const yourStance = isHost ? room.host_stance : room.joiner_stance;
    const opponentStance = isHost ? room.joiner_stance : room.host_stance;

    document.getElementById("roomStanceLabel").textContent = "YOU: " + yourStance;
    document.getElementById("roomOpponentLabel").textContent = "OPPONENT: " + opponentStance;

    if (isHost && previousRoomStatus === "open" && room.status === "in_progress") {
        showNotification("Someone joined your room! The debate has started.");
    }

    previousRoomStatus = room.status;

    if (room.status === "open") {
        document.getElementById("roomTimer").textContent = "WAITING FOR OPPONENT...";
        document.getElementById("messageInput").disabled = true;
        document.getElementById("sendMessageBtn").disabled = true;
        document.getElementById("forfeitBtn").disabled = true;
        return;
    }

    if (room.status === "closed") {
        expired = true;
        document.getElementById("messageInput").disabled = true;
        document.getElementById("sendMessageBtn").disabled = true;
        document.getElementById("roomTimer").textContent = "TIME'S UP";
        clearInterval(countdownTimer);
        clearInterval(pollTimer);

        if (myVerdict) {
            showVerdict(myVerdict);
        }

        return;
    }

    document.getElementById("messageInput").disabled = false;
    document.getElementById("sendMessageBtn").disabled = false;
    document.getElementById("forfeitBtn").disabled = false;

    if (room.started_at && !countdownTimer) {
        startCountdown(room.started_at);
    }
}

function startCountdown(startedAt) {
    const startTime = new Date(startedAt.replace(" ", "T"));
    const endTime = new Date(startTime.getTime() + 1 * 60 * 1000);

    countdownTimer = setInterval(function () {
        const remainingMs = endTime - new Date();

        if (remainingMs <= 0 || expired) {
            document.getElementById("roomTimer").textContent = "TIME'S UP";
            clearInterval(countdownTimer);
            return;
        }

        const minutes = Math.floor(remainingMs / 60000);
        const seconds = Math.floor((remainingMs % 60000) / 1000);

        document.getElementById("roomTimer").textContent =
            String(minutes).padStart(2, "0") + ":" + String(seconds).padStart(2, "0");
    }, 1000);
}

function renderMessages(messages) {
    const log = document.getElementById("chatLog");
    log.innerHTML = "";

    for (let i = 0; i < messages.length; i++) {
        const m = messages[i];
        const isYou = m.user_id == user.id;

        const bubble = document.createElement("div");
        bubble.className = isYou ? "chat-message you" : "chat-message opponent";
        bubble.textContent = m.message;

        log.appendChild(bubble);
    }

    log.scrollTop = log.scrollHeight;
}

document.getElementById("messageForm").addEventListener("submit", function (e) {
    e.preventDefault();

    if (expired) {
        return;
    }

    const input = document.getElementById("messageInput");
    const message = input.value.trim();

    if (!message) {
        return;
    }

    const formData = new FormData();
    formData.append("token", user.token);
    formData.append("room_id", roomId);
    formData.append("message", message);

    axios.post(BASE_URL + "/server/rooms/sendMessage.php", formData)
        .then(function (response) {
            const data = response.data;

            if (data.success) {
                input.value = "";
                loadRoomState();
            }
            else {
                showNotification(data.message);
            }
        })
        .catch(function (error) {
            showNotification("Something went wrong sending your message.");
        });
});

let forfeitArmed = false;
let forfeitArmTimer = null;

document.getElementById("forfeitBtn").addEventListener("click", function () {
    if (expired) {
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
    forfeitRoom();
});

function forfeitRoom() {
    clearInterval(pollTimer);
    clearInterval(countdownTimer);
    expired = true;

    document.getElementById("messageInput").disabled = true;
    document.getElementById("sendMessageBtn").disabled = true;
    document.getElementById("forfeitBtn").disabled = true;

    const formData = new FormData();
    formData.append("token", user.token);
    formData.append("room_id", roomId);

    axios.post(BASE_URL + "/server/rooms/forfeitRoom.php", formData)
        .then(function (response) {
            const data = response.data;

            if (data.success) {
                document.getElementById("roomTimer").textContent = "TIME'S UP";
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

function showVerdict(data) {
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
