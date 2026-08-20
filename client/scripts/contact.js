document.getElementById("contactForm").addEventListener("submit", function (e) {
    e.preventDefault();

    const name = document.getElementById("contactName").value;
    const email = document.getElementById("contactEmail").value;
    const message = document.getElementById("contactMessage").value;

    const formData = new FormData();
    formData.append("name", name);
    formData.append("email", email);
    formData.append("message", message);

    axios.post(BASE_URL + "/server/contact/sendMessage.php", formData)
        .then(function (response) {
            const data = response.data;
            showNotification(data.message);

            if (data.success) {
                document.getElementById("contactForm").reset();
            }
        })
        .catch(function (error) {
            showNotification("Something went wrong. Try again.");
        });
});
