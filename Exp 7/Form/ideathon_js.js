let registrationData = [];

function processRegistration() {
    const email = document.getElementById('email').value;
    const phone = document.getElementById('phone').value;
    const idea = document.getElementById('idea').value;
    
    const yearInput = document.querySelector('input[name="year"]:checked');
    const year = yearInput ? yearInput.value : "N/A";

    const nameInputs = document.querySelectorAll('.memberName');
    let namesArray = [];
    nameInputs.forEach(input => {
        if (input.value.trim() !== "") namesArray.push(input.value.trim());
    });

    const domainBoxes = document.querySelectorAll('input[name="domain"]:checked');
    let domainsArray = [];
    domainBoxes.forEach(box => domainsArray.push(box.value));

    const ppt = document.getElementById('pptFile').files[0];
    const pptName = ppt ? ppt.name : "No file uploaded";

    if (namesArray.length === 0 || !email) {
        alert("Please fill in at least the leader's name and email!");
        return;
    }

    const newEntry = {
        names: namesArray.join(', '),
        email: email,
        phone: phone,
        year: year,
        domains: domainsArray.join(', ') || 'None',
        idea: idea,
        fileName: pptName
    };

    registrationData.push(newEntry);

    showRecent(newEntry);
    renderTable();

    alert("Registration Complete!");
    
    document.getElementById('regForm').reset();
    document.getElementById('file-name-display').innerText = "No file chosen";
    
    if (typeof adjustFields === "function") {
        adjustFields(1);
    }
}

function showRecent(entry) {
    const output = document.getElementById('displayArea');
    output.style.display = "block";
    output.innerHTML = `
        <h3>Submission Recorded</h3>
        <div class="data-row"><strong>Team:</strong> ${entry.names}</div>
        <div class="data-row"><strong>Contact:</strong> ${entry.email} | ${entry.phone}</div>
        <div class="data-row"><strong>Domains:</strong> ${entry.domains}</div>
        <div class="data-row"><strong>Brief:</strong> ${entry.idea}</div>
        <div class="data-row"><strong>File:</strong> ${entry.fileName}</div>
    `;
    output.scrollIntoView({ behavior: 'smooth' });
}

function renderTable() {
    const tableBody = document.getElementById('tableBody');
    tableBody.innerHTML = ""; 

    registrationData.forEach(entry => {
        const row = `<tr>
            <td>${entry.names}</td>
            <td>${entry.email}</td>
            <td>${entry.year}</td>
            <td>${entry.domains}</td>
            <td>${entry.idea}</td>
        </tr>`;
        tableBody.innerHTML += row;
    });
}

function copyTable() {
    if (registrationData.length === 0) return alert("No registrations found!");
    let text = "Members\tEmail\tYear\tDomains\tIdea\n";
    registrationData.forEach(e => {
        text += `${e.names}\t${e.email}\t${e.year}\t${e.domains}\t${e.idea}\n`;
    });
    navigator.clipboard.writeText(text).then(() => alert("Table copied to clipboard!"));
}

function downloadCSV() {
    if (registrationData.length === 0) return alert("No registrations found!");
    let csvContent = "data:text/csv;charset=utf-8,Members,Email,Year,Domains,Idea\n";
    registrationData.forEach(e => {
        csvContent += `"${e.names}","${e.email}","${e.year}","${e.domains}","${e.idea}"\n`;
    });
    const link = document.createElement("a");
    link.setAttribute("href", encodeURI(csvContent));
    link.setAttribute("download", "ideathon_registrations_2026.csv");
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}