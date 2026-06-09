// additional logic for hour toggles when an all_day is toggled

if (typeof toggleAllDay == "undefined") {

  // update an AllDay toggle to be consistent with the Hour checkboxes
  function updateAllDay(all_day) {
    if (!all_day || all_day.tagName !== 'LABEL') {
      console.warn("updateAllDay() called with " + all_day);
      return;
    }
    if ('updatePromise' in all_day) {
      return all_day.updatePromise;
    }
    all_day.updatePromise = new Promise((resolve, reject) => {
      try {
        const FIELD = all_day.closest('div.field');
        // uncheck all_day if a single hour is unchecked
        if (FIELD.querySelector("label.hour input[type='checkbox']:not(:checked):not(:disabled)")){
          toggle_uncheck(all_day);
          resolve(false);
        } else {
          toggle_check(all_day);
          resolve(true);
        }
      } catch (e) {
        console.log(e);
      }
    });
    all_day.updatePromise.then(() => { delete all_day.updatePromise });
    return all_day.updatePromise;
  }

  // cascade a change in the AllDay toggle DOWN to the Hour checkboxes
  function toggleAllDay(event) {
    try {
      const ALL_DAY = event.currentTarget;
      const ALL_DAY_CHECKED = toggle_checked(ALL_DAY);
      const ON_DAY = ALL_DAY.closest('div.on_day');
      const BY_HOUR = ON_DAY.querySelector('div.by_hour');
      BY_HOUR.querySelectorAll("input[type='checkbox']:not(:disabled)").forEach((hour) => {
        hour.checked = ALL_DAY_CHECKED;
      });
    } catch (e) {
      console.log(e);
    }
  }

  document.addEventListener("DOMContentLoaded", function() {
    // initialize all label.toggle.all_day
    document.querySelectorAll('label.toggle.all_day').forEach(function(all_day) {
      updateAllDay(all_day)
    });
    // setup event listeners to keep Hours & Days in sync
    document.querySelectorAll('label.toggle.all_day').forEach(function(all_day) {
      // propagate a click on an AllDay toggle DOWN to the Hour toggles
      all_day.addEventListener("click", toggleAllDay);
      on_day = all_day.closest('.on_day');
      // propagate a click on an Hour toggle UP to the AllDay toggle
      on_day.querySelectorAll('label.toggle.hour').forEach(function(hour) {
        hour.addEventListener("click", () => { updateAllDay(all_day); });
      });
    });
  });
}