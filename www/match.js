docReady(event => {
    initPage();
    document.getElementById('invite-friends').addEventListener('click', clickInviteFriends, false);
    document.getElementById('map-icon').addEventListener(      'click', clickMapIcon,       false);
    var thumbs = document.querySelectorAll('button.thumb');
    addListeners(thumbs, 'click', clickThumb);
    addListeners(thumbs, 'click', clickSetRep);
});


winReady(event => {
    var thumbs = document.querySelectorAll('button.thumb');
    addListeners(thumbs, 'click', clickThumb);
    addListeners(thumbs, 'click', clickSetRep);
});


function initPage(){
    var currentTime = new Date();
    var hour = currentTime.getHours();
    for (var elem of document.getElementById('hrs_trunc').children) {
        if (elem.nodeName == 'TD'){
            var hr = parseInt(elem.getAttribute('hr'), 10);
            if (hr <= hour){
                elem.classList.add('past');
                elem.classList.remove('toggle');
                elem.style.color = '';
                elem.removeEventListener('click', clickTdToggle);
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
    if(this.classList.contains('fa-thumbs-o-up')){
        rep.value = '+1';
    } else {
        rep.value = '-1';
    }
}



function clickInviteFriends(){
    this.style.display = 'none';
    document.getElementById('friend-invites').style.display = 'block';
}
