
let countdown;
let timeLeft;

const display = document.getElementById('time-display');
const input = document.getElementById('user-input');

function updateDisplay(seconds) {
    const hrs = Math.floor(seconds / 3600);
    const mins = Math.floor((seconds % 3600) / 60);
    const secs = seconds % 60;

    let timeString = "";

    if (hrs > 0) {
        timeString = `${hrs.toString().padStart(2, '0')} : ${mins.toString().padStart(2, '0')} : ${secs.toString().padStart(2, '0')}`;
    } else {
        timeString = `${mins.toString().padStart(2, '0')} : ${secs.toString().padStart(2, '0')}`;
    }

    display.innerText = timeString;

    display.scrollLeft = (display.scrollWidth - display.clientWidth) / 2;
}

function startTimer() {
    clearInterval(countdown);

    if (timeLeft === null || timeLeft === undefined || timeLeft <= 0) {
        timeLeft = parseInt(input.value);
    }

    if (isNaN(timeLeft) || timeLeft <= 0) {
        alert("Please enter a valid duration.");
        return;
    }

    updateDisplay(timeLeft);

    countdown = setInterval(() => {
        timeLeft--;
        updateDisplay(timeLeft);

        if (timeLeft <= 0) {
            clearInterval(countdown);
            timeLeft = 0;
            updateDisplay(0);

            setTimeout(() => {
                alert(" TIMER HAS HALTED.");
                resetTimer();
            }, 100);
        }
    }, 1000);
    display.classList.add('timer-active');
}

function stopTimer() {
    clearInterval(countdown);
    display.classList.remove('timer-active');
}

function resetTimer() {
    clearInterval(countdown);
    timeLeft = null;
    display.innerText = "00 : 00";
    input.value = "";
    display.classList.remove('timer-active');
}

input.addEventListener('input', function () {
    if (this.value < 0) this.value = 0;
    if (this.value.length > 9) this.value = this.value.slice(0, 9);
    timeLeft = parseInt(this.value);
});