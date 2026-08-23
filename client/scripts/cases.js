loadCases();

function loadCases() {
    axios.post(BASE_URL + "/server/cases/listCases.php")
        .then(function (response) {
            const data = response.data;

            if (data.success) {
                renderCases(data.cases);
            }
        })
        .catch(function (error) {
            showNotification("Couldn't load cases.");
        });
}

function renderCases(cases) {
    const container = document.getElementById("casesList");
    container.innerHTML = "";

    if (cases.length === 0) {
        container.innerHTML = "<p class='no-cases'>No cases have been decided yet.</p>";
        return;
    }

    for (let i = 0; i < cases.length; i++) {
        const c = cases[i];

        const record = document.createElement("div");
        record.className = "case-record";

        const verdictClass = "verdict-" + c.verdict.toLowerCase();

        record.innerHTML =
            "<div class='case-record-top'>" +
            "<span class='case-record-number'>CASE #" + String(c.id).padStart(5, "0") + "</span>" +
            "<span class='case-record-verdict " + verdictClass + "'>" + c.verdict + "</span>" +
            "</div>" +
            "<p class='case-record-topic'>" + c.topic + "</p>" +
            "<p class='case-record-date'>" + c.case_date + "</p>";

        container.appendChild(record);
    }
}
