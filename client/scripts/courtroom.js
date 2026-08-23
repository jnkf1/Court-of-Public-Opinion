loadRooms();
checkActiveRoom();

function checkActiveRoom() {
    const user = loadStoredData("user");

    if (!user) {
        return;
    }

    const formData = new FormData();
    formData.append("token", user.token);

    axios.post(BASE_URL + "/server/rooms/getMyActiveRoom.php", formData)
        .then(function (response) {
            const data = response.data;

            if (data.success && data.room_id) {
                document.getElementById("returnToRoomLink").href = "/client/pages/room.html?room_id=" + data.room_id;
                document.getElementById("activeRoomBanner").classList.remove("hidden");
            }
        })
        .catch(function (error) {
            // not critical, fail silently
        });
}

document.getElementById("createRoomBtn").addEventListener("click", function () {
    const user = loadStoredData("user");

    if (!user) {
        showNotification("Please sign up or log in before creating a room.");

        setTimeout(function () {
            window.location.href = "/client/pages/profile.html";
        }, 1500);

        return;
    }

    const topic = document.getElementById("roomTopic").value;
    const stance = selectedStance;

    if (!topic || !stance) {
        showNotification("Please enter a topic and pick a side.");
        return;
    }

    const formData = new FormData();
    formData.append("token", user.token);
    formData.append("topic", topic);
    formData.append("stance", stance);

    axios.post(BASE_URL + "/server/rooms/createRoom.php", formData)
        .then(function (response) {
            const data = response.data;
            showNotification(data.message);

            if (data.success) {
                document.getElementById("roomTopic").value = "";

                for (let i = 0; i < stanceButtons.length; i++) {
                    stanceButtons[i].classList.remove("selected");
                }
                selectedStance = null;

                loadRooms();
            }
        })
        .catch(function (error) {
            showNotification("Something went wrong. Try again.");
        });
});

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

function loadRooms() {
    axios.post(BASE_URL + "/server/rooms/listRooms.php")
        .then(function (response) {
            const data = response.data;

            if (data.success) {
                renderRooms(data.rooms);
            }
        })
        .catch(function (error) {
            showNotification("Couldn't load open rooms.");
        });
}

function renderRooms(rooms) {
    const container = document.getElementById("openRoomsList");
    container.innerHTML = "";

    if (rooms.length === 0) {
        container.innerHTML = "<p class='no-rooms'>No open rooms right now. Be the first to create one.</p>";
        return;
    }

    const user = loadStoredData("user");

    for (let i = 0; i < rooms.length; i++) {
        const room = rooms[i];

        const card = document.createElement("div");
        card.className = "room-card";

        const isOwnRoom = user && room.host_id == user.id;
        const actionButton = isOwnRoom
            ? "<button type='button' class='room-cancel-btn' data-room-id='" + room.id + "'>CANCEL</button>"
            : "<button type='button' class='room-join-btn' data-room-id='" + room.id + "'>JOIN</button>";

        card.innerHTML =
            "<div>" +
            "<p class='room-topic'>" + room.topic + "</p>" +
            "<p class='room-host'>HOSTED BY " + room.host_username.toUpperCase() + "</p>" +
            "</div>" +
            "<p class='room-stance'>" + room.host_stance + "</p>" +
            actionButton;

        container.appendChild(card);
    }

    const joinButtons = document.querySelectorAll(".room-join-btn");

    for (let i = 0; i < joinButtons.length; i++) {
        joinButtons[i].addEventListener("click", function () {
            joinRoom(this.getAttribute("data-room-id"));
        });
    }

    const cancelButtons = document.querySelectorAll(".room-cancel-btn");

    for (let i = 0; i < cancelButtons.length; i++) {
        cancelButtons[i].addEventListener("click", function () {
            cancelRoom(this.getAttribute("data-room-id"));
        });
    }
}

function joinRoom(roomId) {
    const user = loadStoredData("user");

    if (!user) {
        showNotification("Please log in to join a room.");
        return;
    }

    const formData = new FormData();
    formData.append("token", user.token);
    formData.append("room_id", roomId);

    axios.post(BASE_URL + "/server/rooms/joinRoom.php", formData)
        .then(function (response) {
            const data = response.data;

            if (data.success) {
                window.location.href = "/client/pages/room.html?room_id=" + roomId;
            }
            else {
                showNotification(data.message);
            }
        })
        .catch(function (error) {
            showNotification("Something went wrong. Try again.");
        });
}

function cancelRoom(roomId) {
    const user = loadStoredData("user");

    if (!user) {
        showNotification("Please log in to cancel a room.");
        return;
    }

    if (!confirm("Cancel this room?")) {
        return;
    }

    const formData = new FormData();
    formData.append("token", user.token);
    formData.append("room_id", roomId);

    axios.post(BASE_URL + "/server/rooms/cancelRoom.php", formData)
        .then(function (response) {
            const data = response.data;
            showNotification(data.message);

            if (data.success) {
                loadRooms();
            }
        })
        .catch(function (error) {
            showNotification("Something went wrong. Try again.");
        });
}
