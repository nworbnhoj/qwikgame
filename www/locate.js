docReady(event => {
    initPage();
    addListeners(document.querySelectorAll('input.guess'), 'keydown', keydownGuess);
    document.getElementById('venue-country').addEventListener('keydown', keydownCountry, false);
});


winReady(event => {
});


function initPage(){
}
