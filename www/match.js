ready(event => {
    initPage();
});


function addMoreListeners(){
    for (var elem of document.querySelectorAll('button.thumb')) {
        elem.addEventListener('click', clickThumb, false);
    }
    for (var elem of document.querySelectorAll('button.thumb')) {
        elem.addEventListener('click', clickSetRep, false);
    }
    for (var elem of document.querySelectorAll('td.toggle')) {
        elem.addEventListener('click', clickTdToggle, false);
    }

    addEvent(document.getElementById('invite-friends'), 'click'  , clickInviteFriends);
}



function initPage(){
    var currentTime = new Date();
    var hour = currentTime.getHours();
    for (var elem of document.getElementById('hrs_trunc').children) {
        if (elem.type == 'td'){
            var hr = this.getAttribute('hr');
            if (hr <= hour){
                elem.classList.add('past');
                elem.classList.remove('toggle');
                elem.style.color = '';
            }
        }
    }
}


function clickThumb(){
    if (this.classList.contains('fa-thumbs-o-up')){
        this.classList.remove('fa-thumbs-o-up','green');
        this.classList.add('fa-thumbs-o-down','red');
    } else {
        this.classList.remove('fa-thumbs-o-down','red');
        this.classList.add('fa-thumbs-o-up','green');
    }
}


function clickSetRep(){
    var rep = document.getElementById('rep');
    if(thumb.hasClass('fa-thumbs-o-up')){
        rep.val('+1');
    } else {
        rep.val('-1');
    }
}


function clickTdToggle(){
    var td = this;
    var input = this.parentNode.firstChild;
    var val = parseInt(input.nodeValue);
    var bit = parseInt(td.getAttribute('bit'));
    if (td.getAttribute('on') == 1){
        td.style.backgroundColor = 'LightGrey';
        td.setAttribute('on', '0');
        input.value = val - bit;
    } else {
        td.style.backgroundColor = 'DarkOrange';
        td.setAttribute('on', '1');
        input.value = val + bit;
    }
}



function clickInviteFriends(){
    this.style.display = 'none';
    document.getElementById('friend-invites').style.display = 'block';
}
