///////////////// Service Worker functions ///////////////////


// Registering Service Worker
if('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
        navigator.serviceWorker.register('sw.js');
        console.log("serviceWorker registered.");
    });
};


function getServiceWorker(){
    if(!'serviceWorker' in navigator){
        console.log("failed to get navigator.serviceWorker");
        return;
    }
    var worker = navigator.serviceWorker.controller;  
    if(!worker){
        console.log("failed to get navigator.serviceWorker.controller");
        return;
    }
    return worker;
}


function clearCache(key){
    var worker = getServiceWorker();
    if (worker){
        worker.postMessage({'command': 'clearCache', 'key': key});
    }
}		


///////////////// DOM Ready functions ///////////////////

// https://stackoverflow.com/questions/6348494/addeventlistener-vs-onclick
function docReady(callbackFunction){
  if(document.readyState != 'loading')
    callbackFunction(event)
  else
    document.addEventListener("DOMContentLoaded", callbackFunction)
}

	
function winReady(callbackFunction){
  if(document.readyState == 'complete')
    callbackFunction(event);
  else
    window.addEventListener("load", callbackFunction);
}


// A general DOM document ready function
docReady(event => {
    refreshData();
    addListeners(document.querySelectorAll('button.help'),   'click', clickButtonHelp);
    addListeners(document.querySelectorAll('button.cross'),  'click', clickButtonCross);
    addListeners(document.querySelectorAll('.select-game'), 'change', changeSelectGame);
    addListeners(document.querySelectorAll('td.toggle'),     'click', clickTdToggle);
});


// A general DOM Window ready function
winReady(event => {});


// adds an event Listener with callback on a NodeList or HTMLCollection 
function addListeners(elements, event, callback){
    const array = [...elements];
    array.forEach(function (element, index) {
        element.addEventListener(event, callback, false);
    });
}


//////////////// EVENT ACTIONS ////////////////////////////

function refreshData(){
  refreshRecords();
  refreshOptions();
  refreshDatalists();
}


function refreshRecords(){
  for (var base of document.querySelectorAll('div.base.json')) {
    if(!activeUndo(base)){
      replicateBase(base);
    }
  }
}


function refreshOptions(){
  for (var select of document.querySelectorAll('select.json')) {
    fillOptions(select);
  }
  for (var optgroup of document.querySelectorAll('optgroup.json')) {
    fillOptions(optgroup);
  }
}


function refreshDatalists(){
  for (var datalist of document.querySelectorAll('datalist')) {
    fillDatalist(datalist);
  }
}


function clickButtonHelp(){
    toggle(nextSibling(this, 'span.help'));
}


function clickButtonCross(){
    for (var elem of this.parentNode.querySelectorAll('select')) {
        elem.removeAttribute("required");
    }
}


function changeSelectGame(){
    var game = this.value;
    for (var elem of this.parentNode.querySelectorAll('.json')){
        switch(elem.tagName){
            case 'INPUT':
                var id = elem.getAttribute('list');
                if (!id){ return false; }
                var datalist = document.querySelector("datalist#"+id);
                var path = "json/"+id+".options.php?game="+game;
                qwikJSON(path, setInnerJSON, datalist);
            break;
            case 'SELECT':
                var id = elem.getAttribute('id');
                if (!id){ return false; }
                var path = "json/"+id+".options.php?game="+game;
                qwikJSON(path, setInnerJSON, elem);
            break;
            case 'OPTGROUP':
                var id = elem.getAttribute('id');
                if (!id){ return false; }
                var path = "json/"+id+".options.php?game="+game;
                qwikJSON(path, setInnerJSON, elem);
            break;
            default:
                console.log("WARNING: unexpected element with class='json':\n"+elem);
        }
    }
}


function clickMapIcon(){
    var selectGame = document.querySelector(".select-game");
    if (selectGame){
        var game = selectGame.value;
        this.setAttribute('href', "venues.php?game="+game);
    }
}


function showUndo(event, delay=10, tag=''){
  const FORM = event.target.form;
  const UNDO_BUTTON = FORM.querySelector("button.undo");
  const OTHER_BUTTONS = FORM.querySelectorAll('button:not(.undo)');
  const TAG_SPAN = FORM.querySelector("span.tag");
  setButtons([UNDO_BUTTON], OTHER_BUTTONS);           // show undo, hide others
  TAG_SPAN.innerHTML = tag;                           // replace tag
  FORM.appendChild(delayInput(delay));                // add hidden delay input
  setTimeout(()=>{ endUndo(UNDO_BUTTON); }, delay*1000);   // 10sec
}


