const dailyTopic = document.getElementById("daily_case").textContent.trim();

const forBtn = document.getElementById("forBtn");
const againstBtn = document.getElementById("againstBtn");
const debateBtn = document.getElementById("debateBtn");

let selectedStance = null;

function selectStance(stance, clickedBtn) {
    forBtn.classList.remove("selected");
    againstBtn.classList.remove("selected");

    if (selectedStance === stance) {
        selectedStance = null;
    }
    else {
        clickedBtn.classList.add("selected");
        selectedStance = stance;
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
        return;
    }

    debateBtn.href = "/client/pages/debate.html?topic=" + encodeURIComponent(dailyTopic) + "&stance=" + selectedStance;
});
