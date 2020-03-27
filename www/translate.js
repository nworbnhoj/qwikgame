docReady(event => {
    initPage();
});


winReady(event => {});


function initPage(){}


function addMoreListeners(){
    for (var elem of document.querySelectorAll('.phrase')) {
        elem.addEventListener('click', showEdit, false);
     }
     for (var elem of document.querySelectorAll('.pending')) {
         elem.addEventListener('click', showEdit, false);
     }
}


function showEdit(){
  toggle(nextSibling(this, '.edit-phrase'));
}