function endUndo(button){
  button.style.display='none';
  if(button.form.contains(document.activeElement)){
    document.activeElement.blur();
  }
}


function clickUndo(event){
  const UNDO_BUTTON = event.target;
  const FORM = UNDO_BUTTON.form;
  const DELAY = FORM.querySelector("input[name='delay']");
  const PARENT = FORM.parentElement;
  const TAG_SPAN = FORM.querySelector("span.tag");
  UNDO_BUTTON.innerHTML = '<<<';                          // replace undo text
  TAG_SPAN.innerHTML = "...";                             // replace tag text
  FORM.removeChild(DELAY);                                //  do not delay undo
}


function setButtons(show, hide){
  const DISPLAY = show ? 'inline-block' : 'none';
  for (let button of show) {
    button.style.display = 'inline-block';
  }
  for (let button of hide) {
    button.style.display = 'none';
  }
}


function delayInput(delay){
  const INPUT = document.createElement('input');
  INPUT.setAttribute('hidden', '');
  INPUT.setAttribute('name', 'delay');
  INPUT.setAttribute('value', delay);
  return INPUT;
}


function activeUndo(base){
  const PARENT = base.parentElement;
  for(let undo_button of PARENT.querySelectorAll("button.undo")){
    if (undo_button.style.display !== 'none'){
      return true;
    }
  }
  return false;
}


///////////////// DOM helper functions ///////////////////


// returns the next sibling element matching selector, or null otherwise
function nextSibling(element, selector) {
  if (!element || !selector) return null;
  var sibling = element.nextElementSibling;
  if (!sibling || sibling.matches(selector)) return sibling;
  return nextSibling(sibling, selector);
};


// toggle the element visibility
function toggle(element){
    if(window.getComputedStyle(element).display !== 'none') {
        element.style.display = 'none';
        return;
    }
    element.style.display = 'block';
}


// toggle cells in hour selection tables between orange and grey
function clickTdToggle(){
    var td = this;
    var input = td.parentNode.firstElementChild;
    var value = parseInt(input.getAttribute('value'));
    var bit = parseInt(td.getAttribute('bit'));
    if (td.getAttribute('on') == 1){
        td.style.backgroundColor = 'LightGrey';
        td.setAttribute('on', '0');
        value -= bit;
    } else {
        td.style.backgroundColor = 'DarkOrange';
        td.setAttribute('on', '1');
        value += bit;
    }
    input.setAttribute('value', value);
}



///////////////// JSON functions ///////////////////


function initJSON(parentElement){
    addListeners(parentElement.querySelectorAll('button.help'), 'click', clickButtonHelp);

    // call moreInitJSON() if it has been defined somewhere
    if (typeof moreInitJSON == 'function') { moreInitJSON(parentElement); }
}


function setInnerJSON(json, element){
    element.innerHTML = json;
    initJSON(element);
}


function replaceRecords(json, base){
    if(window.activeUndo(base)){ return; }
    
    var parentNode = base.parentNode;
    if(!parentNode.contains(document.activeElement)){
      while (parentNode.childNodes.length) {
        parentNode.removeChild(parentNode.firstChild);  // removes Listeners
      }
      parentNode.innerHTML = json.trim();
      parentNode.insertBefore(base, parentNode.firstChild);
      initJSON(parentNode);
    }
}


function replicateBase(base){
    var id = base.getAttribute('id');
    if (!id){
        console.log("Failed to replicate base: missing id attribute.");
        return false;
    }
    var baseHtml = base.outerHTML;
    var params = {html:baseHtml};
    var esc = encodeURIComponent;
    var query = Object.keys(params).map(k => esc(k) + '=' + esc(params[k])).join('&');
    var path = 'json/'+id+'.listing.php?'+query;
    qwikJSON(path, replaceRecords, base);
}


function fillOptions(element){
    var id = element.getAttribute('id');
    if (!id){
        console.log("Failed to fill options: missing id attribute.");
        return false;
    }
    const FORM = element.closest("form");
    const GAME_ELEMENT = FORM.querySelector("[name=game]");
    let query = (GAME_ELEMENT !== null) ? '?game='+GAME_ELEMENT.value : '' ;    
    var path = 'json/'+id+'.options.php'+query;
    qwikJSON(path, setInnerJSON, element);
}


