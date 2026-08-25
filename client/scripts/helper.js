function loadStoredData(key, defaultValue = null) {
  let saved = localStorage.getItem(key);
  if (saved === null) {
    return defaultValue;
  }
  return JSON.parse(saved);
}

function saveStoredData(key, value) {
  let newValue = JSON.stringify(value);
  localStorage.setItem(key, newValue);
}

function showNotification(message) {
  const notif = document.getElementById("notification");
  notif.textContent = message;
  notif.classList.remove("hidden");

  setTimeout(function () {
    notif.classList.add("hidden");
  }, 3000);
}

// Notifies a room host wherever they're browsing the moment someone joins their
// still-open room. Skipped on room.html/courtroom.html, which already poll this
// themselves (more specifically, with their own UI to update) - this just covers
// every other page so the host finds out no matter where they wandered off to.
if (!window.location.pathname.includes("room.html") && !window.location.pathname.includes("courtroom.html")) {
  let previousActiveRoomStatus = null;

  function pollForRoomJoin() {
    const user = loadStoredData("user");

    if (!user) {
      return;
    }

    const formData = new FormData();
    formData.append("token", user.token);

    axios.post(BASE_URL + "/server/rooms/getMyActiveRoom.php", formData)
      .then(function (response) {
        const data = response.data;

        if (data.success && previousActiveRoomStatus === "open" && data.room_status === "in_progress") {
          showNotification("Someone joined your room! The debate has started.");
        }

        previousActiveRoomStatus = data.success ? data.room_status : null;
      })
      .catch(function (error) {
        // not critical, fail silently
      });
  }

  pollForRoomJoin();
  setInterval(pollForRoomJoin, 3000);
}