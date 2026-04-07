let registrationData = [];

// Initialize history on load
document.addEventListener('DOMContentLoaded', loadRegistrations);

async function loadRegistrations() {
    try {
        const response = await fetch('index.php?api=1&action=fetch');
        const data = await response.json();
        
        if (data.status === 'error') {
            console.error(data.message);
            return;
        }

        registrationData = data.map(item => ({
            names: item.member_names,
            email: item.email,
            phone: item.phone,
            year: item.year_of_study,
            domains: item.domains,
            idea: item.project_brief
        }));

        renderTable();
    } catch (error) {
        console.error('Error loading registrations:', error);
    }
}

async function processRegistration() {
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

    const teamSizeInput = document.querySelector('input[name="teamSize"]:checked');
    const teamSize = teamSizeInput ? teamSizeInput.value : 1;

    if (namesArray.length === 0 || !email) {
        alert("Please fill in at least the leader's name and email!");
        return;
    }

    const payload = {
        teamSize: teamSize,
        memberNames: namesArray.join(', '),
        email: email,
        phone: phone,
        year: year,
        domains: domainsArray.join(', ') || 'None',
        idea: idea
    };

    try {
        const response = await fetch('index.php?api=1&action=save', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });
        const result = await response.json();

        if (result.status === 'success') {
            alert("Registration Complete!");
            showRecent(payload);
            loadRegistrations();
            document.getElementById('regForm').reset();
            document.getElementById('file-name-display').innerText = "No file chosen";
            if (typeof adjustFields === "function") adjustFields(1);
        } else {
            alert("Error: " + result.message);
        }
    } catch (error) {
        console.error('Error saving registration:', error);
        alert("Server error. Check your database connection.");
    }
}

function showRecent(entry) {
    const output = document.getElementById('displayArea');
    output.style.display = "block";
    output.innerHTML = `
        <h3>Submission Recorded</h3>
        <div class="data-row"><strong>Team:</strong> ${entry.memberNames}</div>
        <div class="data-row"><strong>Contact:</strong> ${entry.email} | ${entry.phone}</div>
        <div class="data-row"><strong>Domains:</strong> ${entry.domains}</div>
        <div class="data-row"><strong>Brief:</strong> ${entry.idea}</div>
    `;
    output.scrollIntoView({ behavior: 'smooth' });
}

function renderTable() {
    const tableBody = document.getElementById('tableBody');
    tableBody.innerHTML = ""; 

    if (registrationData.length === 0) {
        tableBody.innerHTML = "<tr><td colspan='5' style='text-align:center;'>No registrations found</td></tr>";
        return;
    }

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

function updateFileName() {
    const input = document.getElementById('pptFile');
    const display = document.getElementById('file-name-display');
    display.innerText = input.files[0] ? `Selected: ${input.files[0].name}` : "No file chosen";
}

function adjustFields(size) {
    const container = document.getElementById('memberFields');
    container.innerHTML = ""; 
    for (let i = 1; i <= size; i++) {
        container.innerHTML += `
            <label>Member ${i} Name:</label>
            <input type="text" class="memberName" placeholder="Full Name">
        `;
    }
}
