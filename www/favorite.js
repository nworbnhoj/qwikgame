docReady(event => {
    initPage();
    addListeners(document.querySelectorAll('.tr-toggle'), 'click', clickTrToggle);
    document.getElementById('hr-any').addEventListener(   'click', clickAnytime,  false);
    document.getElementById('map-icon').addEventListener( 'click' , clickMapIcon, false);
});


winReady(event => {});



function initPage(){}


function clickAnytime(){
    if (this.checked){
        document.getElementById('hr').style.display = 'none';
    } else {
        document.getElementById('hr').style.display = 'block';
    }
}



function clickTrToggle(){
    const ALL_HOURS = 16777215; // binary 11111111111111111111
    var tr = this.parentNode;
    var input = tr.firstElementChild;
    var last = tr.lastElementChild;
    if (input.getAttribute('value') != ALL_HOURS){
        var on = 1;
        var color = 'DarkOrange';
        input.setAttribute('value', ALL_HOURS); 
    } else {
        var on = 0;
        var color = 'LightGrey';
        input.setAttribute('value', 0);
    }
    for (var td of tr.children) {
        td.style.backgroundColor = color;
        td.setAttribute('on', on);        
    }
}


