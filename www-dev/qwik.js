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

  [].forEach.call(document.querySelectorAll('[name=list]'), function(radio){
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

function showGroup(button) {
    button.nextElementSibling.classList.toggle('hidden');
    button.lastElementChild.classList.toggle('flip');
}

window.onload = function() {
  [].forEach.call(document.querySelectorAll('[name=list]'), function(list_radio) {
    list_radio.onclick = showDetail;
  })
}