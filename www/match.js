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



function clickInviteFriends(){
    this.style.display = 'none';
    document.getElementById('friend-invites').style.display = 'block';
}
