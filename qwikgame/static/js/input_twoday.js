// additional logic for the twoday field
if (typeof validateTwoday == "undefined") {
    // validate a required twoday field
    function validateTwoday(event) {
        try {
            const FIELD = event.currentTarget.closest('div.field, fieldset');
            const TWODAY = FIELD.querySelector("input[name='two_day']");
            const REQUIRED = FIELD.closest('.required');
            const CHECKED = FIELD.querySelector("input[type='checkbox']:not(:disabled):checked");
            TWODAY.setCustomValidity(REQUIRED && !CHECKED ? 'an hour is required' : '');
        } catch (e) {
            console.log(e);
        }
    }
    document.addEventListener("DOMContentLoaded", () => {
        // add validation to twoday fields
        document.querySelectorAll("input[name='two_day']").forEach((twoday) => {
            const FIELD = twoday.closest('div.field, fieldset');
            FIELD.querySelectorAll("input[type='checkbox']").forEach((checkbox) => {
                checkbox.addEventListener('change', validateTwoday);
            });
            twoday.dispatchEvent(new Event('change'));
        });
    });
}