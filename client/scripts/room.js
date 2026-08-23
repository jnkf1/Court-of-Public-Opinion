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

            renderRoom(data.room);
            renderMessages(data.messages);
        })
        .catch(function (error) {
            showNotification("Something went wrong loading the debate.");
        });
}

function renderRoom(room) {
    document.getElementById("roomTopicLabel").textContent = room.topic;

    const isHost = room.host_id == user.id;
    const yourStance = isHost ? room.host_stance : room.joiner_stance;
    const opponentStance = isHost ? room.joiner_stance : room.host_stance;

    document.getElementById("roomStanceLabel").textContent = "YOU: " + yourStance;
    document.getElementById("roomOpponentLabel").textContent = "OPPONENT: " + opponentStance;

    if (room.status === "open") {
        document.getElementById("roomTimer").textContent = "WAITING FOR OPPONENT...";
        document.getElementById("messageInput").disabled = true;
        document.getElementById("sendMessageBtn").disabled = true;
        return;
    }

    if (room.status === "closed") {
        expired = true;
        document.getElementById("messageInput").disabled = true;
        document.getElementById("sendMessageBtn").disabled = true;
        document.getElementById("roomTimer").textContent = "TIME'S UP";
        clearInterval(countdownTimer);
        clearInterval(pollTimer);
        return;
    }

    document.getElementById("messageInput").disabled = false;
    document.getElementById("sendMessageBtn").disabled = false;

    if (room.started_at && !countdownTimer) {
        startCountdown(room.started_at);
    }
}

function startCountdown(startedAt) {
    const startTime = new Date(startedAt.replace(" ", "T"));
    const endTime = new Date(startTime.getTime() + 15 * 60 * 1000);

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
