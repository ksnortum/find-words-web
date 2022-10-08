// Validate all form input, then compile the data into an object and send to the server.
// Also, control when a field is disabled or not. 

const ONLY_LETTERS_REGEX = /^[a-zA-Z]*$/;

function findWords() {
    const form = document.getElementById('form');
    if (validate(form)) {
        const data = {};
        data.typeOfGame = getTypeOfGame();
        data.letters = form["letters"].value.trim();
        data.contains = form["contains"].value.trim();
        data.startsWith = form["starts_with"].value.trim();
        data.endsWith = form["ends_with"].value.trim();
        data.numberOfLetters = form["number_of_letters"].value;
        data.dict = getDictionary();
        // console.log(data);  // TODO testing

        // Send data to server
        sendData(data);
    }
}

function typeOfGameProcess() {
    const typeOfGame = getTypeOfGame();
    const numOfLetters = document.getElementById("number_of_letters");
    const numOfLettersClear = document.getElementById("number_of_letters_clear");
    const letters = document.getElementById("letters");
    const lettersLabel = document.getElementById("letters_label");
    const lettersClear = document.getElementById("letters_clear");

    clearAll();

    switch(typeOfGame) {
        case "scrabble":
            numOfLetters.value = "";
            numOfLetters.disabled = true;
            numOfLettersClear.disabled = true;
            letters.disabled = false;
            lettersClear.disabled = false;
            lettersLabel.innerHTML = "Available Letters:";
            break;
        case "crossword":
            numOfLetters.value = "";
            numOfLetters.disabled = false;
            numOfLettersClear.disabled = false;
            letters.value = "";
            letters.disabled = true;
            lettersClear.disabled = true;
            // lettersLabel.innerHTML = "";
            break;
        case "wordle":
            numOfLetters.value = "5";
            numOfLetters.disabled = false;
            numOfLettersClear.disabled = false;
            letters.disabled = false;
            lettersClear.disabled = false;
            lettersLabel.innerHTML = "Can't Have Letters:";
            break;
        default:
            console.log("Programming error: could not find type of game");
    }

    return typeOfGame;
}

function validate(form) {
    if (getTypeOfGame() === "none") {
        return false;
    }

    let didWePass = true;

    if (getDictionary() === "none") {
        didWePass = false;
    }

    if (!validateLetters(form)) {
        didWePass = false;
    }

    if (!validateStartsWith(form)) {
        didWePass = false;
    }

    if (!validateEndsWith(form)) {
        didWePass = false;
    }

    return didWePass;
}

function validateEndsWith(form) {
    const endsWith = form["ends_with"].value.trim();
    const contains = form["contains"].value.trim();
    const errorSpan = document.getElementById("ends_with_error");
    let didWePass = true;

    if (!endsWith.match(ONLY_LETTERS_REGEX)) {
        errorSpan.innerHTML = '"Ends With" must be only letters';
        didWePass = false;
    }

    if (contains.endsWith("$") && endsWith !== "") {
        errorSpan.innerHTML = 'Can\'t have "$" anchor in "Contains" and letters in "Ends With"';
        didWePass = false;
    }

    if (didWePass) {
        errorSpan.innerHTML = "";
    }

    return didWePass;
}

function validateStartsWith(form) {
    const startsWith = form["starts_with"].value.trim();
    const contains = form["contains"].value.trim();
    const errorSpan = document.getElementById("starts_with_error");
    let didWePass = true;

    if (!startsWith.match(ONLY_LETTERS_REGEX)) {
        errorSpan.innerHTML = '"Starts With" must be only letters';
        didWePass = false;
    }

    if (contains.startsWith("^") && startsWith !== "") {
        errorSpan.innerHTML = 'Can\'t have "^" anchor in "Contains" and letters in "Starts With"';
        didWePass = false;
    }

    if (didWePass) {
        errorSpan.innerHTML = "";
    }

    return didWePass;
}

