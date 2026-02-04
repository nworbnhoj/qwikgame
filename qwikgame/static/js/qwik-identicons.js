///////////////////////// Identicons /////////////////////////

Identicons.svgPath = '/static/img/identicons.min.svg';
import Identicons from '/static/js/identicons.min.js';
window.Identicons = Identicons;

const IDENTICONS = document.querySelectorAll('.identicon');
IDENTICONS.forEach((e) => Identicons.render(e.dataset.hash, e));