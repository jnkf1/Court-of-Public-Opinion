let dailyTopic = "";

const forBtn = document.getElementById("forBtn");
const againstBtn = document.getElementById("againstBtn");
const debateBtn = document.getElementById("debateBtn");

let selectedStance = null;

axios.post(BASE_URL + "/server/topics/getDailyTopic.php")
    .then(function (response) {
        const data = response.data;

        if (!data.success) {
            showNotification("Couldn't load today's case.");
            return;
        }

        dailyTopic = data.topic.topic;

        document.getElementById("caseNumber").textContent = "CASE NO. " + String(data.topic.id).padStart(5, "0");
        document.getElementById("caseCategory").textContent = data.topic.categories;
        document.getElementById("caseDescription").textContent = data.topic.description;
        document.getElementById("daily_case").textContent = dailyTopic;
    })
    .catch(function (error) {
        showNotification("Couldn't load today's case.");
    });

function selectStance(stance, clickedBtn) {
    forBtn.classList.remove("selected");
    againstBtn.classList.remove("selected");

    if (selectedStance === stance) {
        selectedStance = null;
        localStorage.removeItem("debateCase");
    }
    else {
        clickedBtn.classList.add("selected");
        selectedStance = stance;
        saveStoredData("debateCase", { topic: dailyTopic, stance: selectedStance });
    }
}

forBtn.addEventListener("click", function () {
    selectStance("FOR", forBtn);
});

againstBtn.addEventListener("click", function () {
    selectStance("AGAINST", againstBtn);
});

debateBtn.addEventListener("click", function (e) {
    if (!selectedStance) {
        e.preventDefault();
        showNotification("Please pick a side first.");
    }
});
