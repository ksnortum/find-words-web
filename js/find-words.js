// Validate all form input, then compile the data into an object and send to the server.
// Also, control when a field is disabled or not. 

const ONLY_LETTERS_REGEX = /^[a-zA-Z]*$/;

/**
 * Validate form data, and if valid, send a JavaScript object to server.
 */
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

        // Send data to server
        sendData(data);
    }
}

/**
 * Takes care of all GUI changes that happen when the type of game changes.
 * 
 * @returns {string} type of game
 */
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

/**
 * Validate all input data.
 * 
 * @param {HTMLElement} form the HTML form
 * @returns {boolean} did we pass validation?
 */
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

    if (!validateContains(form)) {
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

/**
 * Validate the contains value
 * @param {HTMLElement} form the HTML form
 * @returns {bool} did we pass?
 */
function validateContains(form) {
    const contains = form["contains"].value.trim();
    const errorSpan = document.getElementById("contains_error");
    let didWePass = true;

    if (!isValidRegex(contains)) {
        errorSpan.innerHTML = '"Contains" is not a valid regex'
        didWePass = false;
    }

    if (didWePass) {
        errorSpan.innerHTML = "";
    }

    return didWePass;
}

/**
 * Validate the endsWith value
 * TODO: is this logic good?  Use if/else?
 * 
 * @param {HTMLElement} form the HTML form
 * @returns {bool} did we pass?
 */
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

/**
 * Validate the startsWith input
 * TODO: is this logic good?  Use if/else?
 * 
 * @param {HTMLElement} form the HTML form
 * @returns {bool} did we pass?
 */
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

/**
 * Validate the "available" / "can't have" letters.
 * 
 * @param {HTMLElement} form the HTML form
 * @returns {bool} did we pass?
 */
function validateLetters(form) {
    const letters = form["letters"].value.trim();
    const typeOfGame = getTypeOfGame();
    const errorSpan = document.getElementById("letters_error");
    let didWePass = true;

    if (typeOfGame === "scrabble") {
        if (letters.length < 1) {
            errorSpan.innerHTML = "You must have at least one available letter";
            didWePass = false;
        } else if (letters.length > 26) {
            errorSpan.innerHTML = "You cannot have over 26 letters";
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

/**
 * @returns {string} the selected dictionary name, or "none".
 */
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

/**
 * @returns {string} the type of game, or "none".
 */
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

/**
 * Send input data to the server as a JSON string.  Get's back an array of CustomWords.
 * 
 * @param {object} data all the input data
 */
function sendData(data) {
    const wordsDiv = document.getElementById("words");
    wordsDiv.hidden = false;
    wordsDiv.innerHTML = "Just a moment...";
    
    const xhr = new XMLHttpRequest();
    xhr.open('POST', "php/find-words.php");
    xhr.setRequestHeader('Content-Type', 'application/json');
    xhr.send(JSON.stringify(data));

    xhr.onerror = () => {
        alert('Request failed.');
    }

    xhr.onload = () => {
        if (xhr.readyState === xhr.DONE && xhr.status === 200) {
            wordsDiv.innerHTML = wordsToTable(xhr.responseText);
        }
    }
}

/**
 * Format an array of CustomWords into a table with varying columns.
 * 
 * @param {array} text JSON encoded array of custom words
 * @returns {string} HTML Table with words and possible values and/or descriptions
 */
function wordsToTable(text) {
    if (text == null || text.trim() === "" || text.trim() === "[]") {
        return "No words were found that fit these conditions";
    }

    const typeOfGame = getTypeOfGame();
    const dictionary = getDictionary();
    const words = JSON.parse(text);
    let html = "<table border=\"1\"><tr><th>Word</th>";

    if (typeOfGame === "scrabble") {
        html += "<th>Value</th>";
    }

    if (dictionary.endsWith("define")) {
        html += "<th>Definition</th>";
    }

    html += "</tr>";
    
    for (const word of words) {
        html += "<tr><td>" + word.word + "</td>"; 

        if (typeOfGame === "scrabble") {
            html += "<td>" + word.value + "</td>";
        }

        if (dictionary.endsWith("define")) {
            html += "<td>" + word.definition + "</td>";
        }

        html += "</tr>";
    }

    html += "</table>";

    return html;
}

function onlyClearLetters() {
    document.getElementById('letters').value = '';
    document.getElementById('letters_error').innerHTML = '';
}

function onlyClearContains() {
    document.getElementById('contains').value = '';
    document.getElementById('contains_error').innerHTML = '';
}

function onlyClearStartsWith() {
    document.getElementById('starts_with').value = '';
    document.getElementById('starts_with_error').innerHTML = '';
}

function onlyClearEndsWith() {
    document.getElementById('ends_with').value = '';
    document.getElementById('ends_with_error').innerHTML = '';
}

function onlyClearNumberOfLetters() {
    document.getElementById('number_of_letters').value = '';
    document.getElementById('number_of_letters_error').innerHTML = '';
}

function clearLetters() {
    onlyClearLetters();
    document.getElementById('letters').focus();
}

function clearContains() {
    onlyClearContains();
    document.getElementById('contains').focus();
}

function clearStartsWith() {
    onlyClearStartsWith();
    document.getElementById('starts_with').focus();
}

function clearEndsWith() {
    onlyClearEndsWith();
    document.getElementById('ends_with').focus();
}

function clearNumberOfLetters() {
    onlyClearNumberOfLetters();
    document.getElementById('number_of_letters').focus();
}

function clearAll() {
    const typeOfGame = getTypeOfGame();

    onlyClearLetters();
    onlyClearContains();
    onlyClearStartsWith();
    onlyClearEndsWith();
    if (typeOfGame !== "wordle") {
        onlyClearNumberOfLetters();
    }
    document.getElementById('words').hidden = true;
}

function isValidRegex(regexString) {
    try {
        new RegExp(regexString);
        return true;
    } catch (e) {
        return false;
    }
}