const track       = document.getElementById('authTrack');
const panelLogin  = document.getElementById('panelLogin');
const panelSignup = document.getElementById('panelSignup');

function showSignup(e) {
    if (e) e.preventDefault();
    track.classList.add('show-signup');
    panelLogin.classList.add('hidden');
    setTimeout(() => panelSignup.classList.remove('hidden'), 180);
}

function showLogin(e) {
    if (e) e.preventDefault();
    track.classList.remove('show-signup');
    panelSignup.classList.add('hidden');
    setTimeout(() => panelLogin.classList.remove('hidden'), 180);
}