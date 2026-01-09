// additional logic for hour toggles when an all_day is toggled

if (typeof toggleAllDay == "undefined") {
  function toggleAllDay(event) {
    try {
      let all_day = event.currentTarget;
      let all_day_checked = toggle_checked(all_day);
      var hours = all_day.closest('div.by_day').querySelector('div.radio_block').children;
      for (hour of hours) {
        var checkbox = hour.firstElementChild;
        if (!checkbox.disabled) {
          checkbox.checked = all_day_checked;
        }
      }
    } catch (e) {
      console.log(e);
    }
  }

  window.addEventListener("load", function() {
    document.querySelectorAll('label.toggle.all_day').forEach(function(all_day) {
      all_day.addEventListener("click", toggleAllDay);
    });
  });

}