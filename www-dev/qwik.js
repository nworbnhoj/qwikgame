function enableElement(element, enable) {
  if (enable) {
    element.classList.remove('disabled');
    element.querySelectorAll('input').forEach(function(input){
      input.disabled = false;
    });
    element.querySelectorAll('.disabled').forEach(function(disabled){
      disabled.classList.remove('disabled');
    });
  } else {
    element.classList.add('disabled');
    element.querySelectorAll('input').forEach(function(input){
      input.disabled = true;
    });
    element.querySelectorAll('h6, div').forEach(function(enabled){
      enabled.classList.add('disabled');
    });
  }
}

function enableInviteFriend(event) {
  let element = event.currentTarget;
  enableElement(document.getElementById("invite_friend_body"), element.checked);
  enableElement(document.getElementById("rival_skill_body"), !element.checked);
}

function href(event) {
    let element = event.currentTarget;
    window.location.href = element.dataset.href;
}

function next() {
  var list = document.querySelectorAll('[name=list]');
  for (let i = 0; i < list.length; i++) {
    if (list[i].checked) {
       list[i].checked = false;
       list[(i+1 < list.length) ? i+1 : 0].checked = true;
       break;
    }
  }
  showDetail();
}

function previous() {
  var list = document.querySelectorAll('[name=list]');
  for (let i = 0; i < list.length; i++) {
    if (list[i].checked) {
       list[i].checked = false;
       list[(i-1 < 0) ? list.length-1 : i-1].checked = true;
       break;
    }
  }
  showDetail();
}

function showDetail() {
  var width_600 = window.matchMedia("only screen and (max-width: 600px)").matches;
  var width_768 = window.matchMedia("only screen and (max-width: 768px)").matches;
  // var width_992 = window.matchMedia("only screen and (max-width: 992px)").matches;
  // var width_1200 = window.matchMedia("only screen and (max-width: 1200px)").matches;
  var list_bar = document.getElementById("list_bar");
  var detail = document.getElementById("detail");
  if (width_600 || width_768) {           // mobile
    list_bar.style.display = "none";
    detail.style.display = "flex";
  } else {                                // desktop
    list_bar.style.display = "flex";
    detail.style.display = "flex";    
  }

  list_bar.querySelectorAll('[name=list]').forEach(function(radio){
    if (radio.checked) {
          document.getElementById(radio.dataset.id).classList.remove("hidden");
      } else {
          document.getElementById(radio.dataset.id).classList.add("hidden");
      }
  })
}

function slide(slider) {
  var words = slider.previousElementSibling.children;
  for (let i = 0; i < words.length; i++) {
    var word = words.item(i);
    if (i == slider.value) {
      word.classList.remove('invisible');
    } else {
      word.classList.add('invisible');
    }
  }
}

function showChat() {
    document.querySelectorAll("span.chat_show").forEach(function(span) {
          span.style.display = (span.style.display === "none") ? "inline" : "none";
    })
    document.querySelectorAll("div.chat_block").forEach(function(div) {
          div.style.display = (div.style.display === "none") ? "flex" : "none";
    })
}

function showGroup(event) {
    let button = event.currentTarget;
    button.nextElementSibling.classList.toggle('hidden');
    button.lastElementChild.classList.toggle('flip');
}

function showNextSibling(event) {
  let button = event.currentTarget;
  let next_sibling = button.nextElementSibling;
  next_sibling.classList.toggle('hidden');
}

function showNextParentSibling(event) {
  let button = event.currentTarget;
  let next_sibling = button.parentNode.nextElementSibling;
  next_sibling.classList.toggle('hidden');
}

function toggleAllDay(event) {
  let button = event.currentTarget;
  var checked = button.firstElementChild.checked;
  var hours = button.closest('.schedule_keen').querySelector('.radio_block').children;
  for (hour of hours) {
    var checkbox = hour.firstElementChild;
    if (!checkbox.disabled) {
      checkbox.checked = checked;
    }
  }
}

window.onload = function() {
  document.querySelectorAll('[name=list]').forEach(function(list_radio) {
      list_radio.onclick = showDetail;
  });
  document.querySelectorAll('button.show-group').forEach(function(button){
      button.onclick = showGroup;
  });
  document.querySelectorAll('button.show-next-sibling').forEach(function(button){
      button.onclick = showNextParentSibling;
  });
  document.querySelectorAll('button.show-next-parent-sibling').forEach(function(button){
      button.onclick = showNextParentSibling;
  });
  document.querySelectorAll('.href').forEach(function(element){
      element.onclick = href;
  });
  document.querySelectorAll('label.toggle.button.all-day').forEach(function(button){
      button.onclick = toggleAllDay;
  });
  document.querySelectorAll('input.enable-invite-friend').forEach(function(checkbox){
      checkbox.oninput = enableInviteFriend;
  });
}