function validateLetters(form) {
    const letters = form["letters"].value.trim();
    const typeOfGame = getTypeOfGame();
    const errorSpan = document.getElementById("letters_error");
    let didWePass = true;

    if (typeOfGame !== "crossword") {
        if (letters.length < 1) {
            errorSpan.innerHTML = "You must have at least one available letter";
            didWePass = false;
        } else if (letters.length > 20) {
            errorSpan.innerHTML = "You cannot have over 20 letters";
            didWePass = false;
        }
    }

    const lettersOrDots = /^[a-z.]*$/;
    if (!letters.match(lettersOrDots)) {
        errorSpan.innerHTML = 'Letters can only be "a" thru "z" and dots';
        didWePass = false;
    }

    const dots = letters.match(/\./g);
    if (dots && dots.length > 2) {
        errorSpan.innerHTML = "Letters can have no more than two dots";
        didWePass = false;
    }

    if (didWePass) {
        errorSpan.innerHTML = "";
    }

    return didWePass;
}

function getDictionary() {
    const dicts = document.querySelectorAll('option[name="dicts"]');
    let selectedDict = "none";

    for (const dict of dicts) {
        if (dict.selected) {
            selectedDict = dict.value.trim();
            break;
        }
    }

    return selectedDict;
}

function getTypeOfGame() {
    const typeOfGames = document.querySelectorAll('input[name="type_of_game"]');
    let selectedType = "none";

    for (const typeOfGame of typeOfGames) {
        if (typeOfGame.checked) {
            selectedType = typeOfGame.value;
            break;
        }
    }

    return selectedType;
}

function sendData(data) {
    const wordsDiv = document.getElementById("words");
    wordsDiv.hidden = false;
    wordsDiv.innerHTML = "Just a moment...";
    
    const xhr = new XMLHttpRequest();

    // configure a POST request
    xhr.open('POST', "php/find-words.php");

    // set Content-Type header
    xhr.setRequestHeader('Content-Type', 'application/json');

    // pass params to send() method
    xhr.send(JSON.stringify(data));

    xhr.onerror = () => {
        console.error('Request failed.');
    }

    // listen for load event
    xhr.onload = () => {
        if (xhr.readyState === xhr.DONE && xhr.status === 200) {
            wordsDiv.innerHTML = wordsToTable(xhr.responseText);
        }
    }
}

// in: JSON encoded array of custom words
// out: HTML table with words inside
function wordsToTable(text) {
    if (text == null || text.trim() === "") {
        return "No words were found that fit these conditions";
    }

    const typeOfGame = getTypeOfGame();
    const dictionary = getDictionary();
    const words = JSON.parse(text);
    let html = "<table><tr><th>Word</th>";

    if (typeOfGame === "scrabble") {
        html += "<th>Value</th>";
    }

    // if (words[0].definition != "") {
    if (dictionary.endsWith("define")) {
        html += "<th>Definition</th>";
    }

    html += "</tr>";
    
    for (const word of words) {
        html += "<tr><td>" + word.word + "</td>"; 

        if (typeOfGame === "scrabble") {
            html += "<td>" + word.value + "</td>";
        }

        // if (word.definition != "") {
        if (dictionary.endsWith("define")) {
            html += "<td>" + word.definition + "</td>";
        }

        html += "</tr>";
    }

    html += "</table>";

    return html;
}

function clearLetters() {
    document.getElementById('letters').value = '';
    document.getElementById('letters_error').innerHTML = '';
}

function clearContains() {
    document.getElementById('contains').value = '';
    document.getElementById('contains_error').innerHTML = '';
}

function clearStartsWith() {
    document.getElementById('starts_with').value = '';
    document.getElementById('starts_with_error').innerHTML = '';
}

function clearEndsWith() {
    document.getElementById('ends_with').value = '';
    document.getElementById('ends_with_error').innerHTML = '';
}

function clearNumberOfLetters() {
    document.getElementById('number_of_letters').value = '';
    document.getElementById('number_of_letters_error').innerHTML = '';
}

function clearAll() {
    clearLetters();
    clearContains();
    clearStartsWith();
    clearEndsWith();
    clearNumberOfLetters();
    document.getElementById('words').hidden = true;
}