function fillDatalist(datalist){
    var id = datalist.getAttribute('id');
    if (!id){
        console.log("Failed to fill datalist: missing id attribute.");
        return false;
    }
    var input = document.querySelector("[list='"+id+"']");
    if (!input){
        console.log("Failed to fill datalist: unable to find input with list=".id);
        return false;
    }
    var selectGame = input.form.querySelector(".select-game");
    if (selectGame){
        var game = selectGame.value;
        var query = "?game="+game;
    } else {
        console.log("Failed to fill datalist: unable to find .select-game");        
        var query = '';
    }
    var path = "json/"+id+".options.php"+query;
    qwikJSON(path, setInnerJSON, datalist);
}


function qwikJSON(path, callback, element){
    var protocol = window.location.protocol;
    var host = window.location.host;
    var url = protocol + "//" + host + "/" + path;
    args = Array.prototype.slice.call(arguments);
    args.splice(1, 1);
    var xhr = new XMLHttpRequest();
    xhr.callback = callback;
    xhr.arguments = args;
    xhr.onload = xhrSuccess;
    xhr.onerror = xhrError;
    xhr.open("GET", url, true);
    xhr.send(null);
}


function xhrSuccess() {
    var args = this.arguments;
    var url = args[0].split("?")[0];  
    var json = JSON.parse(this.responseText);
    args[0] = !json ? '' : json ;  // replace url with json in args
    this.callback.apply(this, args);
    var length = nFormatter(this.responseText.length, 1);
    console.log("json reply from "+url+" ("+length+")");
}


function xhrError() {
    console.error("failed to get JSON: " . this.statusText);
}


///////////////// Generic helper functions ///////////////////


// https://stackoverflow.com/questions/1912501/unescape-html-entities-in-javascript/34064434#34064434
function htmlDecode(input) {
  var doc = new DOMParser().parseFromString(input, "text/html");
  return doc.documentElement.textContent;
}


// https://stackoverflow.com/questions/18082/validate-decimal-numbers-in-javascript-isnumeric
function isNumeric(n) {
  return !isNaN(parseFloat(n)) && isFinite(n);
}


// https://stackoverflow.com/questions/13/determine-a-users-timezone/5492192#5492192
function TimezoneDetect(){
    var dtDate = new Date('1/1/' + (new Date()).getUTCFullYear());
    var intOffset = 10000; //set initial offset high so it is adjusted on the first attempt
    var intMonth;
    var intHoursUtc;
    var intHours;
    var intDaysMultiplyBy;

    //go through each month to find the lowest offset to account for DST
    for (intMonth=0;intMonth < 12;intMonth++){
        //go to the next month
        dtDate.setUTCMonth(dtDate.getUTCMonth() + 1);

        //To ignore daylight saving time look for the lowest offset.
        //Since, during DST, the clock moves forward, it'll be a bigger number.
        if (intOffset > (dtDate.getTimezoneOffset() * (-1))){
            intOffset = (dtDate.getTimezoneOffset() * (-1));
        }
    }

    return intOffset;
}


// https://stackoverflow.com/questions/26361649/how-to-handle-right-to-left-text-input-fields-the-right-way?noredirect=1&lq=1
function rtl(element){   
    if(element.setSelectionRange){
        element.setSelectionRange(0,0);
    }
}


// https://stackoverflow.com/questions/9461621/format-a-number-as-2-5k-if-a-thousand-or-more-otherwise-900#9462382
function nFormatter(num, digits) {
  var si = [
    { value: 1, symbol: "" },
    { value: 1E3, symbol: "k" },
    { value: 1E6, symbol: "M" },
    { value: 1E9, symbol: "G" },
    { value: 1E12, symbol: "T" },
    { value: 1E15, symbol: "P" },
    { value: 1E18, symbol: "E" }
  ];
  var rx = /\.0+$|(\.[0-9]*[1-9])0+$/;
  var i;
  for (i = si.length - 1; i > 0; i--) {
    if (num >= si[i].value) {
      break;
    }
  }
  return (num / si[i].value).toFixed(digits).replace(rx, "$1") + si[i].symbol;
}




const MSqC = {lat: -36.4497857, lng: 146.43003739999995};
