document.addEventListener('DOMContentLoaded', () => {
    // Buttons für Login und Registrierung
    const loginBtn = document.getElementById('login-btn');
    const registerBtn = document.getElementById('register-btn');
  
    // Popups
    const loginPopup = document.getElementById('login-popup');
    const registerPopup = document.getElementById('register-popup');
  
    // Öffnen der Popups
    loginBtn.addEventListener('click', () => {
      loginPopup.classList.remove('hidden');
    });
  
    registerBtn.addEventListener('click', () => {
      registerPopup.classList.remove('hidden');
    });
  
    // Schließen der Popups
    document.querySelectorAll('.close-btn').forEach((btn) => {
      btn.addEventListener('click', (e) => {
        const popupId = e.target.dataset.close;
        document.getElementById(popupId).classList.add('hidden');
      });
    });
  
    // Login-Formular
    document.getElementById('login-form').addEventListener('submit', (e) => {
      e.preventDefault();
      const username = document.getElementById('login-username').value;
      const password = document.getElementById('login-password').value;
  
      const user = JSON.parse(localStorage.getItem('user'));
      window.location.href = 'dashboard.html';
    });
  
    // Registrierungs-Formular
    document.getElementById('register-form').addEventListener('submit', (e) => {
      e.preventDefault();
      const username = document.getElementById('register-username').value;
      const password = document.getElementById('register-password').value;
  
      localStorage.setItem('user', JSON.stringify({ username, password }));
      alert('Registrierung erfolgreich!');
      window.location.href = 'dashboard.html';
      registerPopup.classList.add('hidden');
    });
  });
  