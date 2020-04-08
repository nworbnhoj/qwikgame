docReady(event => {
    initPage();
});


winReady(event => {
});


function addMoreListeners(){
    for (var elem of document.querySelectorAll('input.guess')) {
        elem.addEventListener('keydown', keydownGuess, false);
    }

    addEvent(document.getElementById('venue-country'), 'keydown', keydownCountry);
}


function initPage(){
}
